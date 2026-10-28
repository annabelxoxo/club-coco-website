<?php
// Wordt bovenaan elke pagina geladen: config, database en hulpfuncties.

declare(strict_types=1);

define('ROOT', dirname(__DIR__));

$configFile = ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('config.php ontbreekt. Kopieer config.sample.php naar config.php en vul je databasegegevens in.');
}
$config = require $configFile;

if (!empty($config['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

date_default_timezone_set('Europe/Brussels');

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
