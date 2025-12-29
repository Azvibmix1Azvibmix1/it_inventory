<?php 

class AssetsController extends Controller 
{
    private $assetModel;
    private $locationModel;
    private $userModel;

    public function __construct()
    {
        // تأكد أن المستخدم مسجل دخول
        $this->requireLogin();

        // تحميل الموديلات المطلوبة
        $this->assetModel    = $this->model('Asset');
        $this->userModel     = $this->model('User');
        $this->locationModel = $this->model('Location'); 
    }

    // 1. عرض قائمة الأصول (الرئيسية)
    public function index()
    {
        $assets = $this->assetModel->getAllAssets();

        $data = [
            'assets' => $assets
        ];
        
        $this->view('assets/index', $data);
    }

    // 2. إضافة أصل جديد
    public function add()
    {
        // الموظف العادي ما يضيف أصول
        $this->requireRole(['super_admin', 'manager']);

        // --- تجهيز قائمة الموظفين (للقائمة المنسدلة) ---
        $users_list = [];
        if (isSuperAdmin()) {
            // السوبر أدمن يشوف كل المستخدمين
            $users_list = $this->userModel->getUsers();
        } elseif (isManager()) {
            // المدير يشوف الموظفين التابعين له فقط
            $users_list = $this->userModel->getUsersByManager($_SESSION['user_id']);
        }

        // جلب قائمة المواقع
        $locations = $this->locationModel->getAll(); 

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // تنظيف البيانات
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'asset_tag'   => trim($_POST['asset_tag'] ?? ''),
                'serial_no'   => trim($_POST['serial_no'] ?? ''),
                'brand'       => trim($_POST['brand'] ?? ''),
                'model'       => trim($_POST['model'] ?? ''),
                'type'        => trim($_POST['type'] ?? ''),
                'location_id' => !empty($_POST['location_id']) ? (int) $_POST['location_id'] : null,
                // المستخدم الذي تم تعيين العهدة له
                'assigned_to' => !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null, 
                'status'      => 'Active',
                'created_by'  => $_SESSION['user_id'],

                // بيانات لإعادة العرض عند الخطأ
                'users_list'  => $users_list,
                'locations'   => $locations,
                'asset_err'   => ''
            ];

            // تحقق بسيط من الحقول الأساسية
            if (empty($data['asset_tag']) || empty($data['type'])) {
                $data['asset_err'] = 'الرجاء تعبئة الحقول الأساسية (رقم الأصل والنوع)';
            }

            if (empty($data['asset_err'])) {
                if ($this->assetModel->add($data)) {
                    flash('asset_msg', 'تمت إضافة الأصل بنجاح');
                    redirect('index.php?page=assets/index');
                } else {
                    die('حدث خطأ أثناء الإضافة في قاعدة البيانات');
                }
            } else {
                // عرض الصفحة مع الأخطاء
                $this->view('assets/add', $data);
            }

        } else {
            // GET Request: عرض الصفحة لأول مرة
            $data = [
                'asset_tag'   => '',
                'serial_no'   => '',
                'brand'       => '',
                'model'       => '',
                'type'        => '',
                'location_id' => '',
                'assigned_to' => '',
                'users_list'  => $users_list, // القائمة المفلترة حسب الصلاحية
                'locations'   => $locations,
                'asset_err'   => ''
            ];
            
            $this->view('assets/add', $data);
        }
    }

    // 3. تعديل أصل
    public function edit($id)
    {
        // منع الموظف العادي من التعديل
        $this->requireRole(['super_admin', 'manager']);

        $assetId = (int) $id;
        if ($assetId <= 0) {
            redirect('index.php?page=assets/index');
        }

        // جلب قائمة الموظفين (نفس منطق add)
        $users_list = [];
        if (isSuperAdmin()) {
            $users_list = $this->userModel->getUsers();
        } elseif (isManager()) {
            $users_list = $this->userModel->getUsersByManager($_SESSION['user_id']);
        }
        
        $locations = $this->locationModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'id'          => $assetId,
                'asset_tag'   => trim($_POST['asset_tag'] ?? ''),
                'serial_no'   => trim($_POST['serial_no'] ?? ''),
                'brand'       => trim($_POST['brand'] ?? ''),
                'model'       => trim($_POST['model'] ?? ''),
                'type'        => trim($_POST['type'] ?? ''),
                'location_id' => !empty($_POST['location_id']) ? (int) $_POST['location_id'] : null,
                'assigned_to' => !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null,
                'status'      => trim($_POST['status'] ?? 'Active'),
                'users_list'  => $users_list,
                'locations'   => $locations
            ];

            if ($this->assetModel->update($data)) {
                flash('asset_msg', 'تم تحديث بيانات الأصل');
                redirect('index.php?page=assets/index');
            } else {
                die('خطأ في التعديل');
            }

        } else {
            // جلب بيانات الأصل الحالي
            $asset = $this->assetModel->getAssetById($assetId);

            if (!$asset) {
                redirect('index.php?page=assets/index');
            }

            $data = [
                'id'          => $assetId,
                'asset_tag'   => $asset->asset_tag,
                'serial_no'   => $asset->serial_no,
                'brand'       => $asset->brand,
                'model'       => $asset->model,
                'type'        => $asset->type,
                'location_id' => $asset->location_id,
                'assigned_to' => $asset->assigned_to,
                'status'      => $asset->status,
                'users_list'  => $users_list,
                'locations'   => $locations
            ];

            $this->view('assets/edit', $data);
        }
    }

    // 4. حذف أصل
    public function delete()
    {
        // منع الموظف العادي من الحذف
        $this->requireRole(['super_admin', 'manager']);

        // نسمح بالحذف فقط عن طريق POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            flash('asset_msg', 'طريقة طلب غير صحيحة للحذف');
            redirect('index.php?page=assets/index');
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            flash('asset_msg', 'معرّف أصل غير صالح');
            redirect('index.php?page=assets/index');
            return;
        }

        if ($this->assetModel->delete($id)) {
            flash('asset_msg', 'تم حذف الأصل');
        } else {
            flash('asset_msg', 'فشل الحذف، حاول مرة أخرى');
        }

        redirect('index.php?page=assets/index');
    }

    // 5. عرض عهد الموظف (الصفحة الخاصة بالموظف)
    public function my_assets()
    {
        // تسجيل الدخول متأكد منه من الـ __construct
        $userId   = $_SESSION['user_id'];
        $myAssets = $this->assetModel->getAssetsByUserId($userId); 

        $data = [
            'assets' => $myAssets
        ];

        $this->view('assets/my_assets', $data);
    }
}
?>
