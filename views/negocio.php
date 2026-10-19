<?php
use App\Directory;
use App\Http\Business;
use App\View;

$c = Business::card($b);
$tags = array_values(array_filter(array_map('trim', explode(',', (string) $b['tags']))));
$modes = array_filter(explode(',', $b['service_mode']));
$initials = mb_strtoupper(mb_substr(preg_replace('/[^\p{L}\p{N} ]/u', '', $b['name']) ?: 'G', 0, 1));
$place = trim($b['neighborhood'] . ', ' . $b['city'], ', ');
$links = directions_links($b['show_address'] && $b['lat'] !== null ? (string) $b['lat'] : null, $b['show_address'] && $b['lng'] !== null ? (string) $b['lng'] : null, ($b['show_address'] && $b['address'] ? $b['address'] . ', ' : '') . $place);
?>
<header class="page-head biz-head">
  <div class="spot" aria-hidden="true"></div>
  <div class="wrap">
    <nav class="crumbs" aria-label="<?= e(t('crumbs_label')) ?>">
      <a href="<?= u() ?>"><?= e(t('crumbs_home')) ?></a><span aria-hidden="true">/</span>
      <a href="<?= u('business') ?>"><?= e(t('nav_business')) ?></a><span aria-hidden="true">/</span>
      <a href="<?= e(Business::catUrl($b['category'])) ?>"><?= e($catLabel) ?></a>
    </nav>
    <div class="biz-head-in">
      <span class="biz-logo big cat-<?= e($b['category']) ?>"><?php if ($c['logo']): ?><img src="<?= e($c['logo']) ?>" width="140" height="140" alt="<?= e(t('biz_logo_alt', ['biz' => $b['name']])) ?>"><?php else: ?><b aria-hidden="true"><?= e($initials) ?></b><?php endif; ?></span>
      <div>
        <span class="eyebrow"><?= e($catLabel) ?></span>
        <h1><?= e($b['name']) ?></h1>
        <p class="lead"><?= e(t('biz_answer', ['biz' => $b['name'], 'cat' => mb_strtolower($catLabel), 'place' => $place ?: $b['city'], 'owner' => $b['owner_name']])) ?></p>
      </div>
    </div>
  </div>
</header>

<section class="sec glow-bg">
  <div class="wrap detail">
    <div style="display:grid;gap:28px">
      <div class="prose"><?= nl2p($b['description']) ?></div>
      <?php if ($tags): ?>
      <div>
        <h2 style="font-size:1.2rem;color:#fff;margin-bottom:12px"><?= e(t('biz_services')) ?></h2>
        <ul class="tag-list"><?php foreach ($tags as $tg): ?><li><a href="<?= e(u('business') . '?q=' . rawurlencode($tg)) ?>"><?= e($tg) ?></a></li><?php endforeach; ?></ul>
      </div>
      <?php endif; ?>
      <div class="notice"><?= View::icon('people', 18) ?> <?= e(t('biz_member_note', ['owner' => $b['owner_name'], 'name' => setting('church_name', 'Graça Church')])) ?></div>

      <?php if ($related): ?>
      <div>
        <h2 style="font-size:1.4rem;color:#fff;margin-bottom:16px"><?= e(t('biz_related', ['cat' => mb_strtolower($catLabel)])) ?></h2>
        <div class="biz-grid"><?php foreach ($related as $r): ?><?= View::render('partials/biz-card', ['b' => $r]) ?><?php endforeach; ?></div>
      </div>
      <?php endif; ?>
    </div>

    <aside class="panel">
      <h2><?= e(t('biz_contact')) ?></h2>
      <a class="btn btn-wa" href="<?= e($c['wa']) ?>" target="_blank" rel="noopener"><?= View::icon('whatsapp', 20) ?><?= e(t('biz_wa_cta')) ?></a>
      <dl class="info">
        <div><dt><?= View::icon('whatsapp') ?></dt><dd><b>WhatsApp</b><?= e(phone_display($b['whatsapp'])) ?></dd></div>
        <?php if ($b['instagram']): ?><div><dt><?= View::icon('instagram') ?></dt><dd><b>Instagram</b><a href="https://www.instagram.com/<?= e($b['instagram']) ?>/" target="_blank" rel="noopener">@<?= e($b['instagram']) ?></a></dd></div><?php endif; ?>
        <?php if ($b['website']): ?><div><dt><?= View::icon('external') ?></dt><dd><b><?= e(t('biz_site')) ?></b><a href="<?= e($b['website']) ?>" target="_blank" rel="noopener nofollow"><?= e(preg_replace('#^https?://(www\.)?#', '', rtrim($b['website'], '/'))) ?></a></dd></div><?php endif; ?>
        <?php if ($b['email']): ?><div><dt><?= View::icon('send') ?></dt><dd><b>E-mail</b><?= e($b['email']) ?></dd></div><?php endif; ?>
        <div><dt><?= View::icon('pin') ?></dt><dd><b><?= e($place ?: t('biz_mode_online')) ?></b><?= $b['show_address'] && $b['address'] ? e($b['address']) : '' ?><?php if ($b['service_area']): ?><br><?= e(t('biz_area')) ?>: <?= e($b['service_area']) ?><?php endif; ?></dd></div>
        <?php if ($modes): ?><div><dt><?= View::icon('check') ?></dt><dd><b><?= e(t('biz_how')) ?></b><?= e(implode(' · ', array_map(fn ($m) => t('biz_mode_' . $m), $modes))) ?></dd></div><?php endif; ?>
        <?php if ($b['hours']): ?><div><dt><?= View::icon('clock') ?></dt><dd><b><?= e(t('biz_hours')) ?></b><?= e($b['hours']) ?></dd></div><?php endif; ?>
      </dl>
      <?php if ($b['show_address'] && ($b['address'] || $b['lat'] !== null)): ?>
      <div class="routes">
        <a href="<?= e($links['google']) ?>" target="_blank" rel="noopener"><?= View::icon('map', 24) ?><span>Google</span></a>
        <a href="<?= e($links['waze']) ?>" target="_blank" rel="noopener"><?= View::icon('pin', 24) ?><span>Waze</span></a>
        <a href="<?= e($links['apple']) ?>" target="_blank" rel="noopener"><?= View::icon('locate', 24) ?><span>Apple</span></a>
      </div>
      <?php endif; ?>
      <button class="btn btn-ghost btn-sm" type="button" data-share data-title="<?= e($b['name']) ?>"><?= View::icon('share', 18) ?><?= e(t('biz_share')) ?></button>
    </aside>
  </div>
</section>
