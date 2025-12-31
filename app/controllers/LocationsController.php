<?php

class LocationsController extends Controller
{
    private $locationModel;

    public function __construct()
    {
        // لازم يكون مسجل دخول
        if (!isLoggedIn()) {
            redirect('index.php?page=login');
        }

        $this->locationModel = $this->model('Location');
    }

    // صفحة عرض المواقع (الهيكل كامل)
    public function index()
    {
        $main_locations = $this->locationModel->getMainLocations(); // مثلاً الكليات / الفروع الرئيسية
        $all_locations  = $this->locationModel->getAll();           // كل المستويات

        $data = [
            'main_locations' => $main_locations,
            'all_locations'  => $all_locations,

            // حقول جاهزة لو بنضيف من نفس الصفحة
            'name_ar'   => '',
            'name_en'   => '',
            'type'      => '',
            'parent_id' => '',
            'name_err'  => ''
        ];

        $this->view('locations/index', $data);
    }

    // إضافة موقع جديد (فرع / كلية / مبنى / طابق / معمل)
    public function add()
    {
        // 🔐 صلاحية بسيطة: نخلي فقط admin + superadmin يقدروا يضيفوا مواقع
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'إضافة المواقع مسموحة للمديرين فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'name_ar'   => trim($_POST['name_ar'] ?? ''),
                'name_en'   => trim($_POST['name_en'] ?? ''),
                'type'      => trim($_POST['type'] ?? 'Building'), // College / Branch / Building / Floor / Lab
                'parent_id' => !empty($_POST['parent_id']) ? trim($_POST['parent_id']) : null,
                'name_err'  => ''
            ];

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
                // لو فيه خطأ، نرجع للرئيسية مع رسالة
                flash('location_msg', $data['name_err'], 'alert alert-danger');
                redirect('index.php?page=locations/index');
            }

        } else {
            // طلب GET عادي → نرجعه لصفحة الهيكل
            redirect('index.php?page=locations/index');
        }
    }

    // تعديل موقع
    public function edit($id)
    {
        // فقط admin + superadmin
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'تعديل المواقع للمديرين فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'id'        => $id,
                'name_ar'   => trim($_POST['name_ar'] ?? ''),
                'name_en'   => trim($_POST['name_en'] ?? ''),
                'type'      => trim($_POST['type'] ?? ''),
                'parent_id' => !empty($_POST['parent_id']) ? trim($_POST['parent_id']) : null
            ];

            if ($this->locationModel->update($data)) {
                flash('location_msg', 'تم تحديث الموقع بنجاح');
                redirect('index.php?page=locations/index');
            } else {
                die('حدث خطأ أثناء تحديث بيانات الموقع');
            }

        } else {
            // عرض بيانات الموقع في الفورم
            $location      = $this->locationModel->getLocationById($id);
            $all_locations = $this->locationModel->getAll();

            if (!$location) {
                redirect('index.php?page=locations/index');
            }

            $data = [
                'id'           => $id,
                'name_ar'      => $location->name_ar,
                'name_en'      => $location->name_en,
                'type'         => $location->type,
                'parent_id'    => $location->parent_id,
                'all_locations'=> $all_locations
            ];

            $this->view('locations/edit', $data);
        }
    }

    // حذف موقع
    public function delete($id = null)
    {
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'حذف المواقع للمديرين فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if (empty($id)) {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        }

        if ($this->locationModel->delete($id)) {
            flash('location_msg', 'تم حذف الموقع');
            redirect('index.php?page=locations/index');
        } else {
            die('حدث خطأ أثناء حذف الموقع');
        }
    }
}
