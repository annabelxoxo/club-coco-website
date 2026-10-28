<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';

start_session();
header('X-Frame-Options: DENY');

if (!empty($_SESSION['user_id'])) {
    redirect('index.php');
}

// Nog geen account? Dan eerst installeren.
if ((int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
    redirect('install.php');
}

const MAX_POGINGEN = 5;     // zoveel foute pogingen...
const BLOKKEER_MIN = 15;    // ...per zoveel minuten

$ip = $_SERVER['REMOTE_ADDR'] ?? 'onbekend';
$fout = '';
$email = '';

function pogingen(string $ip): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL ' . BLOKKEER_MIN . ' MINUTE)');
    $stmt->execute([$ip]);
    return (int) $stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = post('email');
    $wachtwoord = (string) ($_POST['wachtwoord'] ?? '');

    if (pogingen($ip) >= MAX_POGINGEN) {
        $fout = 'Te veel foute pogingen. Wacht ' . BLOKKEER_MIN . ' minuten en probeer opnieuw.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($wachtwoord, $user['password_hash'])) {
            // Gelukt: nieuwe sessie-id (tegen session fixation) en pogingen wissen
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_naam'] = $user['naam'];
            $_SESSION['laatst_actief'] = time();
            db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);

            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($wachtwoord, PASSWORD_DEFAULT), $user['id']]);
            }
            redirect('index.php');
        }

        db()->prepare('INSERT INTO login_attempts (ip) VALUES (?)')->execute([$ip]);
        // Bewust vaag: we verklappen niet of het e-mailadres bestaat
        $fout = 'E-mailadres of wachtwoord klopt niet.';
    }
}

admin_head('Inloggen', '', false);
?>
<div class="login-wrap">
  <form class="login" method="post" autocomplete="on">
    <h1>club Coco</h1>
    <p class="sub">Log in om de website aan te passen</p>
    <?php if ($fout): ?><div class="flash flash-fout"><?= h($fout) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label>E-mailadres
      <input type="email" name="email" id="email" value="<?= h($email) ?>" required autofocus autocomplete="username">
    </label>
    <label>Wachtwoord
      <input type="password" name="wachtwoord" id="wachtwoord" required autocomplete="current-password">
    </label>
    <button class="btn" type="submit">Inloggen</button>
  </form>
</div>
<?php admin_foot(); ?>
