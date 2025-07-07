<?php
$file = __DIR__ . '/../vendor/autoload.php';

if (!is_file($file)) {
    exit('Please run "composer install" to install the dependencies, Composer is not installed, please install <a href="https://getcomposer.org/" target="_blank">Composer</a>.');
}

require $file;

use Core\App;

App::create(basePath: dirname(__DIR__), debug: true, timezone: 'UTC');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');

if (function_exists('frankenphp_handle_request')) {
    $maxRequests = (int)($_ENV['MAX_REQUESTS'] ?? 0);
    App::runWorker($maxRequests);
} else {
    App::runWeb();
}