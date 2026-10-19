<?php
use App\Directory;
use App\Http\Admin;
use App\View;
?>
<div class="head">
  <div><h1>Guia de negócios</h1><p>Empresas e serviços cadastrados pelos membros. Revise antes de aprovar: o que estiver "No ar" aparece no site, no Google e nas buscas por IA.</p></div>
  <div class="actions">
    <a class="btn btn-ghost" href="/negocios" target="_blank" rel="noopener"><?= View::icon('external', 18) ?>Ver o guia</a>
    <a class="btn btn-ghost" href="/negocios/cadastrar" target="_blank" rel="noopener"><?= View::icon('plus', 18) ?>Formulário de cadastro</a>
  </div>
</div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>
<?php if ($review): ?><div class="alert"><b><?= $review ?> negócio(s) no ar foram editados pelo dono.</b> Eles aparecem primeiro na aba "No ar" com o selo "editado".</div><?php endif; ?>

<div class="actions" style="justify-content:flex-start;margin-bottom:16px">
  <?php foreach (Admin::BIZ_STATUS as $k => $label): ?>
  <a class="btn btn-sm <?= $status === $k ? 'btn-primary' : 'btn-ghost' ?>" href="/admin/negocios?status=<?= $k ?>"><?= e($label) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</a>
  <?php endforeach; ?>
</div>

<?php if (!$rows): ?>
  <div class="empty"><?= $status === 'pendente' ? 'Nenhum cadastro esperando aprovação.' : 'Nada por aqui.' ?></div>
<?php else: ?>
<div class="table-wrap">
  <table>
    <thead><tr><th>Negócio</th><th>Categoria</th><th>Responsável</th><th>Recebido</th><th>Visitas</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $b): ?>
      <tr>
        <td><a href="/admin/negocios/<?= (int) $b['id'] ?>"><b><?= e($b['name']) ?></b></a><?= $b['needs_review'] ? ' <span class="pill warn">editado</span>' : '' ?><?= $b['featured'] ? ' <span class="pill vi">destaque</span>' : '' ?><span class="sub"><?= e(trim($b['neighborhood'] . ', ' . $b['city'], ', ')) ?></span></td>
        <td><?= e(Directory::categoryLabel($b['category'], 'pt')) ?></td>
        <td><?= e($b['owner_name']) ?><span class="sub"><?= e(phone_display($b['whatsapp'])) ?></span></td>
        <td style="white-space:nowrap"><?= e(date('d/m/Y', strtotime($b['created_at']))) ?></td>
        <td><?= (int) $b['views'] ?></td>
        <td><div class="actions"><a class="btn btn-primary btn-sm" href="/admin/negocios/<?= (int) $b['id'] ?>"><?= $status === 'pendente' ? 'Revisar' : 'Editar' ?></a></div></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
