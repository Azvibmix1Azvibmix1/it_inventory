<?php declare(strict_types=1);

// Paths (safe defines)
// تعريف ثابت APPROOT مرة واحدة فقط
if (!defined('APPROOT')) {
    define('APPROOT', __DIR__);
}
if (!defined('PUBLICROOT')) {
    define('PUBLICROOT', dirname(__DIR__) . '/public');
}

// تحميل ملف config الصحيح
require_once __DIR__ . '/config/config.php';

// Load config
$cfg = __DIR__ . '/config/config.php';
if (file_exists($cfg)) require_once $cfg;

// Helpers
$helpers = [
  __DIR__ . '/helpers/session_helper.php',
  __DIR__ . '/helpers/url_helper.php',
];
foreach ($helpers as $f) { if (file_exists($f)) require_once $f; }

// Libraries
$libs = [
  __DIR__ . '/libraries/Core.php',
  __DIR__ . '/libraries/Controller.php',
  __DIR__ . '/libraries/Database.php',
  __DIR__ . '/libraries/App.php',
  __DIR__ . '/libraries/Router.php',

];
foreach ($libs as $f) { if (file_exists($f)) require_once $f; }
