<?php
class LocationsController extends Controller {
    private $locationModel;

    public function __construct() {
        requireLogin();
        $this->locationModel = $this->model('Location');
    }

    /** صفحة عرض الهيكل + نموذج إضافة موقع جديد */
    public function index() {
        $locations = $this->locationModel->getAll();

        $data = [
            'locations' => $locations,
            'name_ar' => '',
            'name_en' => '',
            'type' => 'College',
            'parent_id' => '',
            'name_err' => '',
        ];

        $this->view('locations/index', $data);
    }

    /** إضافة موقع جديد */
    public function add() {
        // صلاحية: المديرين + السوبر أدمن فقط
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'إضافة المواقع مسموحة للمديرين فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=locations/index');
            return;
        }

        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $data = [
            'name_ar'   => trim($_POST['name_ar'] ?? ''),
            'name_en'   => trim($_POST['name_en'] ?? ''),
            'type'      => trim($_POST['type'] ?? 'Building'),
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
            'name_err'  => '',
        ];

        if (empty($data['name_ar'])) {
            $data['name_err'] = 'الاسم العربي مطلوب';
        }

        if (!empty($data['name_err'])) {
            // رجّع نفس الصفحة مع القائمة
            $data['locations'] = $this->locationModel->getAll();
            $this->view('locations/index', $data);
            return;
        }

        if ($this->locationModel->add($data)) {
            flash('location_msg', 'تم إضافة الموقع بنجاح');
            redirect('index.php?page=locations/index');
        } else {
            die('خطأ في قاعدة البيانات أثناء إضافة الموقع');
        }
    }

    /** تعديل موقع (مرن: يقبل id من الباراميتر أو من GET) */
    public function edit($id = null) {
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'تعديل المواقع مسموح للمديرين فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if (empty($id)) {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        }
        $id = (int)$id;

        if ($id <= 0) {
            flash('location_msg', 'معرّف موقع غير صالح', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'id'        => $id,
                'name_ar'   => trim($_POST['name_ar'] ?? ''),
                'name_en'   => trim($_POST['name_en'] ?? ''),
                'type'      => trim($_POST['type'] ?? ''),
                'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
            ];

            if ($this->locationModel->update($data)) {
                flash('location_msg', 'تم تحديث الموقع بنجاح');
                redirect('index.php?page=locations/index');
            } else {
                die('حدث خطأ أثناء تحديث بيانات الموقع');
            }
            return;
        }

        // GET -> عرض نموذج التعديل
        $location  = $this->locationModel->getLocationById($id);
        $locations = $this->locationModel->getAll();

        if (!$location) {
            flash('location_msg', 'الموقع غير موجود', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            return;
        }

        $data = [
            'id'        => $location->id,
            'name_ar'   => $location->name_ar,
            'name_en'   => $location->name_en,
            'type'      => $location->type,
            'parent_id' => $location->parent_id,
            'locations' => $locations,
        ];

        $this->view('locations/edit', $data);
    }

    /** حذف موقع (POST فقط) */
    public function delete($id = null) {
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'حذف المواقع مسموح للمديرين فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            flash('location_msg', 'طريقة طلب غير صحيحة للحذف', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            return;
        }

        if (empty($id)) {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        }
        $id = (int)$id;

        if ($id <= 0) {
            flash('location_msg', 'معرّف موقع غير صالح', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            return;
        }

        if ($this->locationModel->delete($id)) {
            flash('location_msg', 'تم حذف الموقع');
        } else {
            flash('location_msg', 'فشل الحذف (قد يكون مرتبطًا بعناصر أخرى)', 'alert alert-danger');
        }

        redirect('index.php?page=locations/index');
    }
}
