<?php
use App\Content;
use App\I18n;
use App\Schedule;
use App\View;

$name = setting('church_name', 'Graça Church');
$place = $ev['location_name'] ?: $name;
$address = $ev['address'] ?: church_address();
$lat = $ev['lat'] ?? (setting('lat') ?: null);
$lng = $ev['lng'] ?? (setting('lng') ?: null);
$links = directions_links($lat !== null ? (string) $lat : null, $lng !== null ? (string) $lng : null, $place . ', ' . $address);
$first = $dates[0] ?? null;
$hours = max(1, (int) round($ev['duration_min'] / 60));

echo View::render('partials/page-head', [
    'crumbs' => [t('nav_schedule') => u('schedule'), $ev['title'] => null],
    'label' => Content::categoryLabel($ev['category']),
    'title' => $ev['title'],
    'lead' => e(Schedule::describe($ev)) . ' · ' . e($place),
]);
?>
<section class="sec glow-bg">
  <div class="wrap detail">
    <div style="display:grid;gap:28px">
      <?php if ($first): $diff = max(0, $first['start']->getTimestamp() - $now->getTimestamp()); $live = $first['start'] <= $now; ?>
      <div class="pass">
        <span class="pass-label"><span class="live" aria-hidden="true"></span><?= e($live ? t('live_now') : t('next_date')) ?></span>
        <p class="pass-when" style="font-size:1.4rem;font-weight:800;color:#fff"><?= e(Schedule::relative($first['start'], $now)) ?></p>
        <div data-countdown="<?= e($first['start']->format(DATE_ATOM)) ?>" data-end="<?= e($first['end']->format(DATE_ATOM)) ?>">
          <div class="count" <?= $live ? 'hidden' : '' ?> aria-hidden="true">
            <div><b data-d><?= intdiv($diff, 86400) ?></b><small><?= e(t('js_days')) ?></small></div>
            <div><b data-h><?= sprintf('%02d', intdiv($diff % 86400, 3600)) ?></b><small><?= e(t('js_hours')) ?></small></div>
            <div><b data-m><?= sprintf('%02d', intdiv($diff % 3600, 60)) ?></b><small><?= e(t('js_min')) ?></small></div>
          </div>
          <p class="count-msg" data-msg aria-live="polite"><?= $live ? e(t('js_started')) : '' ?></p>
        </div>
        <div class="btns">
          <a class="btn btn-light btn-sm" href="<?= e(I18n::url('event', $ev['slug']) . '.ics') ?>"><?= View::icon('calendar', 18) ?><?= e(t('add_calendar')) ?></a>
          <a class="btn btn-ghost btn-sm" href="<?= u('alerts') ?>" data-push-open><?= View::icon('bell', 18) ?><?= e(t('remind_me')) ?></a>
        </div>
      </div>
      <?php else: ?>
      <div class="notice"><?= e(t('event_past')) ?> <a href="<?= u('schedule') ?>"><?= e(t('nav_schedule')) ?></a></div>
      <?php endif; ?>

      <div class="prose"><?= nl2p((string) ($ev['description'] ?: $ev['summary'])) ?></div>

      <?php if (count($dates) > 1): ?>
      <div class="panel">
        <h2><?= e(t('more_dates')) ?></h2>
        <ul class="dates">
          <?php foreach ($dates as $d): ?><li><span><?= e(Schedule::longDate($d['start'])) ?></span><b><?= e(Schedule::time($d['start'])) ?></b></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>

    <aside class="panel">
      <h2><?= e(t('where')) ?></h2>
      <dl class="info">
        <div><dt><?= View::icon('pin') ?></dt><dd><b><?= e($place) ?></b><?= e($address) ?></dd></div>
        <div><dt><?= View::icon('clock') ?></dt><dd><b><?= e(Schedule::describe($ev)) ?></b><?= e(t('duration', ['n' => $hours])) ?></dd></div>
      </dl>
      <div class="routes">
        <a href="<?= e($links['google']) ?>" target="_blank" rel="noopener"><?= View::icon('map', 24) ?><span>Google</span></a>
        <a href="<?= e($links['waze']) ?>" target="_blank" rel="noopener"><?= View::icon('pin', 24) ?><span>Waze</span></a>
        <a href="<?= e($links['apple']) ?>" target="_blank" rel="noopener"><?= View::icon('locate', 24) ?><span>Apple</span></a>
      </div>
      <a class="arrow-link" href="<?= u('schedule') ?>"><?= e(t('see_all_schedule')) ?><?= View::icon('arrow', 18) ?></a>
    </aside>
  </div>
</section>
