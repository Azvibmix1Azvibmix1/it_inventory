<?php
// app/controllers/PartsController.php
require_once '../app/models/Part.php';
require_once '../app/models/Location.php';

class PartsController {
    private $partModel;
    private $locationModel;

    public function __construct(){
        if(!isLoggedIn()){ redirect('index.php?page=login'); }
        $this->partModel = new Part();
        $this->locationModel = new Location();
    }

    public function index(){
        $parts = $this->partModel->getAll();
        $data = ['parts' => $parts];
        require_once '../app/views/parts/index.php';
    }

    public function add(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            $data = [
                'name' => trim($_POST['name']),
                'category' => trim($_POST['category']),
                'quantity' => (int)$_POST['quantity'],
                'min_stock' => (int)$_POST['min_stock'],
                'location_id' => !empty($_POST['location_id']) ? $_POST['location_id'] : NULL,
                'description' => trim($_POST['description'])
            ];

            if($this->partModel->add($data)){
                flash('part_msg', 'Part Added Successfully');
                redirect('index.php?page=parts');
            }
        } else {
            // نحتاج المواقع لتحديد مكان التخزين
            $locations = $this->locationModel->getAll(); 
            $data = ['locations' => $locations];
            require_once '../app/views/parts/add.php';
        }
    }
    
    // دالة سريعة لتحديث الكمية
    public function update_stock(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
             $id = $_POST['id'];
             $qty = $_POST['quantity'];
             $this->partModel->updateQuantity($id, $qty);
             redirect('index.php?page=parts');
        }
    }
}
?>
