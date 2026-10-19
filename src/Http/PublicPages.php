<?php
declare(strict_types=1);

namespace App\Http;

use App\Content;
use App\I18n;
use App\Schedule;
use App\Seo;
use App\View;
use DateTimeImmutable;

final class PublicPages
{
    private static function name(): string
    {
        return setting('church_name', 'Graça Church');
    }

    private static function services(): string
    {
        $s = Content::cultoSummary();
        return $s ? t('meta_services', ['summary' => $s]) : '';
    }

    private static function crumbs(array $items): array
    {
        return Seo::breadcrumb([t('crumbs_home') => I18n::url()] + $items);
    }

    public static function home(): void
    {
        $now = new DateTimeImmutable();
        $events = Content::withNext(Content::events(), $now);
        $groups = Content::groups();
        $faqs = Content::faqs();
        $next = Content::nextCulto($now);
        $week = Content::week($now);
        $summary = Content::cultoSummary();

        $jsonld = [Seo::website(), Seo::church(), Seo::faq($faqs)];
        foreach (array_slice($events, 0, 6) as $ev) {
            $jsonld[] = Seo::event($ev, $ev['next']);
        }
        View::page('home', compact('now', 'events', 'groups', 'faqs', 'next', 'summary', 'week'), [
            'title' => t('meta_home_title', ['name' => self::name()]),
            'description' => t('meta_home_desc', ['name' => self::name(), 'services' => self::services(), 'address' => church_address()]),
            'route' => 'home',
            'nav' => 'home',
            'jsonld' => $jsonld,
            'preload_hero' => true,
        ]);
    }

    public static function programacao(): void
    {
        $now = new DateTimeImmutable();
        $events = Content::withNext(Content::events(), $now);
        $agenda = Content::agenda(35, $now);
        $byCat = [];
        foreach ($events as $ev) {
            $byCat[$ev['category']][] = $ev;
        }
        $jsonld = [self::crumbs([t('nav_schedule') => I18n::url('schedule')])];
        foreach ($events as $ev) {
            $jsonld[] = Seo::event($ev, $ev['next']);
        }
        $summary = Content::cultoSummary();
        View::page('programacao', compact('now', 'events', 'agenda', 'byCat', 'summary'), [
            'title' => t('meta_schedule_title', ['name' => self::name()]),
            'description' => t('meta_schedule_desc', ['name' => self::name(), 'services' => self::services()]),
            'route' => 'schedule',
            'nav' => 'agenda',
            'jsonld' => $jsonld,
        ]);
    }

    public static function evento(string $slug): void
    {
        $ev = Content::event($slug) ?? View::notFound();
        $now = new DateTimeImmutable();
        $dates = Schedule::occurrences($ev, $now, 6);
        $jsonld = [self::crumbs([t('nav_schedule') => I18n::url('schedule'), $ev['title'] => I18n::url('event', $ev['slug'])])];
        if ($dates) {
            $jsonld[] = Seo::event($ev, $dates[0]);
        }
        View::page('evento', compact('ev', 'dates', 'now'), [
            'title' => t('meta_event_title', ['title' => $ev['title'], 'name' => self::name()]),
            'description' => t('meta_event_desc', ['title' => $ev['title'], 'when' => Schedule::describe($ev), 'summary' => $ev['summary']]),
            'route' => 'event',
            'slug' => $ev['slug'],
            'nav' => 'agenda',
            'jsonld' => $jsonld,
            'robots' => $dates ? null : 'noindex',
        ]);
    }

