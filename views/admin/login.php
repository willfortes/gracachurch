<?php use App\Auth; ?>
<div class="login">
  <div class="login-logo"><img src="/img/logo-claro.png" width="180" height="73" alt="Graça Church"></div>
  <form class="card form" method="post" action="/admin/login<?= isset($_GET['next']) ? '?next=' . e(rawurlencode((string) $_GET['next'])) : '' ?>">
    <h1 style="font-size:1.4rem">Entrar no painel</h1>
    <?= App\View::render('admin/_flash', ['flash' => $error ?? null]) ?>
    <?= Auth::csrfField() ?>
    <label class="f">E-mail<input type="email" name="email" required autocomplete="username" autofocus id="email"></label>
    <label class="f">Senha<input type="password" name="password" required autocomplete="current-password" id="password"></label>
    <button class="btn btn-primary" type="submit">Entrar</button>
    <p class="hint">Esqueceu a senha? Peça para outro administrador criar uma nova em Usuários.</p>
  </form>
</div>
