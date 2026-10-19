<?php
declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/** Cria avisos, agenda lembretes automáticos e envia a fila de push. */
final class Notifier
{
    public const AUDIENCES = [
        'all' => 'Todos os inscritos',
        'cultos' => 'Quem quer avisos dos cultos',
        'ministerios' => 'Quem quer avisos dos ministérios',
        'eventos' => 'Quem quer avisos de eventos especiais',
    ];

    public const CATEGORY_TOPIC = ['culto' => 'cultos', 'ministerio' => 'ministerios', 'evento' => 'eventos'];

    /** Cria o aviso e coloca na fila todos os inscritos do público. Retorna o id, ou null se já existia (ref_key). */
    public static function create(array $n): ?int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            if (!empty($n['ref_key']) && Db::value('SELECT id FROM notifications WHERE ref_key = ?', [$n['ref_key']])) {
                $pdo->rollBack();
                return null;
            }
            $id = Db::insert('notifications', [
                'title' => truncate($n['title'], 120),
                'body' => truncate($n['body'], 300),
                'i18n' => !empty($n['i18n']) ? json_encode($n['i18n'], JSON_UNESCAPED_UNICODE) : null,
                'url' => $n['url'] ?? '/',
                'audience' => $n['audience'] ?? 'all',
                'source' => $n['source'] ?? 'manual',
                'ref_key' => $n['ref_key'] ?? null,
                'ttl' => (int) ($n['ttl'] ?? 86400),
                'created_by' => $n['created_by'] ?? null,
            ]);
            [$where, $params] = self::audienceWhere($n['audience'] ?? 'all');
            $total = Db::run(
                "INSERT IGNORE INTO push_outbox (notification_id, subscription_id) SELECT ?, id FROM push_subscriptions WHERE $where",
                [$id, ...$params]
            );
            Db::run("UPDATE notifications SET total = ?, status = ? WHERE id = ?", [$total, $total ? 'sending' : 'done', $id]);
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function countAudience(string $audience): int
    {
        [$where, $params] = self::audienceWhere($audience);
        return (int) Db::value("SELECT COUNT(*) FROM push_subscriptions WHERE $where", $params);
    }

    /** @return array{0:string,1:array} */
    private static function audienceWhere(string $audience): array
    {
        if ($audience === 'all') {
            return ['1=1', []];
        }
        return ['JSON_CONTAINS(topics, JSON_QUOTE(?))', [$audience]];
    }

    /**
     * Lembretes automáticos: para cada encontro com aviso ligado, quando chega a hora do lembrete
     * (início menos reminder_min) e o encontro ainda não começou, cria um aviso único.
     */
    public static function scheduleReminders(?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable();
        $created = 0;

        foreach (Db::all('SELECT * FROM events WHERE published = 1 AND notify = 1') as $ev) {
            $created += self::remind($ev, $now, 'event', self::CATEGORY_TOPIC[$ev['category']] ?? 'cultos', '/eventos/' . $ev['slug']);
        }
        foreach (Db::all('SELECT * FROM cell_groups WHERE published = 1 AND notify = 1') as $g) {
            $created += self::remind($g, $now, 'group', 'grupo:' . $g['id'], '/grupos/' . $g['slug']);
        }
        return $created;
    }

    private static function remind(array $item, DateTimeImmutable $now, string $kind, string $audience, string $url): int
    {
        $reminder = max(5, (int) $item['reminder_min']);
        foreach (Schedule::occurrences($item, $now, 3, 40) as $occ) {
            $start = $occ['start'];
            if ($start <= $now) {
                continue; // já começou: não avisa mais
            }
            if ($start->modify("-$reminder minutes") > $now) {
                break; // ainda não é hora (as próximas datas são mais tarde)
            }
            // Monta o texto em cada idioma; cada pessoa recebe no idioma em que se inscreveu
            $texts = [];
            foreach (I18n::LANGS as $lang) {
                $texts[$lang] = I18n::with($lang, function () use ($item, $kind, $start, $now) {
                    $it = Content::localize($kind, $item);
                    $title = ($kind === 'group' ? $it['name'] : $it['title']) . ' · ' . Schedule::relative($start, $now);
                    $body = $kind === 'group'
                        ? t('notif_group_body', ['until' => self::untilText($start, $now)])
                        : t('notif_event_body', ['until' => self::untilText($start, $now), 'place' => ($it['location_name'] ?? '') ?: setting('church_name', 'Graça Church')]);
                    return ['title' => $title, 'body' => $body];
                });
            }
            $id = self::create([
                'title' => $texts['pt']['title'],
                'body' => $texts['pt']['body'],
                'i18n' => ['en' => $texts['en'], 'es' => $texts['es']],
                'url' => $url . '?utm_source=push',
                'audience' => $audience,
                'source' => 'auto',
                'ref_key' => $kind . ':' . $item['id'] . ':' . $start->format('Y-m-d\TH:i'),
                'ttl' => max(300, $start->getTimestamp() - $now->getTimestamp()),
            ]);
            return $id ? 1 : 0;
        }
        return 0;
    }

