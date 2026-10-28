<?php
// Databaseverbinding met PDO. Gebruik overal db() en prepared statements.

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        global $config;
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);
        try {
            $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit(!empty($config['debug'])
                ? 'Geen verbinding met de database: ' . htmlspecialchars($e->getMessage())
                : 'De website is even niet bereikbaar. Probeer het later opnieuw.');
        }
    }
    return $pdo;
}
