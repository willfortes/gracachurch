<?php
use App\Auth;
use App\Directory;
use App\Http\Admin;
use App\View;

$modes = explode(',', $b['service_mode']);
?>
<div class="head">
  <div><p><a href="/admin/negocios?status=<?= e($b['status']) ?>">← Guia de negócios</a></p><h1 style="margin-top:8px"><?= e($b['name']) ?></h1><p>Cadastrado em <?= e(date('d/m/Y H:i', strtotime($b['created_at']))) ?> por <?= e($b['owner_name']) ?> · <?= (int) $b['views'] ?> visitas</p></div>
  <div class="actions">
    <a class="btn btn-ghost" style="color:#0f5c2e" href="<?= e(whatsapp_link($b['whatsapp'], 'Olá, ' . explode(' ', $b['owner_name'])[0] . '! Aqui é da Graça Church, sobre o cadastro do ' . $b['name'] . ' no guia de negócios.')) ?>" target="_blank" rel="noopener"><?= View::icon('whatsapp', 18) ?>Falar com o dono</a>
    <?php if ($b['status'] === 'aprovado'): ?><a class="btn btn-ghost" href="/negocios/<?= e($b['slug']) ?>" target="_blank" rel="noopener"><?= View::icon('external', 18) ?>Ver no site</a><?php endif; ?>
  </div>
</div>
<?= View::render('admin/_flash', ['flash' => $flash]) ?>
<form class="form" method="post" action="/admin/negocios/<?= (int) $b['id'] ?>/salvar" enctype="multipart/form-data">
  <?= Auth::csrfField() ?>
  <fieldset class="fieldset">
    <legend>Aprovação</legend>
    <div class="row">
      <label class="f">Situação
        <select name="status" id="b-status"><?php foreach (Admin::BIZ_STATUS as $k => $l): ?><option value="<?= $k ?>"<?= $b['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
      </label>
      <label class="switch"><input type="checkbox" name="featured" value="1"<?= $b['featured'] ? ' checked' : '' ?>><span>Destaque<small>Aparece primeiro nas buscas e categorias</small></span></label>
    </div>
    <label class="f">Observação interna<small>Ex.: motivo da recusa, o que foi conferido</small><input type="text" name="review_note" value="<?= e($b['review_note']) ?>" maxlength="400" id="b-note"></label>
  </fieldset>

  <fieldset class="fieldset">
    <legend>Negócio</legend>
    <div class="row">
      <label class="f">Nome<input type="text" name="name" value="<?= e($b['name']) ?>" maxlength="120" required id="b-name"></label>
      <label class="f">Categoria<select name="category" id="b-cat"><?php foreach (Directory::CATEGORIES as $slug => $c): ?><option value="<?= $slug ?>"<?= $b['category'] === $slug ? ' selected' : '' ?>><?= e($c[1]) ?></option><?php endforeach; ?></select></label>
    </div>
    <label class="f">Descrição<textarea name="description" maxlength="2000" id="b-desc"><?= e($b['description']) ?></textarea></label>
    <label class="f">Palavras-chave<small>Separadas por vírgula. Ajudam muito a busca.</small><input type="text" name="tags" value="<?= e($b['tags']) ?>" maxlength="400" id="b-tags"></label>
    <div class="row" style="align-items:center">
      <?php if ($b['logo']): ?><img src="/uploads/negocios/<?= e($b['logo']) ?>" width="96" height="96" alt="Logo atual" style="border-radius:16px"><label class="switch"><input type="checkbox" name="remove_logo" value="1"><span>Remover logo</span></label><?php endif; ?>
      <label class="f">Trocar logo<input type="file" name="logo" accept="image/jpeg,image/png,image/webp" id="b-logo"></label>
    </div>
  </fieldset>

  <fieldset class="fieldset">
    <legend>Contato</legend>
    <div class="row">
      <label class="f">Responsável<input type="text" name="owner_name" value="<?= e($b['owner_name']) ?>" maxlength="120" id="b-owner"></label>
      <label class="f">WhatsApp<input type="tel" name="whatsapp" value="<?= e($b['whatsapp']) ?>" maxlength="30" id="b-wa"></label>
      <label class="f">Instagram<input type="text" name="instagram" value="<?= e($b['instagram']) ?>" maxlength="80" id="b-ig"></label>
      <label class="f">Site<input type="url" name="website" value="<?= e($b['website']) ?>" maxlength="200" id="b-site"></label>
      <label class="f">E-mail<input type="email" name="email" value="<?= e($b['email']) ?>" maxlength="160" id="b-email"></label>
    </div>
  </fieldset>

  <fieldset class="fieldset">
    <legend>Onde e como atende</legend>
    <div class="row">
      <?php foreach (['local' => 'No local', 'domicilio' => 'Atende em casa', 'online' => 'Online'] as $m => $l): ?>
      <label class="switch"><input type="checkbox" name="service_mode[]" value="<?= $m ?>"<?= in_array($m, $modes, true) ? ' checked' : '' ?>><span><?= $l ?></span></label>
      <?php endforeach; ?>
    </div>
    <div class="row">
      <label class="f">Bairro<input type="text" name="neighborhood" value="<?= e($b['neighborhood']) ?>" maxlength="80" id="b-hood"></label>
      <label class="f">Cidade<input type="text" name="city" value="<?= e($b['city']) ?>" maxlength="80" id="b-city"></label>
      <label class="f">Região atendida<input type="text" name="service_area" value="<?= e($b['service_area']) ?>" maxlength="160" id="b-area"></label>
      <label class="f">Horário<input type="text" name="hours" value="<?= e($b['hours']) ?>" maxlength="160" id="b-hours"></label>
    </div>
    <label class="f">Endereço<input type="text" name="address" value="<?= e($b['address']) ?>" maxlength="200" id="b-address"></label>
    <label class="switch"><input type="checkbox" name="show_address" value="1"<?= $b['show_address'] ? ' checked' : '' ?>><span>Mostrar endereço no site</span></label>
    <details><summary class="hint" style="cursor:pointer">Marcar no mapa (opcional, só aparece se o endereço for público)</summary>
      <div style="margin-top:12px"><?= View::render('admin/_location', ['lat' => $b['lat'], 'lng' => $b['lng'], 'centerLat' => setting('lat'), 'centerLng' => setting('lng')]) ?></div>
    </details>
    <label class="f">Endereço da página<small>Parte final do link</small><input type="text" name="slug" value="<?= e($b['slug']) ?>" maxlength="140" id="b-slug"></label>
  </fieldset>

  <div class="form-foot">
    <a class="btn btn-ghost" href="/admin/negocios?status=<?= e($b['status']) ?>">Cancelar</a>
    <button class="btn btn-primary" type="submit"><?= View::icon('check', 18) ?>Salvar</button>
  </div>
</form>
<form method="post" action="/admin/negocios/<?= (int) $b['id'] ?>/excluir" data-confirm="Excluir o cadastro de <?= e($b['name']) ?>? Isso apaga a página do site." style="margin-top:20px">
  <?= Auth::csrfField() ?><button class="btn btn-danger btn-sm" type="submit"><?= View::icon('trash', 16) ?>Excluir cadastro</button>
</form>
