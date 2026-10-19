<?php
declare(strict_types=1);

namespace App\Http;

use App\Content;
use App\Db;
use App\I18n;
use App\Schedule;
use App\View;
use DateTimeImmutable;

/** sitemap.xml, robots.txt e llms.txt: o que buscadores e assistentes de IA leem. */
final class Discovery
{
    public static function sitemap(): void
    {
        $last = fn (string $sql) => substr((string) (Db::value($sql) ?? date('Y-m-d')), 0, 10);
        $evDate = $last('SELECT MAX(updated_at) FROM events');
        $grDate = $last('SELECT MAX(updated_at) FROM cell_groups');
        $file = fn (string $v) => date('Y-m-d', filemtime(ROOT . '/views/' . $v . '.php'));
        $urls = [
            ['home', '', max($evDate, $grDate), '1.0'],
            ['schedule', '', $evDate, '0.9'],
            ['groups', '', $grDate, '0.9'],
            ['directions', '', $file('como-chegar'), '0.8'],
            ['about', '', $file('sobre'), '0.6'],
            ['alerts', '', $file('avisos'), '0.5'],
        ];
        foreach (Content::events() as $ev) {
            $urls[] = ['event', $ev['slug'], substr($ev['updated_at'], 0, 10), '0.7'];
        }
        foreach (Content::groups() as $g) {
            $urls[] = ['groups', $g['slug'], substr($g['updated_at'], 0, 10), '0.7'];
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "
"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "
";
        foreach ($urls as [$route, $slug, $mod, $prio]) {
            $alts = '';
            foreach (I18n::LANGS as $l) {
                $alts .= '<xhtml:link rel="alternate" hreflang="' . I18n::HTML_LANG[$l] . '" href="' . e(app_url(I18n::url($route, $slug, $l))) . '"/>';
            }
            foreach (I18n::LANGS as $l) {
                $xml .= '  <url><loc>' . e(app_url(I18n::url($route, $slug, $l))) . '</loc><lastmod>' . $mod . '</lastmod><priority>' . $prio . '</priority>' . $alts . "</url>
";
            }
        }
        $xml .= '</urlset>';
        View::send($xml, 200, 'public, max-age=3600', 'application/xml; charset=utf-8');
    }

    public static function robots(): void
    {
        $txt = "# Buscadores e assistentes de IA são bem-vindos: queremos que achem nossos cultos e grupos.\n"
            . "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api/\n\n"
            . "User-agent: GPTBot\nAllow: /\nDisallow: /admin\n\n"
            . "User-agent: OAI-SearchBot\nAllow: /\nDisallow: /admin\n\n"
            . "User-agent: ClaudeBot\nAllow: /\nDisallow: /admin\n\n"
            . "User-agent: PerplexityBot\nAllow: /\nDisallow: /admin\n\n"
            . "User-agent: Google-Extended\nAllow: /\n\n"
            . 'Sitemap: ' . app_url('/sitemap.xml') . "\n";
        View::send($txt, 200, 'public, max-age=86400', 'text/plain; charset=utf-8');
    }

    /** Resumo em Markdown com os fatos principais, para ferramentas de IA (llmstxt.org). */
    public static function llms(): void
    {
        $now = new DateTimeImmutable();
        $name = setting('church_name', 'Graça Church');
        $md = "# $name Palhoça\n\n";
        $md .= '> Igreja cristã evangélica em Palhoça, Santa Catarina, Brasil. ' . truncate(setting('about_text'), 240) . "\n\n";
        $md .= "## Fatos rápidos\n\n";
        $md .= '- Endereço: ' . church_address() . "\n";
        if ($s = Content::cultoSummary()) {
            $md .= "- Cultos: $s\n";
        }
        if ($w = setting('whatsapp_1')) {
            $md .= '- WhatsApp: ' . phone_display($w) . (setting('whatsapp_2') ? ' ou ' . phone_display(setting('whatsapp_2')) : '') . "\n";
        }
        if ($ig = setting('instagram')) {
            $md .= "- Instagram: @$ig (https://www.instagram.com/$ig/)\n";
        }
        $md .= '- Site: ' . app_url('/') . "\n- Entrada gratuita e aberta a todos.\n\n";

        $md .= "## Programação\n\n";
        foreach (Content::withNext(Content::events(), $now) as $ev) {
            $md .= '- [' . $ev['title'] . '](' . app_url('/eventos/' . $ev['slug']) . '): ' . Schedule::describe($ev)
                . '. Próxima data: ' . Schedule::longDate($ev['next']['start']) . '.' . ($ev['summary'] ? ' ' . $ev['summary'] : '') . "\n";
        }
        $groups = Content::groups();
        if ($groups) {
            $md .= "\n## Grupos de convívio\n\n" . setting('groups_intro') . "\n\n";
            foreach ($groups as $g) {
                $md .= '- [' . $g['name'] . '](' . app_url('/grupos/' . $g['slug']) . '): ' . $g['neighborhood'] . ', ' . $g['city']
                    . ' · ' . Schedule::describe($g) . ' · Público: ' . $g['audience'] . "\n";
            }
        }
        $faqs = Content::faqs();
        if ($faqs) {
            $md .= "\n## Perguntas frequentes\n\n";
            foreach ($faqs as $f) {
                $md .= '### ' . $f['question'] . "\n\n" . $f['answer'] . "\n\n";
            }
        }
        $md .= "## Páginas\n\n- [Programação](" . app_url('/programacao') . ")\n- [Grupos de convívio](" . app_url('/grupos') . ")\n- [Como chegar](" . app_url('/como-chegar') . ")\n- [Quem somos](" . app_url('/sobre') . ")\n- [Receber avisos](" . app_url('/avisos') . ")\n";
        View::send($md, 200, 'public, max-age=3600', 'text/markdown; charset=utf-8');
    }
}
