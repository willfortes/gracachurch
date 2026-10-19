<?php
declare(strict_types=1);

namespace App;

/** Login do painel com sessão PHP, proteção CSRF e limite de tentativas. */
final class Auth
{
    private const MAX_ATTEMPTS = 8;
    private const WINDOW_MIN = 15;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = str_starts_with(app_url(), 'https://') || (($_SERVER['HTTPS'] ?? '') === 'on');
        session_name('gc_admin');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        // Sessão expira após 12 horas sem uso
        if (isset($_SESSION['seen']) && time() - $_SESSION['seen'] > 43200) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['seen'] = time();
    }

    public static function user(): ?array
    {
        self::start();
        if (empty($_SESSION['uid'])) {
            return null;
        }
        static $user = null;
        $user ??= Db::one('SELECT id, name, email FROM users WHERE id = ?', [$_SESSION['uid']]);
        return $user;
    }

    public static function require(): array
    {
        $u = self::user();
        if ($u === null) {
            redirect('/admin/login?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/admin'));
        }
        return $u;
    }

    public static function tooManyAttempts(): bool
    {
        $n = (int) Db::value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL ' . self::WINDOW_MIN . ' MINUTE)',
            [client_ip()]
        );
        return $n >= self::MAX_ATTEMPTS;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = Db::one('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
        // password_verify roda mesmo sem usuário para não revelar quais e-mails existem
        $hash = $user['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuJ8rWbS6ZC2K5f0bQ9oZb4n1n1n1n1n1';
        if (!password_verify($password, $hash) || $user === null) {
            Db::insert('login_attempts', ['ip' => client_ip()]);
            return false;
        }
        self::start();
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $user['id'];
        Db::run('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        Db::run('DELETE FROM login_attempts WHERE ip = ?', [client_ip()]);
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Db::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        return true;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_regenerate_id(true);
        session_destroy();
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
        }
        return $_SESSION['csrf'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::csrfToken()) . '">';
    }

    public static function checkCsrf(): void
    {
        self::start();
        $token = $_POST['_csrf'] ?? '';
        if (!same_origin() || !is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
            http_response_code(419);
            exit('Sessão expirada. Volte, recarregue a página e tente de novo.');
        }
    }

    public static function flash(?string $message = null, string $type = 'ok'): ?array
    {
        self::start();
        if ($message !== null) {
            $_SESSION['flash'] = ['message' => $message, 'type' => $type];
            return null;
        }
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }
}
