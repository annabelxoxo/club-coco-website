<?php
// Item toevoegen (item.php?categorie=3) of bewerken (item.php?id=12)
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/admin_layout.php';

$id = (int) ($_GET['id'] ?? 0);
$item = ['naam' => '', 'beschrijving' => '', 'prijs' => '', 'favoriet' => 0, 'zichtbaar' => 1,
         'category_id' => (int) ($_GET['categorie'] ?? 0)];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM menu_items WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch() ?: redirect('kaart.php');
}

$cats = db()->query('SELECT id, naam FROM categories ORDER BY kolom, volgorde, id')->fetchAll();
$fout = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (post('actie') === 'verwijder' && $id) {
        db()->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
        flash("\"{$item['naam']}\" is verwijderd.");
        redirect('kaart.php#cat-' . $item['category_id']);
    }

    $item = [
        'naam'         => post('naam'),
        'beschrijving' => post('beschrijving'),
        'prijs'        => trim(str_replace('€', '', post('prijs'))),
        'favoriet'     => isset($_POST['favoriet']) ? 1 : 0,
        'zichtbaar'    => isset($_POST['zichtbaar']) ? 1 : 0,
        'category_id'  => (int) post('category_id'),
    ];
    $catBestaat = in_array($item['category_id'], array_map(fn($c) => (int) $c['id'], $cats), true);

    if ($item['naam'] === '') {
        $fout = 'Geef het item een naam.';
    } elseif (!$catBestaat) {
        $fout = 'Kies een categorie.';
    } else {
        $waarden = [$item['category_id'], $item['naam'], $item['beschrijving'] ?: null,
                    $item['prijs'] !== '' ? $item['prijs'] : null, $item['favoriet'], $item['zichtbaar']];
        if ($id) {
            db()->prepare('UPDATE menu_items SET category_id = ?, naam = ?, beschrijving = ?, prijs = ?, favoriet = ?, zichtbaar = ? WHERE id = ?')
                ->execute([...$waarden, $id]);
            flash("\"{$item['naam']}\" is opgeslagen.");
        } else {
            // Nieuw item komt onderaan de categorie
            $stmt = db()->prepare('SELECT COALESCE(MAX(volgorde), 0) + 1 FROM menu_items WHERE category_id = ?');
            $stmt->execute([$item['category_id']]);
            db()->prepare('INSERT INTO menu_items (category_id, naam, beschrijving, prijs, favoriet, zichtbaar, volgorde) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([...$waarden, (int) $stmt->fetchColumn()]);
            flash("\"{$item['naam']}\" is toegevoegd aan de kaart.");
        }
        redirect('kaart.php#cat-' . $item['category_id']);
    }
}

admin_head($id ? 'Item bewerken' : 'Nieuw item', 'kaart');
?>
<div class="page-head">
  <div>
    <h1><?= $id ? 'Item bewerken' : 'Nieuw item' ?></h1>
    <p><a href="kaart.php">← Terug naar de kaart</a></p>
  </div>
</div>

<div class="card">
  <?php if ($fout): ?><div class="flash flash-fout" style="margin-bottom:14px"><?= h($fout) ?></div><?php endif; ?>
  <form class="stack" method="post">
    <?= csrf_field() ?>
    <div class="row">
      <label style="flex:2 1 260px">Naam
        <input type="text" name="naam" id="naam" value="<?= h($item['naam']) ?>" required maxlength="150" autofocus>
      </label>
      <label>Prijs
        <span class="hint">bv. 4,50 of 7,90 / 32 of +0,50</span>
        <input type="text" name="prijs" id="prijs" value="<?= h($item['prijs']) ?>" maxlength="30" inputmode="decimal">
      </label>
    </div>
    <label>Extra uitleg <span class="hint">optioneel, klein onder de naam (bv. "iced met kokosmelk en mangopuree")</span>
      <input type="text" name="beschrijving" id="beschrijving" value="<?= h($item['beschrijving']) ?>" maxlength="255">
    </label>
    <label>Categorie
      <select name="category_id" id="category_id" required>
        <option value="">Kies…</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $item['category_id'] ? 'selected' : '' ?>><?= h($c['naam']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="check"><input type="checkbox" name="favoriet" id="favoriet" <?= $item['favoriet'] ? 'checked' : '' ?>> ♥ Favoriet van het huis</label>
    <label class="check"><input type="checkbox" name="zichtbaar" id="zichtbaar" <?= $item['zichtbaar'] ? 'checked' : '' ?>> Tonen op de website</label>
    <div class="actions">
      <button class="btn" type="submit"><?= $id ? 'Opslaan' : 'Toevoegen' ?></button>
      <a class="btn light" href="kaart.php">Annuleren</a>
    </div>
  </form>
</div>

<?php if ($id): ?>
<div class="card">
  <h2>Verwijderen</h2>
  <p class="muted small" style="margin-bottom:12px">Tijdelijk niet beschikbaar? Zet dan liever "Tonen op de website" uit, dan hoef je het later niet opnieuw in te geven.</p>
  <form method="post" id="verwijder-form">
    <?= csrf_field() ?>
    <label class="check" style="margin-bottom:10px"><input type="checkbox" id="zeker" required> Ja, ik wil "<?= h($item['naam']) ?>" definitief verwijderen</label>
    <button class="btn danger" name="actie" value="verwijder">Verwijder item</button>
  </form>
</div>
<?php endif; ?>
<?php admin_foot(); ?>
