<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/admin_layout.php';

$fout = '';
$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$huidigeGebruiker['id']]);
$ik = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $actie = post('actie');
    $huidig = (string) ($_POST['huidig'] ?? '');

    // Voor elke wijziging eerst het huidige wachtwoord vragen
    if (!password_verify($huidig, $ik['password_hash'])) {
        $fout = 'Je huidige wachtwoord klopt niet.';
    } elseif ($actie === 'wachtwoord') {
        $nieuw = (string) ($_POST['nieuw'] ?? '');
        if (strlen($nieuw) < 10) {
            $fout = 'Kies een nieuw wachtwoord van minstens 10 tekens.';
        } elseif ($nieuw !== ($_POST['nieuw2'] ?? '')) {
            $fout = 'De twee nieuwe wachtwoorden zijn niet hetzelfde.';
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($nieuw, PASSWORD_DEFAULT), $ik['id']]);
            session_regenerate_id(true);
            flash('Je wachtwoord is gewijzigd.');
            redirect('account.php');
        }
    } elseif ($actie === 'nieuw_account') {
        $naam = post('naam');
        $email = post('email');
        $ww = (string) ($_POST['wachtwoord'] ?? '');
        $bestaat = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $bestaat->execute([$email]);
        if ($naam === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fout = 'Vul een naam en een geldig e-mailadres in.';
        } elseif ((int) $bestaat->fetchColumn() > 0) {
            $fout = 'Er bestaat al een account met dit e-mailadres.';
        } elseif (strlen($ww) < 10) {
            $fout = 'Kies een wachtwoord van minstens 10 tekens.';
        } else {
            db()->prepare('INSERT INTO users (naam, email, password_hash) VALUES (?, ?, ?)')
                ->execute([$naam, $email, password_hash($ww, PASSWORD_DEFAULT)]);
            flash("Account voor $naam aangemaakt. Geef het wachtwoord persoonlijk door.");
            redirect('account.php');
        }
    } elseif ($actie === 'verwijder_account') {
        $id = (int) post('id');
        if ($id === (int) $ik['id']) {
            $fout = 'Je kan je eigen account niet verwijderen.';
        } else {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('Account verwijderd.');
            redirect('account.php');
        }
    }
}

$accounts = db()->query('SELECT id, naam, email, created_at FROM users ORDER BY id')->fetchAll();

admin_head('Account', 'account');
?>
<div class="page-head">
  <div>
    <h1>Account</h1>
    <p>Ingelogd als <?= h($ik['naam']) ?> (<?= h($ik['email']) ?>).</p>
  </div>
</div>
<?php if ($fout): ?><div class="flash flash-fout"><?= h($fout) ?></div><?php endif; ?>

<div class="grid-2">
  <div class="card">
    <h2>Wachtwoord wijzigen</h2>
    <form class="stack" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="actie" value="wachtwoord">
      <label>Huidig wachtwoord <input type="password" name="huidig" id="huidig-1" required autocomplete="current-password"></label>
      <label>Nieuw wachtwoord <span class="hint">minstens 10 tekens</span>
        <input type="password" name="nieuw" id="nieuw" required minlength="10" autocomplete="new-password"></label>
      <label>Herhaal nieuw wachtwoord <input type="password" name="nieuw2" id="nieuw2" required minlength="10" autocomplete="new-password"></label>
      <div class="actions"><button class="btn" type="submit">Wachtwoord wijzigen</button></div>
    </form>
  </div>

  <div class="card">
    <h2>Iemand anders toegang geven</h2>
    <form class="stack" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="actie" value="nieuw_account">
      <label>Naam <input type="text" name="naam" id="naam-nieuw" required></label>
      <label>E-mailadres <input type="email" name="email" id="email-nieuw" required autocomplete="off"></label>
      <label>Wachtwoord voor die persoon <span class="hint">minstens 10 tekens</span>
        <input type="password" name="wachtwoord" id="ww-nieuw" required minlength="10" autocomplete="new-password"></label>
      <label>Jouw huidige wachtwoord <span class="hint">ter bevestiging</span>
        <input type="password" name="huidig" id="huidig-2" required autocomplete="current-password"></label>
      <div class="actions"><button class="btn" type="submit">Account aanmaken</button></div>
    </form>
  </div>
</div>

<div class="card">
  <h2>Wie heeft toegang?</h2>
  <ul class="items">
    <?php foreach ($accounts as $a): ?>
    <li style="grid-template-columns:1fr auto">
      <div class="naam"><?= h($a['naam']) ?> <small><?= h($a['email']) ?></small></div>
      <?php if ((int) $a['id'] !== (int) $ik['id']): ?>
        <form class="row" method="post" style="flex-wrap:nowrap">
          <?= csrf_field() ?>
          <input type="hidden" name="actie" value="verwijder_account">
          <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
          <input type="password" name="huidig" id="huidig-del-<?= (int) $a['id'] ?>" placeholder="Jouw wachtwoord" required style="max-width:170px">
          <button class="btn sm danger" type="submit" style="flex:none">Verwijderen</button>
        </form>
      <?php else: ?>
        <span class="pill">jij</span>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php admin_foot(); ?>
