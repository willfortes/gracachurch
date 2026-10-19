<?php
// Teste da criptografia Web Push: faz o papel do navegador e descriptografa a mensagem.
// Rodar: php tests/push_test.php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

use App\WebPush;

$fail = 0;
function check(bool $ok, string $msg): void { global $fail; echo ($ok ? "ok   " : "FAIL ") . $msg . "\n"; if (!$ok) $fail++; }

// 1. Chaves do "navegador"
$ua = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
$ec = openssl_pkey_get_details($ua)['ec'];
$uaPub = "\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);
$auth = random_bytes(16);

$payload = json_encode(['title' => 'Culto hoje às 19h30', 'body' => 'Graça Church']);
$body = WebPush::encrypt($payload, $uaPub, $auth);

// 2. Descriptografa como o navegador faria (RFC 8291)
$salt = substr($body, 0, 16);
$rs = unpack('N', substr($body, 16, 4))[1];
$idlen = ord($body[20]);
$asPub = substr($body, 21, $idlen);
$ct = substr($body, 21 + $idlen);
check($rs === 4096 && $idlen === 65, 'cabeçalho aes128gcm');

$shared = openssl_pkey_derive(openssl_pkey_get_public(WebPush::ecPublicPem($asPub)), $ua, 32);
$ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\0" . $uaPub . $asPub, $auth);
$cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
$nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
$plain = openssl_decrypt(substr($ct, 0, -16), 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, substr($ct, -16));
check($plain === $payload . "\x02", 'descriptografia confere com a mensagem');

// 3. Token VAPID assinado e verificável com a chave pública
$keys = WebPush::generateKeys();
$wp = new WebPush($keys['public'], $keys['private'], 'mailto:teste@example.com');
[$header] = $wp->prepare('https://fcm.googleapis.com/fcm/send/abc', WebPush::b64e($uaPub), WebPush::b64e($auth), 'oi');
preg_match('/^vapid t=([^.]+)\.([^.]+)\.([^,]+), k=(.+)$/', $header, $m);
check(count($m) === 5, 'formato do cabeçalho Authorization');
$claims = json_decode(WebPush::b64d($m[2]), true);
check($claims['aud'] === 'https://fcm.googleapis.com', 'aud = origem do serviço de push');
$raw = WebPush::b64d($m[3]);
check(strlen($raw) === 64, 'assinatura ES256 com 64 bytes');
// raw -> DER para verificar com openssl
$toDer = function (string $i): string { $i = ltrim($i, "\0"); if (ord($i[0]) & 0x80) $i = "\0" . $i; return "\x02" . chr(strlen($i)) . $i; };
$seq = $toDer(substr($raw, 0, 32)) . $toDer(substr($raw, 32));
$der = "\x30" . chr(strlen($seq)) . $seq;
$ok = openssl_verify($m[1] . '.' . $m[2], $der, WebPush::ecPublicPem(WebPush::b64d($m[4])), OPENSSL_ALGO_SHA256);
check($ok === 1, 'assinatura VAPID válida');

check(WebPush::isAllowedEndpoint('https://fcm.googleapis.com/fcm/send/x'), 'aceita FCM');
check(WebPush::isAllowedEndpoint('https://web.push.apple.com/abc'), 'aceita Apple');
check(WebPush::isAllowedEndpoint('https://wns2-bl2p.notify.windows.com/w/?token=1'), 'aceita Windows');
check(!WebPush::isAllowedEndpoint('https://evil.com/fcm.googleapis.com'), 'recusa domínio estranho');
check(!WebPush::isAllowedEndpoint('http://fcm.googleapis.com/x'), 'recusa http');

echo $fail ? "\n$fail falha(s)\n" : "\nTudo certo\n";
exit($fail ? 1 : 0);
