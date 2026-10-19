<?php /** Busca de endereço + mapa para marcar o ponto. $lat, $lng, $centerLat, $centerLng, $hint */ ?>
<div class="grid" style="gap:10px">
  <div class="geo-search">
    <input type="search" data-geo-q placeholder="Digite rua, bairro e cidade para buscar" aria-label="Buscar endereço no mapa" id="geo-q">
    <button class="btn btn-ghost" type="button" data-geo-btn>Buscar</button>
  </div>
  <div class="geo-results" data-geo-results></div>
  <div class="pick-map" data-pick-map data-lat="<?= e($centerLat ?? '') ?>" data-lng="<?= e($centerLng ?? '') ?>"></div>
  <p class="hint"><?= e($hint ?? 'Clique no mapa para marcar o local. Dá para arrastar o alfinete.') ?></p>
  <div class="row">
    <label class="f">Latitude<input type="text" name="lat" value="<?= e($lat ?? '') ?>" inputmode="decimal" id="lat"></label>
    <label class="f">Longitude<input type="text" name="lng" value="<?= e($lng ?? '') ?>" inputmode="decimal" id="lng"></label>
  </div>
</div>
