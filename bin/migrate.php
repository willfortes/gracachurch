<?php
// Aplica as migrações pendentes de database/migrations.  Uso: php bin/migrate.php [--demo]
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use App\Db;

$pdo = Db::pdo();
$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(190) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$done = array_column(Db::all('SELECT name FROM migrations'), 'name');

$runFile = function (string $file) use ($pdo): void {
    $sql = file_get_contents($file);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (preg_split('/;\s*(\r?\n|$)/', $sql) as $stmt) {
        if (trim($stmt) !== '') {
            $pdo->exec($stmt);
        }
    }
};

$files = glob(ROOT . '/database/migrations/*.sql');
sort($files);
$count = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $done, true)) {
        continue;
    }
    echo "Aplicando $name... ";
    $runFile($file);
    Db::insert('migrations', ['name' => $name]);
    echo "ok\n";
    $count++;
}
echo $count ? "$count migração(ões) aplicada(s).\n" : "Banco já está atualizado.\n";

if (in_array('--demo', $argv, true)) {
    if (Db::value('SELECT COUNT(*) FROM cell_groups')) {
        echo "Dados de exemplo ignorados: já existem grupos cadastrados.\n";
    } else {
        $runFile(ROOT . '/database/demo.sql');
        echo "Dados de exemplo carregados.\n";
    }
}
