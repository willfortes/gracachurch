<?php
use App\Auth;
use App\View;

$v = fn (string $k) => e($values[$k] ?? '');
$long = ['hero_text', 'about_text', 'groups_intro', 'directions_note'];
$groups = [
    'Textos do site' => ['church_name', 'church_city_label', 'hero_title', 'hero_text', 'about_title', 'about_text', 'groups_intro'],
    'Endereço' => ['address_street', 'address_city', 'address_state', 'address_zip', 'directions_note'],
    'Contato' => ['whatsapp_1', 'whatsapp_2', 'instagram', 'email'],
];
?>
<div class="head"><div><h1>Configurações</h1><p>Informações gerais da igreja usadas em todo o site, no Google e nos avisos.</p></div></div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>
<form class="form" method="post" action="/admin/configuracoes">
  <?= Auth::csrfField() ?>
  <?php foreach ($groups as $title => $keys): ?>
  <fieldset class="fieldset">
    <legend><?= e($title) ?></legend>
    <div class="row">
    <?php foreach ($keys as $k): [$label, $max] = $fields[$k]; ?>
      <?php if (in_array($k, $long, true)): ?>
        </div><label class="f"><?= e($label) ?><textarea name="<?= $k ?>" maxlength="<?= $max ?>" id="s-<?= $k ?>"><?= $v($k) ?></textarea></label><div class="row">
      <?php else: ?>
        <label class="f"><?= e($label) ?><input type="text" name="<?= $k ?>" value="<?= $v($k) ?>" maxlength="<?= $max ?>" id="s-<?= $k ?>"></label>
      <?php endif; ?>
    <?php endforeach; ?>
    </div>
  </fieldset>
  <?php endforeach; ?>

  <?= View::render('admin/_translations', ['values' => $tr, 'fields' => [
      'church_city_label' => ['Cidade (topo)', 'text', 60], 'hero_title' => ['Frase principal', 'text', 140], 'hero_text' => ['Boas-vindas', 'textarea', 300],
      'about_title' => ['Título do "Quem somos"', 'text', 140], 'about_text' => ['Texto do "Quem somos"', 'textarea', 3000],
      'groups_intro' => ['Apresentação dos grupos', 'textarea', 500], 'directions_note' => ['Dica para chegar', 'textarea', 600],
  ]]) ?>

  <fieldset class="fieldset">
    <legend>Local da igreja no mapa</legend>
    <p class="hint">Importante para o mapa, o botão de rota e o Google. Busque o endereço ou clique no ponto exato da entrada.</p>
    <?= View::render('admin/_location', ['lat' => $values['lat'] ?? '', 'lng' => $values['lng'] ?? '', 'centerLat' => '-27.645', 'centerLng' => '-48.67']) ?>
  </fieldset>

  <div class="form-foot">
    <span class="hint">As mudanças aparecem no site na hora.</span>
    <button class="btn btn-primary" type="submit"><?= View::icon('check', 18) ?>Salvar configurações</button>
  </div>
</form>
