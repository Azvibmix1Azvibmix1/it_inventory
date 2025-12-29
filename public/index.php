<?php
// 1. تعريف المسار الرئيسي
$root = dirname(__DIR__); 

// 2. الملفات الأساسية
require_once $root . '/app/config/config.php';
require_once $root . '/app/helpers/session_helper.php';

// 3. المكتبات
require_once $root . '/app/libraries/Database.php';
require_once $root . '/app/libraries/Controller.php';

// 4. المتحكمات
require_once $root . '/app/controllers/AuthController.php';
require_once $root . '/app/controllers/DashboardController.php';
require_once $root . '/app/controllers/AssetsController.php';
require_once $root . '/app/controllers/TicketsController.php';
require_once $root . '/app/controllers/LocationsController.php';
require_once $root . '/app/controllers/UsersController.php';
require_once $root . '/app/controllers/SparePartsController.php';

// 5. التوجيه
$url = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

switch($url){
    // --- المصادقة ---
    case 'login':
    case 'users/login':
        $auth = new AuthController();
        $auth->login();
        break;
        
    case 'logout':
        $auth = new AuthController();
        $auth->logout();
        break;

    case 'users/register':
        $auth = new AuthController(); 
        $auth->register();
        break;

    // --- لوحة التحكم ---
    case 'dashboard':
        $dash = new DashboardController();
        $dash->index();
        break;

    case 'dashboard/announce':
        $dash = new DashboardController();
        $dash->add_announcement();
        break;

    // --- الأصول (Assets) ---
    case 'assets':
    case 'assets/index':
        $assets = new AssetsController();
        $assets->index();
        break;

    case 'assets/add':
        $assets = new AssetsController();
        $assets->add();
        break;

    case 'assets/edit':
        $assets = new AssetsController();
        $id = isset($_GET['id']) ? $_GET['id'] : 0; 
        $assets->edit($id); 
        break;

    case 'assets/delete':
        $assets = new AssetsController();
        $assets->delete();
        break;

    case 'assets/approve':
        $assets = new AssetsController();
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        // تأكد أن دالة approve موجودة في AssetsController
        if(method_exists($assets, 'approve')){
            $assets->approve($id);
        } else {
            die("الدالة approve غير موجودة في المتحكم");
        }
        break;

    case 'assets/my': // رابط العهد الشخصية للموظف
    case 'assets/my_assets':
        $assets = new AssetsController();
        $assets->my_assets();
        break;

    // --- قطع الغيار (SpareParts) ---
    case 'spare_parts':       
    case 'spareparts':
    case 'SpareParts/index':  
    case 'spare_parts/index':
        $parts = new SparePartsController();
        $parts->index();
        break;

    case 'SpareParts/add':    
    case 'spare_parts/add':
        $parts = new SparePartsController();
        $parts->add();
        break;

    case 'SpareParts/edit':   
    case 'spare_parts/edit':
        $parts = new SparePartsController();
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $parts->edit($id);
        break;

    case 'SpareParts/delete':
    case 'spare_parts/delete':
        $parts = new SparePartsController();
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $parts->delete($id);
        break;

    // --- التذاكر (Tickets) ---
    case 'tickets':
    case 'Tickets/index': 
    case 'tickets/index':
        $tickets = new TicketsController();
        $tickets->index();
        break;

    case 'Tickets/add':   
    case 'tickets/add':
        $tickets = new TicketsController();
        $tickets->add();
        break;

    case 'Tickets/show':
    case 'tickets/show':
        $tickets = new TicketsController();
        $tickets->show();
        break;

    // --- المواقع (Locations) ---
    case 'locations':
    case 'locations/index':
        $loc = new LocationsController();
        $loc->index();
        break;

    case 'locations/add':
        $loc = new LocationsController();
        $loc->add();
        break;

    case 'locations/edit': // تمت الإضافة (لأننا برمجنا الدالة edit في الخطوات السابقة)
        $loc = new LocationsController();
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $loc->edit($id);
        break;

    case 'locations/delete':
        $loc = new LocationsController();
        $loc->delete();
        break;

    // --- المستخدمين (Users) ---
    case 'users':
    case 'users/index':
        $users = new UsersController();
        $users->index();
        break;

    case 'users/add':
        $users = new UsersController();
        $users->add();
        break;
    
    case 'users/delete':
        $users = new UsersController();
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $users->delete($id);
        break;
    
    case 'users/edit':
        $users = new UsersController();
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $users->edit($id);
        break;

    // --- الافتراضي ---
    default:
        $dash = new DashboardController();
        $dash->index();
        break;
}
?>