<?php
declare(strict_types=1);

namespace App\Http;

use App\Db;
use App\WebPush;

/** Inscrição dos navegadores nos avisos push. */
final class Api
{
    private static function input(): array
    {
        if (!same_origin()) {
            json_out(['error' => 'origem não permitida'], 403);
        }
        $raw = file_get_contents('php://input', false, null, 0, 8192) ?: '';
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            json_out(['error' => 'json inválido'], 400);
        }
        return $data;
    }

    private static function cleanTopics(mixed $topics): array
    {
        $valid = [];
        foreach ((array) $topics as $t) {
            $t = (string) $t;
            if (in_array($t, ['cultos', 'ministerios', 'eventos'], true) || preg_match('/^grupo:\d{1,9}$/', $t)) {
                $valid[$t] = true;
            }
        }
        return array_slice(array_keys($valid), 0, 20);
    }

    public static function subscribe(): void
    {
        $data = self::input();
        $sub = $data['subscription'] ?? [];
        $endpoint = (string) ($sub['endpoint'] ?? '');
        $p256dh = (string) ($sub['keys']['p256dh'] ?? '');
        $auth = (string) ($sub['keys']['auth'] ?? '');

        if (!WebPush::isAllowedEndpoint($endpoint) || strlen($endpoint) > 2000) {
            json_out(['error' => 'serviço de push não reconhecido'], 422);
        }
        if (strlen(WebPush::b64d($p256dh)) !== 65 || strlen(WebPush::b64d($auth)) !== 16) {
            json_out(['error' => 'chaves inválidas'], 422);
        }
        // Renovação feita pelo service worker: herda os tópicos da inscrição antiga
        if (!empty($data['oldEndpoint'])) {
            $old = Db::value('SELECT topics FROM push_subscriptions WHERE endpoint_hash = ?', [hash('sha256', (string) $data['oldEndpoint'])]);
            if ($old) {
                $data['topics'] = json_decode((string) $old, true);
            }
        }
        $topics = self::cleanTopics($data['topics'] ?? []);
        if (!$topics) {
            json_out(['error' => 'escolha pelo menos um tipo de aviso'], 422);
        }
        $lang = in_array($data['lang'] ?? '', \App\I18n::LANGS, true) ? $data['lang'] : 'pt';
        $platform = substr(preg_replace('/[^a-z]/', '', strtolower((string) ($data['platform'] ?? ''))) ?? '', 0, 40);

        Db::run(
            'INSERT INTO push_subscriptions (endpoint_hash, endpoint, p256dh, auth, topics, lang, platform)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth), topics = VALUES(topics),
                lang = VALUES(lang), platform = VALUES(platform), failures = 0',
            [hash('sha256', $endpoint), $endpoint, $p256dh, $auth, json_encode($topics), $lang, $platform]
        );
        // Troca de inscrição (o navegador renovou as chaves): remove a antiga
        if (!empty($data['oldEndpoint']) && $data['oldEndpoint'] !== $endpoint) {
            Db::run('DELETE FROM push_subscriptions WHERE endpoint_hash = ?', [hash('sha256', (string) $data['oldEndpoint'])]);
        }
        json_out(['ok' => true, 'topics' => $topics]);
    }

    public static function unsubscribe(): void
    {
        $data = self::input();
        $endpoint = (string) ($data['endpoint'] ?? '');
        Db::run('DELETE FROM push_subscriptions WHERE endpoint_hash = ?', [hash('sha256', $endpoint)]);
        json_out(['ok' => true]);
    }

    public static function vapid(): void
    {
        json_out(['key' => env('VAPID_PUBLIC_KEY', '')]);
    }
}
