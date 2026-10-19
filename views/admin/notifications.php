<?php
use App\Auth;
use App\Notifier;
use App\View;

$pages = ['/' => 'Página inicial', '/programacao' => 'Programação', '/grupos' => 'Grupos de convívio', '/como-chegar' => 'Como chegar'];
?>
<div class="head">
  <div><h1>Avisos</h1><p>Mande uma mensagem para o celular e o computador de quem ativou os avisos. Os lembretes antes de cada encontro saem sozinhos.</p></div>
</div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>
<?php if (!$pushReady): ?>
<div class="alert"><b>Avisos push ainda não configurados.</b> Rode <code>php bin/vapid.php</code> e coloque as chaves no <code>.env</code>.</div>
<?php endif; ?>

<div class="grid g2" style="align-items:start">
  <form class="card form" method="post" action="/admin/avisos/enviar">
    <h2>Novo aviso</h2>
    <?= Auth::csrfField() ?>
    <label class="f">Título<small>Curto e direto. Ex.: Culto especial hoje às 19h30</small>
      <input type="text" name="title" required maxlength="80" data-preview data-count="c-title" id="n-title"><span class="counter" id="c-title"></span></label>
    <label class="f">Mensagem
      <textarea name="body" required maxlength="200" data-preview data-count="c-body" style="min-height:90px" id="n-body"></textarea><span class="counter" id="c-body"></span></label>
    <?= View::render('admin/_translations', ['values' => [], 'fields' => ['title' => ['Título', 'text', 80], 'body' => ['Mensagem', 'textarea', 200]]]) ?>
    <div class="row">
      <label class="f">Para quem
        <select name="audience" data-counts="<?= e(json_encode($counts)) ?>" id="n-audience">
          <?php foreach ($audiences as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?> (<?= (int) $counts[$k] ?>)</option><?php endforeach; ?>
        </select>
        <span class="hint" id="aud-count"></span>
      </label>
      <label class="f">Ao tocar, abre
        <select name="url" id="n-url">
          <?php foreach ($pages as $u => $l): ?><option value="<?= e($u) ?>"><?= e($l) ?></option><?php endforeach; ?>
        </select>
      </label>
    </div>
    <div>
      <p class="hint" style="margin-bottom:8px">Prévia</p>
      <div class="preview"><img src="/icons/icon-192.png" width="40" height="40" alt=""><div><b id="pv-title"></b><span id="pv-body"></span></div></div>
    </div>
    <button class="btn btn-primary" type="submit"<?= $pushReady ? '' : ' disabled' ?>><?= View::icon('send', 18) ?>Enviar agora</button>
  </form>

  <section class="card">
    <h2>Quem recebe</h2>
    <ul class="list">
      <?php foreach (Notifier::AUDIENCES as $k => $label): ?>
      <li><span><?= e($label) ?></span><b><?= (int) $counts[$k] ?></b></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($platforms): ?>
    <h2 style="margin-top:22px">Aparelhos</h2>
    <ul class="list">
      <?php $pn = ['ios' => 'iPhone / iPad', 'android' => 'Android', 'desktop' => 'Windows / Linux', 'mac' => 'Mac', 'outro' => 'Outros']; ?>
      <?php foreach ($platforms as $p): ?><li><span><?= e($pn[$p['p']] ?? $p['p']) ?></span><b><?= (int) $p['n'] ?></b></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
</div>

<h2 style="margin:32px 0 14px;font-size:1.2rem">Histórico</h2>
<?php if (!$history): ?>
  <div class="empty">Nenhum aviso ainda.</div>
<?php else: ?>
<div class="table-wrap">
  <table>
    <thead><tr><th>Aviso</th><th>Público</th><th>Origem</th><th>Quando</th><th>Entrega</th></tr></thead>
    <tbody>
    <?php foreach ($history as $n): ?>
      <tr>
        <td><b><?= e($n['title']) ?></b><span class="sub"><?= e($n['body']) ?></span></td>
        <td><?= e($audiences[$n['audience']] ?? $n['audience']) ?></td>
        <td><?= $n['source'] === 'auto' ? 'Lembrete automático' : e($n['author'] ?? 'Equipe') ?></td>
        <td style="white-space:nowrap"><?= e(date('d/m/Y H:i', strtotime($n['created_at']))) ?></td>
        <td>
          <span class="pill <?= $n['status'] === 'done' ? 'on' : 'warn' ?>"><?= $n['status'] === 'done' ? 'concluído' : 'enviando' ?></span>
          <span class="sub"><?= (int) $n['sent'] ?> de <?= (int) $n['total'] ?> entregues<?= $n['failed'] ? ', ' . (int) $n['failed'] . ' falharam' : '' ?></span>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
