<?php
use App\View;

$name = setting('church_name', 'Graça Church');
$wa = setting('whatsapp_1');
$err = fn (string $k) => isset($errors[$k]) ? '<p class="field-err" id="err-' . $k . '">' . e($errors[$k]) . '</p>' : '';
$inv = fn (string $k) => isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : '';

echo View::render('partials/page-head', [
    'crumbs' => [t('nav_help') => null],
    'label' => t('nav_help'),
    'title' => t('help_title'),
    'lead' => e(t('help_lead')),
]);
?>
<section class="sec glow-bg">
  <div class="wrap detail">
    <?php if ($sent): ?>
    <div class="panel help-done" role="status">
      <span class="help-check" aria-hidden="true"><?= View::icon('check', 40) ?></span>
      <h2 style="font-size:clamp(1.8rem,4vw,2.6rem)"><?= e(t('help_done_title')) ?></h2>
      <p class="lead"><?= e(t('help_done_text')) ?></p>
      <div class="btns btns-stack">
        <?php if ($wa): ?><a class="btn btn-wa" href="<?= e(whatsapp_link($wa, t('help_wa_now'))) ?>" target="_blank" rel="noopener"><?= View::icon('whatsapp', 20) ?><?= e(t('help_talk_now')) ?></a><?php endif; ?>
        <a class="btn btn-ghost" href="<?= u() ?>"><?= e(t('crumbs_home')) ?></a>
      </div>
    </div>
    <?php else: ?>
    <form class="panel help-form" method="post" action="<?= u('help') ?>" novalidate>
      <?php if (!empty($errors)): ?>
      <div class="status err" role="alert" tabindex="-1" data-autofocus><?= e($errors['form'] ?? t('help_err_summary')) ?></div>
      <?php endif; ?>
      <input type="hidden" name="t" value="<?= e($token) ?>">
      <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

      <label class="field">
        <span><?= e(t('help_name')) ?> <b aria-hidden="true">*</b></span>
        <input type="text" name="name" required maxlength="160" autocomplete="name" value="<?= e($old['name'] ?? '') ?>" id="help-name"<?= $inv('name') ?>>
        <?= $err('name') ?>
      </label>
      <label class="field">
        <span><?= e(t('help_phone')) ?> <b aria-hidden="true">*</b></span>
        <small><?= e(t('help_phone_hint')) ?></small>
        <input type="tel" name="phone" required maxlength="40" autocomplete="tel" inputmode="tel" placeholder="(48) 99999-9999" value="<?= e($old['phone'] ?? '') ?>" id="help-phone"<?= $inv('phone') ?>>
        <?= $err('phone') ?>
      </label>
      <label class="field">
        <span><?= e(t('help_message')) ?> <b aria-hidden="true">*</b></span>
        <small><?= e(t('help_message_hint')) ?></small>
        <textarea name="message" required maxlength="4000" rows="5" id="help-message"<?= $inv('message') ?>><?= e($old['message'] ?? '') ?></textarea>
        <?= $err('message') ?>
      </label>
      <label class="field">
        <span><?= e(t('help_extra')) ?></span>
        <textarea name="extra" maxlength="2000" rows="3" id="help-extra"><?= e($old['extra'] ?? '') ?></textarea>
      </label>
      <label class="check"<?= $inv('consent') ?>>
        <input type="checkbox" name="consent" value="1" required<?= !empty($_POST['consent']) ? ' checked' : '' ?> id="help-consent">
        <span><?= e(t('help_consent')) ?><small><?= e(t('help_privacy')) ?></small></span>
      </label>
      <?= $err('consent') ?>
      <button class="btn btn-primary" type="submit"><?= View::icon('send', 20) ?><?= e(t('help_send')) ?></button>
      <p class="muted" style="font-size:.85rem"><?= e(t('help_required')) ?></p>
    </form>
    <?php endif; ?>

    <aside class="panel">
      <h2><?= e(t('help_prefer')) ?></h2>
      <p class="muted"><?= e(t('help_prefer_text')) ?></p>
      <div class="contact-list">
        <?php foreach (array_filter([$wa, setting('whatsapp_2')]) as $num): ?>
        <a class="contact-item" href="<?= e(whatsapp_link($num, t('wa_hello', ['name' => $name]))) ?>" target="_blank" rel="noopener"><span class="ci-icon wa"><?= View::icon('whatsapp', 22) ?></span><span><b>WhatsApp</b><?= e(phone_display($num)) ?></span></a>
        <?php endforeach; ?>
        <?php if ($ig = setting('instagram')): ?>
        <a class="contact-item" href="https://www.instagram.com/<?= e($ig) ?>/" target="_blank" rel="noopener"><span class="ci-icon"><?= View::icon('instagram', 22) ?></span><span><b>Instagram</b>@<?= e($ig) ?></span></a>
        <?php endif; ?>
        <a class="contact-item" href="<?= u('directions') ?>"><span class="ci-icon"><?= View::icon('pin', 22) ?></span><span><b><?= e(t('nav_directions')) ?></b><?= e(church_address()) ?></span></a>
      </div>
    </aside>
  </div>
</section>
