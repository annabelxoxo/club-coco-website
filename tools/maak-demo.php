<?php
// Maakt een statische DEMO van de site + adminpagina's voor GitHub Pages
// (GitHub Pages kan geen PHP of MySQL draaien, dus opslaan werkt in de demo niet).
//
// Gebruik (MySQL van XAMPP moet draaien):
//   php tools/maak-demo.php
//
// Wat het doet:
//   1. kopieert de site naar een tijdelijke map met een eigen config.php
//   2. maakt een aparte database "clubcoco_demo" met database/schema.sql
//   3. start de PHP-server, maakt een demo-account, logt in en bewaart elke pagina
//   4. schrijft index.html en admin/*.html, en ruimt alles weer op
// Je echte database "clubcoco" wordt nooit aangeraakt.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_NAAM = 'clubcoco_demo';
const POORT   = 8765;

$dbPass = getenv('DEMO_DB_PASS') ?: '';
$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'coco-demo-' . getmypid();

function stap(string $tekst): void { echo "• $tekst\n"; }

function kopieer(string $van, string $naar): void
{
    if (is_dir($van)) {
        @mkdir($naar, 0777, true);
        foreach (scandir($van) as $f) {
            if ($f !== '.' && $f !== '..') kopieer("$van/$f", "$naar/$f");
        }
    } else {
        copy($van, $naar);
    }
}

function wis(string $pad): void
{
    if (is_dir($pad)) {
        foreach (scandir($pad) as $f) {
            if ($f !== '.' && $f !== '..') wis("$pad/$f");
        }
        @rmdir($pad);
    } elseif (is_file($pad)) {
        @unlink($pad);
    }
}

// --- 1. tijdelijke kopie --------------------------------------------------
stap('Tijdelijke kopie maken');
mkdir($tmp, 0777, true);
foreach (['index.php', 'admin', 'includes', 'assets', 'images', 'database'] as $deel) {
    kopieer("$root/$deel", "$tmp/$deel");
}
@mkdir("$tmp/uploads");
file_put_contents("$tmp/config.php", '<?php return ' . var_export([
    'db_host' => DB_HOST, 'db_name' => DB_NAAM, 'db_user' => DB_USER, 'db_pass' => $dbPass, 'debug' => true,
], true) . ';');

// --- 2. demo-database -----------------------------------------------------
stap('Database ' . DB_NAAM . ' klaarzetten');
$db = new mysqli(DB_HOST, DB_USER, $dbPass);
$db->set_charset('utf8mb4');
$db->query('DROP DATABASE IF EXISTS ' . DB_NAAM);
$db->query('CREATE DATABASE ' . DB_NAAM . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db->select_db(DB_NAAM);
$db->multi_query(file_get_contents("$root/database/schema.sql"));
do { if ($r = $db->store_result()) $r->free(); } while ($db->more_results() && $db->next_result());
if ($db->errno) exit("Fout in schema.sql: {$db->error}\n");

// --- 3. server starten en pagina's ophalen --------------------------------
stap('PHP-server starten');
$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . POORT, '-t', $tmp], [1 => ['file', 'php://temp', 'w'], 2 => ['file', 'php://temp', 'w']], $pipes);
for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', POORT); $i++) usleep(100000);

