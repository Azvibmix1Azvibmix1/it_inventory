<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
set_time_limit(10);



/**
 * Front Controller + Simple Router
 * - Reads config
 * - Loads helpers
 * - Autoloads Controllers/Models/Libraries
 * - Dispatches based on ?page=controller/method
 */

// -------------------------
// Basic bootstrap
// -------------------------
if (!defined('PUBLICROOT')) {
  define('PUBLICROOT', __DIR__);
}

// Try load config first (defines APPROOT/URLROOT/DB_*)
$cfg = dirname(__DIR__) . '/app/config/config.php';
if (file_exists($cfg)) {
  require_once $cfg;
}

// Fallback APPROOT if not defined in config
if (!defined('APPROOT')) {
  define('APPROOT', dirname(__DIR__) . '/app');
}

// Safe error reporting (you can toggle via config if you have ENV/DEBUG)
if (defined('APP_ENV') && constant('APP_ENV') === 'production') {
  ini_set('display_errors', '0');
  error_reporting(E_ALL);
} else {
  ini_set('display_errors', '1');
  error_reporting(E_ALL);
}


// -------------------------
// Helpers (load only if exist)
// -------------------------
$helpers = [
  APPROOT . '/helpers/session_helper.php',
  APPROOT . '/helpers/url_helper.php',
  APPROOT . '/helpers/flash_helper.php',
];

foreach ($helpers as $h) {
  if (file_exists($h)) require_once $h;
}

// If redirect() not defined, define a minimal one
if (!function_exists('redirect')) {
  function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
  }
}

// If flash() not defined, no-op fallback
if (!function_exists('flash')) {
  function flash(string $name, string $message = '', string $class = 'alert alert-success'): void {
    // no-op fallback (project may already define flash in helper)
  }
}

// -------------------------
// Autoloader
// -------------------------
spl_autoload_register(function (string $class): void {
  $candidates = [
    APPROOT . '/controllers/' . $class . '.php',
    APPROOT . '/models/' . $class . '.php',
    APPROOT . '/libraries/' . $class . '.php',
  ];

  foreach ($candidates as $file) {
    if (file_exists($file)) {
      require_once $file;
      return;
    }
  }
});

// -------------------------
// Routing utilities
// -------------------------
function normalize_route(string $route): string {
  $route = urldecode($route);
  $route = str_replace('\\', '/', $route);
  $route = trim($route);
  $route = trim($route, "/ \t\n\r\0\x0B");

  // Collapse multiple slashes
  $route = preg_replace('#/+#', '/', $route) ?? $route;

  // Block traversal
  if (str_contains($route, '..')) {
    return 'dashboard/index';
  }

  return $route === '' ? 'dashboard/index' : $route;
}

function safe_call(object $controller, string $method, string $fallbackRoute, string $missingMessage): void {
  if (method_exists($controller, $method)) {
    $controller->$method();
    return;
  }

  flash('app_msg', $missingMessage, 'alert alert-warning');
  redirect('index.php?page=' . $fallbackRoute);
}

// -------------------------
// Define routes
// -------------------------
if (file_exists(APPROOT . '/controllers/ApiController.php')) {
  require_once APPROOT . '/controllers/ApiController.php';
}

$routes = [
  // Auth
  'login'          => [AuthController::class, 'login'],
  'auth/login'     => [AuthController::class, 'login'],
  'users/login'    => [AuthController::class, 'login'],

  'logout'         => [AuthController::class, 'logout'],
  'auth/logout'    => [AuthController::class, 'logout'],

  'register'       => [AuthController::class, 'register'],
  'auth/register'  => [AuthController::class, 'register'],
  'users/register' => [AuthController::class, 'register'],

  // Dashboard
  'dashboard'          => [DashboardController::class, 'index'],
  'dashboard/index'    => [DashboardController::class, 'index'],
  'dashboard/announce' => [DashboardController::class, 'add_announcement'],

  // Assets (الأجهزة)
  'assets'           => [AssetsController::class, 'index'],
  'assets/index'     => [AssetsController::class, 'index'],
  'assets/add'       => [AssetsController::class, 'add'],
  'assets/edit'      => [AssetsController::class, 'edit'],
  'assets/show'      => [AssetsController::class, 'show'],
  'assets/delete'    => [AssetsController::class, 'delete'],
  'assets/exportcsv' => [AssetsController::class, 'exportCsv'],
  'assets/my'        => [AssetsController::class, 'my_assets'],
  'assets/my_assets' => [AssetsController::class, 'my_assets'],
  
  // Locations
  'locations'        => [LocationsController::class, 'index'],
  'locations/index'  => [LocationsController::class, 'index'],
  'locations/add'    => [LocationsController::class, 'add'],
  'locations/edit'   => [LocationsController::class, 'edit'],
  'locations/delete' => [LocationsController::class, 'delete'],

  // Users
  'users'         => [UsersController::class, 'index'],
  'users/index'   => [UsersController::class, 'index'],
  'users/add'     => [UsersController::class, 'add'],
  'users/edit'    => [UsersController::class, 'edit'],
  'users/delete'  => [UsersController::class, 'delete'],
  'users/profile' => [UsersController::class, 'profile'],

  // Tickets (الدعم الفني)
'tickets' => [TicketsController::class, 'index'],
'tickets/index' => [TicketsController::class, 'index'],
'tickets/add' => [TicketsController::class, 'add'],
'tickets/show' => [TicketsController::class, 'show'],
'tickets/update_status' => [TicketsController::class, 'update_status'],
'tickets/escalate' => [TicketsController::class, 'escalate'],
'tickets/upload' => [TicketsController::class, 'upload'],

  // assets/assign
  'assets/assign'    => [AssetsController::class, 'assign'],
  'assets/unassign'  => [AssetsController::class, 'unassign'],

  // SpareParts
  'spareParts/adjust' => ['SparePartsController', 'adjust'],
  'spareparts/adjust' => ['SparePartsController', 'adjust'],

  'spareParts/transfer' => ['SparePartsController', 'transfer'],
  'spareparts/transfer' => ['SparePartsController', 'transfer'],
  'spare_parts/transfer' => ['SparePartsController', 'transfer'],

  // SpareParts Movements (JSON)
  'spareparts/movements' => ['SparePartsController', 'movements'],
  'spare_parts/movements' => ['SparePartsController', 'movements'],
  // API (JSON)
'api/locations'      => ['ApiController', 'locations'],
'api/location_path'  => ['ApiController', 'location_path'],

  

];

