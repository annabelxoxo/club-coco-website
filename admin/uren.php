<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/admin_layout.php';

$fouten = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stmt = db()->prepare('INSERT INTO opening_hours (dag, gesloten, open, sluit) VALUES (?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE gesloten = VALUES(gesloten), open = VALUES(open), sluit = VALUES(sluit)');
    $nieuw = [];
    foreach (DAGEN as $nr => $naam) {
        $gesloten = isset($_POST['gesloten'][$nr]) ? 1 : 0;
        $open = (string) ($_POST['open'][$nr] ?? '');
        $sluit = (string) ($_POST['sluit'][$nr] ?? '');
        $geldig = fn($t) => (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);

        if (!$gesloten && (!$geldig($open) || !$geldig($sluit))) {
            $fouten[] = "$naam: vul een openings- én sluitingsuur in, of vink \"Gesloten\" aan.";
        } elseif (!$gesloten && $sluit <= $open) {
            $fouten[] = "$naam: het sluitingsuur moet later zijn dan het openingsuur.";
        }
        $nieuw[$nr] = [$nr, $gesloten, $gesloten || !$geldig($open) ? null : $open, $gesloten || !$geldig($sluit) ? null : $sluit];
    }
    if (!$fouten) {
        foreach ($nieuw as $rij) {
            $stmt->execute($rij);
        }
        flash('Openingsuren opgeslagen.');
        redirect('uren.php');
    }
}

$uren = opening_hours();
// Bij een fout: toon wat er net ingevuld werd
if ($fouten) {
    foreach (DAGEN as $nr => $naam) {
        $uren[$nr]['gesloten'] = isset($_POST['gesloten'][$nr]) ? 1 : 0;
        $uren[$nr]['open'] = $_POST['open'][$nr] ?? '';
        $uren[$nr]['sluit'] = $_POST['sluit'][$nr] ?? '';
    }
}

admin_head('Openingsuren', 'uren');
?>
<div class="page-head">
  <div>
    <h1>Openingsuren</h1>
    <p>Eén keer gesloten door een feestdag of vakantie? Zet dat liever als melding op het <a href="index.php">overzicht</a>, dan blijven de gewone uren staan.</p>
  </div>
</div>

<?php foreach ($fouten as $f): ?><div class="flash flash-fout"><?= h($f) ?></div><?php endforeach; ?>

<div class="card">
  <form method="post">
    <?= csrf_field() ?>
    <?php foreach ($uren as $nr => $dag): ?>
      <div class="hours-row">
        <strong><?= h($dag['naam']) ?></strong>
        <label>Open <input type="time" name="open[<?= $nr ?>]" id="open-<?= $nr ?>" value="<?= h(tijd($dag['open'])) ?>"></label>
        <label>Sluit <input type="time" name="sluit[<?= $nr ?>]" id="sluit-<?= $nr ?>" value="<?= h(tijd($dag['sluit'])) ?>"></label>
        <label class="check"><input type="checkbox" name="gesloten[<?= $nr ?>]" id="gesloten-<?= $nr ?>" <?= $dag['gesloten'] ? 'checked' : '' ?>> Gesloten</label>
      </div>
    <?php endforeach; ?>
    <div class="actions" style="margin-top:16px"><button class="btn" type="submit">Opslaan</button></div>
  </form>
</div>
<?php admin_foot(); ?>
