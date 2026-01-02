<?php
class AssetsController extends Controller {
    private $assetModel;
    private $userModel;
    private $locationModel;

    public function __construct() {
        requireLogin();

        $this->assetModel     = $this->model('Asset');
        $this->userModel      = $this->model('User');
        $this->locationModel  = $this->model('Location');
    }

    public function index() {
        $assets = $this->assetModel->getAllAssets();
        $data = ['assets' => $assets];
        $this->view('assets/index', $data);
    }

    public function add() {
        requirePermission('assets.assign', 'assets/index');
        $this->requireRole(['super_admin', 'manager']);

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
                'asset_tag'   => trim($_POST['asset_tag'] ?? ''),
                'serial_no'   => trim($_POST['serial_no'] ?? ''),
                'brand'       => trim($_POST['brand'] ?? ''),
                'model'       => trim($_POST['model'] ?? ''),
                'type'        => trim($_POST['type'] ?? ''),
                'location_id' => !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null,
                'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                'status'      => 'Active',
                'created_by'  => $_SESSION['user_id'],

                'users_list'  => $users_list,
                'locations'   => $locations,
                'asset_err'   => ''
            ];

            if (empty($data['asset_tag']) || empty($data['type'])) {
                $data['asset_err'] = 'الرجاء تعبئة الحقول الأساسية (رقم الأصل والنوع)';
            }

            if (!empty($data['asset_err'])) {
                $this->view('assets/add', $data);
                return;
            }

            if ($this->assetModel->add($data)) {
                flash('asset_msg', 'تمت إضافة الأصل بنجاح');
                redirect('index.php?page=assets/index');
            } else {
                die('حدث خطأ أثناء الإضافة في قاعدة البيانات');
            }
            return;
        }

        // GET
        $data = [
            'asset_tag'   => '',
            'serial_no'   => '',
            'brand'       => '',
            'model'       => '',
            'type'        => '',
            'location_id' => '',
            'assigned_to' => '',
            'users_list'  => $users_list,
            'locations'   => $locations,
            'asset_err'   => ''
        ];

        $this->view('assets/add', $data);
    }

    /** تعديل أصل (مرن: id من الباراميتر أو GET) */
    public function edit($id = null) {
        requirePermission('assets.edit', 'assets/index');
        $this->requireRole(['super_admin', 'manager']);

        if (empty($id)) {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        }
        $assetId = (int)$id;

        if ($assetId <= 0) {
            redirect('index.php?page=assets/index');
            return;
        }

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
                'location_id' => !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null,
                'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
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
            return;
        }

        // GET
        $asset = $this->assetModel->getAssetById($assetId);
        if (!$asset) {
            redirect('index.php?page=assets/index');
            return;
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

    /** حذف أصل (POST فقط) */
    public function delete() {
        requirePermission('assets.delete', 'assets/index');
        $this->requireRole(['super_admin', 'manager']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            flash('asset_msg', 'طريقة طلب غير صحيحة للحذف');
            redirect('index.php?page=assets/index');
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
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

    public function my_assets() {
        $userId  = $_SESSION['user_id'];
        $myAssets = $this->assetModel->getAssetsByUserId($userId);
        $data = ['assets' => $myAssets];
        $this->view('assets/my_assets', $data);
    }
}
