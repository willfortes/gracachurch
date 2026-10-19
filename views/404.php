<?php
use App\View;

echo View::render('partials/page-head', [
    'label' => t('nf_eyebrow'),
    'title' => t('nf_title'),
    'lead' => e(t('nf_lead')),
]);
?>
<section class="sec">
  <div class="wrap btns">
    <a class="btn btn-primary" href="<?= u('schedule') ?>"><?= View::icon('calendar', 20) ?><?= e(t('nav_schedule')) ?></a>
    <a class="btn btn-ghost" href="<?= u('groups') ?>"><?= View::icon('people', 20) ?><?= e(t('nav_groups')) ?></a>
    <a class="btn btn-ghost" href="<?= u('directions') ?>"><?= View::icon('pin', 20) ?><?= e(t('nav_directions')) ?></a>
  </div>
</section>
