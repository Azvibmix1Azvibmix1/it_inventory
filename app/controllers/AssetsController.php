<?php
class AssetsController extends Controller
{
    private $assetModel;
    private $locationModel;
    private $userModel;

    public function __construct()
    {
        // ✅ بدل requireLogin() (غير موجودة عندك)
        if (!isLoggedIn()) {
            redirect('index.php?page=login');
            exit;
        }

        $this->assetModel = $this->model('Asset');
        $this->userModel = $this->model('User');
        $this->locationModel = $this->model('Location');
    }

    // 1) عرض قائمة الأصول
    public function index()
    {
        $assets = $this->assetModel->getAllAssets();

        $data = [
            'assets' => $assets
        ];

        $this->view('assets/index', $data);
    }

    // 2) إضافة أصل جديد
    public function add()
    {
        // ✅ بدل requireRole (غير موجودة)
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'ليس لديك صلاحية لإضافة أصول', 'alert alert-danger');
            redirect('index.php?page=assets/index');
            exit;
        }

        // تجهيز قائمة الموظفين حسب الصلاحية
        $users_list = [];
        if (isSuperAdmin()) {
            $users_list = $this->userModel->getUsers();
        } elseif (isManager()) {
            $users_list = $this->userModel->getUsersByManager($_SESSION['user_id']);
        }

        // جلب المواقع
        $locations = $this->locationModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'asset_tag' => trim($_POST['asset_tag'] ?? ''),
                'serial_no' => trim($_POST['serial_no'] ?? ''),
                'brand' => trim($_POST['brand'] ?? ''),
                'model' => trim($_POST['model'] ?? ''),
                'type' => trim($_POST['type'] ?? ''),
                'location_id' => !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null,
                'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                'status' => 'Active',
                'created_by' => $_SESSION['user_id'],

                'users_list' => $users_list,
                'locations' => $locations,
                'asset_err' => ''
            ];

            if (empty($data['asset_tag']) || empty($data['type'])) {
                $data['asset_err'] = 'الرجاء تعبئة الحقول الأساسية (رقم الأصل والنوع)';
            }

            if (empty($data['asset_err'])) {
                if ($this->assetModel->add($data)) {
                    flash('asset_msg', 'تمت إضافة الأصل بنجاح');
                    redirect('index.php?page=assets/index');
                    exit;
                } else {
                    die('حدث خطأ أثناء الإضافة في قاعدة البيانات');
                }
            }

            $this->view('assets/add', $data);
            return;
        }

        // GET
        $data = [
            'asset_tag' => '',
            'serial_no' => '',
            'brand' => '',
            'model' => '',
            'type' => '',
            'location_id' => '',
            'assigned_to' => '',
            'users_list' => $users_list,
            'locations' => $locations,
            'asset_err' => ''
        ];

        $this->view('assets/add', $data);
    }

    // 3) تعديل أصل  ✅ (يدعم GET/POST بدون Missing argument)
    public function edit($id = null)
    {
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'ليس لديك صلاحية لتعديل الأصول', 'alert alert-danger');
            redirect('index.php?page=assets/index');
            exit;
        }

        if (empty($id)) {
            $id = $_GET['id'] ?? ($_POST['id'] ?? null);
        }
        $assetId = (int)$id;

        if ($assetId <= 0) {
            redirect('index.php?page=assets/index');
            exit;
        }

        // قوائم المستخدمين والمواقع
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
                'id' => $assetId,
                'asset_tag' => trim($_POST['asset_tag'] ?? ''),
                'serial_no' => trim($_POST['serial_no'] ?? ''),
                'brand' => trim($_POST['brand'] ?? ''),
                'model' => trim($_POST['model'] ?? ''),
                'type' => trim($_POST['type'] ?? ''),
                'location_id' => !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null,
                'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                'status' => trim($_POST['status'] ?? 'Active'),

                'users_list' => $users_list,
                'locations' => $locations
            ];

            if ($this->assetModel->update($data)) {
                flash('asset_msg', 'تم تحديث بيانات الأصل');
                redirect('index.php?page=assets/index');
                exit;
            }

            die('خطأ في التعديل');
        }

        // GET
        $asset = $this->assetModel->getAssetById($assetId);
        if (!$asset) {
            redirect('index.php?page=assets/index');
            exit;
        }

        $data = [
            'id' => $assetId,
            'asset_tag' => $asset->asset_tag,
            'serial_no' => $asset->serial_no,
            'brand' => $asset->brand,
            'model' => $asset->model,
            'type' => $asset->type,
            'location_id' => $asset->location_id,
            'assigned_to' => $asset->assigned_to,
            'status' => $asset->status,
            'users_list' => $users_list,
            'locations' => $locations
        ];

        $this->view('assets/edit', $data);
    }

    // 4) حذف أصل ✅ (يدعم POST/GET)
    public function delete($id = null)
    {
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'ليس لديك صلاحية لحذف الأصول', 'alert alert-danger');
            redirect('index.php?page=assets/index');
            exit;
        }

        if (empty($id)) {
            $id = $_POST['id'] ?? ($_GET['id'] ?? null);
        }
        $id = (int)$id;

        if ($id <= 0) {
            flash('asset_msg', 'معرّف أصل غير صالح', 'alert alert-danger');
            redirect('index.php?page=assets/index');
            exit;
        }

        if ($this->assetModel->delete($id)) {
            flash('asset_msg', 'تم حذف الأصل');
        } else {
            flash('asset_msg', 'فشل الحذف، حاول مرة أخرى', 'alert alert-danger');
        }

        redirect('index.php?page=assets/index');
    }

    // 5) عرض عهد الموظف
    public function my_assets()
    {
        $userId = $_SESSION['user_id'];
        $myAssets = $this->assetModel->getAssetsByUserId($userId);

        $data = [
            'assets' => $myAssets
        ];

        $this->view('assets/my_assets', $data);
    }
}
