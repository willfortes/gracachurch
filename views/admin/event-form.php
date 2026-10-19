<?php
use App\Auth;
use App\View;
?>
<div class="head">
  <div><h1><?= $ev['id'] ? 'Editar programação' : 'Nova programação' ?></h1><p>Campos com explicação embaixo ajudam o site a aparecer melhor no Google e nos assistentes.</p></div>
  <?php if ($ev['id']): ?><a class="btn btn-ghost" href="/eventos/<?= e($ev['slug']) ?>" target="_blank" rel="noopener"><?= View::icon('external', 18) ?>Ver no site</a><?php endif; ?>
</div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>
<form class="form" method="post" action="/admin/programacao/salvar">
  <?= Auth::csrfField() ?>
  <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
  <fieldset class="fieldset">
    <legend>O que é</legend>
    <div class="row">
      <label class="f">Nome<input type="text" name="title" value="<?= e($ev['title']) ?>" required maxlength="120" placeholder="Ex.: Culto de domingo" id="title"></label>
      <label class="f">Tipo
        <select name="category" id="category">
          <option value="culto"<?= $ev['category'] === 'culto' ? ' selected' : '' ?>>Culto</option>
          <option value="ministerio"<?= $ev['category'] === 'ministerio' ? ' selected' : '' ?>>Ministério (encontro mensal)</option>
          <option value="evento"<?= $ev['category'] === 'evento' ? ' selected' : '' ?>>Evento especial</option>
        </select>
      </label>
    </div>
    <label class="f">Resumo<small>Uma frase. Aparece nos cartões e no Google.</small><input type="text" name="summary" value="<?= e($ev['summary']) ?>" maxlength="300" data-count="c-sum" id="summary"><span class="counter" id="c-sum"></span></label>
    <label class="f">Descrição completa<small>Opcional. Pule uma linha entre parágrafos.</small><textarea name="description" maxlength="5000" id="description"><?= e($ev['description']) ?></textarea></label>
  </fieldset>

  <?= View::render('admin/_translations', ['values' => $tr, 'fields' => [
      'title' => ['Nome', 'text', 120], 'summary' => ['Resumo', 'text', 300], 'description' => ['Descrição', 'textarea', 5000], 'location_name' => ['Nome do local', 'text', 120],
  ]]) ?>

  <?= View::render('admin/_recurrence', ['item' => $ev, 'allowOnce' => true]) ?>

  <fieldset class="fieldset">
    <legend>Onde</legend>
    <p class="hint">Deixe em branco quando for no templo da igreja.</p>
    <div class="row">
      <label class="f">Nome do local<input type="text" name="location_name" value="<?= e($ev['location_name']) ?>" maxlength="120" placeholder="Ex.: Centro de Eventos" id="location_name"></label>
      <label class="f">Endereço<input type="text" name="address" value="<?= e($ev['address']) ?>" maxlength="255" id="address"></label>
    </div>
    <details>
      <summary class="hint" style="cursor:pointer">Marcar no mapa (para eventos fora do templo)</summary>
      <div style="margin-top:12px"><?= View::render('admin/_location', ['lat' => $ev['lat'], 'lng' => $ev['lng'], 'centerLat' => setting('lat'), 'centerLng' => setting('lng')]) ?></div>
    </details>
  </fieldset>

  <fieldset class="fieldset">
    <legend>Exibição</legend>
    <div class="row">
      <label class="switch"><input type="checkbox" name="published" value="1"<?= $ev['published'] ? ' checked' : '' ?>><span>Mostrar no site<small>Desmarque para esconder sem excluir</small></span></label>
      <label class="switch"><input type="checkbox" name="featured" value="1"<?= $ev['featured'] ? ' checked' : '' ?>><span>Destaque</span></label>
      <label class="f">Ordem<small>Menor aparece primeiro</small><input type="number" name="sort" value="<?= (int) $ev['sort'] ?>" id="sort"></label>
      <label class="f">Endereço da página<small>Gerado do nome se vazio</small><input type="text" name="slug" value="<?= e($ev['slug']) ?>" maxlength="120" id="slug"></label>
    </div>
  </fieldset>

  <div class="form-foot">
    <a class="btn btn-ghost" href="/admin/programacao">Cancelar</a>
    <button class="btn btn-primary" type="submit"><?= View::icon('check', 18) ?>Salvar</button>
  </div>
</form>
