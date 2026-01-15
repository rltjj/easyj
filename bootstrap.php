<?php
declare(strict_types=1);

define('APP_RUNNING', true);

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_set_cookie_params([
    'httponly' => true,
    'secure'   => !empty($_SERVER['HTTPS']),
    'samesite' => 'Lax',
]);
session_start();

$env = parse_ini_file(__DIR__ . '/.env');


define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/config/database.php';


define('PUBLIC_PATH', BASE_PATH . '/www/public');

