<?php
/**
 * PHP built-in server router for FuelPHP.
 *
 * Usage: php -S localhost:8000 router.php
 *
 * Without -t, PHP sets SCRIPT_NAME = REQUEST_URI which confuses FuelPHP's
 * URI detector. This router fixes SCRIPT_NAME and also handles static asset
 * serving from public/ so the built-in server acts like a real web server.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve real static files from public/ (assets, favicon, etc.)
$public = __DIR__ . '/public';
if ($uri !== '/' && file_exists($public . $uri) && !is_dir($public . $uri)) {
    return false; // let the built-in server serve it directly
}

// Fix SCRIPT_NAME so FuelPHP URI detection works correctly
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Run through FuelPHP's front controller
chdir($public);
require $public . '/index.php';
