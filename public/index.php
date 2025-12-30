<?php
// public/index.php

$root = dirname(__DIR__);

// ملفات أساسية
require_once $root . '/app/config/config.php';
require_once $root . '/app/helpers/session_helper.php';

// المكتبات
require_once $root . '/app/libraries/Database.php';
require_once $root . '/app/libraries/Controller.php';

// المتحكمات
require_once $root . '/app/controllers/AuthController.php';
require_once $root . '/app/controllers/DashboardController.php';
require_once $root . '/app/controllers/AssetsController.php';
require_once $root . '/app/controllers/TicketsController.php';
require_once $root . '/app/controllers/LocationsController.php';
require_once $root . '/app/controllers/UsersController.php';
require_once $root . '/app/controllers/SparePartsController.php';

$url = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

switch ($url) {

    // --- Auth ---
    case 'login':
    case 'users/login':
    case 'auth/login':
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
    case 'SpareParts/index':
    case 'spare_parts/index':
        (new SparePartsController())->index();
        break;

    case 'SpareParts/add':
    case 'spare_parts/add':
        (new SparePartsController())->add();
        break;

    case 'SpareParts/edit':
    case 'spare_parts/edit':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        (new SparePartsController())->edit($id);
        break;

    case 'SpareParts/delete':
    case 'spare_parts/delete':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        (new SparePartsController())->delete($id);
        break;

    // --- Tickets ---
    case 'tickets':
    case 'Tickets/index':
    case 'tickets/index':
        (new TicketsController())->index();
        break;

    case 'Tickets/add':
    case 'tickets/add':
        (new TicketsController())->add();
        break;

    case 'Tickets/show':
    case 'tickets/show':
        (new TicketsController())->show();
        break;

    case 'tickets/update_status':
        (new TicketsController())->update_status();
        break;

    case 'tickets/escalate':
        (new TicketsController())->escalate();
        break;

    case 'tickets/upload':
        (new TicketsController())->upload();
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

    // --- Default ---
    default:
        (new DashboardController())->index();
        break;
}
