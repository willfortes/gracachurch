<?php
// Gera as chaves dos avisos push. Copie as duas linhas para o .env (uma vez só: trocar as chaves invalida as inscrições).
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

$k = App\WebPush::generateKeys();
echo "VAPID_PUBLIC_KEY={$k['public']}\n";
echo "VAPID_PRIVATE_KEY={$k['private']}\n";
