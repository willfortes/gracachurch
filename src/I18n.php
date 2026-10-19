<?php
declare(strict_types=1);

namespace App;

/**
 * Idiomas do site: português (padrão, sem prefixo), inglês (/en) e espanhol (/es).
 * As rotas têm nomes traduzidos: /programacao, /en/schedule, /es/programacion.
 */
final class I18n
{
    public const LANGS = ['pt', 'en', 'es'];
    public const NAMES = ['pt' => 'Português', 'en' => 'English', 'es' => 'Español'];
    public const LOCALES = ['pt' => 'pt_BR', 'en' => 'en_US', 'es' => 'es_ES'];
    public const HTML_LANG = ['pt' => 'pt-BR', 'en' => 'en', 'es' => 'es'];

    /** Segmento de URL de cada página, por idioma. */
    public const ROUTES = [
        'schedule' => ['pt' => 'programacao', 'en' => 'schedule', 'es' => 'programacion'],
        'event' => ['pt' => 'eventos', 'en' => 'events', 'es' => 'eventos'],
        'groups' => ['pt' => 'grupos', 'en' => 'groups', 'es' => 'grupos'],
        'directions' => ['pt' => 'como-chegar', 'en' => 'directions', 'es' => 'como-llegar'],
        'about' => ['pt' => 'sobre', 'en' => 'about', 'es' => 'nosotros'],
        'alerts' => ['pt' => 'avisos', 'en' => 'alerts', 'es' => 'avisos'],
        'help' => ['pt' => 'quero-ajuda', 'en' => 'need-help', 'es' => 'quiero-ayuda'],
        'business' => ['pt' => 'negocios', 'en' => 'businesses', 'es' => 'negocios'],
        'offline' => ['pt' => 'offline', 'en' => 'offline', 'es' => 'offline'],
    ];

    public static string $lang = 'pt';
    private static array $dict = [];

    public static function set(string $lang): void
    {
        self::$lang = in_array($lang, self::LANGS, true) ? $lang : 'pt';
    }

    /** Executa $fn em outro idioma (usado para montar avisos no idioma de cada pessoa). */
    public static function with(string $lang, callable $fn): mixed
    {
        $prev = self::$lang;
        self::set($lang);
        try {
            return $fn();
        } finally {
            self::$lang = $prev;
        }
    }

    public static function t(string $key, array $vars = []): string
    {
        $lang = self::$lang;
        if (!isset(self::$dict[$lang])) {
            self::$dict[$lang] = require ROOT . "/lang/$lang.php";
        }
        $text = self::$dict[$lang][$key] ?? null;
        if ($text === null && $lang !== 'pt') {
            self::$dict['pt'] ??= require ROOT . '/lang/pt.php';
            $text = self::$dict['pt'][$key] ?? null;
        }
        $text ??= $key;
        foreach ($vars as $k => $v) {
            $text = str_replace('{' . $k . '}', (string) $v, $text);
        }
        return $text;
    }

    public static function prefix(?string $lang = null): string
    {
        $lang ??= self::$lang;
        return $lang === 'pt' ? '' : '/' . $lang;
    }

    public static function seg(string $route, ?string $lang = null): string
    {
        return self::ROUTES[$route][$lang ?? self::$lang];
    }

    /** URL de uma página no idioma atual (ou no indicado). */
    public static function url(string $route = 'home', string $slug = '', ?string $lang = null): string
    {
        $lang ??= self::$lang;
        if ($route === 'home') {
            return self::prefix($lang) ?: '/';
        }
        $path = self::prefix($lang) . '/' . self::seg($route, $lang);
        return $slug !== '' ? $path . '/' . $slug : $path;
    }

    /** Separa o idioma do caminho: "/en/schedule" => ["en", "/schedule"] */
    public static function split(string $path): array
    {
        if (preg_match('#^/(en|es)(/.*)?$#', $path, $m)) {
            return [$m[1], ($m[2] ?? '') === '' ? '/' : $m[2]];
        }
        return ['pt', $path];
    }

    /** Converte um caminho em português para outro idioma: "/eventos/x" => "/en/events/x" */
    public static function translatePath(string $path, string $lang): string
    {
        [, $rest] = self::split(parse_url($path, PHP_URL_PATH) ?: '/');
        $query = parse_url($path, PHP_URL_QUERY);
        $parts = explode('/', trim($rest, '/'));
        foreach (self::ROUTES as $segs) {
            if (in_array($parts[0], $segs, true)) {
                $parts[0] = $segs[$lang];
                break;
            }
        }
        $out = self::prefix($lang) . '/' . implode('/', array_filter($parts, fn ($p) => $p !== ''));
        return ($out === '' ? '/' : $out) . ($query ? '?' . $query : '');
    }

    /** Idioma preferido pelo navegador (Accept-Language). */
    public static function fromBrowser(): string
    {
        $header = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        if ($header === '') {
            return 'pt';
        }
        $best = 'pt';
        $bestQ = -1.0;
        foreach (explode(',', $header) as $i => $part) {
            [$tag, $q] = array_pad(explode(';q=', trim($part)), 2, '1');
            $code = substr(trim($tag), 0, 2);
            $q = (float) $q - $i * 0.001;
            if (in_array($code, self::LANGS, true) && $q > $bestQ) {
                $best = $code;
                $bestQ = $q;
            }
        }
        return $best;
    }

    public static function htmlLang(): string
    {
        return self::HTML_LANG[self::$lang];
    }
}
