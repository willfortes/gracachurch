<?php use App\Auth; ?>
<div class="login">
  <div class="login-logo"><img src="/img/logo-claro.png" width="180" height="73" alt="Graça Church"></div>
  <form class="card form" method="post" action="/admin/setup">
    <div>
      <h1 style="font-size:1.4rem">Criar o primeiro administrador</h1>
      <p class="hint" style="margin-top:6px">Isto só aparece uma vez. Depois, novos usuários são criados dentro do painel.</p>
    </div>
    <?= App\View::render('admin/_flash', ['flash' => $error ?? null]) ?>
    <?php if (!$hasToken): ?>
      <div class="alert">Defina <code>SETUP_TOKEN</code> no arquivo <code>.env</code> do servidor para liberar este passo, ou crie o usuário pelo terminal com <code>php bin/create-admin.php</code>.</div>
    <?php endif; ?>
    <?= Auth::csrfField() ?>
    <label class="f">Código de configuração<small>O valor de SETUP_TOKEN no .env</small><input type="password" name="token" required id="token"></label>
    <label class="f">Seu nome<input type="text" name="name" required maxlength="120" autocomplete="name" id="name"></label>
    <label class="f">E-mail<input type="email" name="email" required autocomplete="username" id="email"></label>
    <label class="f">Senha<small>Pelo menos 10 caracteres</small><input type="password" name="password" required minlength="10" autocomplete="new-password" id="password"></label>
    <button class="btn btn-primary" type="submit"<?= $hasToken ? '' : ' disabled' ?>>Criar e entrar</button>
  </form>
</div>
