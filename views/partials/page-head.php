<header class="page-head">
  <div class="spot" aria-hidden="true"></div>
  <div class="wrap">
    <?php if (!empty($crumbs)): ?>
    <nav class="crumbs" aria-label="<?= e(t('crumbs_label')) ?>">
      <a href="<?= u() ?>"><?= e(t('crumbs_home')) ?></a>
      <?php foreach ($crumbs as $label => $href): ?>
        <span aria-hidden="true">/</span><?= $href ? '<a href="' . e($href) . '">' . e($label) . '</a>' : '<span aria-current="page">' . e($label) . '</span>' ?>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if (!empty($label)): ?><span class="eyebrow"><?= e($label) ?></span><?php endif; ?>
    <h1><?= accent($title) ?></h1>
    <?php if (!empty($lead)): ?><p class="lead"><?= $lead ?></p><?php endif; ?>
  </div>
</header>
