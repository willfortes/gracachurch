<?php
use App\Content;
use App\I18n;
use App\Schedule;
use App\View;

$name = setting('church_name', 'Graça Church');
$specials = array_values(array_filter($events, fn ($e) => $e['category'] === 'evento'));
$ministries = array_values(array_filter($events, fn ($e) => $e['category'] === 'ministerio'));
$links = directions_links(setting('lat') ?: null, setting('lng') ?: null, $name . ', ' . church_address());
$live = $next && $next['next']['start'] <= $now;
$todayKey = $now->format('Y-m-d');
$pillars = [['prayer', 'hands'], ['fasting', 'flame'], ['word', 'book'], ['holiness', 'drop']];
$groupsByDay = array_count_values(array_map(fn ($g) => (int) $g['weekday'], array_filter($groups, fn ($g) => $g['recurrence'] === 'weekly')));
?>
<section class="hero" aria-labelledby="hero-title">
  <div class="hero-media" aria-hidden="true">
    <img src="/img/louvor-1024.webp" srcset="/img/louvor-640.webp 640w, /img/louvor-1024.webp 1024w, /img/louvor-1600.webp 1600w" sizes="100vw" width="1600" height="1064" alt="" fetchpriority="high" decoding="async">
  </div>
  <div class="spot" aria-hidden="true"></div>
  <canvas class="embers" data-embers aria-hidden="true"></canvas>

  <div class="wrap hero-inner">
    <div>
      <span class="eyebrow rise"><?= e(t('hero_eyebrow', ['city' => setting('church_city_label', 'Palhoça · SC')])) ?></span>
      <h1 id="hero-title" class="rise rise-2"><?= accent(setting('hero_title')) ?></h1>
      <p class="facts rise rise-3">
        <?php if ($summary): ?><strong><?= e(t('services_label')) ?>: <?= e($summary) ?>.</strong><?php endif; ?>
        <?= e(church_address()) ?>. <?= e(setting('hero_text')) ?>
      </p>
      <div class="btns btns-stack rise rise-4">
        <a class="btn btn-primary" href="<?= u('directions') ?>"><?= View::icon('pin', 20) ?><?= e(t('cta_directions')) ?></a>
        <a class="btn btn-ghost" href="#semana"><?= View::icon('calendar', 20) ?><?= e(t('cta_week')) ?></a>
      </div>
      <a class="scroll-cue" href="#semana"><span aria-hidden="true"></span><?= e(t('scroll_cue')) ?></a>
    </div>

    <?php if ($next): $start = $next['next']['start']; $diff = max(0, $start->getTimestamp() - $now->getTimestamp()); ?>
    <aside class="pass rise rise-3" aria-labelledby="pass-title">
      <div class="pass-top">
        <span class="pass-label"><span class="live" aria-hidden="true"></span><?= e($live ? t('live_now') : t('next_service')) ?></span>
        <a class="btn btn-ghost btn-sm" href="<?= e(I18n::url('event', $next['slug']) . '.ics') ?>" aria-label="<?= e(t('add_calendar')) ?>" style="min-height:40px;padding:0 14px"><?= View::icon('calendar', 18) ?></a>
      </div>
      <h2 id="pass-title"><?= e($next['title']) ?></h2>
      <p class="pass-when"><?= e(Schedule::relative($start, $now)) ?></p>
      <div data-countdown="<?= e($start->format(DATE_ATOM)) ?>" data-end="<?= e($next['next']['end']->format(DATE_ATOM)) ?>">
        <div class="count" <?= $live ? 'hidden' : '' ?> aria-hidden="true">
          <div><b data-d><?= intdiv($diff, 86400) ?></b><small><?= e(t('js_days')) ?></small></div>
          <div><b data-h><?= sprintf('%02d', intdiv($diff % 86400, 3600)) ?></b><small><?= e(t('js_hours')) ?></small></div>
          <div><b data-m><?= sprintf('%02d', intdiv($diff % 3600, 60)) ?></b><small><?= e(t('js_min')) ?></small></div>
        </div>
        <p class="count-msg" data-msg aria-live="polite"><?= $live ? e(t('js_started')) : '' ?></p>
      </div>
      <p class="pass-place"><?= View::icon('pin', 18) ?><span><?= e($next['location_name'] ?: $name) ?> · <?= e($next['address'] ?: church_address()) ?></span></p>
      <div class="btns">
        <a class="btn btn-light btn-sm" href="<?= e($links['google']) ?>" target="_blank" rel="noopener" style="flex:1"><?= View::icon('map', 18) ?><?= e(t('route')) ?></a>
        <a class="btn btn-ghost btn-sm" href="<?= u('alerts') ?>" data-push-open style="flex:1"><?= View::icon('bell', 18) ?><?= e(t('remind_me')) ?></a>
      </div>
    </aside>
    <?php endif; ?>
  </div>
