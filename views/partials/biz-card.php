<?php
use App\Directory;
use App\Http\Business;
use App\View;

$c = Business::card($b);
$initials = mb_strtoupper(mb_substr(preg_replace('/[^\p{L}\p{N} ]/u', '', $b['name']) ?: 'G', 0, 1));
?>
<article class="biz<?= $c['featured'] ? ' featured' : '' ?>">
  <a class="biz-main" href="<?= e($c['url']) ?>">
    <span class="biz-logo cat-<?= e($b['category']) ?>">
      <?php if ($c['logo']): ?><img src="<?= e($c['logo']) ?>" width="72" height="72" alt="" loading="lazy" decoding="async"><?php else: ?><b aria-hidden="true"><?= e($initials) ?></b><?php endif; ?>
    </span>
    <span class="biz-text">
      <span class="biz-cat"><?= View::icon(Directory::categoryIcon($b['category']), 14) ?><?= e($c['cat']) ?></span>
      <h3><?= e($b['name']) ?></h3>
      <span class="biz-desc"><?= e($c['desc']) ?></span>
      <span class="biz-meta">
        <?php if ($c['place']): ?><span><?= View::icon('pin', 14) ?><?= e($c['place']) ?></span><?php endif; ?>
        <?php if ($c['online']): ?><span class="chip-s"><?= e(t('biz_mode_online')) ?></span><?php endif; ?>
        <?php if ($c['home']): ?><span class="chip-s"><?= e(t('biz_mode_domicilio')) ?></span><?php endif; ?>
      </span>
    </span>
  </a>
  <a class="btn btn-wa btn-sm biz-wa" href="<?= e($c['wa']) ?>" target="_blank" rel="noopener" aria-label="<?= e(t('biz_wa_label', ['biz' => $b['name']])) ?>"><?= View::icon('whatsapp', 18) ?><span>WhatsApp</span></a>
</article>
