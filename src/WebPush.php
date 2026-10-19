<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Web Push sem dependências: VAPID (RFC 8292) + criptografia aes128gcm (RFC 8291).
 * Funciona com Chrome, Edge, Firefox e Safari (macOS, e iOS/iPadOS 16.4+ com o app instalado).
 */
final class WebPush
{
    /** Serviços de push aceitos. Evita que alguém cadastre uma URL qualquer para o servidor chamar. */
    private const ALLOWED_HOSTS = [
        'fcm.googleapis.com', 'android.googleapis.com', 'updates.push.services.mozilla.com',
        'push.services.mozilla.com', 'web.push.apple.com', 'notify.windows.com', 'push.apple.com',
    ];

    private string $publicKey;   // 65 bytes, ponto não comprimido
    private \OpenSSLAsymmetricKey $privateKey;

    public function __construct(string $publicKeyB64, string $privateKeyB64, private string $subject)
    {
        $this->publicKey = self::b64d($publicKeyB64);
        $d = self::b64d($privateKeyB64);
        if (strlen($this->publicKey) !== 65 || strlen($d) !== 32) {
            throw new RuntimeException('Chaves VAPID inválidas. Gere com: php bin/vapid.php');
        }
        $key = openssl_pkey_get_private(self::ecPrivatePem($d, $this->publicKey));
        if ($key === false) {
            throw new RuntimeException('Não foi possível ler a chave VAPID privada.');
        }
        $this->privateKey = $key;
    }

    public static function fromEnv(): ?self
    {
        $pub = env('VAPID_PUBLIC_KEY');
        $priv = env('VAPID_PRIVATE_KEY');
        if (!$pub || !$priv) {
            return null;
        }
        return new self($pub, $priv, env('VAPID_SUBJECT', 'mailto:contato@example.com'));
    }

    public static function isAllowedEndpoint(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return false;
        }
        $host = strtolower($parts['host']);
        foreach (self::ALLOWED_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }
        return false;
    }

    /** @return array{0: string, 1: string} [cabeçalho Authorization, corpo criptografado] */
    public function prepare(string $endpoint, string $p256dh, string $auth, string $payload): array
    {
        return [$this->vapidHeader($endpoint), self::encrypt($payload, self::b64d($p256dh), self::b64d($auth))];
    }

    /**
     * Envia várias mensagens em paralelo.
     * @param list<array{id:int, endpoint:string, p256dh:string, auth:string, payload:string, ttl:int, urgency?:string, topic?:string}> $messages
     * @return array<int, int> id => status HTTP (0 em erro de rede)
     */
    public function sendMany(array $messages, int $concurrency = 20): array
    {
        $results = [];
        $queue = $messages;
        $mh = curl_multi_init();
        $active = [];

        $add = function () use (&$queue, &$active, $mh, &$results): void {
            $msg = array_shift($queue);
            if ($msg === null) {
                return;
            }
            try {
                [$authHeader, $body] = $this->prepare($msg['endpoint'], $msg['p256dh'], $msg['auth'], $msg['payload']);
            } catch (\Throwable) {
                $results[$msg['id']] = 400; // chave do navegador inválida: trata como inscrição morta
                return;
            }
            $headers = [
                'Authorization: ' . $authHeader,
                'Content-Encoding: aes128gcm',
                'Content-Type: application/octet-stream',
                'TTL: ' . max(60, (int) $msg['ttl']),
                'Urgency: ' . ($msg['urgency'] ?? 'normal'),
            ];
            if (!empty($msg['topic'])) {
                $headers[] = 'Topic: ' . substr(preg_replace('/[^A-Za-z0-9_-]/', '', $msg['topic']) ?? '', 0, 32);
            }
            $ch = curl_init($msg['endpoint']);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);
            curl_multi_add_handle($mh, $ch);
            $active[(int) $ch] = $msg['id'];
        };

        for ($i = 0; $i < $concurrency; $i++) {
            $add();
        }
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 1.0);
            }
            while ($info = curl_multi_info_read($mh)) {
                $ch = $info['handle'];
                $id = $active[(int) $ch];
                $results[$id] = $info['result'] === CURLE_OK ? (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE) : 0;
                unset($active[(int) $ch]);
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
                $add();
            }
        } while (($running || $active) && $status === CURLM_OK);
        curl_multi_close($mh);
        return $results;
    }

    private function vapidHeader(string $endpoint): string
    {
        $p = parse_url($endpoint);
        $aud = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
        $header = self::b64e(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64e(json_encode(['aud' => $aud, 'exp' => time() + 12 * 3600, 'sub' => $this->subject], JSON_UNESCAPED_SLASHES));
        $input = "$header.$claims";
        if (!openssl_sign($input, $der, $this->privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Falha ao assinar o token VAPID.');
        }
        return 'vapid t=' . $input . '.' . self::b64e(self::derToRaw($der)) . ', k=' . self::b64e($this->publicKey);
    }

    /** Criptografia aes128gcm de uma mensagem para a chave pública do navegador (RFC 8291). */
    public static function encrypt(string $payload, string $uaPublic, string $authSecret): string
    {
        if (strlen($uaPublic) !== 65 || strlen($authSecret) !== 16) {
            throw new RuntimeException('Inscrição de push inválida.');
        }
        $local = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $details = openssl_pkey_get_details($local)['ec'];
        $asPublic = "\x04" . str_pad($details['x'], 32, "\0", STR_PAD_LEFT) . str_pad($details['y'], 32, "\0", STR_PAD_LEFT);

        $peer = openssl_pkey_get_public(self::ecPublicPem($uaPublic));
        if ($peer === false) {
            throw new RuntimeException('Chave do navegador inválida.');
        }
        $shared = openssl_pkey_derive($peer, $local, 32);
        if ($shared === false) {
            throw new RuntimeException('Falha no ECDH.');
        }
        $salt = random_bytes(16);
        $ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\0" . $uaPublic . $asPublic, $authSecret);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);

        $cipher = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($cipher === false) {
            throw new RuntimeException('Falha ao criptografar.');
        }
        return $salt . pack('N', 4096) . chr(65) . $asPublic . $cipher . $tag;
    }

    /** Gera um par de chaves VAPID. @return array{public: string, private: string} */
    public static function generateKeys(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $ec = openssl_pkey_get_details($key)['ec'];
        return [
            'public' => self::b64e("\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT)),
            'private' => self::b64e(str_pad($ec['d'], 32, "\0", STR_PAD_LEFT)),
        ];
    }

    public static function ecPublicPem(string $raw65): string
    {
        $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $raw65;
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    public static function ecPrivatePem(string $d, string $raw65): string
    {
        $der = hex2bin('30770201010420') . $d . hex2bin('a00a06082a8648ce3d030107a144034200') . $raw65;
        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
    }

    /** Assinatura ECDSA em DER -> formato JOSE (r||s, 64 bytes). */
    public static function derToRaw(string $der): string
    {
        $pos = 2;
        if (ord($der[1]) & 0x80) {
            $pos += ord($der[1]) & 0x7f;
        }
        $out = '';
        for ($i = 0; $i < 2; $i++) {
            $pos++; // 0x02
            $len = ord($der[$pos++]);
            $int = substr($der, $pos, $len);
            $pos += $len;
            $out .= str_pad(ltrim($int, "\0"), 32, "\0", STR_PAD_LEFT);
        }
        return $out;
    }

    public static function b64e(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function b64d(string $s): string
    {
        $s = strtr(trim($s), '-_', '+/');
        return base64_decode($s . str_repeat('=', (4 - strlen($s) % 4) % 4), true) ?: '';
    }
}
