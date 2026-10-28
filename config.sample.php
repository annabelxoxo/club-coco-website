<?php
// Kopieer dit bestand naar config.php en vul je eigen gegevens in.
// config.php staat in .gitignore: wachtwoorden horen niet op GitHub.

return [
    'db_host' => 'localhost',
    'db_name' => 'clubcoco',
    'db_user' => 'root',
    'db_pass' => '',          // XAMPP: leeg laten, Laragon: leeg laten, hosting: wachtwoord van je database

    // Zet op false zodra de site echt online staat (toont dan geen PHP-fouten aan bezoekers)
    'debug'   => true,
];
