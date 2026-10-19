<?php
use App\Auth;
use App\View;
?>
<div class="head">
  <div><h1><?= $g['id'] ? 'Editar grupo' : 'Novo grupo de convívio' ?></h1></div>
  <?php if ($g['id']): ?><a class="btn btn-ghost" href="/grupos/<?= e($g['slug']) ?>" target="_blank" rel="noopener"><?= View::icon('external', 18) ?>Ver no site</a><?php endif; ?>
</div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>
<form class="form" method="post" action="/admin/grupos/salvar">
  <?= Auth::csrfField() ?>
  <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
  <fieldset class="fieldset">
    <legend>O grupo</legend>
    <div class="row">
      <label class="f">Nome do grupo<input type="text" name="name" value="<?= e($g['name']) ?>" required maxlength="120" placeholder="Ex.: Grupo Pagani" id="name"></label>
      <label class="f">Para quem<small>Ex.: Todos, Casais, Jovens, Mulheres</small><input type="text" name="audience" value="<?= e($g['audience']) ?>" maxlength="80" list="audiences" id="audience"></label>
      <datalist id="audiences"><option value="Todos"><option value="Casais"><option value="Jovens"><option value="Adolescentes"><option value="Mulheres"><option value="Homens"></datalist>
    </div>
    <label class="f">Descrição<small>Opcional. Conte como é o encontro.</small><textarea name="description" maxlength="3000" id="description"><?= e($g['description']) ?></textarea></label>
  </fieldset>

  <fieldset class="fieldset">
    <legend>Líder</legend>
    <div class="row">
      <label class="f">Nome<input type="text" name="leader_name" value="<?= e($g['leader_name']) ?>" maxlength="120" id="leader_name"></label>
      <label class="f">WhatsApp<small>Com DDD. Vira o botão "Quero participar".</small><input type="tel" name="leader_phone" value="<?= e($g['leader_phone']) ?>" maxlength="30" placeholder="(48) 99999-9999" id="leader_phone"></label>
      <label class="f">Timóteos<small>Auxiliares do grupo (opcional)</small><input type="text" name="assistants" value="<?= e($g['assistants'] ?? '') ?>" maxlength="160" id="assistants"></label>
    </div>
  </fieldset>

  <fieldset class="fieldset">
    <legend>Onde</legend>
    <div class="row">
      <label class="f">Bairro<input type="text" name="neighborhood" value="<?= e($g['neighborhood']) ?>" required maxlength="80" id="neighborhood"></label>
      <label class="f">Cidade<input type="text" name="city" value="<?= e($g['city']) ?>" maxlength="80" id="city"></label>
    </div>
    <label class="f">Endereço completo<small>Fica guardado no painel. Só aparece no site se você marcar a opção abaixo.</small><input type="text" name="address" value="<?= e($g['address']) ?>" maxlength="255" id="address"></label>
    <label class="switch"><input type="checkbox" name="show_address" value="1"<?= $g['show_address'] ? ' checked' : '' ?>><span>Mostrar endereço completo no site<small>Desmarcado: o site mostra só o bairro, e o mapa mostra a região aproximada (~100 m)</small></span></label>
    <?= View::render('admin/_location', ['lat' => $g['lat'], 'lng' => $g['lng'], 'centerLat' => $church['lat'], 'centerLng' => $church['lng'], 'hint' => 'Marque o local do encontro. Isso permite o mapa e o botão "Perto de mim".']) ?>
  </fieldset>

  <?= View::render('admin/_translations', ['values' => $tr, 'fields' => ['audience' => ['Para quem', 'text', 80], 'description' => ['Descrição', 'textarea', 3000]]]) ?>

  <?= View::render('admin/_recurrence', ['item' => $g, 'allowOnce' => false]) ?>

  <fieldset class="fieldset">
    <legend>Exibição</legend>
    <div class="row">
      <label class="switch"><input type="checkbox" name="published" value="1"<?= $g['published'] ? ' checked' : '' ?>><span>Mostrar no site</span></label>
      <label class="f">Endereço da página<small>Gerado do nome se vazio</small><input type="text" name="slug" value="<?= e($g['slug']) ?>" maxlength="120" id="slug"></label>
    </div>
  </fieldset>

  <div class="form-foot">
    <a class="btn btn-ghost" href="/admin/grupos">Cancelar</a>
    <button class="btn btn-primary" type="submit"><?= View::icon('check', 18) ?>Salvar grupo</button>
  </div>
</form>
