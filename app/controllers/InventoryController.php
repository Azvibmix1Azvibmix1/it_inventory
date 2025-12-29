<?php
require_once '../app/models/Location.php';

class LocationsController {
    private $locationModel;

    public function __construct(){
        if(!isLoggedIn()){ redirect('index.php?page=login'); }
        $this->locationModel = new Location();
    }

    public function index(){
        // نحتاج الفروع الرئيسية للعرض الهرمي
        $main_locations = $this->locationModel->getMainLocations();
        
        // ونحتاج كل المواقع عشان قائمة "اختر الأب" عند الإضافة
        $all_locations = $this->locationModel->getAll();

        $data = [
            'main_locations' => $main_locations,
            'all_locations'  => $all_locations
        ];
        
        require_once APPROOT . '/views/locations/index.php';
    }

    public function add(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            $data = [
                'name_ar' => trim($_POST['name_ar']),
                'name_en' => trim($_POST['name_en']),
                'type'    => trim($_POST['type']),
                // إذا القيمة فارغة نرسل NULL
                'parent_id' => !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL
            ];

            if($this->locationModel->add($data)){
                flash('location_msg', 'تم إضافة الموقع للهيكل التنظيمي');
                redirect('index.php?page=locations');
            }
        } else {
            redirect('index.php?page=locations');
        }
    }

    public function delete(){
        if($_SESSION['user_role'] != 'admin'){ redirect('index.php?page=locations'); }
        if(isset($_GET['id'])){
            if($this->locationModel->delete($_GET['id'])){
                flash('location_msg', 'تم الحذف');
                redirect('index.php?page=locations');
            }
        }
    }
}
?>