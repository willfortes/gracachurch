<?php
use App\Auth;
use App\Content;
use App\Schedule;
use App\View;
?>
<div class="head">
  <div><h1>Programação</h1><p>Cultos, encontros de ministérios e eventos especiais. O que está aqui aparece no site, no app e gera os lembretes automáticos.</p></div>
  <a class="btn btn-primary" href="/admin/programacao/novo"><?= View::icon('plus', 18) ?>Nova programação</a>
</div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>
<?php if (!$rows): ?>
  <div class="empty">Nenhuma programação cadastrada. <a href="/admin/programacao/novo">Cadastrar a primeira</a>.</div>
<?php else: ?>
<div class="table-wrap">
  <table>
    <thead><tr><th>Nome</th><th>Quando</th><th>Próxima</th><th>Lembrete</th><th>Situação</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="/admin/programacao/<?= (int) $r['id'] ?>"><b><?= e($r['title']) ?></b></a><span class="sub"><?= e(Content::CATEGORIES[$r['category']] ?? '') ?></span></td>
        <td><?= e(Schedule::describe($r)) ?></td>
        <td><?= $r['next'] ? e(Schedule::shortDate($r['next']['start'])) : '<span class="pill off">já passou</span>' ?></td>
        <?php $m = (int) $r['reminder_min']; $ml = $m < 60 ? "$m min" : (intdiv($m, 60) . 'h' . ($m % 60 ? sprintf('%02d', $m % 60) : '')); ?>
        <td><?= $r['notify'] ? e($ml) . ' antes' : '<span class="pill off">desligado</span>' ?></td>
        <td><span class="pill <?= $r['published'] ? 'on' : 'off' ?>"><?= $r['published'] ? 'no site' : 'oculto' ?></span></td>
        <td>
          <div class="actions">
            <a class="btn btn-ghost btn-sm" href="/admin/programacao/<?= (int) $r['id'] ?>"><?= View::icon('edit', 16) ?>Editar</a>
            <form class="inline" method="post" action="/admin/programacao/<?= (int) $r['id'] ?>/excluir" data-confirm="Excluir “<?= e($r['title']) ?>”? Isso não pode ser desfeito.">
              <?= Auth::csrfField() ?><button class="btn btn-danger btn-sm" type="submit" aria-label="Excluir <?= e($r['title']) ?>"><?= View::icon('trash', 16) ?></button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
