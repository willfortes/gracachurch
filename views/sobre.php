<?php
use App\View;

echo View::render('partials/page-head', [
    'crumbs' => [t('nav_about') => null],
    'label' => t('nav_about'),
    'title' => setting('about_title'),
    'lead' => e(plain(setting('hero_title'))),
]);
?>
<section class="sec glow-bg">
  <div class="wrap">
    <div class="pillars" style="margin-top:0">
      <?php foreach (['prayer', 'fasting', 'word', 'holiness'] as $key): ?>
      <div class="pillar"><h3><?= e(t('pillar_' . $key)) ?></h3><p><?= e(t('pillar_' . $key . '_text')) ?></p></div>
      <?php endforeach; ?>
    </div>
    <div class="about-grid">
      <p class="quote reveal"><?= e(t('about_quote')) ?></p>
      <div class="about-text reveal"><?= nl2p(setting('about_text')) ?></div>
    </div>
  </div>
</section>

<?php if ($faqs): ?>
<section class="sec" style="padding-top:0">
  <div class="wrap faq-grid">
    <div class="sec-head"><span class="eyebrow"><?= e(t('faq_eyebrow')) ?></span><h2><?= accent(t('faq_quick')) ?></h2></div>
    <div class="faq">
      <?php foreach ($faqs as $f): ?><details><summary><?= e($f['question']) ?></summary><p><?= e($f['answer']) ?></p></details><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
