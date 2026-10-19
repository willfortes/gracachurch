<?php
use App\Content;
use App\Schedule;
use App\View;

$label = $g['neighborhood'] . ', ' . $g['city'];
[$plat, $plng] = $g['lat'] !== null ? Content::publicCoords($g) : [null, null];
$links = directions_links($plat !== null ? (string) $plat : null, $plng !== null ? (string) $plng : null, ($g['show_address'] && $g['address'] ? $g['address'] : $label));
$first = $dates[0] ?? null;

echo View::render('partials/page-head', [
    'crumbs' => [t('nav_groups') => u('groups'), $g['name'] => null],
    'label' => t('group_label', ['aud' => $g['audience']]),
    'title' => $g['name'],
    'lead' => e(Schedule::describe($g)) . ' · ' . e($label),
]);
?>
<section class="sec glow-bg">
  <div class="wrap detail">
    <div style="display:grid;gap:28px">
      <?php if ($first): $diff = max(0, $first['start']->getTimestamp() - $now->getTimestamp()); ?>
      <div class="pass">
        <span class="pass-label"><span class="live" aria-hidden="true"></span><?= e(t('next_meeting')) ?></span>
        <p class="pass-when" style="font-size:1.4rem;font-weight:800;color:#fff"><?= e(Schedule::relative($first['start'], $now)) ?></p>
        <div data-countdown="<?= e($first['start']->format(DATE_ATOM)) ?>" data-end="<?= e($first['end']->format(DATE_ATOM)) ?>">
          <div class="count" aria-hidden="true">
            <div><b data-d><?= intdiv($diff, 86400) ?></b><small><?= e(t('js_days')) ?></small></div>
            <div><b data-h><?= sprintf('%02d', intdiv($diff % 86400, 3600)) ?></b><small><?= e(t('js_hours')) ?></small></div>
            <div><b data-m><?= sprintf('%02d', intdiv($diff % 3600, 60)) ?></b><small><?= e(t('js_min')) ?></small></div>
          </div>
          <p class="count-msg" data-msg aria-live="polite"></p>
        </div>
        <div class="btns">
          <?php if ($g['leader_phone']): ?>
          <a class="btn btn-wa" href="<?= e(whatsapp_link($g['leader_phone'], t('wa_join', ['group' => $g['name']]))) ?>" target="_blank" rel="noopener"><?= View::icon('whatsapp', 20) ?><?= e(t('join')) ?></a>
          <?php endif; ?>
          <button class="btn btn-ghost" type="button" data-group-follow="<?= (int) $g['id'] ?>"><?= View::icon('bell', 20) ?><?= e(t('follow_group')) ?></button>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($g['description']): ?><div class="prose"><?= nl2p($g['description']) ?></div><?php endif; ?>

      <?php if ($g['lat'] !== null): ?>
        <?= View::render('partials/sky', ['points' => Content::mapData([$g], true), 'id' => 'mapa-grupo']) ?>
      <?php endif; ?>
    </div>

    <aside class="panel">
      <h2><?= e(t('info')) ?></h2>
      <dl class="info">
        <div><dt><?= View::icon('clock') ?></dt><dd><b><?= e(Schedule::describe($g)) ?></b><?= e(t('meeting_length', ['n' => max(1, (int) round($g['duration_min'] / 60))])) ?></dd></div>
        <div><dt><?= View::icon('pin') ?></dt><dd><b><?= e($label) ?></b><?= $g['show_address'] && $g['address'] ? e($g['address']) : e(t('address_by_leader')) ?></dd></div>
        <div><dt><?= View::icon('people') ?></dt><dd><b><?= e(t('for_whom')) ?></b><?= e($g['audience']) ?></dd></div>
        <?php if ($g['leader_name']): ?>
        <div><dt><?= View::icon('user') ?></dt><dd><b><?= e(t('leader')) ?></b><?= e($g['leader_name']) ?><?= $g['leader_phone'] ? ' · ' . e(phone_display($g['leader_phone'])) : '' ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($g['assistants'])): ?>
        <div><dt><?= View::icon('people') ?></dt><dd><b><?= e(t('assistants')) ?></b><?= e($g['assistants']) ?></dd></div>
        <?php endif; ?>
      </dl>
      <?php if ($g['show_address'] || $g['lat'] !== null): ?>
      <div class="routes">
        <a href="<?= e($links['google']) ?>" target="_blank" rel="noopener"><?= View::icon('map', 24) ?><span>Google</span></a>
        <a href="<?= e($links['waze']) ?>" target="_blank" rel="noopener"><?= View::icon('pin', 24) ?><span>Waze</span></a>
        <a href="<?= e($links['apple']) ?>" target="_blank" rel="noopener"><?= View::icon('locate', 24) ?><span>Apple</span></a>
      </div>
      <?php endif; ?>
      <a class="arrow-link" href="<?= u('groups') ?>"><?= e(t('other_groups')) ?><?= View::icon('arrow', 18) ?></a>
    </aside>
  </div>
</section>
