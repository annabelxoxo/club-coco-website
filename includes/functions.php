<?php
// Kleine hulpfuncties die op de site en in admin gebruikt worden.

/** Tekst veilig tonen in HTML (tegen XSS). Gebruik dit bij ALLES wat uit de database komt. */
function h(?string $tekst): string
{
    return htmlspecialchars((string) $tekst, ENT_QUOTES, 'UTF-8');
}

/** Alle instellingen in één keer ophalen, daarna uit het geheugen. */
function settings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = db()->query('SELECT sleutel, waarde FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache;
}

function setting(string $sleutel, string $standaard = ''): string
{
    $waarde = settings()[$sleutel] ?? '';
    return $waarde !== '' ? $waarde : $standaard;
}

function save_setting(string $sleutel, string $waarde): void
{
    $stmt = db()->prepare('INSERT INTO settings (sleutel, waarde) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE waarde = VALUES(waarde)');
    $stmt->execute([$sleutel, $waarde]);
}

/** Tekst met witregels omzetten naar <p>-alinea's. */
function paragraphs(string $tekst): string
{
    $delen = preg_split('/\R\s*\R/', trim($tekst)) ?: [];
    $html = '';
    foreach ($delen as $deel) {
        if (trim($deel) !== '') {
            $html .= '<p>' . nl2br(h(trim($deel))) . '</p>';
        }
    }
    return $html;
}

const DAGEN = [1 => 'Maandag', 2 => 'Dinsdag', 3 => 'Woensdag', 4 => 'Donderdag', 5 => 'Vrijdag', 6 => 'Zaterdag', 0 => 'Zondag'];

/** Openingsuren in volgorde maandag → zondag. */
function opening_hours(): array
{
    $rijen = db()->query('SELECT * FROM opening_hours')->fetchAll();
    $perDag = [];
    foreach ($rijen as $rij) {
        $perDag[(int) $rij['dag']] = $rij;
    }
    $uit = [];
    foreach (DAGEN as $nr => $naam) {
        $uit[$nr] = $perDag[$nr] ?? ['dag' => $nr, 'gesloten' => 1, 'open' => null, 'sluit' => null];
        $uit[$nr]['naam'] = $naam;
    }
    return $uit;
}

/** "08:00:00" → "08:00" */
function tijd(?string $t): string
{
    return $t ? substr($t, 0, 5) : '';
}

function photos(string $plek, bool $alleenZichtbaar = true): array
{
    $sql = 'SELECT * FROM photos WHERE plek = ?' . ($alleenZichtbaar ? ' AND zichtbaar = 1' : '') . ' ORDER BY volgorde, id';
    $stmt = db()->prepare($sql);
    $stmt->execute([$plek]);
    return $stmt->fetchAll();
}

/** Categorieën met hun items, gegroepeerd per kolom (1, 2, 3). */
function menu_columns(): array
{
    $cats = db()->query('SELECT * FROM categories WHERE zichtbaar = 1 ORDER BY kolom, volgorde, id')->fetchAll();
    $items = db()->query('SELECT * FROM menu_items WHERE zichtbaar = 1 ORDER BY volgorde, id')->fetchAll();

    $perCat = [];
    foreach ($items as $item) {
        $perCat[$item['category_id']][] = $item;
    }
    $kolommen = [1 => [], 2 => [], 3 => []];
    foreach ($cats as $cat) {
        $cat['items'] = $perCat[$cat['id']] ?? [];
        $k = max(1, min(3, (int) $cat['kolom']));
        $kolommen[$k][] = $cat;
    }
    return $kolommen;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/* ---------- Sessies, CSRF en meldingen (alleen in admin) ---------- */

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('coco_admin');
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

/** Stopt het verzoek als het formulier niet van onze eigen adminpagina komt. */
function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('Dit formulier is verlopen. Ga terug, vernieuw de pagina en probeer opnieuw.');
    }
}

function flash(string $tekst, string $soort = 'ok'): void
{
    $_SESSION['flash'][] = ['tekst' => $tekst, 'soort' => $soort];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function post(string $naam): string
{
    $v = $_POST[$naam] ?? '';
    return is_string($v) ? trim($v) : '';
}
