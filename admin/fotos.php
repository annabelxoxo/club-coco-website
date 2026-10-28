<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/admin_layout.php';

const MAX_MB = 8;
const TOEGELATEN = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const PLEKKEN = [
    'galerij' => "Foto's-blok onderaan",
    'kaart'   => 'Boven de kaart (3 foto\'s)',
    'verhaal' => 'Bij "Ons verhaal" (1 foto)',
];
const FORMATEN = ['normaal' => 'Normaal', 'breed' => 'Groot (2×2)', 'hoog' => 'Hoog (1×2)'];

function geldig(string $waarde, array $lijst, string $standaard): string
{
    return array_key_exists($waarde, $lijst) ? $waarde : $standaard;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $actie = post('actie');

    if ($actie === 'upload') {
        $bestand = $_FILES['foto'] ?? null;
        $fout = '';
        if (!$bestand || $bestand['error'] === UPLOAD_ERR_NO_FILE) {
            $fout = 'Kies eerst een foto.';
        } elseif ($bestand['error'] === UPLOAD_ERR_INI_SIZE || $bestand['error'] === UPLOAD_ERR_FORM_SIZE || $bestand['size'] > MAX_MB * 1024 * 1024) {
            $fout = 'Deze foto is te groot. Maximum is ' . MAX_MB . ' MB.';
        } elseif ($bestand['error'] !== UPLOAD_ERR_OK) {
            $fout = 'Uploaden is mislukt. Probeer opnieuw.';
        } else {
            // Kijk naar de echte inhoud van het bestand, niet naar de naam
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($bestand['tmp_name']);
            if (!isset(TOEGELATEN[$mime]) || @getimagesize($bestand['tmp_name']) === false) {
                $fout = 'Dit bestand is geen JPG, PNG of WEBP-foto.';
            }
        }

        if ($fout) {
            flash($fout, 'fout');
        } else {
            $naam = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . TOEGELATEN[$mime];
            if (!move_uploaded_file($bestand['tmp_name'], ROOT . '/uploads/' . $naam)) {
                flash('De foto kon niet opgeslagen worden. Controleer of de map uploads/ schrijfbaar is.', 'fout');
            } else {
                $plek = geldig(post('plek'), PLEKKEN, 'galerij');
                $stmt = db()->prepare('SELECT COALESCE(MAX(volgorde), 0) + 1 FROM photos WHERE plek = ?');
                $stmt->execute([$plek]);
                db()->prepare('INSERT INTO photos (pad, alt, label, plek, formaat, volgorde) VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute(['uploads/' . $naam, post('alt'), post('label') ?: null, $plek,
                               geldig(post('formaat'), FORMATEN, 'normaal'), (int) $stmt->fetchColumn()]);
                flash('Foto toegevoegd.');
            }
        }
        redirect('fotos.php');
    }

    $id = (int) post('id');
    $stmt = db()->prepare('SELECT * FROM photos WHERE id = ?');
    $stmt->execute([$id]);
    $foto = $stmt->fetch();

    if ($foto && $actie === 'opslaan') {
        db()->prepare('UPDATE photos SET alt = ?, label = ?, plek = ?, formaat = ?, volgorde = ?, zichtbaar = ? WHERE id = ?')
            ->execute([post('alt'), post('label') ?: null, geldig(post('plek'), PLEKKEN, $foto['plek']),
                       geldig(post('formaat'), FORMATEN, $foto['formaat']), (int) post('volgorde'),
                       isset($_POST['zichtbaar']) ? 1 : 0, $id]);
        flash('Foto opgeslagen.');
    }

    if ($foto && $actie === 'verwijder') {
        db()->prepare('DELETE FROM photos WHERE id = ?')->execute([$id]);
        // Enkel geüploade bestanden wissen, niet de vaste foto's in images/
        if (str_starts_with($foto['pad'], 'uploads/')) {
            $pad = ROOT . '/' . $foto['pad'];
            if (is_file($pad)) {
                unlink($pad);
            }
        }
        flash('Foto verwijderd.');
    }
    redirect('fotos.php#foto-' . $id);
}

admin_head("Foto's", 'fotos');
?>
<div class="page-head">
  <div>
    <h1>Foto's</h1>
    <p>Voeg foto's toe of kies waar ze op de site komen. Een lager volgnummer staat eerst.</p>
  </div>
