<?php
// Agendador: rode a cada minuto.  Crontab:  * * * * * php /caminho/do/site/bin/cron.php >> /dev/null 2>&1
// 1) cria os lembretes automáticos que chegaram na hora  2) envia a fila de avisos push
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use App\Db;
use App\Notifier;

// Evita duas execuções ao mesmo tempo
if ((int) Db::value("SELECT GET_LOCK('gracachurch_cron', 0)") !== 1) {
    exit(0);
}

try {
    $created = Notifier::scheduleReminders();
    $deadline = time() + 50;
    $sent = $failed = 0;
    $batch = max(10, (int) env('PUSH_BATCH', '500'));
    do {
        [$s, $f] = Notifier::drain($batch);
        $sent += $s;
        $failed += $f;
    } while (($s + $f) >= $batch && time() < $deadline);

    Db::run("INSERT INTO settings (k, v) VALUES ('cron_last_run', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [date('Y-m-d H:i:s')]);
    // Limpeza: tentativas de login antigas e avisos com mais de 1 ano
    if ((int) date('i') === 0) {
        Db::run('DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 1 DAY');
        Db::run('DELETE FROM notifications WHERE created_at < NOW() - INTERVAL 1 YEAR');
    }
    if ($created || $sent || $failed) {
        echo date('Y-m-d H:i:s') . " lembretes=$created enviados=$sent falhas=$failed\n";
    }
} finally {
    Db::value("SELECT RELEASE_LOCK('gracachurch_cron')");
}
