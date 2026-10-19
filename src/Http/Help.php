<?php
declare(strict_types=1);

namespace App\Http;

use App\Db;
use App\I18n;
use App\Notifier;
use App\Seo;
use App\View;

/** "Quero ajuda": pedido de contato/oração que a equipe acompanha no painel. */
final class Help
{
    private const MAX_PER_HOUR = 5;

    private static function key(): string
    {
        return env('APP_KEY') ?: hash('sha256', 'gracachurch|' . env('DB_PASS', '') . '|' . env('VAPID_PRIVATE_KEY', ''));
    }

    /** Token do formulário: prova que ele foi aberto no site e há quanto tempo (sem cookie). */
    public static function formToken(): string
    {
        $ts = (string) time();
        return $ts . '.' . substr(hash_hmac('sha256', $ts, self::key()), 0, 32);
    }

    private static function checkToken(string $token): bool
    {
        [$ts, $sig] = array_pad(explode('.', $token, 2), 2, '');
        if (!ctype_digit($ts) || !hash_equals(substr(hash_hmac('sha256', $ts, self::key()), 0, 32), $sig)) {
            return false;
        }
        $age = time() - (int) $ts;
        return $age >= 3 && $age <= 7200; // pessoas levam alguns segundos; robôs, nenhum
    }

    public static function form(array $errors = [], array $old = [], bool $sent = false): void
    {
        View::page('ajuda', ['errors' => $errors, 'old' => $old, 'sent' => $sent, 'token' => self::formToken()], [
            'title' => t('meta_help_title', ['name' => setting('church_name', 'Graça Church')]),
            'description' => t('meta_help_desc', ['name' => setting('church_name', 'Graça Church')]),
            'route' => 'help',
            'nav' => 'ajuda',
            'jsonld' => [Seo::breadcrumb([t('crumbs_home') => I18n::url(), t('nav_help') => I18n::url('help')])],
            'robots' => $sent ? 'noindex' : null,
        ]);
    }

    public static function submit(): void
    {
        if (!same_origin()) {
            http_response_code(403);
            exit;
        }
        $in = fn (string $k, int $max) => mb_substr(trim(mb_scrub((string) ($_POST[$k] ?? ''), 'UTF-8')), 0, $max);
        $old = ['name' => $in('name', 160), 'phone' => $in('phone', 40), 'message' => $in('message', 4000), 'extra' => $in('extra', 2000)];

        // Robôs: campo escondido preenchido ou formulário enviado rápido demais
        if ($in('website', 200) !== '' || !self::checkToken((string) ($_POST['t'] ?? ''))) {
            self::form([], [], true); // finge sucesso para não ensinar o robô
        }
        $errors = [];
        if (mb_strlen($old['name']) < 3) {
            $errors['name'] = t('help_err_name');
        }
        $digits = preg_replace('/\D+/', '', $old['phone']) ?? '';
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            $errors['phone'] = t('help_err_phone');
        }
        if (mb_strlen($old['message']) < 3) {
            $errors['message'] = t('help_err_message');
        }
        if (empty($_POST['consent'])) {
            $errors['consent'] = t('help_err_consent');
        }
        $ipHash = hash('sha256', client_ip() . self::key());
        if (!$errors && (int) Db::value('SELECT COUNT(*) FROM help_requests WHERE ip_hash = ? AND created_at > NOW() - INTERVAL 1 HOUR', [$ipHash]) >= self::MAX_PER_HOUR) {
            $errors['form'] = t('help_err_limit');
        }
        if ($errors) {
            http_response_code(422);
            self::form($errors, $old);
        }

        $id = Db::insert('help_requests', [
            'name' => $old['name'], 'phone' => $old['phone'], 'message' => $old['message'], 'extra' => $old['extra'] ?: null,
            'lang' => I18n::$lang, 'consent' => 1, 'ip_hash' => $ipHash,
        ]);
        // Avisa a equipe nos aparelhos que ativaram "alertas de pedidos" no painel
        try {
            Notifier::create([
                'title' => 'Novo pedido de ajuda',
                'body' => $old['name'] . ': ' . truncate($old['message'], 90),
                'url' => '/admin/ajuda/' . $id,
                'audience' => 'equipe',
                'source' => 'auto',
                'ttl' => 86400,
            ]);
        } catch (\Throwable) {
            // o pedido já está salvo; o alerta é um extra
        }
        self::form([], [], true);
    }
}