// -------------------------
// Dispatch
// -------------------------
$route = normalize_route((string)($_GET['page'] ?? 'dashboard/index'));
$routeKey = strtolower($route);

try {
  // Special routes that need method_exists protection (print/labels)
  if ($routeKey === 'assets/print') {
    $c = new AssetsController();
    safe_call(
      $c,
      'print_list',
      'assets/index',
      'ميزة الطباعة غير موجودة داخل AssetsController (print_list).'
    );
    exit;
  }

  if ($routeKey === 'assets/labels') {
    $c = new AssetsController();
    safe_call(
      $c,
      'print_labels',
      'assets/index',
      'ميزة الملصقات غير موجودة داخل AssetsController (print_labels).'
    );
    exit;
  }

  // Tickets / Spare Parts (optional controllers)
  if (in_array($routeKey, ['tickets', 'tickets/index', 'tickets/index'], true)) {
    if (class_exists('TicketsController')) {
      (new TicketsController())->index();
    } else {
      (new DashboardController())->index();
    }
    exit;
  }

  if (in_array($routeKey, ['spare_parts', 'spareparts', 'spare_parts/index', 'spareparts/index'], true)) {
    if (class_exists('SparePartsController')) {
      (new SparePartsController())->index();
    } else {
      (new DashboardController())->index();
    }
    exit;
  }

  // movements JSON (سجل الحركة)
// ✅ SpareParts Print
if (in_array($routeKey, ['spareparts/print', 'spare_parts/print'], true)) {
  if (class_exists('SparePartsController')) {
    (new SparePartsController())->printList();
  } else {
    (new DashboardController())->index();
  }
  exit;
}

// ✅ SpareParts Export CSV (Excel)
if (in_array($routeKey, ['spareparts/export', 'spare_parts/export'], true)) {
  if (class_exists('SparePartsController')) {
    (new SparePartsController())->exportExcel();
  } else {
    (new DashboardController())->index();
  }
  exit;
}



  if (in_array($routeKey, ['spare_parts/add', 'spareparts/add'], true)) {
    if (class_exists('SparePartsController')) {
      (new SparePartsController())->add();
    } else {
      (new DashboardController())->index();
    }
    exit;
  }

  if (in_array($routeKey, ['spare_parts/edit', 'spareparts/edit'], true)) {
    if (class_exists('SparePartsController')) {
      (new SparePartsController())->edit();
    } else {
      (new DashboardController())->index();
    }
    exit;
  }

  if (in_array($routeKey, ['spare_parts/delete', 'spareparts/delete'], true)) {
    if (class_exists('SparePartsController')) {
      (new SparePartsController())->delete();
    } else {
      (new DashboardController())->index();
    }
    exit;
  }
// Movements JSON (AJAX)
if (in_array($routeKey, ['spareparts/movements', 'spare_parts/movements'], true)) {
  if (class_exists('SparePartsController')) {
    (new SparePartsController())->movements();
  } else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'SparePartsController غير موجود'], JSON_UNESCAPED_UNICODE);
  }
  exit;
}

  if (in_array($routeKey, ['spare_parts/adjust', 'spareparts/adjust'], true)) {
    if (class_exists('SparePartsController')) {
      (new SparePartsController())->adjust();
    } else {
      (new DashboardController())->index();
    }
    exit;
  }

  // Normal routes
  if (isset($routes[$routeKey])) {
    [$class, $method] = $routes[$routeKey];

    if (!class_exists($class)) {
      throw new RuntimeException("Controller not found: {$class}");
    }

    $controller = new $class();

    if (!method_exists($controller, $method)) {
      throw new RuntimeException("Method not found: {$class}::{$method}()");
    }

    // keep existing transfer behavior
    if (in_array($routeKey, ['spare_parts/transfer', 'spareparts/transfer'], true)) {
      if (class_exists('SparePartsController')) {
        (new SparePartsController())->transfer();
      } else {
        (new DashboardController())->index();
      }
      exit;
    }

    if ($routeKey === 'assets/export') {
  $c = new AssetsController();
  safe_call(
    $c,
    'exportCsv',          // اسم الدالة داخل الكنترولر
    'assets/index',
    'ميزة التصدير غير موجودة داخل AssetsController (exportCsv).'
  );
  exit;
}


    $controller->$method();
  } else {
    // Default fallback
    (new DashboardController())->index();
  }
} catch (Throwable $e) {
  // Friendly error message (and keep debug info if in dev)
  if (defined('APP_ENV') && constant('APP_ENV') === 'production') {
    echo "<h3>حدث خطأ غير متوقع</h3>";
    echo "<p>حاول مرة ثانية أو راجع مدير النظام.</p>";
  } else {
    echo "<h3>Router Error</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
  }
}
