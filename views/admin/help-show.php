<?php
use App\Auth;
use App\Http\Admin;
use App\View;

$first = explode(' ', $h['name'])[0];
?>
<div class="head">
  <div><p><a href="/admin/ajuda">← Pedidos de ajuda</a></p><h1 style="margin-top:8px"><?= e($h['name']) ?></h1><p>Recebido em <?= e(date('d/m/Y \à\s H:i', strtotime($h['created_at']))) ?><?= $h['lang'] !== 'pt' ? ' · enviado em ' . strtoupper($h['lang']) : '' ?></p></div>
  <a class="btn btn-primary" style="background:#1f8f4e" href="<?= e(whatsapp_link($h['phone'], 'Olá, ' . $first . '! Aqui é da Graça Church. Recebemos seu pedido pelo site e queremos te ajudar.')) ?>" target="_blank" rel="noopener"><?= View::icon('whatsapp', 18) ?>Responder no WhatsApp</a>
</div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>
<div class="grid g2" style="align-items:start">
  <section class="card">
    <h2>Pedido</h2>
    <p style="white-space:pre-wrap"><?= e($h['message']) ?></p>
    <?php if ($h['extra']): ?><h2 style="margin-top:20px">Outras perguntas</h2><p style="white-space:pre-wrap"><?= e($h['extra']) ?></p><?php endif; ?>
    <ul class="list" style="margin-top:20px">
      <li><span>WhatsApp</span><b><?= e(phone_display($h['phone'])) ?></b></li>
      <li><span>Autorizou contato</span><b><?= $h['consent'] ? 'Sim' : 'Não' ?></b></li>
    </ul>
  </section>
  <form class="card form" method="post" action="/admin/ajuda/<?= (int) $h['id'] ?>/salvar">
    <h2>Acompanhamento</h2>
    <?= Auth::csrfField() ?>
    <label class="f">Situação
      <select name="status" id="h-status">
        <?php foreach (Admin::HELP_STATUS as $k => $label): ?><option value="<?= $k ?>"<?= $h['status'] === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="f">Anotações da equipe<small>Só a equipe vê. Ex.: quem ligou, o que foi combinado.</small><textarea name="notes" maxlength="4000" id="h-notes"><?= e($h['notes']) ?></textarea></label>
    <?php if ($h['handler']): ?><p class="hint">Última atualização por <?= e($h['handler']) ?> em <?= e(date('d/m H:i', strtotime($h['updated_at']))) ?>.</p><?php endif; ?>
    <button class="btn btn-primary" type="submit">Salvar</button>
  </form>
</div>
<form method="post" action="/admin/ajuda/<?= (int) $h['id'] ?>/excluir" data-confirm="Excluir este pedido? Os dados da pessoa serão apagados." style="margin-top:24px">
  <?= Auth::csrfField() ?><button class="btn btn-danger btn-sm" type="submit"><?= View::icon('trash', 16) ?>Excluir pedido (a pedido da pessoa ou após atendimento)</button>
</form>
