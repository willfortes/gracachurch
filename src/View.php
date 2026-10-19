<?php
declare(strict_types=1);

namespace App;

final class View
{
    /** Renderiza um template de views/ e devolve o HTML. */
    public static function render(string $template, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require ROOT . '/views/' . $template . '.php';
        return (string) ob_get_clean();
    }

    /**
     * Página pública completa.
     * $meta: title, description, path, jsonld (lista de nós), nav, image, robots, status
     */
    public static function page(string $template, array $data, array $meta): never
    {
        $content = self::render($template, $data);
        $html = self::render('layout', ['content' => $content, 'meta' => $meta]);
        // Lembra o último idioma usado (a página inicial abre nele na próxima visita)
        if (($_COOKIE['gc_lang'] ?? '') !== I18n::$lang && ($meta['status'] ?? 200) === 200) {
            setcookie('gc_lang', I18n::$lang, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
        }
        header('Content-Language: ' . I18n::htmlLang());
        self::send($html, (int) ($meta['status'] ?? 200), 'private, max-age=0, must-revalidate');
    }

    public static function admin(string $template, array $data, string $title, string $nav = ''): never
    {
        $content = self::render('admin/' . $template, $data);
        $html = self::render('admin/layout', ['content' => $content, 'title' => $title, 'nav' => $nav, 'user' => Auth::user()]);
        header('X-Frame-Options: DENY');
        self::send($html, 200, 'no-store');
    }

    public static function send(string $body, int $status = 200, string $cache = 'no-store', string $type = 'text/html; charset=utf-8'): never
    {
        http_response_code($status);
        header('Content-Type: ' . $type);
        header('Cache-Control: ' . $cache);
        self::securityHeaders();
        if ($status === 200 && $cache !== 'no-store') {
            // ETag: na revisita, se nada mudou, o navegador recebe só um "304" (quase zero dados)
            $etag = '"' . substr(md5($body), 0, 16) . '"';
            header('ETag: ' . $etag);
            if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
                http_response_code(304);
                exit;
            }
        }
        echo $body;
        exit;
    }

