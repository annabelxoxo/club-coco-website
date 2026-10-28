<?php
// Zet  require __DIR__ . '/../includes/auth.php';  bovenaan ELKE adminpagina.
// Wie niet ingelogd is, wordt naar de loginpagina gestuurd.

require_once __DIR__ . '/bootstrap.php';

start_session();

// Na 2 uur niets doen: automatisch uitloggen
const SESSIE_TIMEOUT = 2 * 60 * 60;

if (empty($_SESSION['user_id'])
    || (isset($_SESSION['laatst_actief']) && time() - $_SESSION['laatst_actief'] > SESSIE_TIMEOUT)) {
    $_SESSION = [];
    redirect('login.php');
}
$_SESSION['laatst_actief'] = time();

// Geen caching van adminpagina's en niet tonen in een iframe van een andere site
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');

$huidigeGebruiker = [
    'id'   => (int) $_SESSION['user_id'],
    'naam' => (string) ($_SESSION['user_naam'] ?? ''),
];
