<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/core/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/core/vendor/autoload.php';



// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/core/bootstrap/app.php';
$app->usePublicPath(__DIR__);


$scriptPath = $_SERVER['SCRIPT_NAME']; 

if ($scriptPath === '/index.php') {
    define('APP_PUBLIC_FOLDER', '');
} else {
    define('APP_PUBLIC_FOLDER', basename(__DIR__));
}

$app->handleRequest(Request::capture());