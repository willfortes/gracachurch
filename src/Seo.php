<?php
declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/** Dados estruturados (schema.org) para Google, buscadores por IA e assistentes de voz. */
final class Seo
{
    public static function churchId(): string
    {
        return app_url('/#igreja');
    }

    public static function church(bool $full = true): array
    {
        $data = [
            '@type' => 'Church',
            '@id' => self::churchId(),
            'name' => setting('church_name', 'Graça Church') . ' Palhoça',
            'alternateName' => ['Graça Church', 'Igreja Graça Church Palhoça'],
            'url' => app_url('/'),
            'logo' => app_url('/icons/icon-512.png'),
            'image' => app_url('/img/og.jpg'),
            'address' => self::address(),
        ];
        if (!$full) {
            return $data;
        }
        $geo = Content::churchGeo();
        if ($geo['lat'] !== '' && $geo['lng'] !== '') {
            $data['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $geo['lat'], 'longitude' => (float) $geo['lng']];
            $data['hasMap'] = 'https://www.google.com/maps/search/?api=1&query=' . $geo['lat'] . ',' . $geo['lng'];
        }
        if ($wa = setting('whatsapp_1')) {
            $data['telephone'] = '+' . phone_digits($wa);
        }
        if ($email = setting('email')) {
            $data['email'] = $email;
        }
        if ($ig = setting('instagram')) {
            $data['sameAs'] = ['https://www.instagram.com/' . $ig . '/'];
        }
        $data['description'] = truncate(setting('about_text'), 300);
        $hours = [];
        foreach (Content::events() as $ev) {
            if ($ev['category'] === 'culto' && $ev['recurrence'] === 'weekly') {
                $start = new DateTimeImmutable('today ' . $ev['start_time']);
                $hours[] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => 'https://schema.org/' . ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][(int) $ev['weekday']],
                    'opens' => $start->format('H:i'),
                    'closes' => $start->modify('+' . (int) $ev['duration_min'] . ' minutes')->format('H:i'),
                ];
            }
        }
        if ($hours) {
            $data['openingHoursSpecification'] = $hours;
        }
        return $data;
    }

    public static function address(?string $street = null, ?string $city = null): array
    {
        return [
            '@type' => 'PostalAddress',
            'streetAddress' => $street ?? setting('address_street', 'BR 282, KM 18'),
            'addressLocality' => $city ?? setting('address_city', 'Palhoça'),
            'addressRegion' => setting('address_state', 'SC'),
            'postalCode' => $street === null ? setting('address_zip', '88135-550') : null,
            'addressCountry' => 'BR',
        ];
    }

    public static function event(array $ev, array $occ): array
    {
        $loc = $ev['location_name']
            ? ['@type' => 'Place', 'name' => $ev['location_name'], 'address' => $ev['address'] ?: church_address()]
            : ['@type' => 'Place', 'name' => setting('church_name', 'Graça Church'), 'address' => self::address()];
        $data = [
            '@type' => 'Event',
            'name' => $ev['title'] . ' · ' . setting('church_name', 'Graça Church'),
            'description' => $ev['summary'] ?: truncate((string) $ev['description'], 200),
            'startDate' => $occ['start']->format(DATE_ATOM),
            'endDate' => $occ['end']->format(DATE_ATOM),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'isAccessibleForFree' => true,
            'location' => $loc,
            'image' => [app_url('/img/og.jpg')],
            'organizer' => ['@type' => 'Organization', 'name' => setting('church_name', 'Graça Church'), 'url' => app_url('/')],
            'url' => app_url(I18n::url('event', $ev['slug'])),
            'inLanguage' => I18n::htmlLang(),
        ];
        if ($ev['recurrence'] !== 'once') {
            $data['eventSchedule'] = self::schedule($ev);
        }
        return $data;
    }

    public static function groupEvent(array $g, array $occ): array
    {
        $place = ['@type' => 'Place', 'name' => $g['name'], 'address' => self::address($g['show_address'] ? (string) $g['address'] : $g['neighborhood'], $g['city'])];
        if ($g['lat'] !== null) {
            $place['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => round((float) $g['lat'], 3), 'longitude' => round((float) $g['lng'], 3)];
        }
        return [
            '@type' => 'Event',
            'name' => $g['name'] . ' · ' . t('jsonld_group_suffix') . ' ' . setting('church_name', 'Graça Church'),
            'description' => $g['description'] ?: t('meta_group_default_desc', ['hood' => $g['neighborhood']]),
            'startDate' => $occ['start']->format(DATE_ATOM),
            'endDate' => $occ['end']->format(DATE_ATOM),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'isAccessibleForFree' => true,
            'location' => $place,
            'image' => [app_url('/img/og.jpg')],
            'organizer' => ['@type' => 'Organization', 'name' => setting('church_name', 'Graça Church'), 'url' => app_url('/')],
            'eventSchedule' => self::schedule($g),
            'url' => app_url(I18n::url('groups', $g['slug'])),
            'inLanguage' => I18n::htmlLang(),
        ];
    }

    private static function schedule(array $item): array
    {
        $s = [
            '@type' => 'Schedule',
            'byDay' => 'https://schema.org/' . ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][(int) $item['weekday']],
            'startTime' => substr((string) $item['start_time'], 0, 5),
            'scheduleTimezone' => date_default_timezone_get(),
            'repeatFrequency' => $item['recurrence'] === 'monthly' ? 'P1M' : 'P1W',
        ];
        if ($item['recurrence'] === 'monthly') {
            $s['byMonthWeek'] = (int) $item['nth'];
        }
        return $s;
    }

    /** Negócio de membro: LocalBusiness com contato, região atendida e redes. */
    public static function business(array $b): array
    {
        $data = [
            '@type' => 'LocalBusiness',
            '@id' => app_url(I18n::url('business', $b['slug'])) . '#negocio',
            'name' => $b['name'],
            'description' => $b['description'],
            'url' => app_url(I18n::url('business', $b['slug'])),
            'telephone' => '+' . phone_digits($b['whatsapp']),
            'address' => self::address($b['show_address'] && $b['address'] ? $b['address'] : ($b['neighborhood'] ?: null), $b['city']),
            'areaServed' => $b['service_area'] ?: $b['city'] . ' – ' . setting('address_state', 'SC'),
            'knowsAbout' => array_values(array_filter(array_map('trim', explode(',', (string) $b['tags'])))),
            'inLanguage' => I18n::htmlLang(),
            'isPartOf' => ['@type' => 'WebSite', 'name' => t('nav_business') . ' · ' . setting('church_name', 'Graça Church'), 'url' => app_url(I18n::url('business'))],
        ];
        if ($b['logo']) {
            $data['image'] = app_url('/uploads/negocios/' . $b['logo']);
            $data['logo'] = $data['image'];
        }
        if ($b['lat'] !== null && $b['show_address']) {
            $data['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $b['lat'], 'longitude' => (float) $b['lng']];
        }
        $same = [];
        if ($b['instagram']) {
            $same[] = 'https://www.instagram.com/' . $b['instagram'] . '/';
        }
        if ($b['website']) {
            $same[] = $b['website'];
        }
        if ($same) {
            $data['sameAs'] = $same;
        }
        if ($b['email']) {
            $data['email'] = $b['email'];
        }
        if ($b['hours']) {
            $data['openingHours'] = $b['hours'];
        }
        return $data;
    }

    public static function faq(array $faqs): ?array
    {
        if (!$faqs) {
            return null;
        }
        return [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
            ], $faqs),
        ];
    }

    public static function breadcrumb(array $items): array
    {
        $list = [];
        $i = 1;
        foreach ($items as $name => $path) {
            $list[] = ['@type' => 'ListItem', 'position' => $i++, 'name' => $name, 'item' => app_url($path)];
        }
        return ['@type' => 'BreadcrumbList', 'itemListElement' => $list];
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => app_url('/#site'),
            'url' => app_url(I18n::url()),
            'name' => setting('church_name', 'Graça Church') . ' Palhoça',
            'inLanguage' => I18n::htmlLang(),
            'publisher' => ['@id' => self::churchId()],
        ];
    }

    /** Junta vários blocos num único JSON-LD com @graph, sem campos vazios. */
    public static function graph(array $nodes): string
    {
        $clean = function ($v) use (&$clean) {
            if (is_array($v)) {
                $v = array_map($clean, $v);
                return array_filter($v, fn ($x) => $x !== null && $x !== '' && $x !== []);
            }
            return $v;
        };
        $nodes = array_values(array_filter($nodes));
        $json = json_encode(['@context' => 'https://schema.org', '@graph' => $clean($nodes)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return str_replace('</', '<\/', (string) $json);
    }
}
