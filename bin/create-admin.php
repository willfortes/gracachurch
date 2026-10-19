<?php
// Cria um usuário do painel pelo terminal.  Uso: php bin/create-admin.php email@exemplo.com "Nome"
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use App\Db;

$email = strtolower(trim($argv[1] ?? ''));
$name = $argv[2] ?? 'Administrador';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php bin/create-admin.php email@exemplo.com \"Nome\"\n");
    exit(1);
}
echo 'Senha (mínimo 10 caracteres): ';
$pass = trim((string) fgets(STDIN));
if (mb_strlen($pass) < 10) {
    fwrite(STDERR, "Senha curta demais.\n");
    exit(1);
}
Db::run(
    'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash)',
    [$name, $email, password_hash($pass, PASSWORD_DEFAULT)]
);
echo "Usuário $email pronto.\n";
