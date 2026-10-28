<?php
// Eenmalig: maakt het eerste adminaccount aan.
// Werkt ALLEEN zolang er nog geen enkel account bestaat. Daarna toont deze pagina niets meer.

require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';

start_session();

if ((int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    http_response_code(404);
    exit('Niet gevonden.');
}

$fout = '';
$naam = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $naam = post('naam');
    $email = post('email');
    $ww = (string) ($_POST['wachtwoord'] ?? '');
    $ww2 = (string) ($_POST['wachtwoord2'] ?? '');

    if ($naam === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fout = 'Vul een naam en een geldig e-mailadres in.';
    } elseif (strlen($ww) < 10) {
        $fout = 'Kies een wachtwoord van minstens 10 tekens.';
    } elseif ($ww !== $ww2) {
        $fout = 'De twee wachtwoorden zijn niet hetzelfde.';
    } else {
        db()->prepare('INSERT INTO users (naam, email, password_hash) VALUES (?, ?, ?)')
            ->execute([$naam, $email, password_hash($ww, PASSWORD_DEFAULT)]);
        $_SESSION['flash'][] = ['tekst' => 'Account aangemaakt. Je kan nu inloggen.', 'soort' => 'ok'];
        redirect('login.php');
    }
}

admin_head('Installatie', '', false);
?>
<div class="login-wrap">
  <form class="login" method="post">
    <h1>Welkom!</h1>
    <p class="sub">Maak het eerste account voor de adminpagina's.</p>
    <?php if ($fout): ?><div class="flash flash-fout"><?= h($fout) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label>Naam <input type="text" name="naam" id="naam" value="<?= h($naam) ?>" required></label>
    <label>E-mailadres <input type="email" name="email" id="email" value="<?= h($email) ?>" required autocomplete="username"></label>
    <label>Wachtwoord <span class="hint">minstens 10 tekens</span>
      <input type="password" name="wachtwoord" id="wachtwoord" required minlength="10" autocomplete="new-password"></label>
    <label>Herhaal wachtwoord
      <input type="password" name="wachtwoord2" id="wachtwoord2" required minlength="10" autocomplete="new-password"></label>
    <button class="btn" type="submit">Account aanmaken</button>
  </form>
</div>
<?php admin_foot(); ?>
