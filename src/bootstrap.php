<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = ROOT . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require ROOT . '/src/helpers.php';

App\Env::load(ROOT . '/.env');
date_default_timezone_set(env('APP_TIMEZONE', 'America/Sao_Paulo'));

if (env('APP_ENV') !== 'local') {
    ini_set('display_errors', '0');
}
