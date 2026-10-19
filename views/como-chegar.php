<?php
use App\Content;
use App\View;

$name = setting('church_name', 'Graça Church');
$links = directions_links(setting('lat') ?: null, setting('lng') ?: null, $name . ', ' . church_address());
$points = Content::mapData(Content::groups(), true);

echo View::render('partials/page-head', [
    'crumbs' => [t('nav_directions') => null],
    'label' => t('nav_directions'),
    'title' => t('directions_title'),
    'lead' => e(church_address()) . ($summary ? '. ' . e(t('services_label')) . ': ' . e($summary) . '.' : ''),
]);
?>
<section class="sec glow-bg">
  <div class="wrap detail">
    <div style="display:grid;gap:24px">
      <p class="address-big"><b><?= e(setting('address_street', 'BR 282, KM 18')) ?></b><span><?= e(setting('address_city', 'Palhoça')) ?> – <?= e(setting('address_state', 'SC')) ?> · <?= e(setting('address_zip')) ?></span></p>
      <div class="routes">
        <a href="<?= e($links['google']) ?>" target="_blank" rel="noopener"><?= View::icon('map', 28) ?><span>Google Maps<small><?= e(t('open_route')) ?></small></span></a>
        <a href="<?= e($links['waze']) ?>" target="_blank" rel="noopener"><?= View::icon('pin', 28) ?><span>Waze<small><?= e(t('open_route')) ?></small></span></a>
        <a href="<?= e($links['apple']) ?>" target="_blank" rel="noopener"><?= View::icon('locate', 28) ?><span>Apple Maps<small><?= e(t('open_route')) ?></small></span></a>
      </div>
      <?php if ($note = setting('directions_note')): ?><div class="notice"><b style="color:#fff"><?= e(t('tip')) ?>:</b> <?= e($note) ?></div><?php endif; ?>
      <?php if ($points): ?><?= View::render('partials/sky', ['points' => $points, 'id' => 'mapa-igreja']) ?><?php endif; ?>
    </div>

    <aside class="panel">
      <h2><?= e(t('address')) ?></h2>
      <dl class="info">
        <div><dt><?= View::icon('pin') ?></dt><dd><b><?= e($name) ?></b><?= e(church_address()) ?></dd></div>
        <?php if ($summary): ?><div><dt><?= View::icon('clock') ?></dt><dd><b><?= e(t('services_label')) ?></b><?= e($summary) ?></dd></div><?php endif; ?>
        <?php foreach (array_filter([setting('whatsapp_1'), setting('whatsapp_2')]) as $wa): ?>
        <div><dt><?= View::icon('whatsapp') ?></dt><dd><b>WhatsApp</b><a href="<?= e(whatsapp_link($wa, t('wa_visit', ['name' => $name]))) ?>" target="_blank" rel="noopener"><?= e(phone_display($wa)) ?></a></dd></div>
        <?php endforeach; ?>
      </dl>
      <button class="btn btn-ghost btn-sm" type="button" data-copy="<?= e($name . ' – ' . church_address()) ?>"><?= View::icon('share', 18) ?><?= e(t('copy_address')) ?></button>
    </aside>
  </div>
</section>
