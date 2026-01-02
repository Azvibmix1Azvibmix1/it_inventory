<?php

class LocationsController extends Controller
{
    private $locationModel;
    private $userModel;

    public function __construct()
    {
        if (!isLoggedIn()) {
            redirect('index.php?page=login');
        }

        $this->locationModel = $this->model('Location');
        $this->userModel     = $this->model('User');
    }

    // صفحة عرض المواقع (الهيكل كامل)
    public function index()
    {
        // نجيب كل المواقع، ونستخدمها في الجدول + القوائم المنسدلة
        $locations = $this->locationModel->getAll();

        $data = [
            'locations' => $locations,

            // حقول جاهزة للنموذج (لتفادي undefined index)
            'name_ar'   => '',
            'name_en'   => '',
            'type'      => 'College',
            'parent_id' => '',
            'name_err'  => ''
        ];

        $this->view('locations/index', $data);
    }

    // إضافة موقع جديد (فرع / كلية / مبنى / طابق / معمل / مكتب / مستودع)
    public function add()
    {
        // صلاحية بسيطة: نخلي فقط المديرين والسوبر أدمن
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
                'type'      => trim($_POST['type'] ?? 'Building'),
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
                // في حالة الخطأ، نرجع لصفحة index مع نفس البيانات عشان الفورم ينعرض صح
                $locations = $this->locationModel->getAll();

                $data['locations'] = $locations;

                $this->view('locations/index', $data);
            }
        } else {
            // GET → رجّع لصفحة المواقع
            redirect('index.php?page=locations/index');
        }
    }

    // تعديل موقع
    public function edit($id)
    {
        // صلاحية للمديرين / السوبر أدمن
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'تعديل المواقع مسموح للمديرين فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        // نحول الـ id إلى عدد صحيح احتياطاً
        $id = (int)$id;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            // حالياً نحدّث الأساسيات فقط (اسم / نوع / أب)
            $data = [
                'id'        => $id,
                'name_ar'   => trim($_POST['name_ar'] ?? ''),
                'name_en'   => trim($_POST['name_en'] ?? ''),
                'type'      => trim($_POST['type'] ?? ''),
                'parent_id' => !empty($_POST['parent_id']) ? trim($_POST['parent_id']) : null,
            ];

            if (empty($data['name_ar'])) {
                flash('location_msg', 'الاسم العربي مطلوب', 'alert alert-danger');
                // نعيد تحميل الصفحة مع البيانات
                $location   = $this->locationModel->getLocationById($id);
                $allLocs    = $this->locationModel->getAll();
                $children   = [];
                foreach ($allLocs as $locRow) {
                    if ((int)$locRow->parent_id === $id) {
                        $children[] = $locRow;
                    }
                }
                $users = $this->userModel->getUsers();

                $viewData = [
                    'location' => $location,
                    'parents'  => $allLocs,
                    'children' => $children,
                    'users'    => $users,
                ];

                $this->view('locations/edit', $viewData);
                return;
            }

            if ($this->locationModel->update($data)) {
                flash('location_msg', 'تم تحديث بيانات الموقع بنجاح');
                redirect('index.php?page=locations/index');
            } else {
                die('حدث خطأ أثناء تحديث بيانات الموقع');
            }

        } else {
            // GET: عرض الفورم مع بيانات الموقع

            // 1) الموقع الحالي
            $location = $this->locationModel->getLocationById($id);
            if (!$location) {
                flash('location_msg', 'الموقع غير موجود', 'alert alert-danger');
                redirect('index.php?page=locations/index');
                exit;
            }

            // 2) كل المواقع (لاستخدامها كـ parents محتملة)
            $allLocations = $this->locationModel->getAll();

            // 3) الأبناء (المواقع التي parent_id = هذا الـ id)
            $children = [];
            foreach ($allLocations as $locRow) {
                if ((int)$locRow->parent_id === $id) {
                    $children[] = $locRow;
                }
            }

            // 4) المستخدمون (لاستخدامهم في صلاحيات الواجهة)
            // لو عندك دوال خاصة (getUsersByManager مثلاً) تقدر تخصصها لاحقاً
            $users = $this->userModel->getUsers();

            $data = [
                'location' => $location,
                'parents'  => $allLocations,
                'children' => $children,
                'users'    => $users
            ];

            $this->view('locations/edit', $data);
        }
    }

    // حذف موقع
    public function delete($id = null)
    {
        // صلاحية للمديرين / السوبر أدمن
        if (!isSuperAdmin() && !isManager()) {
            flash('access_denied', 'حذف المواقع مسموح للمديرين فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if (empty($id)) {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        } else {
            $id = (int)$id;
        }

        if ($this->locationModel->delete($id)) {
            flash('location_msg', 'تم حذف الموقع');
            redirect('index.php?page=locations/index');
        } else {
            die('حدث خطأ أثناء حذف الموقع');
        }
    }
}
