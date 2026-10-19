<?php
use App\Content;
use App\I18n;
use App\Seo;
use App\View;

$name = setting('church_name', 'Graça Church');
$lang = I18n::$lang;
$title = plain($meta['title'] ?? $name);
$desc = truncate(plain($meta['description'] ?? ''), 160);
$route = $meta['route'] ?? 'home';
$slug = $meta['slug'] ?? '';
$canonical = app_url(I18n::url($route, $slug));
$nav = $meta['nav'] ?? '';
$image = app_url('/img/og.jpg');
$geo = Content::churchGeo();
$cur = fn (string $k) => $nav === $k ? ' aria-current="page"' : '';
$wa = setting('whatsapp_1');
$ig = setting('instagram');
$indexable = empty($meta['robots']);
?><!doctype html>
<html lang="<?= I18n::htmlLang() ?>" class="<?= e(a11y_classes()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if ($indexable): ?>
<meta name="robots" content="index, follow, max-image-preview:large">
<?php foreach (I18n::LANGS as $l): ?><link rel="alternate" hreflang="<?= I18n::HTML_LANG[$l] ?>" href="<?= e(app_url(I18n::url($route, $slug, $l))) ?>">
<?php endforeach; ?><link rel="alternate" hreflang="x-default" href="<?= e(app_url(I18n::url($route, $slug, 'pt'))) ?>">
<?php else: ?><meta name="robots" content="<?= e($meta['robots']) ?>">
<?php endif; ?>
<meta name="theme-color" content="#0A0614">
<meta name="color-scheme" content="dark">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/favicon.ico" sizes="48x48">
<link rel="icon" href="/icons/favicon-32.png" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e($name) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="<?= I18n::LOCALES[$lang] ?>">
<?php foreach (I18n::LANGS as $l): if ($l !== $lang): ?><meta property="og:locale:alternate" content="<?= I18n::LOCALES[$l] ?>">
<?php endif; endforeach; ?>
<meta property="og:site_name" content="<?= e($name) ?> Palhoça">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($image) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="geo.region" content="BR-<?= e(setting('address_state', 'SC')) ?>">
<meta name="geo.placename" content="<?= e(setting('address_city', 'Palhoça')) ?>">
<?php if ($geo['lat'] !== ''): ?><meta name="geo.position" content="<?= e($geo['lat'] . ';' . $geo['lng']) ?>">
<meta name="ICBM" content="<?= e($geo['lat'] . ', ' . $geo['lng']) ?>">
<?php endif; ?>
<?php if (!empty($meta['preload_hero'])): ?>
<link rel="preload" as="image" type="image/webp" fetchpriority="high" imagesrcset="/img/louvor-640.webp 640w, /img/louvor-1024.webp 1024w, /img/louvor-1600.webp 1600w" imagesizes="100vw">
<?php endif; ?>
<link rel="preload" as="font" type="font/woff2" href="/fonts/montserrat-latin-wght-normal.woff2" crossorigin>
<meta name="vapid-key" content="<?= e(env('VAPID_PUBLIC_KEY', '')) ?>">
<style><?= file_get_contents(ROOT . '/views/partials/site.css') ?></style>
<?php if (!empty($meta['jsonld'])): ?><script type="application/ld+json"><?= Seo::graph($meta['jsonld']) ?></script>
<?php endif; ?>
<script type="application/json" id="i18n"><?= json_encode([
    'lang' => $lang,
    'starts' => t('js_starts'), 'started' => t('js_started'), 'ended' => t('js_ended'),
    'days' => t('js_days'), 'hours' => t('js_hours'), 'min' => t('js_min'),
    'activating' => t('js_activating'), 'ready' => t('js_ready'), 'denied' => t('js_denied'), 'failed' => t('js_failed'),
    'pick' => t('js_pick'), 'noKey' => t('js_nokey'), 'off' => t('js_off'), 'offFail' => t('js_offfail'),
    'on' => t('js_on'), 'iosFirst' => t('js_iosfirst'), 'unsupported' => t('push_unsupported'),
    'topics' => ['cultos' => t('topic_cultos'), 'eventos' => t('topic_eventos'), 'ministerios' => t('topic_ministerios'), 'grupo' => t('topic_group')],
    'locating' => t('js_locating'), 'sorted' => t('js_sorted'), 'noLoc' => t('js_noloc'), 'here' => t('js_here'),
    'mapLoading' => t('js_maploading'), 'mapFail' => t('js_mapfail'), 'details' => t('see_details'), 'wa' => t('wa_cta'), 'bizResult' => t('js_biz_result'), 'bizResults' => t('js_biz_results'), 'bizNone' => t('js_biz_none'), 'bizDidYouMean' => t('biz_did_you_mean'), 'copied' => t('js_copied'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script src="<?= asset('/js/app.js') ?>" defer></script>
</head>
<body>
<a class="skip" href="#conteudo"><?= e(t('skip')) ?></a>

<header class="top solid" data-top>
  <div class="wrap">
    <a class="top-logo" href="<?= u() ?>" aria-label="<?= e(t('home_label', ['name' => $name])) ?>">
      <img src="/img/logo-claro.png" width="300" height="121" alt="<?= e($name) ?>">
    </a>
    <nav class="top-nav" aria-label="<?= e(t('nav_main')) ?>">
      <a href="<?= u('schedule') ?>"<?= $cur('agenda') ?>><?= e(t('nav_schedule')) ?></a>
      <a href="<?= u('groups') ?>"<?= $cur('grupos') ?>><?= e(t('tab_groups')) ?></a>
      <a href="<?= u('directions') ?>"<?= $cur('chegar') ?>><?= e(t('nav_directions')) ?></a>
      <a href="<?= u('business') ?>"<?= $cur('negocios') ?>><?= e(t('nav_business')) ?></a>
      <a href="<?= u('help') ?>"<?= $cur('ajuda') ?>><?= e(t('nav_help')) ?></a>
    </nav>

    <div class="top-tools">
      <details class="tool" data-close-outside>
        <summary aria-label="<?= e(t('lang_choose')) ?>" title="<?= e(t('lang_choose')) ?>"><?= View::icon('globe', 18) ?><span><?= strtoupper($lang) ?></span></summary>
        <div class="pop">
          <ul class="lang-list">
            <?php foreach (I18n::LANGS as $l): ?>
            <li><a href="<?= e(I18n::url($route, $slug, $l)) ?>" hreflang="<?= I18n::HTML_LANG[$l] ?>" lang="<?= I18n::HTML_LANG[$l] ?>"<?= $l === $lang ? ' aria-current="true"' : '' ?> data-lang="<?= $l ?>"><?= e(I18n::NAMES[$l]) ?><small><?= strtoupper($l) ?></small></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </details>

      <details class="tool" data-close-outside data-a11y>
        <summary aria-label="<?= e(t('a11y_title')) ?>" title="<?= e(t('a11y_title')) ?>"><?= View::icon('access', 20) ?></summary>
        <div class="pop a11y" role="group" aria-label="<?= e(t('a11y_title')) ?>">
          <h2><?= e(t('a11y_title')) ?></h2>
          <div class="a11y-row">
            <span id="a11y-size"><?= e(t('a11y_size')) ?></span>
            <div class="seg" role="group" aria-labelledby="a11y-size">
              <button type="button" data-size="" aria-label="<?= e(t('a11y_size_0')) ?>">A</button>
              <button type="button" data-size="t-1" aria-label="<?= e(t('a11y_size_1')) ?>">A</button>
              <button type="button" data-size="t-2" aria-label="<?= e(t('a11y_size_2')) ?>">A</button>
              <button type="button" data-size="t-3" aria-label="<?= e(t('a11y_size_3')) ?>">A</button>
            </div>
          </div>
          <button type="button" class="toggle" data-pref="contrast" aria-pressed="false"><?= e(t('a11y_contrast')) ?><span class="sw" aria-hidden="true"></span></button>
          <button type="button" class="toggle" data-pref="motion" aria-pressed="false"><?= e(t('a11y_motion')) ?><span class="sw" aria-hidden="true"></span></button>
          <button type="button" class="toggle" data-pref="links" aria-pressed="false"><?= e(t('a11y_links')) ?><span class="sw" aria-hidden="true"></span></button>
          <button type="button" class="toggle" data-pref="spacing" aria-pressed="false"><?= e(t('a11y_spacing')) ?><span class="sw" aria-hidden="true"></span></button>
          <?php if ($lang === 'pt'): ?>
          <button type="button" class="btn btn-ghost btn-sm" data-libras><?= View::icon('hands', 18) ?><?= e(t('a11y_libras')) ?></button>
          <?php endif; ?>
          <button type="button" class="btn btn-ghost btn-sm" data-a11y-reset><?= e(t('a11y_reset')) ?></button>
        </div>
      </details>

      <a class="tool-link" href="<?= u('alerts') ?>" data-push-open aria-label="<?= e(t('cta_alerts')) ?>" title="<?= e(t('cta_alerts')) ?>"><?= View::icon('bell', 20) ?></a>

      <a class="btn btn-wa btn-sm top-contact" href="<?= u('help') ?>" data-contact-open><?= View::icon('whatsapp', 18) ?><span><?= e(t('contact_us')) ?></span></a>

      <details class="menu" data-close-outside>
        <summary aria-label="<?= e(t('menu')) ?>"><span class="burger" aria-hidden="true"><i></i><i></i></span><span class="menu-label"><?= e(t('menu')) ?></span></summary>
        <div class="menu-panel">
          <nav aria-label="<?= e(t('nav_main')) ?>">
            <a href="<?= u() ?>"<?= $cur('home') ?>><?= e(t('tab_home')) ?></a>
            <a href="<?= u('schedule') ?>"<?= $cur('agenda') ?>><?= e(t('nav_schedule')) ?></a>
            <a href="<?= u('groups') ?>"<?= $cur('grupos') ?>><?= e(t('nav_groups')) ?></a>
            <a href="<?= u('directions') ?>"<?= $cur('chegar') ?>><?= e(t('nav_directions')) ?></a>
            <a href="<?= u('business') ?>"<?= $cur('negocios') ?>><?= e(t('nav_business')) ?></a>
            <a href="<?= u('about') ?>"<?= $cur('sobre') ?>><?= e(t('nav_about')) ?></a>
            <a href="<?= u('help') ?>"<?= $cur('ajuda') ?>><?= e(t('nav_help')) ?></a>
            <a href="<?= u('alerts') ?>"<?= $cur('avisos') ?>><?= e(t('cta_alerts')) ?></a>
          </nav>
          <div class="menu-contact">
            <span class="eyebrow"><?= e(t('contact_eyebrow')) ?></span>
            <?= View::render('partials/contact') ?>
          </div>
        </div>
      </details>
    </div>
  </div>
</header>

<main id="conteudo" tabindex="-1">
<?= $content ?>
</main>

<footer class="foot">
  <img class="foot-mark" src="/img/logo-claro.png" width="300" height="121" alt="" loading="lazy">
  <div class="wrap">
    <div class="foot-grid">
      <div>
        <p class="quote" style="font-size:1.6rem;max-width:20ch"><?= e(plain(setting('hero_title'))) ?></p>
      </div>
      <div>
        <h2><?= e(t('foot_visit')) ?></h2>
        <address style="font-style:normal" class="muted"><?= e(church_address()) ?></address>
        <?php if ($s = Content::cultoSummary()): ?><p class="muted" style="margin-top:8px"><?= e(t('services_label')) ?>: <?= e($s) ?></p><?php endif; ?>
        <p style="margin-top:12px"><a class="arrow-link" href="<?= u('directions') ?>"><?= e(t('nav_directions')) ?><?= View::icon('arrow', 18) ?></a></p>
      </div>
      <div>
        <h2><?= e(t('foot_talk')) ?></h2>
        <ul>
          <?php foreach (array_filter([$wa, setting('whatsapp_2')]) as $num): ?>
          <li><a href="<?= e(whatsapp_link($num, t('wa_hello', ['name' => $name]))) ?>" rel="noopener" target="_blank"><?= View::icon('whatsapp', 20) ?><?= e(phone_display($num)) ?></a></li>
          <?php endforeach; ?>
          <?php if ($ig): ?><li><a href="https://www.instagram.com/<?= e($ig) ?>/" rel="noopener" target="_blank"><?= View::icon('instagram', 20) ?>@<?= e($ig) ?></a></li><?php endif; ?>
        </ul>
      </div>
      <div>
        <h2><?= e(t('foot_explore')) ?></h2>
        <ul>
          <li><a href="<?= u('schedule') ?>"><?= e(t('nav_schedule')) ?></a></li>
          <li><a href="<?= u('groups') ?>"><?= e(t('nav_groups')) ?></a></li>
          <li><a href="<?= u('about') ?>"><?= e(t('nav_about')) ?></a></li>
          <li><a href="<?= u('business') ?>"><?= e(t('nav_business')) ?></a></li>
          <li><a href="<?= u('help') ?>"><?= e(t('nav_help')) ?></a></li>
          <li><a href="<?= u('alerts') ?>"><?= e(t('install_app')) ?></a></li>
        </ul>
      </div>
    </div>
    <div class="foot-bottom">
      <span>© <?= date('Y') ?> <?= e($name) ?> · <?= e(setting('address_city', 'Palhoça')) ?>, <?= e(setting('address_state', 'SC')) ?></span>
      <span>
        <?php foreach (I18n::LANGS as $i => $l): ?><?= $i ? ' · ' : '' ?><a href="<?= e(I18n::url($route, $slug, $l)) ?>" hreflang="<?= I18n::HTML_LANG[$l] ?>" lang="<?= I18n::HTML_LANG[$l] ?>" data-lang="<?= $l ?>"><?= e(I18n::NAMES[$l]) ?></a><?php endforeach; ?>
        · <a href="/admin" rel="nofollow"><?= e(t('team_area')) ?></a>
      </span>
    </div>
  </div>
</footer>

<nav class="tabbar" aria-label="<?= e(t('nav_shortcuts')) ?>">
  <a href="<?= u() ?>"<?= $cur('home') ?>><?= View::icon('home') ?><?= e(t('tab_home')) ?></a>
  <a href="<?= u('schedule') ?>"<?= $cur('agenda') ?>><?= View::icon('calendar') ?><?= e(t('tab_schedule')) ?></a>
  <a href="<?= u('groups') ?>"<?= $cur('grupos') ?>><?= View::icon('people') ?><?= e(t('tab_groups')) ?></a>
  <a href="<?= u('directions') ?>"<?= $cur('chegar') ?>><?= View::icon('pin') ?><?= e(t('tab_directions')) ?></a>
  <a href="<?= u('alerts') ?>"<?= $cur('avisos') ?> data-push-open><?= View::icon('bell') ?><?= e(t('tab_alerts')) ?></a>
</nav>

<a class="fab" href="<?= u('help') ?>" data-contact-open aria-label="<?= e(t('contact_us')) ?>"><?= View::icon('whatsapp', 26) ?><span><?= e(t('contact_us')) ?></span></a>

<dialog class="sheet" id="contact-sheet" aria-labelledby="contact-title">
  <div class="sheet-in">
    <div>
      <span class="eyebrow"><?= e(t('contact_eyebrow')) ?></span>
      <h2 id="contact-title" style="margin-top:10px"><?= e(t('contact_title')) ?></h2>
    </div>
    <p><?= e(t('contact_text')) ?></p>
    <?= View::render('partials/contact') ?>
    <button type="button" class="btn btn-ghost" data-contact-close><?= e(t('close')) ?></button>
  </div>
</dialog>

<?= View::render('partials/push-sheet', ['groups' => Content::groups()]) ?>
<?php if ($lang === 'pt'): ?>
<div vw class="enabled" hidden data-vlibras><div vw-access-button class="active"></div><div vw-plugin-wrapper><div class="vw-plugin-top-wrapper"></div></div></div>
<?php endif; ?>
</body>
</html>
