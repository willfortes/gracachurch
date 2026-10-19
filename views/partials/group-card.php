<?php
use App\Content;
use App\Schedule;
use App\View;

$next = $g['next'] ?? Schedule::next($g);
$geo = $g['lat'] !== null ? Content::publicCoords($g) : null;
?>
<article class="group" data-group data-id="<?= (int) $g['id'] ?>" data-weekday="<?= (int) $g['weekday'] ?>"
  data-search="<?= e(mb_strtolower($g['name'] . ' ' . $g['neighborhood'] . ' ' . $g['city'] . ' ' . $g['audience'])) ?>"
  <?php if ($geo): ?>data-lat="<?= e($geo[0]) ?>" data-lng="<?= e($geo[1]) ?>"<?php endif; ?>>
  <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start">
    <h3><a href="<?= u('groups', $g['slug']) ?>"><?= e($g['name']) ?></a></h3>
    <span class="dist" data-dist hidden></span>
  </div>
  <div class="group-meta">
    <span><?= View::icon('pin', 18) ?><?= e($g['neighborhood']) ?>, <?= e($g['city']) ?></span>
    <span><?= View::icon('clock', 18) ?><?= e(Schedule::describe($g)) ?></span>
    <?php if (!empty($g['leader_name'])): ?><span><?= View::icon('user', 18) ?><?= e($g['leader_name']) ?></span><?php endif; ?>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between">
    <span class="tag"><?= e($g['audience']) ?></span>
    <?php if ($next): ?><span class="muted" style="font-size:.88rem"><?= e(t('next_label', ['when' => Schedule::relative($next['start'])])) ?></span><?php endif; ?>
  </div>
  <div class="btns">
    <?php if (!empty($g['leader_phone'])): ?>
    <a class="btn btn-wa btn-sm" href="<?= e(whatsapp_link($g['leader_phone'], t('wa_join', ['group' => $g['name']]))) ?>" target="_blank" rel="noopener"><?= View::icon('whatsapp', 20) ?><?= e(t('join')) ?></a>
    <?php endif; ?>
    <?php if ($geo): ?><button class="btn btn-ghost btn-sm" type="button" data-fly="<?= (int) $g['id'] ?>"><?= View::icon('map', 18) ?><?= e(t('show_on_map')) ?></button><?php endif; ?>
    <a class="btn btn-ghost btn-sm" href="<?= u('groups', $g['slug']) ?>"><?= e(t('see_details')) ?></a>
  </div>
</article>
