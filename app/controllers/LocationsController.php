<?php

class LocationsController extends Controller
{
    private $locationModel;

    public function __construct()
    {
        // لازم يكون مسجّل دخول
        if (!isLoggedIn()) {
            redirect('index.php?page=login');
        }

        $this->locationModel = $this->model('Location');
    }

    /**
     * صفحة عرض الهيكل + نموذج إضافة موقع جديد
     */
    public function index()
    {
        $locations = $this->locationModel->getAll();

        $data = [
            'locations' => $locations,
            'name_ar'   => '',
            'name_en'   => '',
            'type'      => 'College',
            'parent_id' => '',
            'name_err'  => '',
        ];

        $this->view('locations/index', $data);
    }

    /**
     * إضافة موقع جديد (كلية / مبنى / طابق / معمل / مكتب / مستودع)
     */
    public function add()
    {
        // صلاحية: المديرين + السوبر أدمن فقط
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'إضافة المواقع مسموحة للمديرين فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'name_ar'   => trim($_POST['name_ar'] ?? ''),
                'name_en'   => trim($_POST['name_en'] ?? ''),
                'type'      => trim($_POST['type'] ?? 'Building'),
                'parent_id' => !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null,
                'name_err'  => '',
            ];

            // تحقق بسيط
            if (empty($data['name_ar'])) {
                $data['name_err'] = 'الاسم العربي مطلوب';
            }

            if (empty($data['name_err'])) {
                if ($this->locationModel->add($data)) {
                    flash('location_msg', 'تم إضافة الموقع بنجاح');
                    redirect('index.php?page=locations/index');
                } else {
                    die('خطأ في قاعدة البيانات أثناء إضافة الموقع');
                }
            } else {
                // لو فيه خطأ نرجّع نفس الصفحة مع القائمة كاملة
                $data['locations'] = $this->locationModel->getAll();
                $this->view('locations/index', $data);
            }
        } else {
            // GET → رجع لصفحة المواقع
            redirect('index.php?page=locations/index');
        }
    }

    /**
     * تعديل موقع
     */
    public function edit($id)
    {
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'تعديل المواقع مسموح للمديرين فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'id'        => (int) $id,
                'name_ar'   => trim($_POST['name_ar'] ?? ''),
                'name_en'   => trim($_POST['name_en'] ?? ''),
                'type'      => trim($_POST['type'] ?? ''),
                'parent_id' => !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null,
            ];

            if ($this->locationModel->update($data)) {
                flash('location_msg', 'تم تحديث الموقع بنجاح');
                redirect('index.php?page=locations/index');
            } else {
                die('حدث خطأ أثناء تحديث بيانات الموقع');
            }
        } else {
            // GET → نعرض نموذج التعديل
            $location  = $this->locationModel->getLocationById($id);
            $locations = $this->locationModel->getAll();

            if (!$location) {
                redirect('index.php?page=locations/index');
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
    }

    /**
     * حذف موقع
     */
    public function delete($id = null)
    {
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'حذف المواقع مسموح للمديرين فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if (empty($id)) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }

        if ($this->locationModel->delete($id)) {
            flash('location_msg', 'تم حذف الموقع');
            redirect('index.php?page=locations/index');
        } else {
            die('حدث خطأ أثناء حذف الموقع');
        }
    }
}
