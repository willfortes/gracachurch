<?php
declare(strict_types=1);

namespace App\Http;

use App\Db;
use App\Directory;
use App\I18n;
use App\Notifier;
use App\Seo;
use App\View;

/** Guia de negócios e serviços dos membros. */
final class Business
{
    private static function name(): string
    {
        return setting('church_name', 'Graça Church');
    }

    private static function key(): string
    {
        return env('APP_KEY') ?: hash('sha256', 'gracachurch|' . env('DB_PASS', '') . '|' . env('VAPID_PRIVATE_KEY', ''));
    }

    public static function url(array $b): string
    {
        return I18n::url('business', $b['slug']);
    }

    public static function catUrl(string $cat): string
    {
        return I18n::url('business') . '/' . 'categoria' . '/' . $cat;
    }

    // ---------- listagem e busca ----------

    public static function index(): void
    {
        self::listing((string) ($_GET['q'] ?? ''), (string) ($_GET['cat'] ?? ''));
    }

    public static function category(string $slug): void
    {
        if (!isset(Directory::CATEGORIES[$slug])) {
            View::notFound();
        }
        self::listing((string) ($_GET['q'] ?? ''), $slug, true);
    }

    private static function listing(string $q, string $cat, bool $catPage = false): void
    {
        $q = mb_substr(trim($q), 0, 80);
        $found = Directory::search($q, $cat);
        $results = $found['results'];
        $counts = Directory::counts();
        $catLabel = $cat !== '' ? Directory::categoryLabel($cat) : '';

        $items = [];
        foreach (array_slice($results, 0, 30) as $i => $b) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => app_url(self::url($b)), 'name' => $b['name']];
        }
        $crumbs = [t('crumbs_home') => I18n::url(), t('nav_business') => I18n::url('business')];
        if ($catPage) {
            $crumbs[$catLabel] = self::catUrl($cat);
        }
        $jsonld = [Seo::breadcrumb($crumbs)];
        if ($items) {
            $jsonld[] = ['@type' => 'ItemList', 'name' => $catPage ? t('biz_cat_title', ['cat' => $catLabel]) : t('biz_title_plain'), 'itemListElement' => $items];
        }
        if (!$catPage && $q === '') {
            $jsonld[] = Seo::faq([
                ['question' => t('biz_faq1_q'), 'answer' => t('biz_faq1_a', ['name' => self::name()])],
                ['question' => t('biz_faq2_q'), 'answer' => t('biz_faq2_a')],
                ['question' => t('biz_faq3_q'), 'answer' => t('biz_faq3_a')],
            ]);
        }
        View::page('negocios', compact('q', 'cat', 'results', 'counts', 'catLabel', 'catPage') + ['suggestion' => $found['suggestion']], [
            'title' => $catPage ? t('meta_biz_cat_title', ['cat' => $catLabel, 'name' => self::name()]) : t('meta_biz_title', ['name' => self::name()]),
            'description' => $catPage ? t('meta_biz_cat_desc', ['cat' => mb_strtolower($catLabel), 'name' => self::name(), 'n' => count($results)]) : t('meta_biz_desc', ['name' => self::name()]),
            'route' => 'business',
            'slug' => $catPage ? 'categoria' . '/' . $cat : '',
            'nav' => 'negocios',
            'jsonld' => $jsonld,
            'robots' => $q !== '' ? 'noindex, follow' : null, // páginas de busca não entram no Google; categorias sim
        ]);
    }

    /** Busca ao vivo (JSON) para a caixa de busca. */
    public static function api(): void
    {
        I18n::set(in_array($_GET['lang'] ?? '', I18n::LANGS, true) ? $_GET['lang'] : 'pt');
        $found = Directory::search(mb_substr((string) ($_GET['q'] ?? ''), 0, 80), (string) ($_GET['cat'] ?? ''));
        $out = [];
        foreach (array_slice($found['results'], 0, 40) as $b) {
            $out[] = self::card($b);
        }
        json_out(['results' => $out, 'suggestion' => $found['suggestion'], 'total' => count($found['results'])]);
    }

    /** Dados de um cartão (usado na página e na busca ao vivo). */
    public static function card(array $b): array
    {
        return [
            'name' => $b['name'],
            'url' => self::url($b),
            'cat' => Directory::categoryLabel($b['category']),
            'icon' => $b['category'],
            'desc' => truncate($b['description'], 120),
            'place' => trim($b['neighborhood'] . ', ' . $b['city'], ', '),
            'online' => str_contains($b['service_mode'], 'online'),
            'home' => str_contains($b['service_mode'], 'domicilio'),
            'logo' => $b['logo'] ? '/uploads/negocios/' . $b['logo'] : null,
            'wa' => whatsapp_link($b['whatsapp'], t('biz_wa_msg', ['biz' => $b['name']])),
            'lat' => $b['lat'] !== null ? (float) $b['lat'] : null,
            'lng' => $b['lng'] !== null ? (float) $b['lng'] : null,
            'featured' => (bool) $b['featured'],
        ];
    }

    // ---------- página do negócio ----------

    public static function show(string $slug): void
    {
        $b = Db::one("SELECT * FROM businesses WHERE slug = ? AND status = 'aprovado'", [$slug]) ?? View::notFound();
        Db::run('UPDATE businesses SET views = views + 1 WHERE id = ?', [$b['id']]);
        $related = Db::all("SELECT * FROM businesses WHERE status = 'aprovado' AND category = ? AND id <> ? ORDER BY featured DESC, RAND() LIMIT 3", [$b['category'], $b['id']]);
        $catLabel = Directory::categoryLabel($b['category']);
        View::page('negocio', compact('b', 'related', 'catLabel'), [
            'title' => t('meta_bizpage_title', ['biz' => $b['name'], 'cat' => $catLabel, 'place' => $b['neighborhood'] ?: $b['city']]),
            'description' => t('meta_bizpage_desc', ['biz' => $b['name'], 'cat' => mb_strtolower($catLabel), 'place' => trim($b['neighborhood'] . ', ' . $b['city'], ', '), 'desc' => truncate($b['description'], 90)]),
            'route' => 'business',
            'slug' => $b['slug'],
            'nav' => 'negocios',
            'jsonld' => [
                Seo::breadcrumb([t('crumbs_home') => I18n::url(), t('nav_business') => I18n::url('business'), $catLabel => self::catUrl($b['category']), $b['name'] => self::url($b)]),
                Seo::business($b),
            ],
        ]);
    }

    // ---------- cadastro e edição ----------

    public static function formToken(): string
    {
        $ts = (string) time();
        return $ts . '.' . substr(hash_hmac('sha256', 'biz' . $ts, self::key()), 0, 32);
    }

    private static function checkToken(string $token): bool
    {
        [$ts, $sig] = array_pad(explode('.', $token, 2), 2, '');
        if (!ctype_digit($ts) || !hash_equals(substr(hash_hmac('sha256', 'biz' . $ts, self::key()), 0, 32), $sig)) {
            return false;
        }
        $age = time() - (int) $ts;
        return $age >= 5 && $age <= 14400;
    }

    public static function form(array $errors = [], array $old = [], ?array $done = null, ?array $editing = null): void
    {
        View::page('negocio-form', ['errors' => $errors, 'old' => $old ?: ($editing ?? []), 'done' => $done, 'editing' => $editing, 'token' => self::formToken()], [
            'title' => t('meta_bizform_title', ['name' => self::name()]),
            'description' => t('meta_bizform_desc', ['name' => self::name()]),
            'route' => 'business',
            'slug' => 'cadastrar',
            'nav' => 'negocios',
            'robots' => ($done || $editing) ? 'noindex' : null,
        ]);
    }

    public static function editForm(string $token): void
    {
        $b = self::byToken($token) ?? View::notFound();
        self::form([], [], null, $b);
    }

    private static function byToken(string $token): ?array
    {
        return preg_match('/^[a-f0-9]{40}$/', $token) ? Db::one('SELECT * FROM businesses WHERE edit_token = ?', [$token]) : null;
    }

    public static function submit(): void
    {
        self::save(null);
    }

    public static function update(string $token): void
    {
        $b = self::byToken($token) ?? View::notFound();
        self::save($b);
    }

    private static function save(?array $existing): void
    {
        if (!same_origin()) {
            http_response_code(403);
            exit;
        }
        $in = fn (string $k, int $max) => mb_substr(trim(mb_scrub((string) ($_POST[$k] ?? ''), 'UTF-8')), 0, $max);
        $old = [
            'name' => $in('name', 120), 'category' => $in('category', 30), 'description' => $in('description', 2000), 'tags' => $in('tags', 400),
            'owner_name' => $in('owner_name', 120), 'whatsapp' => $in('whatsapp', 30), 'instagram' => ltrim($in('instagram', 80), '@'),
            'website' => $in('website', 200), 'email' => $in('email', 160), 'neighborhood' => $in('neighborhood', 80), 'city' => $in('city', 80) ?: 'Palhoça',
            'address' => $in('address', 200), 'show_address' => !empty($_POST['show_address']) ? 1 : 0, 'service_area' => $in('service_area', 160), 'hours' => $in('hours', 160),
            'service_mode' => implode(',', array_intersect(Directory::MODES, (array) ($_POST['service_mode'] ?? []))) ?: 'local',
        ];
        if ($in('website_url', 200) !== '' || !self::checkToken((string) ($_POST['t'] ?? ''))) {
            self::form([], [], ['token' => null]); // robô: finge sucesso
        }
        $errors = [];
        if (mb_strlen($old['name']) < 2) {
            $errors['name'] = t('biz_err_name');
        }
        if (!isset(Directory::CATEGORIES[$old['category']])) {
            $errors['category'] = t('biz_err_category');
        }
        if (mb_strlen($old['description']) < 20) {
            $errors['description'] = t('biz_err_description');
        }
        if (mb_strlen($old['owner_name']) < 3) {
            $errors['owner_name'] = t('biz_err_owner');
        }
        $digits = preg_replace('/\D+/', '', $old['whatsapp']) ?? '';
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            $errors['whatsapp'] = t('help_err_phone');
        }
        if ($old['neighborhood'] === '' && !str_contains($old['service_mode'], 'online')) {
            $errors['neighborhood'] = t('biz_err_place');
        }
        if ($old['website'] !== '' && !preg_match('#^https?://#i', $old['website'])) {
            $old['website'] = 'https://' . $old['website'];
        }
        if ($old['website'] !== '' && !filter_var($old['website'], FILTER_VALIDATE_URL)) {
            $errors['website'] = t('biz_err_site');
        }
        if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = t('biz_err_email');
        }
        if (!$existing && (empty($_POST['member']) || empty($_POST['consent']))) {
            $errors['consent'] = t('biz_err_consent');
        }
        $ipHash = hash('sha256', client_ip() . self::key());
        if (!$existing && !$errors && (int) Db::value('SELECT COUNT(*) FROM businesses WHERE ip_hash = ? AND created_at > NOW() - INTERVAL 1 DAY', [$ipHash]) >= 5) {
            $errors['form'] = t('help_err_limit');
        }
        $logo = null;
        if (!$errors && !empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            $logo = self::storeLogo($_FILES['logo']['tmp_name'], (int) $_FILES['logo']['size']);
            if ($logo === false) {
                $errors['logo'] = t('biz_err_logo');
            }
        }
        if ($errors) {
            self::form($errors, $old, null, $existing);
        }

        $data = $old;
        $data['search_text'] = Directory::searchText($data);
        if ($logo) {
            $data['logo'] = $logo;
            if ($existing && $existing['logo']) {
                @unlink(ROOT . '/public/uploads/negocios/' . basename($existing['logo']));
            }
        }
        if ($existing) {
            $data['needs_review'] = $existing['status'] === 'aprovado' ? 1 : 0;
            Db::update('businesses', (int) $existing['id'], $data);
            $token = $existing['edit_token'];
            $id = (int) $existing['id'];
        } else {
            $data['slug'] = self::uniqueSlug($old['name'] . ' ' . ($old['neighborhood'] ?: $old['city']));
            $data['edit_token'] = bin2hex(random_bytes(20));
            $data['ip_hash'] = $ipHash;
            $id = Db::insert('businesses', $data);
            $token = $data['edit_token'];
        }
        try {
            Notifier::create([
                'title' => $existing ? 'Negócio editado: ' . $old['name'] : 'Novo negócio para aprovar',
                'body' => $old['name'] . ' · ' . Directory::categoryLabel($old['category'], 'pt') . ' · ' . $old['owner_name'],
                'url' => '/admin/negocios/' . $id,
                'audience' => 'equipe', 'source' => 'auto', 'ttl' => 86400,
            ]);
        } catch (\Throwable) {
        }
        self::form([], [], ['token' => $token, 'edited' => (bool) $existing, 'status' => $existing['status'] ?? 'pendente']);
    }

    public static function uniqueSlug(string $base, int $id = 0): string
    {
        $slug = slugify($base);
        $try = $slug;
        for ($i = 2; Db::value('SELECT id FROM businesses WHERE slug = ? AND id <> ?', [$try, $id]); $i++) {
            $try = "$slug-$i";
        }
        return $try;
    }

    /** Recebe a imagem, recorta em quadrado 400x400 e grava em WebP (sem metadados). */
    public static function storeLogo(string $tmp, int $size): string|false
    {
        if ($size > 4 * 1024 * 1024 || !function_exists('imagecreatefromstring')) {
            return false;
        }
        $info = @getimagesize($tmp);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $info[0] > 8000 || $info[1] > 8000) {
            return false;
        }
        $src = @imagecreatefromstring((string) file_get_contents($tmp));
        if (!$src) {
            return false;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $dst = imagecreatetruecolor(400, 400);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, (int) (($w - $side) / 2), (int) (($h - $side) / 2), 400, 400, $side, $side);
        $dir = ROOT . '/public/uploads/negocios';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(10)) . '.webp';
        $ok = imagewebp($dst, "$dir/$name", 80);
        imagedestroy($src);
        imagedestroy($dst);
        return $ok ? $name : false;
    }
}
