<?php
declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/**
 * Calcula e descreve as datas de encontros recorrentes, no idioma atual (I18n).
 * Um item tem: recurrence (weekly | monthly | once), weekday (0 = domingo), nth (1–4, -1 = último),
 * date (Y-m-d, só para once), start_time (HH:MM[:SS]) e duration_min.
 */
final class Schedule
{
    /** Nomes em português, usados no painel. */
    public const WEEKDAYS = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    public const ICS_DAYS = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];

    private const L = [
        'pt' => [
            'days' => ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
            'plural' => ['domingos', 'segundas', 'terças', 'quartas', 'quintas', 'sextas', 'sábados'],
            'short' => ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'],
            'months' => ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'],
        ],
        'en' => [
            'days' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'plural' => ['Sundays', 'Mondays', 'Tuesdays', 'Wednesdays', 'Thursdays', 'Fridays', 'Saturdays'],
            'short' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'months' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        ],
        'es' => [
            'days' => ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
            'plural' => ['domingos', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábados'],
            'short' => ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'],
            'months' => ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],
        ],
    ];

    private static function l(string $k): array
    {
        return self::L[I18n::$lang][$k];
    }

    public static function day(int $w): string
    {
        return self::l('days')[$w];
    }

    public static function dayShort(int $w): string
    {
        return self::l('short')[$w];
    }

    public static function month(int $n): string
    {
        return self::l('months')[$n - 1];
    }

    public static function monthShort(int $n): string
    {
        $m = self::month($n);
        return I18n::$lang === 'en' ? substr($m, 0, 3) : mb_substr($m, 0, 3);
    }

    /** @return list<array{start: DateTimeImmutable, end: DateTimeImmutable}> */
    public static function occurrences(array $item, DateTimeImmutable $from, int $limit = 1, int $days = 400): array
    {
        [$h, $m] = self::hm($item['start_time'] ?? '19:00');
        $dur = max(15, (int) ($item['duration_min'] ?? 120));
        $out = [];

        if (($item['recurrence'] ?? '') === 'once') {
            if (empty($item['date'])) {
                return [];
            }
            $start = (new DateTimeImmutable($item['date']))->setTime($h, $m);
            $end = $start->modify("+$dur minutes");
            return $end > $from ? [['start' => $start, 'end' => $end]] : [];
        }

        $weekday = (int) ($item['weekday'] ?? 0);
        $nth = (int) ($item['nth'] ?? 1);
        $day = $from->setTime(0, 0);
        for ($i = 0; $i < $days && count($out) < $limit; $i++) {
            $d = $day->modify("+$i day");
            if ((int) $d->format('w') !== $weekday) {
                continue;
            }
            if (($item['recurrence'] ?? '') === 'monthly') {
                $dom = (int) $d->format('j');
                if ($nth === -1) {
                    if ($dom + 7 <= (int) $d->format('t')) {
                        continue;
                    }
                } elseif ((int) ceil($dom / 7) !== $nth) {
                    continue;
                }
            }
            $start = $d->setTime($h, $m);
            $end = $start->modify("+$dur minutes");
            if ($end <= $from) {
                continue;
            }
            $out[] = ['start' => $start, 'end' => $end];
        }
        return $out;
    }

    public static function next(array $item, ?DateTimeImmutable $from = null): ?array
    {
        return self::occurrences($item, $from ?? new DateTimeImmutable(), 1)[0] ?? null;
    }

    /** "Domingos às 19h30" · "Sundays at 7:30 PM" · "Domingos a las 19:30" */
    public static function describe(array $item): string
    {
        $time = self::time($item['start_time'] ?? '');
        $wd = (int) ($item['weekday'] ?? 0);
        $at = I18n::t('at_time', ['time' => $time]);
        return match ($item['recurrence'] ?? '') {
            'weekly' => self::ucfirst(self::l('plural')[$wd]) . ' ' . $at,
            'monthly' => I18n::t('nth_of_month', ['nth' => self::nthLabel((int) ($item['nth'] ?? 1), $wd)]) . ' ' . $at,
            'once' => empty($item['date']) ? '' : self::longDate(new DateTimeImmutable($item['date'])) . ' ' . $at,
            default => '',
        };
    }

    public static function nthLabel(int $nth, int $weekday): string
    {
        $lang = I18n::$lang;
        if ($lang === 'en') {
            $words = [1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', -1 => 'Last'];
            return $words[$nth] . ' ' . self::day($weekday);
        }
        if ($lang === 'es') {
            $words = [1 => 'Primer', 2 => 'Segundo', 3 => 'Tercer', 4 => 'Cuarto', -1 => 'Último'];
            return $words[$nth] . ' ' . mb_strtolower(self::day($weekday));
        }
        $name = mb_strtolower(self::day($weekday));
        $fem = $weekday >= 1 && $weekday <= 5;
        if ($nth === -1) {
            return ($fem ? 'Última ' : 'Último ') . $name;
        }
        return $nth . ($fem ? 'ª ' : 'º ') . $name;
    }

    /** "19h30" · "7:30 PM" · "19:30" */
    public static function time(string|DateTimeImmutable $t): string
    {
        if ($t instanceof DateTimeImmutable) {
            $h = (int) $t->format('G');
            $m = (int) $t->format('i');
        } else {
            [$h, $m] = self::hm($t);
        }
        return match (I18n::$lang) {
            'en' => (($h % 12) ?: 12) . ($m > 0 ? sprintf(':%02d', $m) : '') . ($h < 12 ? ' AM' : ' PM'),
            'es' => sprintf('%d:%02d', $h, $m),
            default => $h . 'h' . ($m > 0 ? sprintf('%02d', $m) : ''),
        };
    }

    /** "Domingo, 26 de outubro" · "Sunday, October 26" */
    public static function longDate(DateTimeImmutable $d): string
    {
        $day = self::day((int) $d->format('w'));
        $month = self::month((int) $d->format('n'));
        return I18n::$lang === 'en'
            ? "$day, $month " . $d->format('j')
            : "$day, " . $d->format('j') . " de $month";
    }

    /** "26 de out." · "Oct 26" · "26 de oct." */
    public static function shortDate(DateTimeImmutable $d): string
    {
        $m = self::monthShort((int) $d->format('n'));
        return I18n::$lang === 'en' ? "$m " . $d->format('j') : $d->format('j') . " de $m.";
    }

    /** "Hoje às 19h30", "Amanhã às 20h", "Sábado às 9h" (até 6 dias), depois a data completa. */
    public static function relative(DateTimeImmutable $start, ?DateTimeImmutable $now = null): string
    {
        $now ??= new DateTimeImmutable();
        $days = (int) $now->setTime(0, 0)->diff($start->setTime(0, 0))->format('%r%a');
        $at = I18n::t('at_time', ['time' => self::time($start)]);
        return match (true) {
            $days === 0 => I18n::t('today') . ' ' . $at,
            $days === 1 => I18n::t('tomorrow') . ' ' . $at,
            $days > 1 && $days < 7 => self::day((int) $start->format('w')) . ' ' . $at,
            default => self::longDate($start) . ' ' . $at,
        };
    }

    public static function dayLabel(DateTimeImmutable $d, ?DateTimeImmutable $now = null): string
    {
        $now ??= new DateTimeImmutable();
        $days = (int) $now->setTime(0, 0)->diff($d->setTime(0, 0))->format('%r%a');
        return match ($days) {
            0 => I18n::t('today'),
            1 => I18n::t('tomorrow'),
            default => self::longDate($d),
        };
    }

    public static function rrule(array $item): ?string
    {
        $day = self::ICS_DAYS[(int) ($item['weekday'] ?? 0)];
        return match ($item['recurrence'] ?? '') {
            'weekly' => "FREQ=WEEKLY;BYDAY=$day",
            'monthly' => 'FREQ=MONTHLY;BYDAY=' . (int) ($item['nth'] ?? 1) . $day,
            default => null,
        };
    }

    public static function ucfirst(string $s): string
    {
        return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }

    /** @return array{0:int,1:int} */
    private static function hm(string $t): array
    {
        $p = explode(':', $t . ':0');
        return [max(0, min(23, (int) $p[0])), max(0, min(59, (int) $p[1]))];
    }
}
