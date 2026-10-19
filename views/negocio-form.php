<?php
use App\Directory;
use App\I18n;
use App\View;

$err = fn (string $k) => isset($errors[$k]) ? '<p class="field-err" id="err-' . $k . '">' . e($errors[$k]) . '</p>' : '';
$inv = fn (string $k) => isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : '';
$v = fn (string $k) => e($old[$k] ?? '');
$modes = explode(',', (string) ($old['service_mode'] ?? 'local'));
$action = $editing ? u('business', 'editar/' . $editing['edit_token']) : u('business', 'cadastrar');

echo View::render('partials/page-head', [
    'crumbs' => [t('nav_business') => u('business'), ($editing ? t('biz_edit_title_short') : t('biz_register_cta')) => null],
    'label' => t('nav_business'),
    'title' => $editing ? t('biz_edit_title') : t('biz_form_title'),
    'lead' => e($editing ? t('biz_edit_lead') : t('biz_form_lead')),
]);
?>
<section class="sec glow-bg">
  <div class="wrap detail">
    <?php if ($done): ?>
    <div class="panel help-done" role="status">
      <span class="help-check" aria-hidden="true"><?= View::icon('check', 40) ?></span>
      <h2 style="font-size:clamp(1.8rem,4vw,2.6rem)"><?= e(!empty($done['edited']) ? t('biz_done_edit') : t('biz_done_title')) ?></h2>
      <p class="lead"><?= e(!empty($done['edited']) && $done['status'] === 'aprovado' ? t('biz_done_edit_text') : t('biz_done_text')) ?></p>
      <?php if (!empty($done['token'])): $editUrl = app_url(I18n::url('business', 'editar/' . $done['token'])); ?>
      <div class="notice" style="text-align:left;width:100%">
        <p><b style="color:#fff"><?= e(t('biz_edit_link')) ?></b></p>
        <p style="margin:8px 0 12px;word-break:break-all"><a href="<?= e($editUrl) ?>"><?= e($editUrl) ?></a></p>
        <button class="btn btn-ghost btn-sm" type="button" data-copy="<?= e($editUrl) ?>"><?= View::icon('share', 18) ?><?= e(t('biz_copy_link')) ?></button>
      </div>
      <?php endif; ?>
      <a class="btn btn-ghost" href="<?= u('business') ?>"><?= e(t('biz_back')) ?></a>
    </div>
    <?php else: ?>
    <form class="panel help-form" method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
      <?php if (!empty($errors)): ?><div class="status err" role="alert" tabindex="-1" data-autofocus><?= e($errors['form'] ?? t('help_err_summary')) ?></div><?php endif; ?>
      <input type="hidden" name="t" value="<?= e($token) ?>">
      <div class="hp" aria-hidden="true"><label>URL <input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></div>

      <fieldset class="form-group">
        <legend><?= e(t('biz_fs_business')) ?></legend>
        <label class="field"><span><?= e(t('biz_f_name')) ?> <b aria-hidden="true">*</b></span><input type="text" name="name" required maxlength="120" value="<?= $v('name') ?>" id="biz-name"<?= $inv('name') ?>><?= $err('name') ?></label>
        <label class="field"><span><?= e(t('biz_f_category')) ?> <b aria-hidden="true">*</b></span>
          <select name="category" required id="biz-category" class="select"<?= $inv('category') ?>>
            <option value=""><?= e(t('biz_f_category_ph')) ?></option>
            <?php foreach (Directory::CATEGORIES as $slug => $c): ?><option value="<?= $slug ?>"<?= ($old['category'] ?? '') === $slug ? ' selected' : '' ?>><?= e(Directory::categoryLabel($slug)) ?></option><?php endforeach; ?>
          </select><?= $err('category') ?></label>
        <label class="field"><span><?= e(t('biz_f_desc')) ?> <b aria-hidden="true">*</b></span><small><?= e(t('biz_f_desc_hint')) ?></small><textarea name="description" required maxlength="2000" rows="5" id="biz-desc"<?= $inv('description') ?>><?= $v('description') ?></textarea><?= $err('description') ?></label>
        <label class="field"><span><?= e(t('biz_f_tags')) ?></span><small><?= e(t('biz_f_tags_hint')) ?></small><input type="text" name="tags" maxlength="400" value="<?= $v('tags') ?>" id="biz-tags"></label>
        <label class="field"><span><?= e(t('biz_f_logo')) ?></span><small><?= e(t('biz_f_logo_hint')) ?></small><input type="file" name="logo" accept="image/jpeg,image/png,image/webp" id="biz-logo"<?= $inv('logo') ?>><?= $err('logo') ?></label>
      </fieldset>

      <fieldset class="form-group">
        <legend><?= e(t('biz_fs_contact')) ?></legend>
        <label class="field"><span><?= e(t('biz_f_owner')) ?> <b aria-hidden="true">*</b></span><small><?= e(t('biz_f_owner_hint')) ?></small><input type="text" name="owner_name" required maxlength="120" value="<?= $v('owner_name') ?>" autocomplete="name" id="biz-owner"<?= $inv('owner_name') ?>><?= $err('owner_name') ?></label>
        <label class="field"><span>WhatsApp <b aria-hidden="true">*</b></span><input type="tel" name="whatsapp" required maxlength="30" inputmode="tel" placeholder="(48) 99999-9999" value="<?= $v('whatsapp') ?>" id="biz-wa"<?= $inv('whatsapp') ?>><?= $err('whatsapp') ?></label>
        <div class="field-row">
          <label class="field"><span>Instagram</span><input type="text" name="instagram" maxlength="80" placeholder="@seunegocio" value="<?= $v('instagram') ?>" id="biz-ig"></label>
          <label class="field"><span><?= e(t('biz_site')) ?></span><input type="url" name="website" maxlength="200" placeholder="https://" value="<?= $v('website') ?>" id="biz-site"<?= $inv('website') ?>><?= $err('website') ?></label>
        </div>
        <label class="field"><span>E-mail</span><input type="email" name="email" maxlength="160" value="<?= $v('email') ?>" autocomplete="email" id="biz-email"<?= $inv('email') ?>><?= $err('email') ?></label>
      </fieldset>

      <fieldset class="form-group">
        <legend><?= e(t('biz_fs_where')) ?></legend>
        <div class="checks" role="group" aria-label="<?= e(t('biz_how')) ?>">
          <?php foreach (Directory::MODES as $m): ?>
          <label class="check"><input type="checkbox" name="service_mode[]" value="<?= $m ?>"<?= in_array($m, $modes, true) ? ' checked' : '' ?>><span><?= e(t('biz_mode_' . $m)) ?><small><?= e(t('biz_mode_' . $m . '_d')) ?></small></span></label>
          <?php endforeach; ?>
        </div>
        <div class="field-row">
          <label class="field"><span><?= e(t('biz_f_hood')) ?></span><input type="text" name="neighborhood" maxlength="80" value="<?= $v('neighborhood') ?>" id="biz-hood"<?= $inv('neighborhood') ?>><?= $err('neighborhood') ?></label>
          <label class="field"><span><?= e(t('biz_f_city')) ?></span><input type="text" name="city" maxlength="80" value="<?= $v('city') ?: 'Palhoça' ?>" id="biz-city"></label>
        </div>
        <label class="field"><span><?= e(t('biz_f_address')) ?></span><input type="text" name="address" maxlength="200" value="<?= $v('address') ?>" id="biz-address"></label>
        <label class="check"><input type="checkbox" name="show_address" value="1"<?= !empty($old['show_address']) ? ' checked' : '' ?>><span><?= e(t('biz_f_show_address')) ?><small><?= e(t('biz_f_show_address_d')) ?></small></span></label>
        <label class="field"><span><?= e(t('biz_area')) ?></span><small><?= e(t('biz_f_area_hint')) ?></small><input type="text" name="service_area" maxlength="160" value="<?= $v('service_area') ?>" id="biz-area"></label>
        <label class="field"><span><?= e(t('biz_hours')) ?></span><small><?= e(t('biz_f_hours_hint')) ?></small><input type="text" name="hours" maxlength="160" value="<?= $v('hours') ?>" id="biz-hours"></label>
      </fieldset>

      <?php if (!$editing): ?>
      <label class="check"<?= $inv('consent') ?>><input type="checkbox" name="member" value="1" required<?= !empty($_POST['member']) ? ' checked' : '' ?>><span><?= e(t('biz_f_member', ['name' => setting('church_name', 'Graça Church')])) ?></span></label>
      <label class="check"<?= $inv('consent') ?>><input type="checkbox" name="consent" value="1" required<?= !empty($_POST['consent']) ? ' checked' : '' ?>><span><?= e(t('biz_f_consent')) ?><small><?= e(t('biz_f_privacy')) ?></small></span></label>
      <?= $err('consent') ?>
      <?php endif; ?>
      <button class="btn btn-primary" type="submit"><?= View::icon('send', 20) ?><?= e($editing ? t('biz_save') : t('biz_send')) ?></button>
      <p class="muted" style="font-size:.85rem"><?= e(t('help_required')) ?> <?= e(t('biz_review_note')) ?></p>
    </form>
    <?php endif; ?>

    <aside class="panel">
      <h2><?= e(t('biz_why_title')) ?></h2>
      <dl class="info">
        <div><dt><?= View::icon('search') ?></dt><dd><b><?= e(t('biz_why1_t')) ?></b><?= e(t('biz_why1')) ?></dd></div>
        <div><dt><?= View::icon('people') ?></dt><dd><b><?= e(t('biz_why2_t')) ?></b><?= e(t('biz_why2')) ?></dd></div>
        <div><dt><?= View::icon('check') ?></dt><dd><b><?= e(t('biz_why3_t')) ?></b><?= e(t('biz_why3')) ?></dd></div>
      </dl>
    </aside>
  </div>
</section>
