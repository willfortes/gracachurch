<?php
use App\Schedule;

/** Campos de recorrência compartilhados por programação e grupos. $item, $allowOnce */
$reminders = [30 => '30 minutos antes', 60 => '1 hora antes', 120 => '2 horas antes', 180 => '3 horas antes', 240 => '4 horas antes', 480 => '8 horas antes', 720 => '12 horas antes', 1440 => '1 dia antes'];
if (!isset($reminders[(int) $item['reminder_min']])) {
    $reminders[(int) $item['reminder_min']] = $item['reminder_min'] . ' minutos antes';
}
?>
<fieldset class="fieldset">
  <legend>Quando acontece</legend>
  <div class="row">
    <label class="f">Repete
      <select name="recurrence" id="recurrence">
        <option value="weekly"<?= $item['recurrence'] === 'weekly' ? ' selected' : '' ?>>Toda semana</option>
        <option value="monthly"<?= $item['recurrence'] === 'monthly' ? ' selected' : '' ?>>Uma vez por mês</option>
        <?php if ($allowOnce): ?><option value="once"<?= $item['recurrence'] === 'once' ? ' selected' : '' ?>>Não repete (data única)</option><?php endif; ?>
      </select>
    </label>
    <label class="f" data-show="monthly">Qual semana do mês
      <select name="nth" id="nth">
        <?php foreach ([1 => '1ª', 2 => '2ª', 3 => '3ª', 4 => '4ª', -1 => 'Última'] as $v => $l): ?>
        <option value="<?= $v ?>"<?= (int) $item['nth'] === $v ? ' selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="f" data-show="weekly monthly">Dia da semana
      <select name="weekday" id="weekday">
        <?php foreach (Schedule::WEEKDAYS as $i => $d): ?>
        <option value="<?= $i ?>"<?= (int) $item['weekday'] === $i ? ' selected' : '' ?>><?= $d ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if ($allowOnce): ?>
    <label class="f" data-show="once">Data<input type="date" name="date" value="<?= e($item['date'] ?? '') ?>" id="date"></label>
    <?php endif; ?>
    <label class="f">Horário de início<input type="time" name="start_time" value="<?= e(substr((string) $item['start_time'], 0, 5)) ?>" required id="start_time"></label>
    <label class="f">Duração (minutos)<input type="number" name="duration_min" value="<?= (int) $item['duration_min'] ?>" min="15" max="1440" step="15" id="duration_min"></label>
  </div>
</fieldset>

<fieldset class="fieldset">
  <legend>Lembrete automático</legend>
  <div class="row">
    <label class="switch"><input type="checkbox" name="notify" value="1"<?= $item['notify'] ? ' checked' : '' ?>><span>Enviar lembrete push<small>Para quem escolheu receber este tipo de aviso</small></span></label>
    <label class="f">Enviar
      <select name="reminder_min" id="reminder_min">
        <?php foreach ($reminders as $v => $l): ?><option value="<?= $v ?>"<?= (int) $item['reminder_min'] === $v ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select>
    </label>
  </div>
</fieldset>
