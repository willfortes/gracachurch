<?php
use App\Content;
use App\Schedule;
use App\View;

echo View::render('partials/page-head', [
    'crumbs' => [t('nav_schedule') => null],
    'label' => t('nav_schedule'),
    'title' => t('schedule_title'),
    'lead' => $summary ? t('schedule_lead', ['summary' => '<strong style="color:#fff">' . e($summary) . '</strong>', 'address' => e(church_address())]) : '',
]);
$todayKey = $now->format('Y-m-d');
?>
<section class="sec glow-bg">
  <div class="wrap detail">
    <div>
      <h2 style="font-size:clamp(1.8rem,4vw,2.6rem);color:#fff;margin-bottom:28px"><?= e(t('next_days')) ?></h2>
      <?php if (!$agenda): ?><p class="muted"><?= e(t('no_upcoming')) ?></p><?php endif; ?>
      <div class="agenda">
        <?php foreach ($agenda as $day => $items): $d = $items[0]['start']; $dl = Schedule::dayLabel($d, $now); ?>
        <div class="agenda-day<?= $day === $todayKey ? ' today' : '' ?>">
          <h3><?= e($dl) ?><?= in_array($dl, [t('today'), t('tomorrow')], true) ? ' · ' . e(Schedule::shortDate($d)) : '' ?></h3>
          <div class="rows">
            <?php foreach ($items as $it): $ev = $it['event']; ?>
            <a class="row" href="<?= u('event', $ev['slug']) ?>">
              <span class="row-title"><?= e($ev['title']) ?></span>
              <span class="row-sub"><?= e(Content::categoryLabel($ev['category'])) ?><?= $ev['location_name'] ? ' · ' . e($ev['location_name']) : '' ?></span>
              <span class="row-time"><?= e(Schedule::time($it['start'])) ?><?php if ($it['start'] <= $now && $it['end'] > $now): ?><small><?= e(t('live_now')) ?></small><?php endif; ?></span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <aside style="display:grid;gap:16px">
      <?php foreach (array_keys(Content::CATEGORIES) as $cat): if (empty($byCat[$cat])) continue; ?>
      <div class="panel">
        <h2><?= e(t('cat_' . $cat . '_pl')) ?></h2>
        <ul class="dates">
          <?php foreach ($byCat[$cat] as $ev): ?>
          <li><a href="<?= u('event', $ev['slug']) ?>" style="text-decoration:none;font-weight:700"><?= e($ev['title']) ?></a><span style="text-align:right"><?= e(Schedule::describe($ev)) ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
      <div class="notice">
        <p style="margin-bottom:14px"><b style="color:#fff"><?= e(t('never_miss')) ?></b> <?= e(t('never_miss_text')) ?></p>
        <a class="btn btn-primary btn-sm" href="<?= u('alerts') ?>" data-push-open><?= View::icon('bell', 18) ?><?= e(t('cta_alerts')) ?></a>
      </div>
    </aside>
  </div>
</section>
