<?php
use App\View;

echo View::render('partials/page-head', [
    'label' => t('offline_eyebrow'),
    'title' => t('offline_title'),
    'lead' => e(t('offline_lead')),
]);
?>
<section class="sec">
  <div class="wrap btns">
    <a class="btn btn-primary" href="<?= u() ?>"><?= e(t('reload')) ?></a>
    <a class="btn btn-ghost" href="<?= u('schedule') ?>"><?= e(t('saved_schedule')) ?></a>
  </div>
</section>
