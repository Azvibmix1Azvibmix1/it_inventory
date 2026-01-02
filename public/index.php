<?php
// public/index.php

// 1) Root
$root = dirname(__DIR__);

// 2) Core config + helpers
require_once $root . '/app/config/config.php';
require_once $root . '/app/helpers/session_helper.php';

// 3) Libraries
require_once $root . '/app/libraries/Database.php';
require_once $root . '/app/libraries/Controller.php';

// ✅ تحديث دور المستخدم من قاعدة البيانات (إذا تغير)
if (function_exists('syncSessionRole')) {
    syncSessionRole();
}

// 4) Controllers
require_once $root . '/app/controllers/AuthController.php';
require_once $root . '/app/controllers/DashboardController.php';
require_once $root . '/app/controllers/AssetsController.php';
require_once $root . '/app/controllers/TicketsController.php';
require_once $root . '/app/controllers/LocationsController.php';
require_once $root . '/app/controllers/UsersController.php';
require_once $root . '/app/controllers/SparePartsController.php';

// 5) Router
$page = $_GET['page'] ?? 'dashboard';

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
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        (new AssetsController())->edit($id);
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
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        (new SparePartsController())->edit($id);
        break;

    case 'spare_parts/delete':
    case 'SpareParts/delete':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        (new SparePartsController())->delete($id);
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
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        (new LocationsController())->edit($id);
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
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        (new UsersController())->edit($id);
        break;

    case 'users/delete':
        (new UsersController())->delete();
        break;

    case 'users/profile':
        (new UsersController())->profile();
        break;

    default:
        (new DashboardController())->index();
        break;
}