    public static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
        $vl = 'https://vlibras.gov.br https://*.vlibras.gov.br';
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'wasm-unsafe-eval' 'unsafe-eval' $vl; style-src 'self' 'unsafe-inline' $vl; "
            . "img-src 'self' data: blob: https://tile.openstreetmap.org $vl; media-src 'self' blob: $vl; "
            . "connect-src 'self' https://nominatim.openstreetmap.org $vl; font-src 'self' data: $vl; frame-src 'self' $vl; frame-ancestors 'self'; "
            . "base-uri 'self'; form-action 'self'; manifest-src 'self'; worker-src 'self' blob: $vl");
    }

    public static function notFound(): never
    {
        self::page('404', [], [
            'title' => 'Página não encontrada',
            'description' => 'Esta página não existe mais.',
            'path' => '/404',
            'robots' => 'noindex',
            'status' => 404,
        ]);
    }

    /** Ícones SVG de traço (24x24), herdam a cor do texto. */
    public static function icon(string $name, int $size = 22): string
    {
        $paths = [
            'home' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5h13V10"/><path d="M10 19.5v-5h4v5"/>',
            'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
            'people' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 19.5c.6-3.3 3-5 6-5s5.4 1.7 6 5"/><circle cx="17" cy="9" r="2.4"/><path d="M16 14.6c2.5-.2 4.4 1.2 5 4.4"/>',
            'pin' => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.4"/>',
            'bell' => '<path d="M6 16.5V11a6 6 0 0 1 12 0v5.5l1.5 2h-15Z"/><path d="M10 20.5a2.2 2.2 0 0 0 4 0"/>',
            'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
            'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
            'download' => '<path d="M12 4v11M7 10.5l5 5 5-5"/><path d="M5 19.5h14"/>',
            'whatsapp' => '<path d="M4.5 19.5 5.6 16A8 8 0 1 1 8.4 18.6Z"/><path d="M9.2 8.6c.2 2.9 2.4 5.3 5.4 5.9l.9-1.4-1.7-.9-.8.8c-.9-.4-1.7-1.2-2.1-2.1l.8-.8-.9-1.7Z"/>',
            'instagram' => '<rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.6"/><circle cx="16.8" cy="7.2" r=".6"/>',
            'locate' => '<circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="7.5"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22"/>',
            'share' => '<path d="M12 4v11M8 7.5l4-3.5 4 3.5"/><path d="M6 11v8.5h12V11"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
            'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
            'map' => '<path d="M9 5 3.5 7v12L9 17l6 2 5.5-2V5L15 7Z"/><path d="M9 5v12M15 7v12"/>',
            'flame' => '<path d="M12 21c-3.6 0-6-2.4-6-5.7 0-4 3.4-5.6 4-9.8 2.6 1.6 4.2 4 4.2 6.3.8-.6 1.3-1.6 1.4-2.6 1.5 1.5 2.4 3.6 2.4 6 0 3.4-2.5 5.8-6 5.8Z"/>',
            'book' => '<path d="M4.5 5.5c2.8-1 5.3-.8 7.5.8 2.2-1.6 4.7-1.8 7.5-.8v13c-2.8-1-5.3-.8-7.5.8-2.2-1.6-4.7-1.8-7.5-.8Z"/><path d="M12 6.3v13"/>',
            'hands' => '<path d="M12 20.5V12M12 12 8.2 4.8a1.5 1.5 0 0 0-2.7 1.3L8 13l-2 3.5 3 4M12 12l3.8-7.2a1.5 1.5 0 0 1 2.7 1.3L16 13l2 3.5-3 4"/>',
            'drop' => '<path d="M12 3.5S6.5 10 6.5 14a5.5 5.5 0 0 0 11 0C17.5 10 12 3.5 12 3.5Z"/>',
            'logout' => '<path d="M14 4.5h4.5v15H14M10 8l-4 4 4 4M6 12h9"/>',
            'edit' => '<path d="m14.5 5.5 4 4L9 19H5v-4Z"/>',
            'trash' => '<path d="M5 7h14M9.5 7V4.5h5V7M7 7l1 12.5h8L17 7"/>',
            'send' => '<path d="m4 12 16-7.5-6 16-2.5-6.5Z"/><path d="m11.5 14 8.5-9.5"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2.5M12 18.5V21M3 12h2.5M18.5 12H21M5.6 5.6l1.8 1.8M16.6 16.6l1.8 1.8M5.6 18.4l1.8-1.8M16.6 7.4l1.8-1.8"/>',
            'help' => '<circle cx="12" cy="12" r="8.5"/><path d="M9.6 9.5a2.5 2.5 0 1 1 3.4 2.3c-.6.3-1 .8-1 1.5v.7M12 16.8v.2"/>',
            'user' => '<circle cx="12" cy="8.5" r="3.5"/><path d="M5 20c.8-3.6 3.6-5.5 7-5.5s6.2 1.9 7 5.5"/>',
            'globe' => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.4 2.3 3.6 5.1 3.6 8.5s-1.2 6.2-3.6 8.5c-2.4-2.3-3.6-5.1-3.6-8.5S9.6 5.8 12 3.5Z"/>',
            'access' => '<circle cx="12" cy="4.8" r="1.8"/><path d="M5 8.5c2.3.7 4.6 1 7 1s4.7-.3 7-1M12 9.5v4.5M12 14l-3 6.5M12 14l3 6.5"/>',
            'sparkle' => '<path d="M12 3c.6 4.4 2.6 6.4 7 7-4.4.6-6.4 2.6-7 7-.6-4.4-2.6-6.4-7-7 4.4-.6 6.4-2.6 7-7Z"/>',
            'external' => '<path d="M13.5 5H19v5.5M19 5l-8 8M17 14v5H5V7h5"/>',
        ];
        $d = $paths[$name] ?? '';
        return '<svg class="i" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
    }
}
