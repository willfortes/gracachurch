<?php
use App\Auth;
use App\View;
?>
<div class="head">
  <div><h1>Perguntas frequentes</h1><p>Aparecem no site e são lidas pelo Google e por assistentes como ChatGPT e Gemini. Escreva a resposta completa logo na primeira frase.</p></div>
</div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>

<div class="grid" style="gap:14px">
  <?php foreach ([...$rows, ['id' => 0, 'question' => '', 'answer' => '', 'sort' => count($rows) + 1, 'published' => 1]] as $f): ?>
  <form class="card form" method="post" action="/admin/perguntas/salvar">
    <?php if (!$f['id']): ?><h2>Nova pergunta</h2><?php endif; ?>
    <?= Auth::csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
    <label class="f">Pergunta<input type="text" name="question" value="<?= e($f['question']) ?>" maxlength="200" required id="q-<?= (int) $f['id'] ?>"></label>
    <label class="f">Resposta<textarea name="answer" maxlength="2000" required id="a-<?= (int) $f['id'] ?>" style="min-height:80px"><?= e($f['answer']) ?></textarea></label>
    <?= View::render('admin/_translations', ['values' => $f['tr'] ?? [], 'idSuffix' => (int) $f['id'], 'fields' => ['question' => ['Pergunta', 'text', 200], 'answer' => ['Resposta', 'textarea', 2000]]]) ?>
    <div class="row" style="align-items:end">
      <label class="f">Ordem<input type="number" name="sort" value="<?= (int) $f['sort'] ?>" id="s-<?= (int) $f['id'] ?>"></label>
      <label class="switch"><input type="checkbox" name="published" value="1"<?= $f['published'] ? ' checked' : '' ?>><span>Mostrar no site</span></label>
      <div class="actions" style="justify-content:flex-start">
        <button class="btn btn-primary btn-sm" type="submit"><?= $f['id'] ? 'Salvar' : 'Adicionar' ?></button>
        <?php if ($f['id']): ?>
        <button class="btn btn-danger btn-sm" type="submit" formaction="/admin/perguntas/<?= (int) $f['id'] ?>/excluir" formnovalidate data-confirm-btn>Excluir</button>
        <?php endif; ?>
      </div>
    </div>
  </form>
  <?php endforeach; ?>
</div>
