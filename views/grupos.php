<?php
use App\Content;
use App\Schedule;
use App\View;

$days = array_values(array_unique(array_map(fn ($g) => (int) $g['weekday'], $groups)));
sort($days);
$points = Content::mapData($groups);
?>
<section class="stage" aria-labelledby="groups-title">
  <?php if ($points): ?>
    <?= View::render('partials/sky', ['points' => $points, 'id' => 'mapa', 'stage' => true]) ?>
  <?php endif; ?>

  <aside class="stage-panel">
    <nav class="crumbs" aria-label="<?= e(t('crumbs_label')) ?>"><a href="<?= u() ?>"><?= e(t('crumbs_home')) ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= e(t('nav_groups')) ?></span></nav>
    <span class="eyebrow"><?= e(t('nav_groups')) ?></span>
    <h1 id="groups-title"><?= accent(t('groups_page_title')) ?></h1>
    <p class="lead" style="font-size:1rem"><?= e(setting('groups_intro')) ?></p>

    <?php if (!$groups): ?>
      <div class="empty">
        <p><b style="color:#fff"><?= e(t('groups_soon')) ?></b></p>
        <?php if ($wa = setting('whatsapp_1')): ?>
        <p style="margin-top:16px"><a class="btn btn-wa" href="<?= e(whatsapp_link($wa, t('wa_group'))) ?>" target="_blank" rel="noopener"><?= View::icon('whatsapp', 20) ?><?= e(t('want_group')) ?></a></p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="tools">
        <label class="search">
          <?= View::icon('search', 20) ?>
          <span class="sr"><?= e(t('search_label')) ?></span>
          <input type="search" placeholder="<?= e(t('search_ph')) ?>" data-group-search autocomplete="off" id="busca-grupo">
        </label>
        <button class="btn btn-primary btn-sm" type="button" data-near hidden><?= View::icon('locate', 20) ?><?= e(t('near_me')) ?></button>
      </div>
      <?php if (count($days) > 1): ?>
      <div class="chips" role="group" aria-label="<?= e(t('filter_day')) ?>">
        <button class="chip" type="button" data-day="" aria-pressed="true"><?= e(t('all_days')) ?></button>
        <?php foreach ($days as $d): ?>
        <button class="chip" type="button" data-day="<?= $d ?>" aria-pressed="false"><?= e(Schedule::day($d)) ?></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <p class="muted" data-near-status role="status" style="margin-bottom:12px" hidden></p>
      <div class="group-list" data-group-list>
        <?php foreach ($groups as $g): ?><?= View::render('partials/group-card', ['g' => $g]) ?><?php endforeach; ?>
      </div>
      <p class="empty" data-group-empty hidden><?= e(t('no_match')) ?></p>
    <?php endif; ?>
  </aside>
</section>