</section>

<div class="marquee" aria-hidden="true">
  <div class="marquee-track">
    <?php for ($k = 0; $k < 2; $k++): ?>
    <span><?php foreach ($pillars as $i => [$key]): ?><?= $i % 2 ? '<em>' . e(t('pillar_' . $key)) . '</em>' : e(t('pillar_' . $key)) ?><i>✦</i><?php endforeach; ?></span>
    <?php endfor; ?>
  </div>
</div>

<section class="sec glow-bg" id="semana" aria-labelledby="week-title">
  <div class="wrap">
    <div class="sec-head-split">
      <div class="sec-head">
        <span class="eyebrow"><?= e(t('week_eyebrow')) ?></span>
        <h2 id="week-title"><?= accent(t('week_title')) ?></h2>
      </div>
      <a class="arrow-link" href="<?= u('schedule') ?>"><?= e(t('full_schedule')) ?><?= View::icon('arrow', 20) ?></a>
    </div>

    <?php foreach (array_slice($specials, 0, 1) as $ev): $d = $ev['next']['start']; ?>
    <a class="feature reveal" href="<?= u('event', $ev['slug']) ?>">
      <div class="feature-date"><b><?= $d->format('j') ?></b><small><?= e(Schedule::month((int) $d->format('n'))) ?></small></div>
      <div><span class="eyebrow" style="color:var(--gold)"><?= e(t('cat_evento')) ?></span><h3><?= e($ev['title']) ?></h3><p><?= e(Schedule::relative($d, $now)) ?><?= $ev['location_name'] ? ' · ' . e($ev['location_name']) : '' ?></p></div>
      <span class="btn btn-light btn-sm"><?= e(t('see_details')) ?><?= View::icon('arrow', 18) ?></span>
    </a>
    <?php endforeach; ?>

    <div class="week" tabindex="0" role="region" aria-label="<?= e(t('week_region')) ?>">
      <?php foreach ($week as $key => $day): $d = $day['date']; $isToday = $key === $todayKey; ?>
      <div class="day<?= $isToday ? ' today' : '' ?>">
        <?php if ($isToday): ?><span class="day-badge"><?= e(t('today')) ?></span><?php endif; ?>
        <div class="day-head"><b><?= e(Schedule::dayShort((int) $d->format('w'))) ?></b><span><?= $d->format('j') ?></span></div>
        <?php foreach ($day['items'] as $it): $ev = $it['event']; ?>
        <a class="slot <?= e($ev['category']) ?>" href="<?= u('event', $ev['slug']) ?>">
          <time datetime="<?= e($it['start']->format(DATE_ATOM)) ?>"><?= e(Schedule::time($it['start'])) ?></time>
          <b><?= e($ev['title']) ?></b>
          <small><?= e(Content::categoryLabel($ev['category'])) ?></small>
        </a>
        <?php endforeach; ?>
        <?php $gc = $groupsByDay[(int) $d->format('w')] ?? 0; ?>
        <?php if (!$day['items'] && !$gc): ?><p class="day-empty"><?= e(t('day_free')) ?></p><?php endif; ?>
        <?php if ($gc): ?><a class="day-groups" href="<?= u('groups') ?>"><?= View::icon('people', 14) ?><?= e(t('day_groups', ['n' => $gc])) ?></a><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="scroll-hint"><?= View::icon('arrow', 16) ?><?= e(t('swipe_hint')) ?></p>

    <?php if ($ministries): ?>
    <div class="months">
      <?php foreach ($ministries as $ev): $d = $ev['next']['start']; ?>
      <a class="month-row reveal" href="<?= u('event', $ev['slug']) ?>">
        <span class="month-date"><b><?= $d->format('j') ?></b><small><?= e(Schedule::monthShort((int) $d->format('n'))) ?></small></span>
        <span><span class="month-title"><?= e($ev['title']) ?></span><span class="month-sub"><?= e(Schedule::describe($ev)) ?></span></span>
        <?= View::icon('arrow', 22) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="sec" id="grupos" aria-labelledby="groups-title">
  <div class="wrap groups-wrap">
    <div>
      <div class="sec-head">
        <span class="eyebrow"><?= e(t('nav_groups')) ?></span>
        <h2 id="groups-title"><?= accent(t('groups_title')) ?></h2>
        <p class="lead"><?= e(setting('groups_intro')) ?></p>
      </div>
      <?php if ($groups): ?>
      <div class="group-list">
        <?php foreach (array_slice($groups, 0, 3) as $g): ?><?= View::render('partials/group-card', ['g' => $g]) ?><?php endforeach; ?>
      </div>
      <div class="btns" style="margin-top:24px">
        <a class="btn btn-primary" href="<?= u('groups') ?>"><?= View::icon('people', 20) ?><?= e(t('all_groups', ['n' => count($groups)])) ?></a>
      </div>
      <?php else: ?>
      <div class="empty">
        <p><b style="color:#fff"><?= e(t('groups_soon')) ?></b></p>
        <?php if ($wa = setting('whatsapp_1')): ?>
        <p style="margin-top:16px"><a class="btn btn-wa" href="<?= e(whatsapp_link($wa, t('wa_group'))) ?>" target="_blank" rel="noopener"><?= View::icon('whatsapp', 20) ?><?= e(t('want_group')) ?></a></p>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php $pts = Content::mapData($groups); if ($pts): ?>
      <?= View::render('partials/sky', ['points' => $pts, 'id' => 'mapa-home']) ?>
    <?php endif; ?>
  </div>