    /** Arquivo de calendário (.ics) para adicionar o encontro na agenda do celular. */
    public static function eventoIcs(string $slug): void
    {
        $ev = Content::event($slug) ?? View::notFound();
        $first = Schedule::next($ev) ?? View::notFound();
        $esc = fn (string $s) => addcslashes(preg_replace('/\s+/', ' ', $s) ?? '', ",;\\");
        $loc = $ev['location_name'] ? $ev['location_name'] . ' – ' . ($ev['address'] ?: church_address()) : self::name() . ' – ' . church_address();
        $tz = date_default_timezone_get();
        $offset = str_replace(':', '', $first['start']->format('P'));
        $url = app_url(I18n::url('event', $ev['slug']));
        $lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Graca Church//Programacao//PT', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'BEGIN:VTIMEZONE', "TZID:$tz", 'BEGIN:STANDARD', 'DTSTART:19700101T000000',
            "TZOFFSETFROM:$offset", "TZOFFSETTO:$offset", 'TZNAME:' . $first['start']->format('T'), 'END:STANDARD', 'END:VTIMEZONE',
            'BEGIN:VEVENT',
            'UID:' . $ev['slug'] . '@' . (parse_url(app_url(), PHP_URL_HOST) ?: 'gracachurch'),
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            "DTSTART;TZID=$tz:" . $first['start']->format('Ymd\THis'),
            "DTEND;TZID=$tz:" . $first['end']->format('Ymd\THis'),
        ];
        if ($rule = Schedule::rrule($ev)) {
            $lines[] = 'RRULE:' . $rule;
        }
        array_push(
            $lines,
            'SUMMARY:' . $esc($ev['title'] . ' · ' . self::name()),
            'DESCRIPTION:' . $esc(($ev['summary'] ?: '') . ' ' . $url),
            'LOCATION:' . $esc($loc),
            'URL:' . $url,
            'BEGIN:VALARM', 'TRIGGER:-PT1H', 'ACTION:DISPLAY', 'DESCRIPTION:' . $esc($ev['title']), 'END:VALARM',
            'END:VEVENT', 'END:VCALENDAR'
        );
        header('Content-Disposition: attachment; filename="' . $ev['slug'] . '.ics"');
        View::send(implode("\r\n", $lines) . "\r\n", 200, 'public, max-age=3600', 'text/calendar; charset=utf-8');
    }

    public static function grupos(): void
    {
        $now = new DateTimeImmutable();
        $groups = Content::groups();
        foreach ($groups as &$g) {
            $g['next'] = Schedule::next($g, $now);
        }
        unset($g);
        $jsonld = [self::crumbs([t('nav_groups') => I18n::url('groups')])];
        foreach (array_slice($groups, 0, 40) as $g) {
            if ($g['next']) {
                $jsonld[] = Seo::groupEvent($g, $g['next']);
            }
        }
        $hoods = array_values(array_unique(array_filter(array_column($groups, 'neighborhood'))));
        View::page('grupos', compact('groups', 'now'), [
            'title' => t('meta_groups_title', ['name' => self::name()]),
            'description' => t('meta_groups_desc', ['name' => self::name(), 'hoods' => $hoods ? ': ' . implode(', ', array_slice($hoods, 0, 6)) : '']),
            'route' => 'groups',
            'nav' => 'grupos',
            'jsonld' => $jsonld,
        ]);
    }

    public static function grupo(string $slug): void
    {
        $g = Content::group($slug) ?? View::notFound();
        $now = new DateTimeImmutable();
        $dates = Schedule::occurrences($g, $now, 4);
        $jsonld = [self::crumbs([t('nav_groups') => I18n::url('groups'), $g['name'] => I18n::url('groups', $g['slug'])])];
        if ($dates) {
            $jsonld[] = Seo::groupEvent($g, $dates[0]);
        }
        View::page('grupo', compact('g', 'dates', 'now'), [
            'title' => t('meta_group_title', ['group' => $g['name'], 'place' => $g['neighborhood'] ?: $g['city']]),
            'description' => t('meta_group_desc', ['name' => self::name(), 'hood' => $g['neighborhood'], 'city' => $g['city'], 'when' => Schedule::describe($g)]),
            'route' => 'groups',
            'slug' => $g['slug'],
            'nav' => 'grupos',
            'jsonld' => $jsonld,
        ]);
    }

    public static function comoChegar(): void
    {
        $summary = Content::cultoSummary();
        View::page('como-chegar', compact('summary'), [
            'title' => t('meta_directions_title', ['name' => self::name(), 'address' => church_address()]),
            'description' => t('meta_directions_desc', ['name' => self::name(), 'address' => church_address(), 'services' => $summary ? ' ' . t('meta_services', ['summary' => $summary]) : '']),
            'route' => 'directions',
            'nav' => 'chegar',
            'jsonld' => [Seo::church(), self::crumbs([t('nav_directions') => I18n::url('directions')])],
        ]);
    }

    public static function sobre(): void
    {
        $faqs = Content::faqs();
        View::page('sobre', compact('faqs'), [
            'title' => t('meta_about_title', ['name' => self::name()]),
            'description' => truncate(setting('about_text'), 155),
            'route' => 'about',
            'nav' => 'sobre',
            'jsonld' => [Seo::church(), Seo::faq($faqs), self::crumbs([t('nav_about') => I18n::url('about')])],
        ]);
    }

    public static function avisos(): void
    {
        View::page('avisos', [], [
            'title' => t('meta_alerts_title', ['name' => self::name()]),
            'description' => t('meta_alerts_desc', ['name' => self::name()]),
            'route' => 'alerts',
            'nav' => 'avisos',
            'jsonld' => [self::crumbs([t('nav_alerts') => I18n::url('alerts')])],
        ]);
    }

    public static function offline(): void
    {
        View::page('offline', [], [
            'title' => t('meta_offline'),
            'description' => t('offline_lead'),
            'route' => 'offline',
            'robots' => 'noindex',
        ]);
    }
}
