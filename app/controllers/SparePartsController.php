<?php
class SparePartsController extends Controller {
    private $spareModel;
    private $locationModel;

    public function __construct(){
        // التحقق من تسجيل الدخول
        if(!isLoggedIn()){ 
            redirect('index.php?page=users/login'); 
        }
        
        // تحميل الموديلات
        $this->spareModel = $this->model('SparePart');
        $this->locationModel = $this->model('Location');
        $locations = $this->locationModel->getAll();

    }

    public function index(){
        // جلب البيانات
        $parts = $this->spareModel->getParts();
        
        // حساب الإحصائيات
        $totalParts = count($parts);
        $outOfStock = 0;
        $lowStock = 0;

        foreach($parts as $part){
            if($part->quantity == 0){
                $outOfStock++;
            } elseif ($part->quantity < 5) {
                $lowStock++;
            }
        }

        $data = [
            'parts' => $parts,
            'total_parts' => $totalParts,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock
        ];
        
        // تحميل ملف العرض (اسم المجلد spare_parts)
        $this->view('spare_parts/index', $data);
    }

    public function add(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            
            // تنظيف المدخلات
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'part_number' => trim($_POST['part_number'] ?? ''),
                'quantity' => trim($_POST['quantity'] ?? 0),
                'min_quantity' => trim($_POST['min_quantity'] ?? 0),
                'location_id' => !empty($_POST['location_id']) ? trim($_POST['location_id']) : null,
                'description' => trim($_POST['description'] ?? ''),
                'locations' => $this->locationModel->getAll(),
                'name_err' => ''
            ];

            // التحقق من الاسم
            if(empty($data['name'])){ 
                $data['name_err'] = 'الرجاء كتابة اسم القطعة'; 
            }

            if(empty($data['name_err'])){
                // الإضافة
                if($this->spareModel->add($data)){
                    flash('part_message', 'تم إضافة قطعة الغيار بنجاح');
                    // التوجيه الصحيح (لاحظ SpareParts)
                    redirect('index.php?page=SpareParts/index'); 
                } else {
                    die('حدث خطأ في قاعدة البيانات');
                }
            } else {
                // العودة للفورم في حال وجود خطأ
                $this->view('spare_parts/add', $data);
            }

        } else {
            // تحميل الصفحة لأول مرة (GET)
            $locations = $this->locationModel->getAll();
            $data = [
                'name' => '',
                'part_number' => '',
                'quantity' => 1,
                'min_quantity' => 5,
                'location_id' => '',
                'description' => '',
                'locations' => $locations,
                'name_err' => ''
            ];
            
            $this->view('spare_parts/add', $data);
        }
    }

    public function edit($id){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
             // كود التحديث (يجب وضعه هنا لاحقاً)
        } else {
            $part = $this->spareModel->getPartById($id);
            $locations = $this->locationModel->getAll();

            if(!$part){
                redirect('index.php?page=SpareParts/index');
            }

            $data = [
                'id' => $part->id,
                'name' => $part->name,
                'part_number' => $part->part_number,
                'quantity' => $part->quantity,
                'min_quantity' => $part->min_quantity,
                'location_id' => $part->location_id,
                'description' => $part->description,
                'locations' => $locations,
                'name_err' => ''
            ];

            $this->view('spare_parts/edit', $data);
        }
    }

    public function delete($id){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            if($this->spareModel->delete($id)){
                flash('part_message', 'تم حذف القطعة');
                // التوجيه الصحيح (لاحظ SpareParts)
                redirect('index.php?page=SpareParts/index');
            } else {
                die('حدث خطأ أثناء الحذف');
            }
        } else {
            redirect('index.php?page=SpareParts/index');
        }
    }
}