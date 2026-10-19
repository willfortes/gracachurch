<?php
/**
 * "Constelação": os grupos como estrelas ao redor da igreja.
 * Direção real a partir do templo; distância comprimida (raiz quadrada) para que grupos distantes
 * caibam sem espremer os próximos. Os anéis mostram a distância real em km.
 * Aparece na hora, sem baixar nada; o mapa interativo carrega quando a pessoa pede (ou sozinho em conexão boa).
 * $points = Content::mapData(...), $id
 */
use App\View;

$W = 1000;
$H = 700;
$church = null;
$stars = [];
foreach ($points as $p) {
    $p['type'] === 'church' ? $church = $p : $stars[] = $p;
}
$all = $church ? [...$stars, $church] : $stars;
$cx = $W / 2;
$cy = $H / 2;
$Rmax = min($W, $H) / 2 - 70;
$placed = [];
$maxKm = 0.0;
if ($all) {
    $c = $church ?? ['lat' => array_sum(array_column($all, 'lat')) / count($all), 'lng' => array_sum(array_column($all, 'lng')) / count($all)];
    $k = cos(deg2rad((float) $c['lat']));
    $polar = [];
    foreach ($stars as $s) {
        $dx = ($s['lng'] - $c['lng']) * $k * 111.32;
        $dy = -($s['lat'] - $c['lat']) * 111.32;
        $km = hypot($dx, $dy);
        $maxKm = max($maxKm, $km);
        $polar[] = [$s, $km, $km < 0.05 ? -M_PI / 4 : atan2($dy, $dx)];
    }
    $maxKm = max($maxKm, 1.0);
    foreach ($polar as [$s, $km, $ang]) {
        $r = max(38, sqrt($km / $maxKm) * $Rmax);
        $placed[] = [$s, round($cx + cos($ang) * $r, 1), round($cy + sin($ang) * $r, 1)];
    }
}
$ringKm = fn (float $f) => $maxKm * $f * $f; // raio relativo -> km reais
$labelled = [];
?>
<div class="sky" data-map data-fit="1" id="<?= e($id ?? 'mapa') ?>">
  <?php if ($all): ?>
  <svg class="stars" viewBox="0 0 <?= $W ?> <?= $H ?>" preserveAspectRatio="xMidYMid meet" role="img" aria-label="<?= e(t('sky_label', ['n' => count($stars)])) ?>">
    <?php foreach ([0.4, 0.7, 1.0] as $f): $km = $ringKm($f); ?>
      <circle class="ring" cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= round($Rmax * $f, 1) ?>"/>
      <?php if ($stars): ?><text x="<?= $cx + 6 ?>" y="<?= round($cy - $Rmax * $f - 6, 1) ?>" style="font-size:11px;fill:var(--text-3)"><?= $km < 10 ? number_format($km, 1, t('dec_sep'), '') : round($km) ?> km</text><?php endif; ?>
    <?php endforeach; ?>
    <?php if ($church): foreach ($placed as [, $x, $y]): ?>
      <line class="beam" x1="<?= $cx ?>" y1="<?= $cy ?>" x2="<?= $x ?>" y2="<?= $y ?>"/>
    <?php endforeach; endif; ?>
    <?php foreach ($placed as $i => [$s, $x, $y]): $hood = explode(' · ', $s['text'])[1] ?? $s['title']; $showLabel = !isset($labelled[$hood]); $labelled[$hood] = true; ?>
      <a href="<?= e($s['url']) ?>" aria-label="<?= e($s['title'] . ', ' . $s['text']) ?>">
        <circle class="halo sky-twinkle" cx="<?= $x ?>" cy="<?= $y ?>" r="15" style="animation-delay:<?= ($i % 5) * .7 ?>s"/>
        <circle class="dot" cx="<?= $x ?>" cy="<?= $y ?>" r="5.5"/>
        <?php if ($showLabel): ?><text x="<?= $x + ($x >= $cx ? 13 : -13) ?>" y="<?= $y + 4 ?>" text-anchor="<?= $x >= $cx ? 'start' : 'end' ?>"><?= e($hood) ?></text><?php endif; ?>
      </a>
    <?php endforeach; ?>
    <?php if ($church): ?>
      <a class="home" href="<?= e($church['url']) ?>" aria-label="<?= e($church['title'] . ', ' . $church['text']) ?>">
        <circle class="halo" cx="<?= $cx ?>" cy="<?= $cy ?>" r="30"/>
        <circle class="dot" cx="<?= $cx ?>" cy="<?= $cy ?>" r="9"/>
        <text x="<?= $cx - 18 ?>" y="<?= $cy + 5 ?>" text-anchor="end" style="font-weight:800;fill:#fff;font-size:13px"><?= e($church['title']) ?></text>
      </a>
    <?php endif; ?>
  </svg>
  <?php endif; ?>
  <div class="sky-bar">
    <div class="legend"><?php if ($church): ?><span class="c"><?= e(t('legend_church')) ?></span><?php endif; ?><?php if ($stars): ?><span><?= e(t('legend_groups')) ?> (<?= count($stars) ?>)</span><?php endif; ?></div>
    <button class="btn btn-light btn-sm" type="button" data-map-load><?= View::icon('map', 18) ?><span><?= e(t('open_map')) ?></span></button>
  </div>
  <script type="application/json" data-map-points><?= json_encode($points, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</div>