</section>

<section class="sec glow-bg" id="quem-somos" aria-labelledby="about-title">
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow"><?= e(t('pillars_eyebrow')) ?></span>
      <h2 id="about-title"><?= accent(setting('about_title')) ?></h2>
    </div>
    <div class="pillars">
      <?php foreach ($pillars as [$key]): ?>
      <div class="pillar"><h3><?= e(t('pillar_' . $key)) ?></h3><p><?= e(t('pillar_' . $key . '_text')) ?></p></div>
      <?php endforeach; ?>
    </div>
    <div class="about-grid">
      <p class="quote reveal"><?= e(t('about_quote')) ?></p>
      <div class="about-text reveal"><?= nl2p(setting('about_text')) ?></div>
    </div>
  </div>
</section>

<section class="sec" id="visite" aria-labelledby="visit-title">
  <div class="wrap visit">
    <figure class="photo reveal">
      <img src="/img/congregacao-640.webp" srcset="/img/congregacao-640.webp 640w, /img/congregacao-1200.webp 1200w" sizes="(min-width: 960px) 600px, 100vw" width="1200" height="901" alt="<?= e(t('photo_alt')) ?>" loading="lazy" decoding="async">
      <figcaption><?= e(t('photo_caption')) ?></figcaption>
    </figure>
    <div style="display:grid;gap:26px">
      <span class="eyebrow"><?= e(t('nav_directions')) ?></span>
      <h2 id="visit-title" class="sr"><?= e(t('visit_title')) ?></h2>
      <p class="address-big"><b><?= e(setting('address_street', 'BR 282, KM 18')) ?></b><span><?= e(setting('address_city', 'Palhoça')) ?> – <?= e(setting('address_state', 'SC')) ?> · <?= e(setting('address_zip')) ?></span></p>
      <?php if ($note = setting('directions_note')): ?><p class="muted"><?= e($note) ?></p><?php endif; ?>
      <div class="routes">
        <a href="<?= e($links['google']) ?>" target="_blank" rel="noopener"><?= View::icon('map', 26) ?><span>Google Maps<small><?= e(t('open_route')) ?></small></span></a>
        <a href="<?= e($links['waze']) ?>" target="_blank" rel="noopener"><?= View::icon('pin', 26) ?><span>Waze<small><?= e(t('open_route')) ?></small></span></a>
        <a href="<?= e($links['apple']) ?>" target="_blank" rel="noopener"><?= View::icon('locate', 26) ?><span>Apple Maps<small><?= e(t('open_route')) ?></small></span></a>
      </div>
      <?php if ($wa = setting('whatsapp_1')): ?>
      <div class="btns btns-stack">
        <a class="btn btn-wa" href="<?= e(whatsapp_link($wa, t('wa_visit', ['name' => $name]))) ?>" target="_blank" rel="noopener"><?= View::icon('whatsapp', 20) ?><?= e(t('first_time')) ?></a>
        <button class="btn btn-ghost" type="button" data-copy="<?= e($name . ' – ' . church_address()) ?>"><?= View::icon('share', 20) ?><?= e(t('copy_address')) ?></button>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="sec" style="padding-top:0" id="contato" aria-labelledby="contact-home-title">
  <div class="wrap">
    <div class="contact-band reveal">
      <div class="sec-head" style="margin-bottom:0">
        <span class="eyebrow"><?= e(t('contact_eyebrow')) ?></span>
        <h2 id="contact-home-title"><?= accent(t('contact_home_title')) ?></h2>
        <p class="lead"><?= e(t('contact_text')) ?></p>
      </div>
      <?= View::render('partials/contact', ['big' => true]) ?>
    </div>
  </div>
