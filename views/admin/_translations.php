<?php
/**
 * Campos de tradução opcionais (inglês e espanhol). Vazio = o site mostra o português.
 * $fields = ['title' => ['Nome', 'text'|'textarea', max]], $values = [lang => [field => value]], $prefix (opcional)
 */
$prefix ??= 'tr';
$langs = ['en' => 'English', 'es' => 'Español'];
?>
<details class="fieldset tr-box">
  <summary><b>Traduções (opcional)</b> <span class="hint">English · Español. Em branco, o site mostra o texto em português.</span></summary>
  <div class="grid g2" style="margin-top:14px">
    <?php foreach ($langs as $l => $label): ?>
    <div class="grid" style="gap:12px">
      <p><span class="pill vi"><?= $label ?></span></p>
      <?php foreach ($fields as $f => [$name, $type, $max]): $id = $prefix . '-' . $l . '-' . $f . (isset($idSuffix) ? '-' . $idSuffix : ''); ?>
      <label class="f"><?= e($name) ?>
        <?php if ($type === 'textarea'): ?>
        <textarea name="<?= $prefix ?>[<?= $l ?>][<?= $f ?>]" maxlength="<?= $max ?>" id="<?= e($id) ?>" lang="<?= $l ?>" style="min-height:80px"><?= e($values[$l][$f] ?? '') ?></textarea>
        <?php else: ?>
        <input type="text" name="<?= $prefix ?>[<?= $l ?>][<?= $f ?>]" value="<?= e($values[$l][$f] ?? '') ?>" maxlength="<?= $max ?>" id="<?= e($id) ?>" lang="<?= $l ?>">
        <?php endif; ?>
      </label>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</details>
