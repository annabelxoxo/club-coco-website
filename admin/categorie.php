<?php
// Categorie toevoegen (categorie.php) of bewerken (categorie.php?id=3)
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/admin_layout.php';

$id = (int) ($_GET['id'] ?? 0);
$cat = ['naam' => '', 'kolom' => 1, 'notitie' => '', 'zichtbaar' => 1];
$aantalItems = 0;

if ($id) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $cat = $stmt->fetch() ?: redirect('kaart.php');
    $stmt = db()->prepare('SELECT COUNT(*) FROM menu_items WHERE category_id = ?');
    $stmt->execute([$id]);
    $aantalItems = (int) $stmt->fetchColumn();
}

$fout = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (post('actie') === 'verwijder' && $id) {
        db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]); // items gaan mee (ON DELETE CASCADE)
        flash("Categorie \"{$cat['naam']}\" en haar items zijn verwijderd.");
        redirect('kaart.php');
    }

    $oudeKolom = (int) $cat['kolom'];
    $cat = [
        'naam'      => post('naam'),
        'kolom'     => max(1, min(3, (int) post('kolom'))),
        'notitie'   => post('notitie'),
        'zichtbaar' => isset($_POST['zichtbaar']) ? 1 : 0,
    ];

    if ($cat['naam'] === '') {
        $fout = 'Geef de categorie een naam.';
    } else {
        $volgendeInKolom = db()->prepare('SELECT COALESCE(MAX(volgorde), 0) + 1 FROM categories WHERE kolom = ?');
        $volgendeInKolom->execute([$cat['kolom']]);
        $onderaan = (int) $volgendeInKolom->fetchColumn();

        if ($id) {
            db()->prepare('UPDATE categories SET naam = ?, kolom = ?, notitie = ?, zichtbaar = ? WHERE id = ?')
                ->execute([$cat['naam'], $cat['kolom'], $cat['notitie'] ?: null, $cat['zichtbaar'], $id]);
            if ($oudeKolom !== $cat['kolom']) { // naar andere kolom verplaatst: onderaan zetten
                db()->prepare('UPDATE categories SET volgorde = ? WHERE id = ?')->execute([$onderaan, $id]);
            }
            flash("Categorie \"{$cat['naam']}\" is opgeslagen.");
        } else {
            db()->prepare('INSERT INTO categories (naam, kolom, notitie, zichtbaar, volgorde) VALUES (?, ?, ?, ?, ?)')
                ->execute([$cat['naam'], $cat['kolom'], $cat['notitie'] ?: null, $cat['zichtbaar'], $onderaan]);
            $id = (int) db()->lastInsertId();
            flash("Categorie \"{$cat['naam']}\" is aangemaakt. Voeg er nu items aan toe.");
        }
        redirect('kaart.php#cat-' . $id);
    }
}

admin_head($id ? 'Categorie bewerken' : 'Nieuwe categorie', 'kaart');
?>
<div class="page-head">
  <div>
    <h1><?= $id ? 'Categorie bewerken' : 'Nieuwe categorie' ?></h1>
    <p><a href="kaart.php">← Terug naar de kaart</a></p>
  </div>
</div>

<div class="card">
  <?php if ($fout): ?><div class="flash flash-fout" style="margin-bottom:14px"><?= h($fout) ?></div><?php endif; ?>
  <form class="stack" method="post">
    <?= csrf_field() ?>
    <label>Naam <span class="hint">bv. "Ontbijt &amp; lunch"</span>
      <input type="text" name="naam" id="naam" value="<?= h($cat['naam']) ?>" required maxlength="100" autofocus>
    </label>
    <label>Kolom op de website <span class="hint">De kaart staat in 3 kolommen naast elkaar (op gsm onder elkaar).</span>
      <select name="kolom" id="kolom">
        <?php for ($k = 1; $k <= 3; $k++): ?>
          <option value="<?= $k ?>" <?= (int) $cat['kolom'] === $k ? 'selected' : '' ?>>Kolom <?= $k ?> (<?= ['links', 'midden', 'rechts'][$k - 1] ?>)</option>
        <?php endfor; ?>
      </select>
    </label>
    <label>Notitie <span class="hint">optioneel, klein onder de categorie (bv. "Ons lunchmenu staat binnenkort online")</span>
      <input type="text" name="notitie" id="notitie" value="<?= h($cat['notitie']) ?>" maxlength="255">
    </label>
    <label class="check"><input type="checkbox" name="zichtbaar" id="zichtbaar" <?= $cat['zichtbaar'] ? 'checked' : '' ?>> Tonen op de website</label>
    <div class="actions">
      <button class="btn" type="submit"><?= $id ? 'Opslaan' : 'Aanmaken' ?></button>
      <a class="btn light" href="kaart.php">Annuleren</a>
    </div>
  </form>
</div>

<?php if ($id): ?>
<div class="card">
  <h2>Verwijderen</h2>
  <p class="muted small" style="margin-bottom:12px">
    <?= $aantalItems ? "Let op: de $aantalItems items in deze categorie worden ook verwijderd." : 'Deze categorie is leeg.' ?>
  </p>
  <form method="post">
    <?= csrf_field() ?>
    <label class="check" style="margin-bottom:10px"><input type="checkbox" id="zeker" required> Ja, verwijder "<?= h($cat['naam']) ?>"<?= $aantalItems ? ' met alle items' : '' ?></label>
    <button class="btn danger" name="actie" value="verwijder">Verwijder categorie</button>
  </form>
</div>
<?php endif; ?>
<?php admin_foot(); ?>