    /** "em 2 h", "em 30 min" (no idioma atual) */
    public static function untilText(DateTimeImmutable $start, DateTimeImmutable $now): string
    {
        $min = (int) round(($start->getTimestamp() - $now->getTimestamp()) / 60);
        if ($min < 60) {
            return t('until_min', ['n' => max(1, $min)]);
        }
        $h = intdiv($min, 60);
        $m = $min % 60;
        if ($h >= 24) {
            $r = Schedule::relative($start, $now);
            return mb_strtolower(mb_substr($r, 0, 1)) . mb_substr($r, 1);
        }
        return $m >= 10 ? t('until_hm', ['h' => $h, 'm' => $m]) : t('until_h', ['h' => $h]);
    }

    /** Envia até $limit mensagens da fila. Retorna [enviadas, falhas]. */
    public static function drain(int $limit = 500): array
    {
        $push = WebPush::fromEnv();
        if ($push === null) {
            return [0, 0];
        }
        $rows = Db::all(
            "SELECT o.id, o.attempts, o.notification_id, s.id AS sub_id, s.endpoint, s.p256dh, s.auth, s.lang,
                    n.title, n.body, n.i18n, n.url, n.ttl, n.source, n.created_at
             FROM push_outbox o
             JOIN push_subscriptions s ON s.id = o.subscription_id
             JOIN notifications n ON n.id = o.notification_id
             WHERE o.next_attempt_at <= NOW()
             ORDER BY o.id LIMIT " . max(1, $limit)
        );
        if (!$rows) {
            self::closeFinished();
            return [0, 0];
        }

        $messages = [];
        $byId = [];
        foreach ($rows as $r) {
            $expired = (time() - strtotime($r['created_at'])) > (int) $r['ttl'];
            if ($expired) {
                Db::run('DELETE FROM push_outbox WHERE id = ?', [$r['id']]);
                Db::run('UPDATE notifications SET failed = failed + 1 WHERE id = ?', [$r['notification_id']]);
                continue;
            }
            $byId[(int) $r['id']] = $r;
            $lang = in_array($r['lang'], I18n::LANGS, true) ? $r['lang'] : 'pt';
            $tr = $r['i18n'] ? (json_decode($r['i18n'], true)[$lang] ?? []) : [];
            $messages[] = [
                'id' => (int) $r['id'],
                'endpoint' => $r['endpoint'],
                'p256dh' => $r['p256dh'],
                'auth' => $r['auth'],
                'payload' => json_encode([
                    'title' => ($tr['title'] ?? '') ?: $r['title'],
                    'body' => ($tr['body'] ?? '') ?: $r['body'],
                    'url' => I18n::translatePath($r['url'], $lang),
                    'tag' => 'n' . $r['notification_id'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ttl' => max(60, (int) $r['ttl'] - (time() - strtotime($r['created_at']))),
                'urgency' => $r['source'] === 'auto' ? 'high' : 'normal',
            ];
        }

        $results = $push->sendMany($messages);
        $sent = $failed = 0;
        foreach ($results as $outboxId => $status) {
            $r = $byId[$outboxId];
            if ($status >= 200 && $status < 300) {
                $sent++;
                Db::run('DELETE FROM push_outbox WHERE id = ?', [$outboxId]);
                Db::run('UPDATE notifications SET sent = sent + 1 WHERE id = ?', [$r['notification_id']]);
                Db::run('UPDATE push_subscriptions SET last_sent_at = NOW(), failures = 0 WHERE id = ?', [$r['sub_id']]);
            } elseif (in_array($status, [400, 403, 404, 410], true)) {
                // Inscrição cancelada ou inválida: remove o navegador (a fila sai junto pela FK)
                $failed++;
                Db::run('UPDATE notifications SET failed = failed + 1 WHERE id = ?', [$r['notification_id']]);
                Db::run('DELETE FROM push_subscriptions WHERE id = ?', [$r['sub_id']]);
            } elseif ((int) $r['attempts'] >= 3) {
                $failed++;
                Db::run('DELETE FROM push_outbox WHERE id = ?', [$outboxId]);
                Db::run('UPDATE notifications SET failed = failed + 1 WHERE id = ?', [$r['notification_id']]);
                Db::run('UPDATE push_subscriptions SET failures = LEAST(failures + 1, 255) WHERE id = ?', [$r['sub_id']]);
            } else {
                // 429, 5xx ou rede: tenta de novo mais tarde
                $wait = 2 ** ((int) $r['attempts']) * 2;
                Db::run(
                    "UPDATE push_outbox SET attempts = attempts + 1, next_attempt_at = DATE_ADD(NOW(), INTERVAL $wait MINUTE) WHERE id = ?",
                    [$outboxId]
                );
            }
        }
        self::closeFinished();
        return [$sent, $failed];
    }

    private static function closeFinished(): void
    {
        Db::run(
            "UPDATE notifications n SET status = 'done'
             WHERE status <> 'done' AND NOT EXISTS (SELECT 1 FROM push_outbox o WHERE o.notification_id = n.id)"
        );
    }
}
