<?php
// public/index.php

// ✅ اقرأ config أولاً (هو اللي يعرّف APPROOT عادة)
$cfg = dirname(__DIR__) . '/app/config/config.php';
if (file_exists($cfg)) {
  require_once $cfg;
} else {
  // fallback لو config غير موجود
  if (!defined('APPROOT')) define('APPROOT', dirname(__DIR__) . '/app');
}

// ✅ PUBLICROOT إذا تحتاجه
if (!defined('PUBLICROOT')) define('PUBLICROOT', __DIR__);

// ✅ session helper
$sessionHelper = APPROOT . '/helpers/session_helper.php';
if (file_exists($sessionHelper)) {
  require_once $sessionHelper;
}


// ✅ Autoload مرن: يشوف أكثر من مسار محتمل
spl_autoload_register(function ($class) {
  $paths = [
    APPROOT . '/controllers/' . $class . '.php',
    APPROOT . '/models/' . $class . '.php',

    // بعض المشاريع تسميها libraries أو core
    APPROOT . '/libraries/' . $class . '.php',
    APPROOT . '/core/' . $class . '.php',

    // أحياناً Helpers فيها كلاس
    APPROOT . '/helpers/' . $class . '.php',
  ];

  foreach ($paths as $file) {
    if (file_exists($file)) {
      require_once $file;
      
      return;
    }
  }
});

// ✅ تحديد الصفحة
$page = isset($_GET['page']) ? trim((string)$_GET['page']) : '';
$page = $page !== '' ? $page : 'dashboard/index';
$page = str_replace(['..', '\\'], ['', '/'], $page);
$page = trim($page, "/ \t\n\r\0\x0B");

// ✅ Router
switch ($page) {

  // --- Auth ---
  case 'login':
  case 'auth/login':
  case 'users/login':
    (new AuthController())->login();
    break;

  case 'logout':
  case 'auth/logout':
    (new AuthController())->logout();
    break;

  case 'register':
  case 'auth/register':
  case 'users/register':
    (new AuthController())->register();
    break;

  // --- Dashboard ---
  case 'dashboard':
  case 'dashboard/index':
    (new DashboardController())->index();
    break;

  case 'dashboard/announce':
    (new DashboardController())->add_announcement();
    break;

  // --- Assets (الأجهزة) ---
  case 'assets':
  case 'assets/index':
    (new AssetsController())->index();
    break;

  case 'assets/add':
    (new AssetsController())->add();
    break;

  case 'assets/edit':
    (new AssetsController())->edit();
    break;

  case 'assets/delete':
    (new AssetsController())->delete();
    break;

  case 'assets/my':
  case 'assets/my_assets':
    (new AssetsController())->my_assets();
    break;

  // ✅ طباعة الأجهزة (بدون Fatal لو الميثود غير موجودة)
  case 'assets/print':
    $c = new AssetsController();
    if (method_exists($c, 'print_list')) {
      $c->print_list();
    } else {
      if (function_exists('flash')) flash('asset_msg', 'ميزة الطباعة غير موجودة داخل AssetsController (print_list).', 'alert alert-warning');
      if (function_exists('redirect')) redirect('index.php?page=assets/index');
      else echo 'print_list not found';
    }
    break;

  case 'assets/labels':
    $c = new AssetsController();
    if (method_exists($c, 'print_labels')) {
      $c->print_labels();
    } else {
      if (function_exists('flash')) flash('asset_msg', 'ميزة الملصقات غير موجودة داخل AssetsController (print_labels).', 'alert alert-warning');
      if (function_exists('redirect')) redirect('index.php?page=assets/index');
      else echo 'print_labels not found';
    }
    break;

  // --- Locations ---
  case 'locations':
  case 'locations/index':
    (new LocationsController())->index();
    break;

  case 'locations/add':
    (new LocationsController())->add();
    break;

  case 'locations/edit':
    (new LocationsController())->edit();
    break;

  case 'locations/delete':
    (new LocationsController())->delete();
    break;

  // --- Users ---
  case 'users':
  case 'users/index':
    (new UsersController())->index();
    break;

  case 'users/add':
    (new UsersController())->add();
    break;

  case 'users/edit':
    (new UsersController())->edit();
    break;

  case 'users/delete':
    (new UsersController())->delete();
    break;

  case 'users/profile':
    (new UsersController())->profile();
    break;

  // --- Tickets (إذا موجودة) ---
  case 'tickets':
  case 'tickets/index':
  case 'Tickets/index':
    if (class_exists('TicketsController')) (new TicketsController())->index();
    else (new DashboardController())->index();
    break;

  // --- Spare Parts (إذا موجودة) ---
  case 'spare_parts':
  case 'spareparts':
  case 'spare_parts/index':
  case 'SpareParts/index':
    if (class_exists('SparePartsController')) (new SparePartsController())->index();
    else (new DashboardController())->index();
    break;

  case 'spare_parts/add':
  case 'SpareParts/add':
    if (class_exists('SparePartsController')) (new SparePartsController())->add();
    else (new DashboardController())->index();
    break;

  case 'spare_parts/edit':
  case 'SpareParts/edit':
    if (class_exists('SparePartsController')) (new SparePartsController())->edit();
    else (new DashboardController())->index();
    break;

  case 'spare_parts/delete':
  case 'SpareParts/delete':
    if (class_exists('SparePartsController')) (new SparePartsController())->delete();
    else (new DashboardController())->index();
    break;

  // --- Default ---
  default:
    (new DashboardController())->index();
    break;
}
