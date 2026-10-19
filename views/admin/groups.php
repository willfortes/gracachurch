<?php
use App\Auth;
use App\Schedule;
use App\View;
?>
<div class="head">
  <div><h1>Grupos de convívio</h1><p>Cada grupo ganha uma página própria, aparece no mapa e pode mandar lembretes para quem segue o grupo.</p></div>
  <a class="btn btn-primary" href="/admin/grupos/novo"><?= View::icon('plus', 18) ?>Novo grupo</a>
</div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>
<?php if (!$rows): ?>
  <div class="empty">Nenhum grupo cadastrado. <a href="/admin/grupos/novo">Cadastrar o primeiro</a>.</div>
<?php else: ?>
<div class="table-wrap">
  <table>
    <thead><tr><th>Grupo</th><th>Quando</th><th>Líder</th><th>No mapa</th><th>Seguidores</th><th>Situação</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="/admin/grupos/<?= (int) $r['id'] ?>"><b><?= e($r['name']) ?></b></a><span class="sub"><?= e($r['neighborhood']) ?>, <?= e($r['city']) ?> · <?= e($r['audience']) ?></span></td>
        <td><?= e(Schedule::describe($r)) ?></td>
        <td><?= e($r['leader_name'] ?? '—') ?><?php if ($r['leader_phone']): ?><span class="sub"><?= e(phone_display($r['leader_phone'])) ?></span><?php endif; ?></td>
        <td><?= $r['lat'] !== null ? '<span class="pill on">sim</span>' : '<span class="pill warn">sem local</span>' ?></td>
        <td><?= (int) ($counts['grupo:' . $r['id']] ?? 0) ?></td>
        <td><span class="pill <?= $r['published'] ? 'on' : 'off' ?>"><?= $r['published'] ? 'no site' : 'oculto' ?></span></td>
        <td>
          <div class="actions">
            <a class="btn btn-ghost btn-sm" href="/admin/grupos/<?= (int) $r['id'] ?>"><?= View::icon('edit', 16) ?>Editar</a>
            <form class="inline" method="post" action="/admin/grupos/<?= (int) $r['id'] ?>/excluir" data-confirm="Excluir o grupo “<?= e($r['name']) ?>”? Isso não pode ser desfeito.">
              <?= Auth::csrfField() ?><button class="btn btn-danger btn-sm" type="submit" aria-label="Excluir <?= e($r['name']) ?>"><?= View::icon('trash', 16) ?></button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
