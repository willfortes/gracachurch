<?php
declare(strict_types=1);

use App\Db;

function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

/** Escapa texto para HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    return rtrim(env('APP_URL', 'http://localhost:8080'), '/') . $path;
}

/** Caminho de arquivo estático com versão (cache longo e troca automática quando o arquivo muda). */
function asset(string $path): string
{
    $file = ROOT . '/public' . $path;
    $v = is_file($file) ? substr(base_convert((string) filemtime($file), 10, 36), -6) : '0';
    return $path . '?v=' . $v;
}

/** Configurações do site (tabela settings), no idioma atual quando houver tradução. */
function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (Db::all('SELECT k, v FROM settings') as $row) {
            $cache[$row['k']] = (string) $row['v'];
        }
    }
    if (App\I18n::$lang !== 'pt' && in_array($key, App\Content::TRANSLATABLE['setting'], true)) {
        $tr = App\Content::translations('setting')[0][$key] ?? '';
        if (trim($tr) !== '') {
            return $tr;
        }
    }
    return ($cache[$key] ?? '') !== '' ? $cache[$key] : $default;
}

/** Texto da interface no idioma atual. */
function t(string $key, array $vars = []): string
{
    return App\I18n::t($key, $vars);
}

/** Endereço de uma página no idioma atual. */
function u(string $route = 'home', string $slug = ''): string
{
    return App\I18n::url($route, $slug);
}

function redirect(string $to, int $code = 303): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function json_out(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n',
    ]);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

/** Só dígitos, com DDI 55 quando faltar. Usado em links do WhatsApp. */
function phone_digits(string $phone): string
{
    $d = preg_replace('/\D+/', '', $phone) ?? '';
    if ($d !== '' && strlen($d) <= 11) {
        $d = '55' . $d;
    }
    return $d;
}

function whatsapp_link(string $phone, string $message = ''): string
{
    $d = phone_digits($phone);
    return 'https://wa.me/' . $d . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

/** (48) 98850-1006 */
function phone_display(string $phone): string
{
    $d = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($d, '55') && strlen($d) > 11) {
        $d = substr($d, 2);
    }
    if (strlen($d) === 11) {
        return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7));
    }
    if (strlen($d) === 10) {
        return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6));
    }
    return $phone;
}

/** Links "como chegar" para os apps de mapa. */
function directions_links(?string $lat, ?string $lng, string $label): array
{
    $hasGeo = $lat !== null && $lat !== '' && $lng !== null && $lng !== '';
    $q = $hasGeo ? "$lat,$lng" : $label;
    return [
        'google' => 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($q),
        'waze' => $hasGeo ? "https://waze.com/ul?ll=$lat,$lng&navigate=yes" : 'https://waze.com/ul?q=' . rawurlencode($label) . '&navigate=yes',
        'apple' => 'https://maps.apple.com/?daddr=' . rawurlencode($q) . '&q=' . rawurlencode($label),
    ];
}

function church_address(): string
{
    $country = ['pt' => '', 'en' => ', Brazil', 'es' => ', Brasil'][App\I18n::$lang];
    return trim(sprintf(
        '%s, %s – %s, %s%s',
        setting('address_street', 'BR 282, KM 18'),
        setting('address_city', 'Palhoça'),
        setting('address_state', 'SC'),
        setting('address_zip', '88135-550'),
        $country
    ));
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Requisição veio do próprio site? (proteção contra envio de formulário por outro domínio) */
function same_origin(): bool
{
    $site = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
    if ($site !== '') {
        return in_array($site, ['same-origin', 'none'], true);
    }
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '') {
        return true;
    }
    $host = parse_url($origin, PHP_URL_HOST);
    return $host !== null && $host === parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
}

function nl2p(string $text): string
{
    $parts = preg_split('/\n\s*\n/', trim($text)) ?: [];
    return implode('', array_map(fn ($p) => '<p>' . nl2br(e(trim($p))) . '</p>', array_filter($parts, fn ($p) => trim($p) !== '')));
}

function truncate(string $text, int $len): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    return mb_strlen($text) > $len ? rtrim(mb_substr($text, 0, $len - 1)) . '…' : $text;
}

/** Destaca a palavra-chave da frase em itálico serifado: *palavra* ou, sem marcação, "avivamento". */
function accent(string $text): string
{
    $html = e($text);
    if (str_contains($html, '*')) {
        return preg_replace('/\*(.+?)\*/u', '<em>$1</em>', $html) ?? $html;
    }
    return preg_replace('/\b(avivamento|revival|avivamiento)\b/iu', '<em>$1</em>', $html, 1) ?? $html;
}

function plain(string $text): string
{
    return str_replace('*', '', $text);
}

/** Preferências de acessibilidade salvas no cookie (aplicadas já no HTML, sem piscar). */
function a11y_classes(): string
{
    $allowed = ['t-1', 't-2', 't-3', 'contrast', 'motion', 'links', 'spacing'];
    $prefs = array_intersect(explode(',', (string) ($_COOKIE['gc_a11y'] ?? '')), $allowed);
    return implode(' ', $prefs);
}
