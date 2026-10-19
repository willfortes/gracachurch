<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Http\Admin;
use App\Http\Api;
use App\Http\Discovery;
use App\Http\Business;
use App\Http\Help;
use App\Http\PublicPages;
use App\I18n;
use App\Router;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$query = ($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';

// Endereços do site antigo
$legacy = ['/queroajuda' => '/quero-ajuda', '/mapagcs' => '/grupos', '/mapaGC.html' => '/grupos'];
if (isset($legacy[rtrim($path, '/')])) {
    redirect($legacy[rtrim($path, '/')], 301);
}

// Endereços com barra no final viram a versão sem barra (uma única URL por página para o SEO)
if ($path !== '/' && str_ends_with($path, '/')) {
    redirect(rtrim($path, '/') . $query, 301);
}

[$lang, $rest] = I18n::split($path);

// Primeira visita na página inicial: segue o idioma do navegador (inglês ou espanhol)
if ($path === '/') {
    header('Vary: Accept-Language, Cookie');
    $chosen = $_COOKIE['gc_lang'] ?? I18n::fromBrowser();
    if (in_array($chosen, ['en', 'es'], true)) {
        redirect('/' . $chosen . $query, 302);
    }
}
I18n::set($lang);

$r = new Router();
$s = fn (string $route) => '/' . I18n::seg($route);

// Site (rotas no idioma atual)
$r->get('/', [PublicPages::class, 'home']);
$r->get($s('schedule'), [PublicPages::class, 'programacao']);
$r->get($s('event') . '/{slug}.ics', [PublicPages::class, 'eventoIcs']);
$r->get($s('event') . '/{slug}', [PublicPages::class, 'evento']);
$r->get($s('groups'), [PublicPages::class, 'grupos']);
$r->get($s('groups') . '/{slug}', [PublicPages::class, 'grupo']);
$r->get($s('directions'), [PublicPages::class, 'comoChegar']);
$r->get($s('about'), [PublicPages::class, 'sobre']);
$r->get($s('alerts'), [PublicPages::class, 'avisos']);
$r->get($s('offline'), [PublicPages::class, 'offline']);
$r->get($s('help'), fn () => Help::form());
$r->post($s('help'), [Help::class, 'submit']);
$r->get($s('business'), [Business::class, 'index']);
$r->get($s('business') . '/cadastrar', fn () => Business::form());
$r->post($s('business') . '/cadastrar', [Business::class, 'submit']);
$r->get($s('business') . '/editar/{token}', [Business::class, 'editForm']);
$r->post($s('business') . '/editar/{token}', [Business::class, 'update']);
$r->get($s('business') . '/categoria/{slug}', [Business::class, 'category']);
$r->get($s('business') . '/{slug}', [Business::class, 'show']);

if ($lang === 'pt') {
    // Buscadores e IA
    $r->get('/sitemap.xml', [Discovery::class, 'sitemap']);
    $r->get('/robots.txt', [Discovery::class, 'robots']);
    $r->get('/llms.txt', [Discovery::class, 'llms']);

    // API dos avisos
    $r->post('/api/push/subscribe', [Api::class, 'subscribe']);
    $r->post('/api/push/unsubscribe', [Api::class, 'unsubscribe']);
    $r->get('/api/push/key', [Api::class, 'vapid']);
    $r->get('/api/negocios', [Business::class, 'api']);

    // Painel
    $r->get('/admin', [Admin::class, 'dashboard']);
    $r->get('/admin/login', [Admin::class, 'loginForm']);
    $r->post('/admin/login', [Admin::class, 'login']);
    $r->post('/admin/logout', [Admin::class, 'logout']);
    $r->get('/admin/setup', [Admin::class, 'setupForm']);
    $r->post('/admin/setup', [Admin::class, 'setup']);

    $r->get('/admin/programacao', [Admin::class, 'events']);
    $r->post('/admin/programacao/salvar', [Admin::class, 'eventSave']);
    $r->post('/admin/programacao/{id}/excluir', [Admin::class, 'eventDelete']);
    $r->get('/admin/programacao/{id}', [Admin::class, 'eventForm']);

    $r->get('/admin/grupos', [Admin::class, 'groups']);
    $r->post('/admin/grupos/salvar', [Admin::class, 'groupSave']);
    $r->post('/admin/grupos/{id}/excluir', [Admin::class, 'groupDelete']);
    $r->get('/admin/grupos/{id}', [Admin::class, 'groupForm']);

    $r->get('/admin/avisos', [Admin::class, 'notifications']);
    $r->post('/admin/avisos/enviar', [Admin::class, 'notificationSend']);

    $r->get('/admin/ajuda', [Admin::class, 'helpList']);
    $r->get('/admin/ajuda/{id}', [Admin::class, 'helpShow']);
    $r->post('/admin/ajuda/{id}/salvar', [Admin::class, 'helpSave']);
    $r->post('/admin/ajuda/{id}/excluir', [Admin::class, 'helpDelete']);
    $r->post('/admin/alertas-equipe', [Admin::class, 'teamPush']);

    $r->get('/admin/negocios', [Admin::class, 'bizList']);
    $r->get('/admin/negocios/{id}', [Admin::class, 'bizForm']);
    $r->post('/admin/negocios/{id}/salvar', [Admin::class, 'bizSave']);
    $r->post('/admin/negocios/{id}/excluir', [Admin::class, 'bizDelete']);

    $r->get('/admin/perguntas', [Admin::class, 'faqs']);
    $r->post('/admin/perguntas/salvar', [Admin::class, 'faqSave']);
    $r->post('/admin/perguntas/{id}/excluir', [Admin::class, 'faqDelete']);

    $r->get('/admin/configuracoes', [Admin::class, 'settings']);
    $r->post('/admin/configuracoes', [Admin::class, 'settingsSave']);

    $r->get('/admin/usuarios', [Admin::class, 'users']);
    $r->post('/admin/usuarios/salvar', [Admin::class, 'userSave']);
    $r->post('/admin/usuarios/senha', [Admin::class, 'passwordSave']);
    $r->post('/admin/usuarios/{id}/excluir', [Admin::class, 'userDelete']);
}

$r->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $rest);
