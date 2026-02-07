<?php declare(strict_types=1);

// Base path
define('APPROOT', dirname(__DIR__));
define('PUBLICROOT', APPROOT . '/public');

// Load config (عدّل المسار إذا config عندك بمكان ثاني)
require_once APPROOT . '/config/config.php';

// Helpers (إذا عندك مجلد helpers)
$helpers = [
  APPROOT . '/helpers/session_helper.php',
  APPROOT . '/helpers/url_helper.php',
];

foreach ($helpers as $h) {
  if (file_exists($h)) require_once $h;
}

// Libraries
$libs = [
  APPROOT . '/libraries/Core.php',
  APPROOT . '/libraries/Controller.php',
  APPROOT . '/libraries/Database.php',
];

foreach ($libs as $lib) {
  if (file_exists($lib)) require_once $lib;
}
