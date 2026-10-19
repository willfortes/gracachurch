<?php
// Só para desenvolvimento sem Docker:  php -S localhost:8080 -t public public/router-dev.php
$file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (PHP_SAPI === 'cli-server' && is_file($file) && !str_ends_with($file, '.php')) {
    if (str_ends_with($file, '.webmanifest')) {
        header('Content-Type: application/manifest+json');
        readfile($file);
        return true;
    }
    return false;
}
require __DIR__ . '/index.php';
