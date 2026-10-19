<?php use App\View; ?>
<dialog class="sheet" id="push-sheet" aria-labelledby="push-title">
  <form class="sheet-in" method="dialog" data-push-form>
    <div>
      <span class="eyebrow"><?= e(t('push_eyebrow')) ?></span>
      <h2 id="push-title" style="margin-top:10px"><?= e(t('push_title')) ?></h2>
    </div>
    <p><?= e(t('push_text')) ?></p>

    <div class="status info" data-push-ios hidden><?= t('push_ios') ?></div>
    <div class="status err" data-push-unsupported hidden><?= e(t('push_unsupported')) ?></div>
    <div class="status err" data-push-denied hidden><?= e(t('push_denied')) ?></div>

    <fieldset class="checks">
      <legend class="sr"><?= e(t('push_types')) ?></legend>
      <label class="check"><input type="checkbox" name="topic" value="cultos" checked><span><?= e(t('topic_cultos_t')) ?><small><?= e(t('topic_cultos_d')) ?></small></span></label>
      <label class="check"><input type="checkbox" name="topic" value="eventos" checked><span><?= e(t('topic_eventos_t')) ?><small><?= e(t('topic_eventos_d')) ?></small></span></label>
      <label class="check"><input type="checkbox" name="topic" value="ministerios"><span><?= e(t('topic_min_t')) ?><small><?= e(t('topic_min_d')) ?></small></span></label>
    </fieldset>
    <?php if ($groups): ?>
    <label>
      <span style="display:block;font-weight:700;margin-bottom:8px"><?= e(t('my_group')) ?></span>
      <select class="select" name="group" data-push-group id="push-group">
        <option value=""><?= e(t('no_group')) ?></option>
        <?php foreach ($groups as $g): ?>
        <option value="<?= (int) $g['id'] ?>"><?= e($g['name']) ?> · <?= e($g['neighborhood']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php endif; ?>

    <div class="status" data-push-status hidden role="status"></div>

    <div class="btns">
      <button type="button" class="btn btn-primary" data-push-save style="flex:1"><?= View::icon('bell', 20) ?><?= e(t('activate')) ?></button>
      <button type="button" class="btn btn-ghost" data-push-close><?= e(t('not_now')) ?></button>
    </div>
    <button type="button" class="btn btn-ghost btn-sm" data-push-off hidden><?= e(t('turn_off')) ?></button>
  </form>
</dialog>
