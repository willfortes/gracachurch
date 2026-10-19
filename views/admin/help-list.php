<?php
use App\Http\Admin;
use App\View;

$pill = ['novo' => 'warn', 'contato' => 'vi', 'concluido' => 'on'];
?>
<div class="head">
  <div><h1>Pedidos de ajuda</h1><p>Pessoas que pediram contato, oração ou ajuda pelo site. Responda pelo WhatsApp e marque o andamento para a equipe saber quem já foi atendido.</p></div>
  <div class="actions">
    <a class="btn btn-ghost" href="/quero-ajuda" target="_blank" rel="noopener"><?= View::icon('external', 18) ?>Ver formulário</a>
    <button class="btn btn-primary" type="button" data-team-push><?= View::icon('bell', 18) ?>Alertar neste aparelho</button>
  </div>
</div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>
<p class="hint" data-team-status style="margin:-10px 0 18px"><?= $teamSubs ? $teamSubs . ' aparelho(s) da equipe recebem alerta a cada pedido novo.' : 'Nenhum aparelho da equipe recebe alertas ainda. Toque em "Alertar neste aparelho" no celular de quem atende.' ?></p>

<div class="actions" style="justify-content:flex-start;margin-bottom:16px">
  <a class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-ghost' ?>" href="/admin/ajuda">Todos (<?= array_sum($counts) ?>)</a>
  <?php foreach (Admin::HELP_STATUS as $k => $label): ?>
  <a class="btn btn-sm <?= $status === $k ? 'btn-primary' : 'btn-ghost' ?>" href="/admin/ajuda?status=<?= $k ?>"><?= e($label) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</a>
  <?php endforeach; ?>
</div>

<?php if (!$rows): ?>
  <div class="empty">Nenhum pedido <?= $status ? 'com este status' : 'ainda' ?>.</div>
<?php else: ?>
<div class="table-wrap">
  <table>
    <thead><tr><th>Pessoa</th><th>Pedido</th><th>Recebido</th><th>Situação</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $h): ?>
      <tr>
        <td><a href="/admin/ajuda/<?= (int) $h['id'] ?>"><b><?= e($h['name']) ?></b></a><span class="sub"><?= e(phone_display($h['phone'])) ?><?= $h['lang'] !== 'pt' ? ' · ' . strtoupper($h['lang']) : '' ?></span></td>
        <td style="max-width:420px"><?= e(truncate($h['message'], 140)) ?></td>
        <td style="white-space:nowrap"><?= e(date('d/m/Y H:i', strtotime($h['created_at']))) ?></td>
        <td><span class="pill <?= $pill[$h['status']] ?>"><?= e(Admin::HELP_STATUS[$h['status']]) ?></span><?php if ($h['handler']): ?><span class="sub"><?= e($h['handler']) ?></span><?php endif; ?></td>
        <td><div class="actions">
          <a class="btn btn-sm" style="background:#1f8f4e;color:#fff" href="<?= e(whatsapp_link($h['phone'], 'Olá, ' . explode(' ', $h['name'])[0] . '! Aqui é da Graça Church. Recebemos seu pedido pelo site e queremos te ajudar.')) ?>" target="_blank" rel="noopener"><?= View::icon('whatsapp', 16) ?>Responder</a>
          <a class="btn btn-ghost btn-sm" href="/admin/ajuda/<?= (int) $h['id'] ?>">Abrir</a>
        </div></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
