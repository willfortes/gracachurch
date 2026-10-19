<?php
use App\Schedule;
use App\View;

$cronOk = $cronLast && (time() - strtotime($cronLast)) < 300;
?>
<div class="head">
  <div>
    <h1>Visão geral</h1>
    <p>O que vem por aí e como estão os avisos.</p>
  </div>
  <div class="actions">
    <a class="btn btn-ghost" href="/admin/programacao/novo"><?= View::icon('plus', 18) ?>Programação</a>
    <a class="btn btn-ghost" href="/admin/grupos/novo"><?= View::icon('plus', 18) ?>Grupo</a>
    <a class="btn btn-primary" href="/admin/avisos"><?= View::icon('send', 18) ?>Enviar aviso</a>
  </div>
</div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>

<?php if ($newHelp = (int) App\Db::value("SELECT COUNT(*) FROM help_requests WHERE status = 'novo'")): ?>
<div class="alert" style="background:#fde9e7;color:#8f1d12"><b><?= $newHelp ?> pedido(s) de ajuda esperando contato.</b> <a href="/admin/ajuda?status=novo">Ver pedidos</a></div>
<?php endif; ?>
<?php if (!$pushReady): ?>
<div class="alert"><b>Avisos push desligados.</b> Rode <code>php bin/vapid.php</code> no servidor e copie as duas chaves para o arquivo <code>.env</code>.</div>
<?php elseif (!$cronOk): ?>
<div class="alert"><b>O agendador não está rodando<?= $cronLast ? ' (última vez: ' . e(date('d/m H:i', strtotime($cronLast))) . ')' : '' ?>.</b> Sem ele os lembretes automáticos não saem. Configure no servidor: <code>* * * * * php <?= e(ROOT) ?>/bin/cron.php</code></div>
<?php endif; ?>

<div class="stats">
  <div class="stat"><b><?= $stats['subs'] ?></b><span>aparelhos recebendo avisos</span></div>
  <div class="stat"><b><?= $stats['sent30'] ?></b><span>avisos entregues em 30 dias</span></div>
  <div class="stat"><b><?= $stats['events'] ?></b><span>programações no ar</span></div>
  <div class="stat"><b><?= $stats['groups'] ?></b><span>grupos de convívio no ar</span></div>
</div>

<div class="grid g2">
  <section class="card">
    <h2>Próximos encontros</h2>
    <?php if (!$upcoming): ?><p class="hint">Nada nos próximos 15 dias.</p><?php endif; ?>
    <ul class="list">
      <?php foreach ($upcoming as $u): ?>
      <li>
        <span><a href="<?= e($u['url']) ?>"><b><?= e($u['title']) ?></b></a><span class="hint" style="display:block"><?= e($u['kind']) ?> · <?= e(Schedule::relative($u['start'], $now)) ?></span></span>
        <?php if ($u['notify']): $at = $u['start']->modify('-' . $u['reminder'] . ' minutes'); ?>
          <span class="pill <?= $at <= $now ? 'off' : 'vi' ?>" title="Lembrete automático"><?= $at <= $now ? 'lembrete enviado' : 'lembrete ' . e(Schedule::time($at)) . ($at->format('Y-m-d') !== $now->format('Y-m-d') ? ' ' . $at->format('d/m') : '') ?></span>
        <?php else: ?>
          <span class="pill off">sem lembrete</span>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="card">
    <h2>Últimos avisos</h2>
    <?php if (!$recent): ?><p class="hint">Nenhum aviso enviado ainda.</p><?php endif; ?>
    <ul class="list">
      <?php foreach ($recent as $n): ?>
      <li>
        <span><b><?= e($n['title']) ?></b><span class="hint" style="display:block"><?= $n['source'] === 'auto' ? 'Lembrete automático' : 'Enviado pela equipe' ?> · <?= e(date('d/m H:i', strtotime($n['created_at']))) ?></span></span>
        <span class="pill <?= $n['status'] === 'done' ? 'on' : 'warn' ?>"><?= $n['status'] === 'done' ? $n['sent'] . '/' . $n['total'] . ' entregues' : 'enviando' ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
    <p style="margin-top:14px"><a href="/admin/avisos">Ver todos os avisos</a></p>
  </section>
</div>
