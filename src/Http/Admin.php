<?php
declare(strict_types=1);

namespace App\Http;

use App\Auth;
use App\Content;
use App\Db;
use App\Notifier;
use App\Schedule;
use App\View;
use DateTimeImmutable;

final class Admin
{
    // ---------- utilidades de formulário ----------

    private static function str(string $key, int $max = 255): string
    {
        return mb_substr(trim(mb_scrub((string) ($_POST[$key] ?? ''), 'UTF-8')), 0, $max);
    }

    private static function int(string $key, ?int $default = null): ?int
    {
        $v = $_POST[$key] ?? '';
        return is_numeric($v) ? (int) $v : $default;
    }

    private static function coord(string $key, float $limit): ?string
    {
        $v = str_replace(',', '.', trim((string) ($_POST[$key] ?? '')));
        return is_numeric($v) && abs((float) $v) <= $limit ? number_format((float) $v, 6, '.', '') : null;
    }

    private static function time(string $key): string
    {
        $v = trim((string) ($_POST[$key] ?? ''));
        return preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $v) ? $v . ':00' : '19:00:00';
    }

    private static function uniqueSlug(string $table, string $base, int $id = 0): string
    {
        $slug = slugify($base);
        $try = $slug;
        for ($i = 2; Db::value("SELECT id FROM `$table` WHERE slug = ? AND id <> ?", [$try, $id]); $i++) {
            $try = "$slug-$i";
        }
        return $try;
    }

    /** Traduções salvas de um item: [lang => [field => value]] */
    public static function loadTranslations(string $entity, int $id): array
    {
        $out = [];
        foreach (Db::all('SELECT lang, field, value FROM translations WHERE entity = ? AND ref_id = ?', [$entity, $id]) as $r) {
            $out[$r['lang']][$r['field']] = $r['value'];
        }
        return $out;
    }

    /** Grava os campos tr[lang][field] do formulário; campo vazio apaga a tradução. */
    private static function saveTranslations(string $entity, int $id, string $key = 'tr'): void
    {
        $allowed = Content::TRANSLATABLE[$entity];
        foreach (['en', 'es'] as $lang) {
            foreach ($allowed as $field) {
                if (!isset($_POST[$key][$lang][$field])) {
                    continue;
                }
                $value = trim(mb_scrub((string) $_POST[$key][$lang][$field], 'UTF-8'));
                if ($value === '') {
                    Db::run('DELETE FROM translations WHERE entity = ? AND ref_id = ? AND field = ? AND lang = ?', [$entity, $id, $field, $lang]);
                } else {
                    Db::run(
                        'INSERT INTO translations (entity, ref_id, field, lang, value) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)',
                        [$entity, $id, $field, $lang, mb_substr($value, 0, 5000)]
                    );
                }
            }
        }
    }

    private static function back(string $to, string $message, string $type = 'ok'): never
    {
        Auth::flash($message, $type);
        redirect($to);
    }

    // ---------- acesso ----------

    public static function loginForm(): void
    {
        if (!Db::value('SELECT COUNT(*) FROM users')) {
            redirect('/admin/setup');
        }
        if (Auth::user()) {
            redirect('/admin');
        }
        View::admin('login', ['error' => Auth::flash()], 'Entrar');
    }

    public static function login(): void
    {
        Auth::checkCsrf();
        if (Auth::tooManyAttempts()) {
            self::back('/admin/login', 'Muitas tentativas. Espere 15 minutos e tente de novo.', 'error');
        }
        if (!Auth::attempt((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            self::back('/admin/login', 'E-mail ou senha incorretos.', 'error');
        }
        $next = (string) ($_GET['next'] ?? '/admin');
        redirect(str_starts_with($next, '/admin') ? $next : '/admin');
    }

    public static function logout(): void
    {
        Auth::checkCsrf();
        Auth::logout();
        redirect('/admin/login');
    }

    public static function setupForm(): void
    {
        if (Db::value('SELECT COUNT(*) FROM users')) {
            redirect('/admin/login');
        }
        View::admin('setup', ['error' => Auth::flash(), 'hasToken' => (bool) env('SETUP_TOKEN')], 'Criar administrador');
    }

    public static function setup(): void
    {
        Auth::checkCsrf();
        if (Db::value('SELECT COUNT(*) FROM users')) {
            redirect('/admin/login');
        }
        $token = env('SETUP_TOKEN');
        if (!$token || !hash_equals($token, (string) ($_POST['token'] ?? ''))) {
            self::back('/admin/setup', 'Código de configuração incorreto. Ele está no arquivo .env (SETUP_TOKEN).', 'error');
        }
        $email = mb_strtolower(self::str('email', 190));
        $pass = (string) ($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($pass) < 10) {
            self::back('/admin/setup', 'Informe um e-mail válido e uma senha com pelo menos 10 caracteres.', 'error');
        }
        Db::insert('users', ['name' => self::str('name', 120) ?: 'Administrador', 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT)]);
        Auth::attempt($email, $pass);
        self::back('/admin', 'Administrador criado. Bem-vindo ao painel!');
    }

    // ---------- visão geral ----------

    public static function dashboard(): void
    {
        Auth::require();
        $now = new DateTimeImmutable();
        $upcoming = [];
        foreach (Db::all('SELECT * FROM events WHERE published = 1') as $ev) {
            foreach (Schedule::occurrences($ev, $now, 3, 15) as $o) {
                $upcoming[] = ['title' => $ev['title'], 'kind' => 'Programação', 'url' => '/admin/programacao/' . $ev['id'], 'notify' => (int) $ev['notify'], 'reminder' => (int) $ev['reminder_min']] + $o;
            }
        }
        foreach (Db::all('SELECT * FROM cell_groups WHERE published = 1') as $g) {
            foreach (Schedule::occurrences($g, $now, 2, 15) as $o) {
                $upcoming[] = ['title' => $g['name'], 'kind' => 'Grupo', 'url' => '/admin/grupos/' . $g['id'], 'notify' => (int) $g['notify'], 'reminder' => (int) $g['reminder_min']] + $o;
            }
        }
        usort($upcoming, fn ($a, $b) => $a['start'] <=> $b['start']);
        $stats = [
            'subs' => (int) Db::value('SELECT COUNT(*) FROM push_subscriptions'),
            'events' => (int) Db::value('SELECT COUNT(*) FROM events WHERE published = 1'),
            'groups' => (int) Db::value('SELECT COUNT(*) FROM cell_groups WHERE published = 1'),
            'sent30' => (int) Db::value('SELECT COALESCE(SUM(sent),0) FROM notifications WHERE created_at > NOW() - INTERVAL 30 DAY'),
        ];
        $recent = Db::all('SELECT * FROM notifications ORDER BY id DESC LIMIT 5');
        $pushReady = (bool) env('VAPID_PUBLIC_KEY') && (bool) env('VAPID_PRIVATE_KEY');
        $cronLast = setting('cron_last_run');
        View::admin('dashboard', [
            'upcoming' => array_slice($upcoming, 0, 8), 'stats' => $stats, 'recent' => $recent,
            'pushReady' => $pushReady, 'cronLast' => $cronLast, 'flash' => Auth::flash(), 'now' => $now,
        ], 'Visão geral', 'home');
    }

    // ---------- programação ----------

    public static function events(): void
    {
        Auth::require();
        $rows = Db::all('SELECT * FROM events ORDER BY published DESC, sort, title');
        $now = new DateTimeImmutable();
        foreach ($rows as &$r) {
            $r['next'] = Schedule::next($r, $now);
        }
        unset($r);
        View::admin('events', ['rows' => $rows, 'flash' => Auth::flash()], 'Programação', 'events');
    }

    public static function eventForm(string $id = ''): void
    {
        Auth::require();
        $ev = $id === 'novo' || $id === ''
            ? ['id' => 0, 'title' => '', 'slug' => '', 'summary' => '', 'description' => '', 'category' => 'culto', 'recurrence' => 'weekly', 'weekday' => 0, 'nth' => 1, 'date' => '', 'start_time' => '19:30:00', 'duration_min' => 120, 'location_name' => '', 'address' => '', 'lat' => null, 'lng' => null, 'notify' => 1, 'reminder_min' => 120, 'featured' => 0, 'published' => 1, 'sort' => 0]
            : (Db::one('SELECT * FROM events WHERE id = ?', [(int) $id]) ?? View::notFound());
        View::admin('event-form', ['ev' => $ev, 'tr' => self::loadTranslations('event', (int) $ev['id']), 'flash' => Auth::flash()], $ev['id'] ? 'Editar: ' . $ev['title'] : 'Nova programação', 'events');
    }

    public static function eventSave(): void
    {
        Auth::require();
        Auth::checkCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $title = self::str('title', 120);
        if ($title === '') {
            self::back($id ? "/admin/programacao/$id" : '/admin/programacao/novo', 'Dê um nome para a programação.', 'error');
        }
        $recurrence = in_array($_POST['recurrence'] ?? '', ['weekly', 'monthly', 'once'], true) ? $_POST['recurrence'] : 'weekly';
        $date = self::str('date', 10);
        if ($recurrence === 'once' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            self::back($id ? "/admin/programacao/$id" : '/admin/programacao/novo', 'Escolha a data do evento.', 'error');
        }
        $data = [
            'title' => $title,
            'slug' => self::uniqueSlug('events', self::str('slug', 120) ?: $title, $id),
            'summary' => self::str('summary', 300),
            'description' => self::str('description', 5000),
            'category' => in_array($_POST['category'] ?? '', ['culto', 'ministerio', 'evento'], true) ? $_POST['category'] : 'culto',
            'recurrence' => $recurrence,
            'weekday' => $recurrence === 'once' ? null : max(0, min(6, (int) self::int('weekday', 0))),
            'nth' => $recurrence === 'monthly' ? (int) self::int('nth', 1) : null,
            'date' => $recurrence === 'once' ? $date : null,
            'start_time' => self::time('start_time'),
            'duration_min' => max(15, min(1440, (int) self::int('duration_min', 120))),
            'location_name' => self::str('location_name', 120) ?: null,
            'address' => self::str('address', 255) ?: null,
            'lat' => self::coord('lat', 90),
            'lng' => self::coord('lng', 180),
            'notify' => isset($_POST['notify']) ? 1 : 0,
            'reminder_min' => max(5, min(2880, (int) self::int('reminder_min', 120))),
            'featured' => isset($_POST['featured']) ? 1 : 0,
            'published' => isset($_POST['published']) ? 1 : 0,
            'sort' => (int) self::int('sort', 0),
        ];
        if ($id) {
            Db::update('events', $id, $data);
        } else {
            $id = Db::insert('events', $data);
        }
        self::saveTranslations('event', $id);
        self::back('/admin/programacao', 'Programação salva: ' . $title . '.');
    }

    public static function eventDelete(string $id): void
    {
        Auth::require();
        Auth::checkCsrf();
        Db::run('DELETE FROM events WHERE id = ?', [(int) $id]);
        Db::run("DELETE FROM translations WHERE entity = 'event' AND ref_id = ?", [(int) $id]);
        self::back('/admin/programacao', 'Programação excluída.');
    }

    // ---------- grupos ----------

    public static function groups(): void
    {
        Auth::require();
        $rows = Db::all('SELECT * FROM cell_groups ORDER BY published DESC, city, neighborhood, name');
        $counts = [];
        foreach (Db::all("SELECT jt.topic AS t, COUNT(*) AS n FROM push_subscriptions, JSON_TABLE(topics, '$[*]' COLUMNS (topic VARCHAR(40) PATH '$')) jt WHERE jt.topic LIKE 'grupo:%' GROUP BY jt.topic") as $c) {
            $counts[$c['t']] = (int) $c['n'];
        }
        View::admin('groups', ['rows' => $rows, 'counts' => $counts, 'flash' => Auth::flash()], 'Grupos de convívio', 'groups');
    }

    public static function groupForm(string $id = ''): void
    {
        Auth::require();
        $g = $id === 'novo' || $id === ''
            ? ['id' => 0, 'name' => '', 'slug' => '', 'neighborhood' => '', 'city' => 'Palhoça', 'address' => '', 'show_address' => 0, 'lat' => null, 'lng' => null, 'recurrence' => 'weekly', 'weekday' => 4, 'nth' => 1, 'start_time' => '20:00:00', 'duration_min' => 120, 'audience' => 'Todos', 'leader_name' => '', 'leader_phone' => '', 'assistants' => '', 'description' => '', 'notify' => 1, 'reminder_min' => 120, 'published' => 1]
            : (Db::one('SELECT * FROM cell_groups WHERE id = ?', [(int) $id]) ?? View::notFound());
        View::admin('group-form', ['g' => $g, 'tr' => self::loadTranslations('group', (int) $g['id']), 'flash' => Auth::flash(), 'church' => Content::churchGeo()], $g['id'] ? 'Editar: ' . $g['name'] : 'Novo grupo', 'groups');
    }

    public static function groupSave(): void
    {
        Auth::require();
        Auth::checkCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $name = self::str('name', 120);
        $back = $id ? "/admin/grupos/$id" : '/admin/grupos/novo';
        if ($name === '' || self::str('neighborhood', 80) === '') {
            self::back($back, 'Preencha o nome do grupo e o bairro.', 'error');
        }
        $recurrence = ($_POST['recurrence'] ?? '') === 'monthly' ? 'monthly' : 'weekly';
        $data = [
            'name' => $name,
            'slug' => self::uniqueSlug('cell_groups', self::str('slug', 120) ?: $name, $id),
            'neighborhood' => self::str('neighborhood', 80),
            'city' => self::str('city', 80) ?: 'Palhoça',
            'address' => self::str('address', 255) ?: null,
            'show_address' => isset($_POST['show_address']) ? 1 : 0,
            'lat' => self::coord('lat', 90),
            'lng' => self::coord('lng', 180),
            'recurrence' => $recurrence,
            'weekday' => max(0, min(6, (int) self::int('weekday', 4))),
            'nth' => $recurrence === 'monthly' ? (int) self::int('nth', 1) : null,
            'start_time' => self::time('start_time'),
            'duration_min' => max(15, min(600, (int) self::int('duration_min', 120))),
            'audience' => self::str('audience', 80) ?: 'Todos',
            'leader_name' => self::str('leader_name', 120) ?: null,
            'leader_phone' => self::str('leader_phone', 30) ?: null,
            'assistants' => self::str('assistants', 160) ?: null,
            'description' => self::str('description', 3000),
            'notify' => isset($_POST['notify']) ? 1 : 0,
            'reminder_min' => max(5, min(2880, (int) self::int('reminder_min', 120))),
            'published' => isset($_POST['published']) ? 1 : 0,
        ];
        if ($id) {
            Db::update('cell_groups', $id, $data);
        } else {
            $id = Db::insert('cell_groups', $data);
        }
        self::saveTranslations('group', $id);
        self::back('/admin/grupos', 'Grupo salvo: ' . $name . '.');
    }

    public static function groupDelete(string $id): void
    {
        Auth::require();
        Auth::checkCsrf();
        Db::run('DELETE FROM cell_groups WHERE id = ?', [(int) $id]);
        Db::run("DELETE FROM translations WHERE entity = 'group' AND ref_id = ?", [(int) $id]);
        self::back('/admin/grupos', 'Grupo excluído.');
    }

    // ---------- avisos ----------

    public static function notifications(): void
    {
        Auth::require();
        $audiences = Notifier::AUDIENCES;
        foreach (Db::all('SELECT id, name FROM cell_groups ORDER BY name') as $g) {
            $audiences['grupo:' . $g['id']] = 'Grupo: ' . $g['name'];
        }
        $counts = [];
        foreach (array_keys($audiences) as $a) {
            $counts[$a] = Notifier::countAudience($a);
        }
        $history = Db::all('SELECT n.*, u.name AS author FROM notifications n LEFT JOIN users u ON u.id = n.created_by ORDER BY n.id DESC LIMIT 40');
        $platforms = Db::all("SELECT IF(platform = '', 'outro', platform) AS p, COUNT(*) AS n FROM push_subscriptions GROUP BY p ORDER BY n DESC");
        View::admin('notifications', [
            'audiences' => $audiences, 'counts' => $counts, 'history' => $history, 'platforms' => $platforms,
            'pushReady' => (bool) env('VAPID_PRIVATE_KEY'), 'flash' => Auth::flash(),
        ], 'Avisos', 'notifications');
    }

    public static function notificationSend(): void
    {
        $user = Auth::require();
        Auth::checkCsrf();
        $title = self::str('title', 80);
        $body = self::str('body', 200);
        if ($title === '' || $body === '') {
            self::back('/admin/avisos', 'Escreva o título e a mensagem do aviso.', 'error');
        }
        $audience = (string) ($_POST['audience'] ?? 'all');
        if (!isset(Notifier::AUDIENCES[$audience]) && !preg_match('/^grupo:\d+$/', $audience)) {
            $audience = 'all';
        }
        $url = self::str('url', 255);
        if (!str_starts_with($url, '/')) {
            $url = '/';
        }
        $i18n = [];
        foreach (['en', 'es'] as $l) {
            $tt = mb_substr(trim((string) ($_POST['tr'][$l]['title'] ?? '')), 0, 80);
            $tb = mb_substr(trim((string) ($_POST['tr'][$l]['body'] ?? '')), 0, 200);
            if ($tt !== '' || $tb !== '') {
                $i18n[$l] = ['title' => $tt ?: $title, 'body' => $tb ?: $body];
            }
        }
        $id = Notifier::create([
            'title' => $title, 'body' => $body, 'i18n' => $i18n, 'url' => $url, 'audience' => $audience,
            'source' => 'manual', 'ttl' => 2 * 86400, 'created_by' => (int) $user['id'],
        ]);
        $total = (int) Db::value('SELECT total FROM notifications WHERE id = ?', [$id]);
        self::back('/admin/avisos', $total
            ? "Aviso na fila para $total aparelho(s). O envio começa em até 1 minuto."
            : 'Aviso salvo, mas ninguém deste público está inscrito ainda.');
    }

    // ---------- perguntas frequentes ----------

    public static function faqs(): void
    {
        Auth::require();
        $rows = Db::all('SELECT * FROM faqs ORDER BY sort, id');
        foreach ($rows as &$r) {
            $r['tr'] = self::loadTranslations('faq', (int) $r['id']);
        }
        unset($r);
        View::admin('faqs', ['rows' => $rows, 'flash' => Auth::flash()], 'Perguntas frequentes', 'faqs');
    }

    public static function faqSave(): void
    {
        Auth::require();
        Auth::checkCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $q = self::str('question', 200);
        $a = self::str('answer', 2000);
        if ($q === '' || $a === '') {
            self::back('/admin/perguntas', 'Preencha a pergunta e a resposta.', 'error');
        }
        $data = ['question' => $q, 'answer' => $a, 'sort' => (int) self::int('sort', 0), 'published' => isset($_POST['published']) ? 1 : 0];
        if ($id) {
            Db::update('faqs', $id, $data);
        } else {
            $id = Db::insert('faqs', $data);
        }
        self::saveTranslations('faq', $id);
        self::back('/admin/perguntas', 'Pergunta salva.');
    }

    public static function faqDelete(string $id): void
    {
        Auth::require();
        Auth::checkCsrf();
        Db::run('DELETE FROM faqs WHERE id = ?', [(int) $id]);
        self::back('/admin/perguntas', 'Pergunta excluída.');
    }

    // ---------- configurações ----------

    public const SETTINGS = [
        'church_name' => ['Nome da igreja', 80],
        'church_city_label' => ['Cidade (aparece no topo)', 60],
        'hero_title' => ['Frase principal da página inicial', 140],
        'hero_text' => ['Texto de boas-vindas', 300],
        'about_title' => ['Título do "Quem somos"', 140],
        'about_text' => ['Texto do "Quem somos"', 3000],
        'groups_intro' => ['Texto de apresentação dos grupos', 500],
        'address_street' => ['Endereço (rua/rodovia e número)', 120],
        'address_city' => ['Cidade', 80],
        'address_state' => ['Estado (sigla)', 2],
        'address_zip' => ['CEP', 9],
        'lat' => ['Latitude', 20],
        'lng' => ['Longitude', 20],
        'directions_note' => ['Dica para chegar (estacionamento, ônibus, referência)', 600],
        'whatsapp_1' => ['WhatsApp principal', 20],
        'whatsapp_2' => ['WhatsApp secundário', 20],
        'instagram' => ['Instagram (sem @)', 60],
        'email' => ['E-mail', 120],
    ];

    public static function settings(): void
    {
        Auth::require();
        $values = [];
        foreach (Db::all('SELECT k, v FROM settings') as $r) {
            $values[$r['k']] = $r['v'];
        }
        View::admin('settings', ['values' => $values, 'fields' => self::SETTINGS, 'tr' => self::loadTranslations('setting', 0), 'flash' => Auth::flash()], 'Configurações', 'settings');
    }

    public static function settingsSave(): void
    {
        Auth::require();
        Auth::checkCsrf();
        foreach (self::SETTINGS as $key => [, $max]) {
            $v = self::str($key, $max);
            if (in_array($key, ['lat', 'lng'], true)) {
                $v = (string) (self::coord($key, $key === 'lat' ? 90 : 180) ?? '');
            }
            if ($key === 'instagram') {
                $v = ltrim($v, '@');
            }
            Db::run('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $v]);
        }
        self::saveTranslations('setting', 0);
        self::back('/admin/configuracoes', 'Configurações salvas.');
    }

    // ---------- usuários ----------

    public static function users(): void
    {
        $me = Auth::require();
        View::admin('users', ['rows' => Db::all('SELECT id, name, email, last_login_at, created_at FROM users ORDER BY name'), 'me' => $me, 'flash' => Auth::flash()], 'Usuários do painel', 'users');
    }

    public static function userSave(): void
    {
        Auth::require();
        Auth::checkCsrf();
        $email = mb_strtolower(self::str('email', 190));
        $pass = (string) ($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($pass) < 10) {
            self::back('/admin/usuarios', 'Informe um e-mail válido e uma senha com pelo menos 10 caracteres.', 'error');
        }
        if (Db::value('SELECT id FROM users WHERE email = ?', [$email])) {
            self::back('/admin/usuarios', 'Já existe um usuário com este e-mail.', 'error');
        }
        Db::insert('users', ['name' => self::str('name', 120) ?: $email, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT)]);
        self::back('/admin/usuarios', 'Usuário criado. Passe a senha para a pessoa por um canal seguro.');
    }

    public static function userDelete(string $id): void
    {
        $me = Auth::require();
        Auth::checkCsrf();
        if ((int) $id === (int) $me['id']) {
            self::back('/admin/usuarios', 'Você não pode excluir a si mesmo.', 'error');
        }
        Db::run('DELETE FROM users WHERE id = ?', [(int) $id]);
        self::back('/admin/usuarios', 'Usuário excluído.');
    }

    public static function passwordSave(): void
    {
        $me = Auth::require();
        Auth::checkCsrf();
        $row = Db::one('SELECT password_hash FROM users WHERE id = ?', [$me['id']]);
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $row['password_hash'])) {
            self::back('/admin/usuarios', 'A senha atual não confere.', 'error');
        }
        if (mb_strlen($new) < 10) {
            self::back('/admin/usuarios', 'A nova senha precisa ter pelo menos 10 caracteres.', 'error');
        }
        Db::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        self::back('/admin/usuarios', 'Senha alterada.');
    }

    // ---------- pedidos de ajuda ----------

    public const HELP_STATUS = ['novo' => 'Novo', 'contato' => 'Em contato', 'concluido' => 'Concluído'];

    public static function helpList(): void
    {
        Auth::require();
        $status = (string) ($_GET['status'] ?? '');
        $where = isset(self::HELP_STATUS[$status]) ? 'WHERE h.status = ?' : '';
        $rows = Db::all(
            "SELECT h.*, u.name AS handler FROM help_requests h LEFT JOIN users u ON u.id = h.handled_by $where ORDER BY h.status = 'novo' DESC, h.created_at DESC LIMIT 300",
            $where ? [$status] : []
        );
        $counts = [];
        foreach (Db::all('SELECT status, COUNT(*) n FROM help_requests GROUP BY status') as $c) {
            $counts[$c['status']] = (int) $c['n'];
        }
        $teamSubs = (int) Db::value("SELECT COUNT(*) FROM push_subscriptions WHERE JSON_CONTAINS(topics, '\"equipe\"')");
        View::admin('help-list', ['rows' => $rows, 'counts' => $counts, 'status' => $status, 'teamSubs' => $teamSubs, 'flash' => Auth::flash()], 'Pedidos de ajuda', 'help');
    }

    public static function helpShow(string $id): void
    {
        Auth::require();
        $h = Db::one('SELECT h.*, u.name AS handler FROM help_requests h LEFT JOIN users u ON u.id = h.handled_by WHERE h.id = ?', [(int) $id]) ?? View::notFound();
        View::admin('help-show', ['h' => $h, 'flash' => Auth::flash()], 'Pedido de ' . $h['name'], 'help');
    }

    public static function helpSave(string $id): void
    {
        $user = Auth::require();
        Auth::checkCsrf();
        $status = (string) ($_POST['status'] ?? 'novo');
        if (!isset(self::HELP_STATUS[$status])) {
            $status = 'novo';
        }
        Db::run('UPDATE help_requests SET status = ?, notes = ?, handled_by = ? WHERE id = ?', [$status, self::str('notes', 4000) ?: null, $user['id'], (int) $id]);
        self::back('/admin/ajuda/' . (int) $id, 'Pedido atualizado.');
    }

    public static function helpDelete(string $id): void
    {
        Auth::require();
        Auth::checkCsrf();
        Db::run('DELETE FROM help_requests WHERE id = ?', [(int) $id]);
        self::back('/admin/ajuda', 'Pedido excluído.');
    }

    /** Liga os alertas de "novo pedido de ajuda" no aparelho de quem está no painel. */
    public static function teamPush(): void
    {
        Auth::require();
        if (!same_origin() || !hash_equals(Auth::csrfToken(), (string) ($_SERVER['HTTP_X_CSRF'] ?? ''))) {
            json_out(['error' => 'sessão expirada'], 419);
        }
        $data = json_decode((string) file_get_contents('php://input', false, null, 0, 8192), true) ?: [];
        $sub = $data['subscription'] ?? [];
        $endpoint = (string) ($sub['endpoint'] ?? '');
        if (!\App\WebPush::isAllowedEndpoint($endpoint)) {
            json_out(['error' => 'serviço de push não reconhecido'], 422);
        }
        $old = Db::value('SELECT topics FROM push_subscriptions WHERE endpoint_hash = ?', [hash('sha256', $endpoint)]);
        $topics = $old ? (json_decode((string) $old, true) ?: []) : [];
        $topics = array_values(array_unique([...$topics, 'equipe']));
        Db::run(
            'INSERT INTO push_subscriptions (endpoint_hash, endpoint, p256dh, auth, topics, lang, platform) VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth), topics = VALUES(topics), failures = 0',
            [hash('sha256', $endpoint), $endpoint, (string) ($sub['keys']['p256dh'] ?? ''), (string) ($sub['keys']['auth'] ?? ''), json_encode($topics), 'pt', 'equipe']
        );
        json_out(['ok' => true]);
    }

    // ---------- guia de negócios ----------

    public const BIZ_STATUS = ['pendente' => 'Aguardando aprovação', 'aprovado' => 'No ar', 'recusado' => 'Recusado'];

    public static function bizList(): void
    {
        Auth::require();
        $status = (string) ($_GET['status'] ?? 'pendente');
        if (!isset(self::BIZ_STATUS[$status])) {
            $status = 'pendente';
        }
        $rows = Db::all('SELECT * FROM businesses WHERE status = ? ORDER BY needs_review DESC, created_at DESC', [$status]);
        $counts = [];
        foreach (Db::all('SELECT status, COUNT(*) n FROM businesses GROUP BY status') as $c) {
            $counts[$c['status']] = (int) $c['n'];
        }
        $review = (int) Db::value("SELECT COUNT(*) FROM businesses WHERE status = 'aprovado' AND needs_review = 1");
        View::admin('biz-list', ['rows' => $rows, 'counts' => $counts, 'status' => $status, 'review' => $review, 'flash' => Auth::flash()], 'Guia de negócios', 'biz');
    }

    public static function bizForm(string $id): void
    {
        Auth::require();
        $b = Db::one('SELECT * FROM businesses WHERE id = ?', [(int) $id]) ?? View::notFound();
        View::admin('biz-form', ['b' => $b, 'flash' => Auth::flash()], 'Negócio: ' . $b['name'], 'biz');
    }

    public static function bizSave(string $id): void
    {
        Auth::require();
        Auth::checkCsrf();
        $b = Db::one('SELECT * FROM businesses WHERE id = ?', [(int) $id]) ?? View::notFound();
        $status = (string) ($_POST['status'] ?? $b['status']);
        if (!isset(self::BIZ_STATUS[$status])) {
            $status = $b['status'];
        }
        $data = [
            'name' => self::str('name', 120) ?: $b['name'],
            'category' => isset(\App\Directory::CATEGORIES[$_POST['category'] ?? '']) ? $_POST['category'] : $b['category'],
            'description' => self::str('description', 2000),
            'tags' => self::str('tags', 400),
            'owner_name' => self::str('owner_name', 120),
            'whatsapp' => self::str('whatsapp', 30),
            'instagram' => ltrim(self::str('instagram', 80), '@') ?: null,
            'website' => self::str('website', 200) ?: null,
            'email' => self::str('email', 160) ?: null,
            'neighborhood' => self::str('neighborhood', 80),
            'city' => self::str('city', 80) ?: 'Palhoça',
            'address' => self::str('address', 200) ?: null,
            'show_address' => isset($_POST['show_address']) ? 1 : 0,
            'lat' => self::coord('lat', 90),
            'lng' => self::coord('lng', 180),
            'service_mode' => implode(',', array_intersect(\App\Directory::MODES, (array) ($_POST['service_mode'] ?? []))) ?: 'local',
            'service_area' => self::str('service_area', 160) ?: null,
            'hours' => self::str('hours', 160) ?: null,
            'featured' => isset($_POST['featured']) ? 1 : 0,
            'status' => $status,
            'review_note' => self::str('review_note', 400) ?: null,
            'needs_review' => 0,
        ];
        if ($status === 'aprovado' && $b['status'] !== 'aprovado') {
            $data['approved_at'] = date('Y-m-d H:i:s');
        }
        $slug = self::str('slug', 140);
        $data['slug'] = \App\Http\Business::uniqueSlug($slug ?: $b['slug'], (int) $b['id']);
        if (!empty($_POST['remove_logo']) && $b['logo']) {
            @unlink(ROOT . '/public/uploads/negocios/' . basename($b['logo']));
            $data['logo'] = null;
        }
        if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            $logo = \App\Http\Business::storeLogo($_FILES['logo']['tmp_name'], (int) $_FILES['logo']['size']);
            if ($logo) {
                if ($b['logo']) {
                    @unlink(ROOT . '/public/uploads/negocios/' . basename($b['logo']));
                }
                $data['logo'] = $logo;
            }
        }
        $data['search_text'] = \App\Directory::searchText($data + $b);
        Db::update('businesses', (int) $b['id'], $data);
        $msg = match (true) {
            $status === 'aprovado' && $b['status'] !== 'aprovado' => 'Aprovado! O negócio já aparece no site.',
            $status === 'recusado' => 'Cadastro recusado. Ele não aparece no site.',
            default => 'Alterações salvas.',
        };
        self::back('/admin/negocios?status=' . $status, $msg);
    }

    public static function bizDelete(string $id): void
    {
        Auth::require();
        Auth::checkCsrf();
        $b = Db::one('SELECT logo FROM businesses WHERE id = ?', [(int) $id]);
        if ($b && $b['logo']) {
            @unlink(ROOT . '/public/uploads/negocios/' . basename($b['logo']));
        }
        Db::run('DELETE FROM businesses WHERE id = ?', [(int) $id]);
        self::back('/admin/negocios', 'Negócio excluído.');
    }
}
