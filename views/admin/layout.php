<?php
use App\Auth;
use App\View;

$items = [
    'home' => ['/admin', 'home', 'Visão geral'],
    'events' => ['/admin/programacao', 'calendar', 'Programação'],
    'groups' => ['/admin/grupos', 'people', 'Grupos de convívio'],
    'help' => ['/admin/ajuda', 'hands', 'Pedidos de ajuda'],
    'biz' => ['/admin/negocios', 'share', 'Guia de negócios'],
    'notifications' => ['/admin/avisos', 'bell', 'Avisos'],
    'faqs' => ['/admin/perguntas', 'help', 'Perguntas frequentes'],
    'settings' => ['/admin/configuracoes', 'settings', 'Configurações'],
    'users' => ['/admin/usuarios', 'user', 'Usuários'],
];
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Painel Graça Church</title>
<link rel="icon" href="/favicon.ico">
<style><?= file_get_contents(ROOT . '/views/partials/admin.css') ?></style>
<meta name="vapid-key" content="<?= e(env('VAPID_PUBLIC_KEY', '')) ?>">
<?php if ($user): ?><meta name="csrf" content="<?= e(Auth::csrfToken()) ?>"><?php endif; ?>
<script src="<?= asset('/js/admin.js') ?>" defer></script>
</head>
<body class="<?= $user ? 'in' : 'out' ?>">
<?php if ($user): ?>
<aside class="side" id="menu">
  <a class="brand" href="/admin"><img src="/img/logo-claro.png" width="120" height="48" alt="Graça Church"><span>Painel</span></a>
  <nav>
    <?php foreach ($items as $key => [$href, $icon, $label]): ?>
    <a href="<?= $href ?>"<?= $nav === $key ? ' aria-current="page"' : '' ?>><?= View::icon($icon, 20) ?><?= e($label) ?><?php if ($key === 'help' && ($newHelp = (int) App\Db::value("SELECT COUNT(*) FROM help_requests WHERE status = 'novo'"))): ?><span class="badge"><?= $newHelp ?></span><?php endif; ?><?php if ($key === 'biz' && ($pend = (int) App\Db::value("SELECT COUNT(*) FROM businesses WHERE status = 'pendente' OR needs_review = 1"))): ?><span class="badge"><?= $pend ?></span><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="side-foot">
    <a href="/" target="_blank" rel="noopener"><?= View::icon('external', 18) ?>Ver o site</a>
    <form method="post" action="/admin/logout"><?= Auth::csrfField() ?><button type="submit"><?= View::icon('logout', 18) ?>Sair (<?= e(explode(' ', $user['name'])[0]) ?>)</button></form>
  </div>
</aside>
<header class="mbar">
  <a href="/admin"><img src="/img/logo-claro.png" width="100" height="40" alt="Graça Church"></a>
  <button type="button" class="menu-btn" data-menu aria-expanded="false" aria-controls="menu">Menu</button>
</header>
<?php endif; ?>
<main class="main">
  <?= $content ?>
</main>
</body>
</html>
