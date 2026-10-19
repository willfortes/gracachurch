<?php if (!empty($flash)): ?>
<div class="flash <?= $flash['type'] === 'error' ? 'error' : 'ok' ?>" role="status"><?= e($flash['message']) ?></div>
<?php endif; ?>
