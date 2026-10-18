<?php
// This check must fail before bootstrapping when Composer dependencies are absent.
$root = dirname(__DIR__);
if (!is_file($root.'/fuel/vendor/autoload.php') || !is_file($root.'/fuel/core/bootstrap.php')) {
    fwrite(STDERR, "FAIL: install Composer dependencies before running smoke tests.\n");
    exit(1);
}
define('DOCROOT', $root.'/public/');
define('APPPATH', $root.'/fuel/app/');
define('PKGPATH', $root.'/fuel/packages/');
define('COREPATH', $root.'/fuel/core/');
define('FUEL_START_TIME', microtime(true));
define('FUEL_START_MEM', memory_get_usage());
require COREPATH.'classes/autoloader.php';
class_alias('Fuel\\Core\\Autoloader', 'Autoloader');
require APPPATH.'bootstrap.php';
if (!class_exists('phpseclib3\\Crypt\\AES')) {
    throw new RuntimeException('Maintained phpseclib 3 autoload is missing.');
}
foreach (array('auth', 'email', 'oil', 'orm', 'parser') as $package) {
    Package::load($package);
}
echo "PASS: Composer autoload, application bootstrap and bundled packages.\n";
