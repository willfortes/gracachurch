<?php
use App\View;

echo View::render('partials/page-head', [
    'crumbs' => [t('nav_alerts') => null],
    'label' => t('app_eyebrow'),
    'title' => t('alerts_title'),
    'lead' => e(t('alerts_lead')),
]);
$platforms = [
    'ios' => [t('ios_title'), ['ios_1', 'ios_2', 'ios_3', 'ios_4'], t('ios_note')],
    'android' => ['Android', ['android_1', 'android_2', 'android_3'], ''],
    'desktop' => [t('desktop_title'), ['desktop_1', 'desktop_2', 'desktop_3'], ''],
    'mac' => ['Mac', ['mac_1', 'mac_2', 'mac_3'], ''],
];
?>
<section class="sec glow-bg">
  <div class="wrap detail">
    <div class="panel" style="gap:20px">
      <h2><?= e(t('step1')) ?></h2>
      <p class="muted"><?= e(t('step1_text')) ?></p>
      <div class="status" data-push-state hidden role="status"></div>
      <div class="btns btns-stack">
        <button class="btn btn-primary" type="button" data-push-open><?= View::icon('bell', 20) ?><?= e(t('choose_alerts')) ?></button>
        <button class="btn btn-light" type="button" data-install hidden><?= View::icon('download', 20) ?><?= e(t('install_app')) ?></button>
      </div>
      <noscript><p class="notice"><?= e(t('need_js')) ?></p></noscript>
    </div>
    <aside class="panel">
      <h2><?= e(t('how_it_works')) ?></h2>
      <dl class="info">
        <div><dt><?= View::icon('clock') ?></dt><dd><b><?= e(t('hiw_1_t')) ?></b><?= e(t('hiw_1')) ?></dd></div>
        <div><dt><?= View::icon('check') ?></dt><dd><b><?= e(t('hiw_2_t')) ?></b><?= e(t('hiw_2')) ?></dd></div>
        <div><dt><?= View::icon('bell') ?></dt><dd><b><?= e(t('hiw_3_t')) ?></b><?= e(t('hiw_3')) ?></dd></div>
      </dl>
    </aside>
  </div>
</section>

<section class="sec" style="padding-top:0">
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow"><?= e(t('step2_eyebrow')) ?></span>
      <h2><?= e(t('step2_title')) ?></h2>
      <p class="lead"><?= e(t('step2_lead')) ?></p>
    </div>
    <div class="platform-grid">
      <?php foreach ($platforms as $key => [$title, $steps, $note]): ?>
      <div class="platform" data-platform="<?= $key ?>">
        <h3><?= e($title) ?> <span class="tag mine-tag"><?= e(t('your_device')) ?></span></h3>
        <ol><?php foreach ($steps as $s): ?><li><?= t($s) ?></li><?php endforeach; ?></ol>
        <?php if ($note): ?><p class="muted" style="margin-top:12px;font-size:.9rem"><?= e($note) ?></p><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