</div>

<div class="card">
  <h2>Nieuwe foto</h2>
  <form class="stack" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="actie" value="upload">
    <label>Foto <span class="hint">JPG, PNG of WEBP, maximaal <?= MAX_MB ?> MB</span>
      <input type="file" name="foto" id="foto" accept="image/jpeg,image/png,image/webp" required>
    </label>
    <label>Wat staat erop? <span class="hint">Korte beschrijving voor blinden en Google, bv. "Cappuccino met latte art"</span>
      <input type="text" name="alt" id="alt-nieuw" maxlength="255" required>
    </label>
    <div class="row">
      <label>Plek op de site
        <select name="plek" id="plek-nieuw">
          <?php foreach (PLEKKEN as $k => $v): ?><option value="<?= $k ?>"><?= h($v) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Grootte <span class="hint">enkel voor het foto's-blok</span>
        <select name="formaat" id="formaat-nieuw">
          <?php foreach (FORMATEN as $k => $v): ?><option value="<?= $k ?>"><?= h($v) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Label <span class="hint">enkel boven de kaart</span>
        <input type="text" name="label" id="label-nieuw" maxlength="60" placeholder="bv. Huisgemaakte buns">
      </label>
    </div>
    <div class="actions"><button class="btn" type="submit">Uploaden</button></div>
  </form>
</div>

<?php foreach (PLEKKEN as $plek => $plekNaam): $fotos = photos($plek, false); ?>
  <h2 style="margin-top:10px"><?= h($plekNaam) ?></h2>
  <?php if (!$fotos): ?>
    <p class="muted">Nog geen foto's hier.</p>
  <?php else: ?>
  <div class="photo-grid">
    <?php foreach ($fotos as $f): ?>
    <div class="photo <?= $f['zichtbaar'] ? '' : 'verborgen' ?>" id="foto-<?= (int) $f['id'] ?>">
      <img src="../<?= h($f['pad']) ?>" alt="<?= h($f['alt']) ?>" loading="lazy">
      <div class="body">
        <form class="stack" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
          <label>Beschrijving <input type="text" name="alt" id="alt-<?= (int) $f['id'] ?>" value="<?= h($f['alt']) ?>" maxlength="255"></label>
          <div class="row">
            <label>Plek
              <select name="plek" id="plek-<?= (int) $f['id'] ?>">
                <?php foreach (PLEKKEN as $k => $v): ?><option value="<?= $k ?>" <?= $k === $f['plek'] ? 'selected' : '' ?>><?= h(ucfirst($k)) ?></option><?php endforeach; ?>
              </select>
            </label>
            <label>Volgorde <input type="number" name="volgorde" id="volgorde-<?= (int) $f['id'] ?>" value="<?= (int) $f['volgorde'] ?>" min="0" max="999"></label>
          </div>
          <?php if ($plek === 'galerij'): ?>
            <label>Grootte
              <select name="formaat" id="formaat-<?= (int) $f['id'] ?>">
                <?php foreach (FORMATEN as $k => $v): ?><option value="<?= $k ?>" <?= $k === $f['formaat'] ? 'selected' : '' ?>><?= h($v) ?></option><?php endforeach; ?>
              </select>
            </label>
          <?php else: ?>
            <input type="hidden" name="formaat" value="<?= h($f['formaat']) ?>">
          <?php endif; ?>
          <?php if ($plek === 'kaart'): ?>
            <label>Label <input type="text" name="label" id="label-<?= (int) $f['id'] ?>" value="<?= h($f['label']) ?>" maxlength="60"></label>
          <?php else: ?>
            <input type="hidden" name="label" value="<?= h($f['label']) ?>">
          <?php endif; ?>
          <label class="check"><input type="checkbox" name="zichtbaar" id="zichtbaar-<?= (int) $f['id'] ?>" <?= $f['zichtbaar'] ? 'checked' : '' ?>> Tonen op de site</label>
          <div class="actions">
            <button class="btn sm" name="actie" value="opslaan">Opslaan</button>
            <button class="btn sm danger" name="actie" value="verwijder" formnovalidate
              onclick="return this.dataset.zeker === '1' || (this.dataset.zeker = '1', this.textContent = 'Klik nog eens om te verwijderen', false)">Verwijderen</button>
          </div>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
<?php endforeach; ?>
<?php admin_foot(); ?>
