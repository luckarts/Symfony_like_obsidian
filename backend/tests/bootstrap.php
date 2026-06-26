<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// bootEnv() calls putenv() which does NOT update $_ENV.
// KernelTestCase checks $_ENV before $_SERVER for APP_ENV.
// Override so a real env var (e.g. Docker APP_ENV=prod) doesn't shadow test env.
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] ?? 'test';