</section>

<section class="sec" style="padding-top:0" aria-labelledby="app-title">
  <div class="wrap">
    <div class="app reveal">
      <div style="display:grid;gap:20px">
        <span class="eyebrow"><?= e(t('app_eyebrow')) ?></span>
        <h2 id="app-title"><?= accent(t('app_title')) ?></h2>
        <p><?= e(t('app_text')) ?></p>
        <div class="platforms-line" aria-label="<?= e(t('works_on')) ?>"><span>Android</span><span>iPhone</span><span>Windows</span><span>Mac</span><span>Linux</span></div>
        <div class="btns btns-stack">
          <button class="btn btn-light" type="button" data-install hidden><?= View::icon('download', 20) ?><?= e(t('install_app')) ?></button>
          <a class="btn btn-light" href="<?= u('alerts') ?>" data-push-open><?= View::icon('bell', 20) ?><?= e(t('cta_alerts')) ?></a>
          <a class="btn btn-ghost" href="<?= u('alerts') ?>" data-install-help><?= e(t('how_install')) ?></a>
        </div>
      </div>
      <div class="phone" aria-hidden="true">
        <div class="phone-screen">
          <span class="phone-time"><?= $next ? e($next['next']['start']->modify('-2 hours')->format('H:i')) : '17:30' ?></span>
          <span class="phone-date"><?= $next ? e(Schedule::longDate($next['next']['start'])) : '' ?></span>
          <div class="notif">
            <img src="/icons/icon-192.png" width="34" height="34" alt="">
            <div><b><?= e(($next['title'] ?? t('next_service')) . ' · ' . ($next ? Schedule::relative($next['next']['start'], $next['next']['start']) : '')) ?></b><span><?= e(t('notif_demo')) ?></span></div>
            <small><?= e(t('now')) ?></small>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($faqs): ?>
<section class="sec glow-bg" aria-labelledby="faq-title">
  <div class="wrap faq-grid">
    <div class="sec-head">
      <span class="eyebrow"><?= e(t('faq_eyebrow')) ?></span>
      <h2 id="faq-title"><?= accent(t('faq_title')) ?></h2>
      <p class="lead"><?= e(t('faq_lead')) ?></p>
    </div>
    <div class="faq">
      <?php foreach ($faqs as $i => $f): ?>
      <details<?= $i === 0 ? ' open' : '' ?>><summary><?= e($f['question']) ?></summary><p><?= e($f['answer']) ?></p></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
