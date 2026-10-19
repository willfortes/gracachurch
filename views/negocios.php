<?php
use App\Directory;
use App\Http\Business;
use App\I18n;
use App\View;

$action = $catPage ? Business::catUrl($cat) : u('business');
$total = array_sum($counts);
$examples = t('biz_examples');
?>
<section class="biz-hero">
  <div class="spot" aria-hidden="true"></div>
  <div class="wrap">
    <nav class="crumbs" aria-label="<?= e(t('crumbs_label')) ?>">
      <a href="<?= u() ?>"><?= e(t('crumbs_home')) ?></a><span aria-hidden="true">/</span>
      <?php if ($catPage): ?><a href="<?= u('business') ?>"><?= e(t('nav_business')) ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= e($catLabel) ?></span>
      <?php else: ?><span aria-current="page"><?= e(t('nav_business')) ?></span><?php endif; ?>
    </nav>
    <span class="eyebrow"><?= e(t('biz_eyebrow', ['n' => $total])) ?></span>
    <h1><?= $catPage ? accent(t('biz_cat_title_h', ['cat' => $catLabel])) : accent(t('biz_title')) ?></h1>
    <p class="lead"><?= e($catPage ? t('biz_cat_lead', ['cat' => mb_strtolower($catLabel), 'name' => setting('church_name', 'Graça Church')]) : t('biz_lead', ['name' => setting('church_name', 'Graça Church')])) ?></p>

    <form class="bigsearch" method="get" action="<?= e($action) ?>" role="search" data-biz-search data-cat="<?= e($cat) ?>" data-lang="<?= I18n::$lang ?>">
      <label class="sr" for="biz-q"><?= e(t('biz_search_label')) ?></label>
      <?= View::icon('search', 26) ?>
      <input id="biz-q" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('biz_search_ph')) ?>" autocomplete="off" enterkeyhint="search" data-examples="<?= e($examples) ?>" aria-describedby="biz-hint">
      <button class="btn btn-primary" type="submit"><?= e(t('biz_search_btn')) ?></button>
    </form>
    <p class="muted biz-hint" id="biz-hint"><?= e(t('biz_search_hint')) ?></p>

    <div class="cat-scroll" role="list">
      <a role="listitem" class="cat-chip<?= $cat === '' ? ' on' : '' ?>" href="<?= u('business') ?>"<?= $cat === '' ? ' aria-current="page"' : '' ?>><?= View::icon('sparkle', 18) ?><?= e(t('biz_all')) ?><small><?= $total ?></small></a>
      <?php foreach (Directory::CATEGORIES as $slug => $c): if (empty($counts[$slug]) && $slug !== $cat) continue; ?>
      <a role="listitem" class="cat-chip<?= $cat === $slug ? ' on' : '' ?>" href="<?= e(Business::catUrl($slug)) ?>"<?= $cat === $slug ? ' aria-current="page"' : '' ?>><?= View::icon($c[0], 18) ?><?= e(Directory::categoryLabel($slug)) ?><small><?= (int) ($counts[$slug] ?? 0) ?></small></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="sec" style="padding-top:clamp(36px,5vw,56px)">
  <div class="wrap">
    <div class="biz-status" aria-live="polite" data-biz-status>
      <?php if ($q !== ''): ?>
        <p><b><?= count($results) ?></b> <?= e(count($results) === 1 ? t('biz_result') : t('biz_results')) ?> “<?= e($q) ?>”</p>
      <?php endif; ?>
      <?php if ($suggestion && $suggestion !== Directory::normalize($q)): ?>
        <p><?= e(t('biz_did_you_mean')) ?> <a href="<?= e($action . '?q=' . rawurlencode($suggestion)) ?>"><b><?= e($suggestion) ?></b></a>?</p>
      <?php endif; ?>
    </div>

    <div class="biz-grid" data-biz-results>
      <?php foreach ($results as $b): ?><?= View::render('partials/biz-card', ['b' => $b]) ?><?php endforeach; ?>
    </div>

    <?php if (!$results): ?>
    <div class="empty biz-empty" data-biz-empty>
      <p><b style="color:#fff;font-size:1.2rem"><?= e($total ? t('biz_none_found') : t('biz_none_yet')) ?></b></p>
      <p style="margin-top:8px"><?= e(t('biz_none_text')) ?></p>
      <p style="margin-top:18px"><a class="btn btn-primary" href="<?= u('business', 'cadastrar') ?>"><?= View::icon('plus', 20) ?><?= e(t('biz_register_cta')) ?></a></p>
    </div>
    <?php endif; ?>

    <div class="biz-cta reveal">
      <div>
        <span class="eyebrow"><?= e(t('biz_cta_eyebrow')) ?></span>
        <h2><?= accent(t('biz_cta_title')) ?></h2>
        <p><?= e(t('biz_cta_text')) ?></p>
      </div>
      <a class="btn btn-light" href="<?= u('business', 'cadastrar') ?>"><?= View::icon('plus', 20) ?><?= e(t('biz_register_cta')) ?></a>
    </div>

    <?php if (!$catPage && $q === ''): ?>
    <div class="faq-grid" style="margin-top:clamp(56px,7vw,96px)">
      <div class="sec-head"><span class="eyebrow"><?= e(t('faq_eyebrow')) ?></span><h2><?= accent(t('biz_faq_title')) ?></h2></div>
      <div class="faq">
        <details open><summary><?= e(t('biz_faq1_q')) ?></summary><p><?= e(t('biz_faq1_a', ['name' => setting('church_name', 'Graça Church')])) ?></p></details>
        <details><summary><?= e(t('biz_faq2_q')) ?></summary><p><?= e(t('biz_faq2_a')) ?></p></details>
        <details><summary><?= e(t('biz_faq3_q')) ?></summary><p><?= e(t('biz_faq3_a')) ?></p></details>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
<template id="biz-card-tpl">
  <article class="biz">
    <a class="biz-main" href="">
      <span class="biz-logo"><b aria-hidden="true"></b></span>
      <span class="biz-text"><span class="biz-cat"></span><h3></h3><span class="biz-desc"></span><span class="biz-meta"></span></span>
    </a>
    <a class="btn btn-wa btn-sm biz-wa" href="" target="_blank" rel="noopener"><?= View::icon('whatsapp', 18) ?><span>WhatsApp</span></a>
  </article>
</template>
