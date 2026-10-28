<?php
require __DIR__ . '/../includes/bootstrap.php';

start_session();

// Uitloggen enkel via het formulier (POST + CSRF), niet via een gewone link
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
redirect('login.php');
