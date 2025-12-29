<?php
class LocationsController extends Controller {
    private $locationModel;

    public function __construct() {
        // التحقق من تسجيل الدخول
        if(!isLoggedIn()){
            redirect('index.php?page=login');
        }
        $this->locationModel = $this->model('Location');
    }

    public function index() {
        $main_locations = $this->locationModel->getMainLocations();
        $all_locations = $this->locationModel->getAll();

        $data = [
            'main_locations' => $main_locations,
            'all_locations' => $all_locations,
            // تهيئة متغيرات الفورم لتجنب الأخطاء
            'name_ar' => '', 'name_en' => '', 'type' => '', 'parent_id' => '', 'name_err' => ''
        ];

        $this->view('locations/index', $data);
    }

    public function add() {
        // الحماية: للمدير العام فقط
        if(!isSuperAdmin()){ 
            flash('access_denied', 'إضافة المواقع للمدير العام فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'name_ar' => trim($_POST['name_ar'] ?? ''),
                'name_en' => trim($_POST['name_en'] ?? ''),
                'type' => trim($_POST['type'] ?? 'Building'),
                'parent_id' => !empty($_POST['parent_id']) ? trim($_POST['parent_id']) : null,
                'name_err' => ''
            ];

            if(empty($data['name_ar'])){
                $data['name_err'] = 'الاسم العربي مطلوب';
            }

            if(empty($data['name_err'])){
                if($this->locationModel->add($data)){
                    flash('location_msg', 'تم إضافة الموقع بنجاح');
                    redirect('index.php?page=locations/index');
                } else {
                    die('خطأ في قاعدة البيانات');
                }
            } else {
                // في حال الخطأ نعيد التوجيه للرئيسية (أو يمكن إعادة عرض الفيو)
                redirect('index.php?page=locations/index');
            }
        } else {
            redirect('index.php?page=locations/index');
        }
    }

    // دالة التعديل (هذه هي الدالة التي كانت تسبب الخطأ لعدم وجودها)
    public function edit($id){
        // الحماية: للمدير العام فقط
        if(!isSuperAdmin()){ 
            flash('access_denied', 'تعديل المواقع للمدير العام فقط', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'id' => $id,
                'name_ar' => trim($_POST['name_ar']),
                'name_en' => trim($_POST['name_en']),
                'type' => trim($_POST['type']),
                'parent_id' => !empty($_POST['parent_id']) ? $_POST['parent_id'] : null
            ];

            // استدعاء دالة التحديث من الموديل
            if($this->locationModel->update($data)){
                flash('location_msg', 'تم تحديث الموقع بنجاح');
                redirect('index.php?page=locations/index');
            } else {
                die('حدث خطأ أثناء التحديث');
            }

        } else {
            // جلب بيانات الموقع لعرضها في الفورم
            $location = $this->locationModel->getLocationById($id);
            $all_locations = $this->locationModel->getAll(); 

            if(!$location){
                redirect('index.php?page=locations/index');
            }

            $data = [
                'id' => $id,
                'name_ar' => $location->name_ar,
                'name_en' => $location->name_en,
                'type' => $location->type,
                'parent_id' => $location->parent_id,
                'all_locations' => $all_locations
            ];

            $this->view('locations/edit', $data);
        }
    }

    public function delete($id = null) {
        if(!isSuperAdmin()){ 
            redirect('index.php?page=locations/index');
        }

        if(empty($id)){
            $id = isset($_GET['id']) ? $_GET['id'] : 0;
        }

        if($this->locationModel->delete($id)){
            flash('location_msg', 'تم حذف الموقع');
            redirect('index.php?page=locations/index');
        }
    }
}