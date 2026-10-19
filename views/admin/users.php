<?php
use App\Auth;
use App\View;
?>
<div class="head"><div><h1>Usuários do painel</h1><p>Pessoas que podem cadastrar programação, grupos e enviar avisos.</p></div></div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>
<div class="table-wrap" style="margin-bottom:22px">
  <table>
    <thead><tr><th>Nome</th><th>E-mail</th><th>Último acesso</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $u): ?>
      <tr>
        <td><b><?= e($u['name']) ?></b><?= (int) $u['id'] === (int) $me['id'] ? ' <span class="pill vi">você</span>' : '' ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= $u['last_login_at'] ? e(date('d/m/Y H:i', strtotime($u['last_login_at']))) : 'nunca' ?></td>
        <td><div class="actions">
          <?php if ((int) $u['id'] !== (int) $me['id']): ?>
          <form class="inline" method="post" action="/admin/usuarios/<?= (int) $u['id'] ?>/excluir" data-confirm="Remover o acesso de <?= e($u['name']) ?>?">
            <?= Auth::csrfField() ?><button class="btn btn-danger btn-sm" type="submit">Remover</button>
          </form>
          <?php endif; ?>
        </div></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="grid g2" style="align-items:start">
  <form class="card form" method="post" action="/admin/usuarios/salvar">
    <h2>Adicionar pessoa</h2>
    <?= Auth::csrfField() ?>
    <label class="f">Nome<input type="text" name="name" required maxlength="120" id="u-name"></label>
    <label class="f">E-mail<input type="email" name="email" required id="u-email" autocomplete="off"></label>
    <label class="f">Senha inicial<small>Pelo menos 10 caracteres</small><input type="password" name="password" required minlength="10" autocomplete="new-password" id="u-pass"></label>
    <button class="btn btn-primary" type="submit">Criar acesso</button>
  </form>
  <form class="card form" method="post" action="/admin/usuarios/senha">
    <h2>Trocar minha senha</h2>
    <?= Auth::csrfField() ?>
    <label class="f">Senha atual<input type="password" name="current_password" required autocomplete="current-password" id="p-cur"></label>
    <label class="f">Nova senha<small>Pelo menos 10 caracteres</small><input type="password" name="new_password" required minlength="10" autocomplete="new-password" id="p-new"></label>
    <button class="btn btn-primary" type="submit">Trocar senha</button>
  </form>
</div>