$cookies = "$tmp/cookies.txt";
function haal(string $pad, ?array $post = null): string
{
    global $cookies;
    $c = curl_init('http://127.0.0.1:' . POORT . '/' . $pad);
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookies, CURLOPT_COOKIEFILE => $cookies,
    ]);
    if ($post !== null) {
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $html = (string) curl_exec($c);
    $code = curl_getinfo($c, CURLINFO_RESPONSE_CODE);
    curl_close($c);
    if ($code !== 200) exit("Pagina $pad gaf code $code:
" . substr(strip_tags($html), 0, 400) . "
");
    return $html;
}
function token(string $html): string
{
    return preg_match('/name="csrf" value="([^"]+)"/', $html, $m) ? $m[1] : '';
}

stap('Demo-account aanmaken en inloggen');
$ww = bin2hex(random_bytes(12));
$html = haal('admin/install.php');
haal('admin/install.php', ['csrf' => token($html), 'naam' => 'Stephany', 'email' => 'demo@clubcoco.be', 'wachtwoord' => $ww, 'wachtwoord2' => $ww]);
$paginas = ['admin/login' => haal('admin/login.php')];   // zonder melding "account aangemaakt"
haal('admin/login.php', ['csrf' => token($paginas['admin/login']), 'email' => 'demo@clubcoco.be', 'wachtwoord' => $ww]);

stap("Pagina's bewaren");
$paginas['index'] = haal('index.php');
foreach (['index', 'kaart', 'uren', 'fotos', 'teksten', 'account', 'categorie'] as $p) {
    $paginas["admin/$p"] = haal("admin/$p.php");
}
foreach ($db->query('SELECT id FROM categories') as $r) {
    $paginas["admin/categorie-{$r['id']}"] = haal("admin/categorie.php?id={$r['id']}");
    $paginas["admin/item-nieuw-{$r['id']}"] = haal("admin/item.php?categorie={$r['id']}");
}
foreach ($db->query('SELECT id FROM menu_items') as $r) {
    $paginas["admin/item-{$r['id']}"] = haal("admin/item.php?id={$r['id']}");
}

// --- 4. omzetten naar statische demo --------------------------------------
$banner = <<<'HTML'
<div class="demo-bar" role="note">DEMO · dit is een voorbeeld op GitHub Pages: wijzigingen worden niet opgeslagen</div>
<style>
.demo-bar{position:sticky;top:0;z-index:999;background:#fbe7a6;color:#3b0d13;font:600 13px/1.4 system-ui,sans-serif;text-align:center;padding:7px 12px}
.demo-toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:1000;background:#3b0d13;color:#fff;font:500 14px/1.4 system-ui,sans-serif;padding:12px 18px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.3);max-width:calc(100% - 32px)}
</style>
<script>
document.addEventListener('submit', function (e) {
  var f = e.target, doel = f.getAttribute('data-demo-doel');
  e.preventDefault();
  if (doel) { location.href = doel; return; }
  var t = document.querySelector('.demo-toast') || document.body.appendChild(document.createElement('div'));
  t.className = 'demo-toast';
  t.textContent = 'Demo: opslaan werkt pas als de site op echte PHP-hosting staat.';
  clearTimeout(t._t); t._t = setTimeout(function () { t.remove(); }, 3500);
});
</script>
HTML;

function zet_om(string $naam, string $html, string $banner): string
{
    $html = preg_replace('/<input type="hidden" name="csrf" value="[^"]*">/', '', $html);
    if ($naam === 'index') return $html;

    $html = preg_replace_callback('/(href|action)="([a-z]+)\.php(?:\?(id|categorie)=(\d+))?(#[^"]*)?"/', function ($m) {
        [$heel, $attr, $pagina] = $m;
        $doel = $pagina;
        if (!empty($m[3])) $doel .= ($m[3] === 'categorie' ? '-nieuw-' : '-') . $m[4];
        return $attr . '="' . $doel . '.html' . ($m[5] ?? '') . '"';
    }, $html);
    // inloggen gaat naar het overzicht, uitloggen naar de loginpagina
    $html = str_replace('action="logout.html"', 'action="login.html" data-demo-doel="login.html"', $html);
    if ($naam === 'admin/login') $html = str_replace('<form class="login"', '<form class="login" data-demo-doel="index.html"', $html);
    return preg_replace('/<body([^>]*)>/', '<body$1>' . "\n" . $banner, $html, 1);
}

foreach ($paginas as $naam => $html) {
    if (trim($html) === '' || str_contains($html, 'Fatal error')) exit("Pagina $naam lukte niet:\n" . substr($html, 0, 500) . "\n");
    file_put_contents("$root/$naam.html", zet_om($naam, $html, $banner));
}

// --- opruimen -------------------------------------------------------------
stap('Opruimen');
proc_terminate($server);
proc_close($server);
$db->query('DROP DATABASE ' . DB_NAAM);
wis($tmp);

echo "\nKlaar: " . count($paginas) . " pagina's. Commit en push, dan staat de demo op GitHub Pages.\n";
