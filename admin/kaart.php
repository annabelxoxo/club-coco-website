<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/admin_layout.php';

/**
 * Verschuift een rij één plaats omhoog of omlaag binnen zijn groep
 * en nummert de volgorde daarna netjes opnieuw (1, 2, 3, ...).
 */
function verschuif(string $tabel, string $groepKolom, int $groepWaarde, int $id, int $richting): void
{
    $stmt = db()->prepare("SELECT id FROM $tabel WHERE $groepKolom = ? ORDER BY volgorde, id");
    $stmt->execute([$groepWaarde]);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $pos = array_search($id, $ids, true);
    $nieuw = $pos === false ? false : $pos + $richting;
    if ($nieuw !== false && $nieuw >= 0 && $nieuw < count($ids)) {
        [$ids[$pos], $ids[$nieuw]] = [$ids[$nieuw], $ids[$pos]];
    }
    $upd = db()->prepare("UPDATE $tabel SET volgorde = ? WHERE id = ?");
    foreach ($ids as $i => $rijId) {
        $upd->execute([$i + 1, $rijId]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $actie = post('actie');
    $id = (int) post('id');

    // Acties op een item
    if (in_array($actie, ['fav', 'zichtbaar', 'omhoog', 'omlaag', 'verwijder'], true)) {
        $stmt = db()->prepare('SELECT * FROM menu_items WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if ($item) {
            switch ($actie) {
                case 'fav':
                    db()->prepare('UPDATE menu_items SET favoriet = 1 - favoriet WHERE id = ?')->execute([$id]);
                    break;
                case 'zichtbaar':
                    db()->prepare('UPDATE menu_items SET zichtbaar = 1 - zichtbaar WHERE id = ?')->execute([$id]);
                    flash($item['zichtbaar'] ? "\"{$item['naam']}\" is verborgen op de site." : "\"{$item['naam']}\" staat weer op de site.");
                    break;
                case 'omhoog':
                case 'omlaag':
                    verschuif('menu_items', 'category_id', (int) $item['category_id'], $id, $actie === 'omhoog' ? -1 : 1);
                    break;
                case 'verwijder':
                    db()->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
                    flash("\"{$item['naam']}\" is verwijderd.");
                    break;
            }
        }
        redirect('kaart.php#cat-' . ($item['category_id'] ?? ''));
    }

    // Acties op een categorie
    if (in_array($actie, ['cat_omhoog', 'cat_omlaag'], true)) {
        $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        if ($cat = $stmt->fetch()) {
            verschuif('categories', 'kolom', (int) $cat['kolom'], $id, $actie === 'cat_omhoog' ? -1 : 1);
        }
        redirect('kaart.php#cat-' . $id);
    }
    redirect('kaart.php');
}

$cats = db()->query('SELECT * FROM categories ORDER BY kolom, volgorde, id')->fetchAll();
$items = db()->query('SELECT * FROM menu_items ORDER BY volgorde, id')->fetchAll();
$perCat = [];
foreach ($items as $it) {
    $perCat[$it['category_id']][] = $it;
}

admin_head('Kaart', 'kaart');
?>
<div class="page-head">
  <div>
    <h1>Kaart</h1>
    <p>Klik op ✎ om een naam of prijs aan te passen. Met ♥ duid je een favoriet aan, met 👁 verberg je iets tijdelijk (bv. als het op is).</p>
  </div>
  <div class="actions">
    <a class="btn light" href="categorie.php">+ Nieuwe categorie</a>
  </div>
</div>

<?php
$vorigeKolom = null;
foreach ($cats as $ci => $cat):
    $catItems = $perCat[$cat['id']] ?? [];
    if ($cat['kolom'] !== $vorigeKolom): $vorigeKolom = $cat['kolom']; ?>
      <h2 class="muted small" style="margin-top:8px">Kolom <?= (int) $cat['kolom'] ?> op de site</h2>
    <?php endif;
    $eersteInKolom = $ci === 0 || $cats[$ci - 1]['kolom'] !== $cat['kolom'];
    $laatsteInKolom = !isset($cats[$ci + 1]) || $cats[$ci + 1]['kolom'] !== $cat['kolom'];
?>
<section class="card cat-card" id="cat-<?= (int) $cat['id'] ?>">
  <div class="cat-top">
    <h2><?= h($cat['naam']) ?>
      <span class="meta"><?= count($catItems) ?> items</span>
      <?php if (!$cat['zichtbaar']): ?><span class="pill">verborgen</span><?php endif; ?>
    </h2>
    <div class="tools">
      <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
        <button class="iconbtn" name="actie" value="cat_omhoog" title="Categorie omhoog" <?= $eersteInKolom ? 'disabled' : '' ?>>↑</button>
        <button class="iconbtn" name="actie" value="cat_omlaag" title="Categorie omlaag" <?= $laatsteInKolom ? 'disabled' : '' ?>>↓</button>
      </form>
      <a class="btn sm light" href="categorie.php?id=<?= (int) $cat['id'] ?>">Categorie bewerken</a>
      <a class="btn sm" href="item.php?categorie=<?= (int) $cat['id'] ?>">+ Item</a>
    </div>
  </div>

  <?php if ($catItems): ?>
  <ul class="items">
    <?php foreach ($catItems as $i => $it): ?>
    <li class="<?= $it['zichtbaar'] ? '' : 'verborgen' ?>">
      <form class="move" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
        <button class="iconbtn" name="actie" value="omhoog" title="Omhoog" <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
        <button class="iconbtn" name="actie" value="omlaag" title="Omlaag" <?= $i === count($catItems) - 1 ? 'disabled' : '' ?>>↓</button>
      </form>
      <div class="naam">
        <?= $it['favoriet'] ? '<span style="color:var(--bordeaux)">♥</span> ' : '' ?><?= h($it['naam']) ?>
        <?php if (!$it['zichtbaar']): ?><span class="pill">verborgen</span><?php endif; ?>
        <?php if ($it['beschrijving']): ?><small><?= h($it['beschrijving']) ?></small><?php endif; ?>
      </div>
      <span class="prijs"><?= $it['prijs'] !== null && $it['prijs'] !== '' ? '€ ' . h($it['prijs']) : '—' ?></span>
      <form class="tools" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
        <button class="iconbtn <?= $it['favoriet'] ? 'fav-on' : '' ?>" name="actie" value="fav" title="<?= $it['favoriet'] ? 'Geen favoriet meer' : 'Maak favoriet' ?>">♥</button>
        <button class="iconbtn" name="actie" value="zichtbaar" title="<?= $it['zichtbaar'] ? 'Verbergen' : 'Weer tonen' ?>"><?= $it['zichtbaar'] ? '👁' : '◌' ?></button>
        <a class="iconbtn" href="item.php?id=<?= (int) $it['id'] ?>" title="Bewerken">✎</a>
      </form>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?>
    <p class="muted" style="padding:14px 18px"><?= $cat['notitie'] ? h($cat['notitie']) : 'Nog geen items in deze categorie.' ?></p>
  <?php endif; ?>
</section>
<?php endforeach; ?>
<?php admin_foot(); ?>
