<?php
/** Cartões de contato (usados na janela "Fale conosco" e na seção da página inicial). $big = true para a versão grande */
use App\View;

$name = setting('church_name', 'Graça Church');
$wa = array_values(array_filter([setting('whatsapp_1'), setting('whatsapp_2')]));
$ig = setting('instagram');
?>
<div class="<?= !empty($big) ? 'contact-grid' : 'contact-list' ?>">
  <?php foreach ($wa as $i => $num): ?>
  <a class="contact-item" href="<?= e(whatsapp_link($num, t('wa_hello', ['name' => $name]))) ?>" target="_blank" rel="noopener">
    <span class="ci-icon wa"><?= View::icon('whatsapp', 24) ?></span>
    <span><b><?= e($i === 0 ? t('contact_wa') : t('contact_wa_2')) ?></b><?= e(phone_display($num)) ?></span>
  </a>
  <?php endforeach; ?>
  <a class="contact-item hl" href="<?= u('help') ?>">
    <span class="ci-icon help"><?= View::icon('hands', 24) ?></span>
    <span><b><?= e(t('contact_help')) ?></b><?= e(t('contact_help_text')) ?></span>
  </a>
  <?php if ($ig): ?>
  <a class="contact-item" href="https://www.instagram.com/<?= e($ig) ?>/" target="_blank" rel="noopener">
    <span class="ci-icon"><?= View::icon('instagram', 24) ?></span>
    <span><b>Instagram</b>@<?= e($ig) ?></span>
  </a>
  <?php endif; ?>
  <a class="contact-item" href="<?= u('directions') ?>">
    <span class="ci-icon"><?= View::icon('pin', 24) ?></span>
    <span><b><?= e(t('nav_directions')) ?></b><?= e(setting('address_street', 'BR 282, KM 18')) ?> · <?= e(setting('address_city', 'Palhoça')) ?></span>
  </a>
</div>
