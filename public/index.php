<?php

ob_start();
session_start();


// نقطة البداية (Router)

// 1. ملفات الإعداد والجلسة
$root = dirname(__DIR__);
require_once __DIR__ . '/../app/helpers/session_helper.php';

require_once '../app/config/config.php';
require_once '../app/helpers/session_helper.php';
require_once '../app/libraries/Controller.php';
require_once '../app/libraries/Database.php';


// 2. تحميل المكتبات الأساسية
require_once $root . '/app/libraries/Database.php';
require_once $root . '/app/libraries/Controller.php';

// 3. تحميل المتحكمات
require_once $root . '/app/controllers/AuthController.php';
require_once $root . '/app/controllers/DashboardController.php';
require_once $root . '/app/controllers/AssetsController.php';
require_once $root . '/app/controllers/TicketsController.php';
require_once $root . '/app/controllers/LocationsController.php';
require_once $root . '/app/controllers/UsersController.php';
require_once $root . '/app/controllers/SparePartsController.php';

// 4. تحديث صلاحية الدور من قاعدة البيانات لو تغيّرت
if (function_exists('syncSessionRole')) {
    syncSessionRole();
}

// 5. استلام الصفحة المطلوبة
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

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

    // --- Assets ---
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

    // --- Spare Parts ---
    case 'spare_parts':
    case 'spareparts':
    case 'spare_parts/index':
    case 'SpareParts/index':
        (new SparePartsController())->index();
        break;

    case 'spare_parts/add':
    case 'SpareParts/add':
        (new SparePartsController())->add();
        break;

    case 'spare_parts/edit':
    case 'SpareParts/edit':
        (new SparePartsController())->edit();
        break;

    case 'spare_parts/delete':
    case 'SpareParts/delete':
        (new SparePartsController())->delete();
        break;

    // --- Tickets ---
    case 'tickets':
    case 'tickets/index':
    case 'Tickets/index':
        (new TicketsController())->index();
        break;

    case 'tickets/add':
    case 'Tickets/add':
        (new TicketsController())->add();
        break;

    case 'tickets/show':
    case 'Tickets/show':
        (new TicketsController())->show();
        break;

    case 'tickets/update_status':
        (new TicketsController())->update_status();
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

    // --- Default ---
    default:
        (new DashboardController())->index();
        break;
}
