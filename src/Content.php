<?php
declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/** Consultas de conteúdo público, já traduzido para o idioma atual. */
final class Content
{
    public const CATEGORIES = ['culto' => 'Cultos', 'ministerio' => 'Ministérios', 'evento' => 'Eventos especiais'];

    /** Campos traduzíveis por tipo de conteúdo (usados também no painel). */
    public const TRANSLATABLE = [
        'event' => ['title', 'summary', 'description', 'location_name'],
        'group' => ['audience', 'description'],
        'faq' => ['question', 'answer'],
        'setting' => ['church_city_label', 'hero_title', 'hero_text', 'about_title', 'about_text', 'groups_intro', 'directions_note'],
    ];

    private static array $tr = [];

    /** @return array<int, array<string, string>> ref_id => field => valor */
    public static function translations(string $entity, ?string $lang = null): array
    {
        $lang ??= I18n::$lang;
        if ($lang === 'pt') {
            return [];
        }
        if (!isset(self::$tr[$entity][$lang])) {
            $map = [];
            foreach (Db::all('SELECT ref_id, field, value FROM translations WHERE entity = ? AND lang = ?', [$entity, $lang]) as $r) {
                $map[(int) $r['ref_id']][$r['field']] = $r['value'];
            }
            self::$tr[$entity][$lang] = $map;
        }
        return self::$tr[$entity][$lang];
    }

    public static function localize(string $entity, array $row): array
    {
        foreach (self::translations($entity)[(int) ($row['id'] ?? 0)] ?? [] as $field => $value) {
            if (trim($value) !== '') {
                $row[$field] = $value;
            }
        }
        return $row;
    }

    public static function localizeAll(string $entity, array $rows): array
    {
        return array_map(fn ($r) => self::localize($entity, $r), $rows);
    }

    public static function categoryLabel(string $cat): string
    {
        return I18n::t('cat_' . $cat);
    }

    public static function events(): array
    {
        static $cache = [];
        return $cache[I18n::$lang] ??= self::localizeAll('event', Db::all(
            "SELECT * FROM events WHERE published = 1
             AND (recurrence <> 'once' OR date >= CURDATE() - INTERVAL 1 DAY)
             ORDER BY sort, title"
        ));
    }

    public static function event(string $slug): ?array
    {
        $row = Db::one('SELECT * FROM events WHERE slug = ? AND published = 1', [$slug]);
        return $row ? self::localize('event', $row) : null;
    }

    public static function groups(): array
    {
        static $cache = [];
        return $cache[I18n::$lang] ??= self::localizeAll('group', Db::all('SELECT * FROM cell_groups WHERE published = 1 ORDER BY city, neighborhood, name'));
    }

    public static function group(string $slug): ?array
    {
        $row = Db::one('SELECT * FROM cell_groups WHERE slug = ? AND published = 1', [$slug]);
        return $row ? self::localize('group', $row) : null;
    }

    public static function faqs(): array
    {
        return self::localizeAll('faq', Db::all('SELECT * FROM faqs WHERE published = 1 ORDER BY sort, id'));
    }

    /** Eventos com a próxima data calculada, ordenados pela data. */
    public static function withNext(array $events, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $out = [];
        foreach ($events as $ev) {
            $ev['next'] = Schedule::next($ev, $now);
            if ($ev['next'] !== null) {
                $out[] = $ev;
            }
        }
        usort($out, fn ($a, $b) => $a['next']['start'] <=> $b['next']['start']);
        return $out;
    }

    public static function nextCulto(?DateTimeImmutable $now = null): ?array
    {
        $cultos = array_filter(self::events(), fn ($e) => $e['category'] === 'culto');
        return self::withNext($cultos, $now)[0] ?? null;
    }

    /** Agenda dos próximos N dias, agrupada por dia. @return array<string, list<array>> */
    public static function agenda(int $days = 35, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $limit = $now->modify("+$days days");
        $items = [];
        foreach (self::events() as $ev) {
            foreach (Schedule::occurrences($ev, $now, 12, $days + 1) as $occ) {
                if ($occ['start'] > $limit) {
                    break;
                }
                $items[] = ['event' => $ev] + $occ;
            }
        }
        usort($items, fn ($a, $b) => $a['start'] <=> $b['start']);
        $byDay = [];
        foreach ($items as $it) {
            $byDay[$it['start']->format('Y-m-d')][] = $it;
        }
        return $byDay;
    }

    /** Os 7 dias a partir de hoje, cada um com seus encontros (para a "semana" da página inicial). */
    public static function week(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $now->setTime(0, 0)->modify("+$i day");
            $days[$d->format('Y-m-d')] = ['date' => $d, 'items' => []];
        }
        foreach (self::agenda(7, $now) as $key => $items) {
            if (isset($days[$key])) {
                $days[$key]['items'] = $items;
            }
        }
        return $days;
    }

    /** "Domingos às 19h30 e quartas às 20h", no idioma atual. */
    public static function cultoSummary(): string
    {
        $parts = [];
        foreach (self::events() as $ev) {
            if ($ev['category'] === 'culto' && $ev['recurrence'] === 'weekly') {
                $parts[] = mb_strtolower(Schedule::describe($ev));
            }
        }
        if (!$parts) {
            return '';
        }
        $last = array_pop($parts);
        $text = $parts ? implode(', ', $parts) . ' ' . I18n::t('and') . ' ' . $last : $last;
        if (I18n::$lang === 'en') {
            // dias da semana e AM/PM em inglês ficam com maiúscula
            $text = preg_replace_callback('/\b(sundays|mondays|tuesdays|wednesdays|thursdays|fridays|saturdays|am|pm)\b/', fn ($m) => in_array($m[1], ['am', 'pm'], true) ? strtoupper($m[1]) : ucfirst($m[1]), $text) ?? $text;
        }
        return Schedule::ucfirst($text);
    }

    public static function churchGeo(): array
    {
        return ['lat' => setting('lat'), 'lng' => setting('lng')];
    }

    /**
     * Coordenadas públicas de um grupo. Grupos que se reúnem em casas sem endereço público
     * aparecem com precisão de ~100 m, para não expor a casa exata de ninguém.
     * @return array{0: float, 1: float}
     */
    public static function publicCoords(array $g): array
    {
        $digits = $g['show_address'] ? 6 : 3;
        return [round((float) $g['lat'], $digits), round((float) $g['lng'], $digits)];
    }

    /** Dados do mapa (igreja + grupos) para o JavaScript. */
    public static function mapData(array $groups, bool $withChurch = true): array
    {
        $points = [];
        $geo = self::churchGeo();
        if ($withChurch && $geo['lat'] !== '' && $geo['lng'] !== '') {
            $points[] = [
                'lat' => (float) $geo['lat'], 'lng' => (float) $geo['lng'], 'type' => 'church',
                'title' => setting('church_name', 'Graça Church'), 'text' => church_address(), 'url' => I18n::url('directions'),
            ];
        }
        foreach ($groups as $g) {
            if ($g['lat'] === null || $g['lng'] === null) {
                continue;
            }
            [$lat, $lng] = self::publicCoords($g);
            $points[] = [
                'lat' => $lat, 'lng' => $lng, 'type' => 'group', 'id' => (int) $g['id'],
                'title' => $g['name'], 'text' => Schedule::describe($g) . ' · ' . $g['neighborhood'], 'url' => I18n::url('groups', $g['slug']),
                'aud' => $g['audience'],
                'wa' => $g['leader_phone'] ? whatsapp_link($g['leader_phone'], t('wa_join', ['group' => $g['name']])) : null,
            ];
        }
        return $points;
    }
}
