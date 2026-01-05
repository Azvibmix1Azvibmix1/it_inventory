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
        $parts = method_exists($this->spareModel, 'getParts')
  ? $this->spareModel->getParts()
  : $this->spareModel->getAll();

        
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
$prefillLoc = (int)($_GET['location_id'] ?? 0);

$data = [
    'name' => '',
    'part_number' => '',
    'quantity' => 1,
    'min_quantity' => 5,
    'location_id' => $prefillLoc > 0 ? $prefillLoc : '',
    'description' => '',
    'locations' => $locations,
    'name_err' => ''
];

            
            $this->view('spare_parts/add', $data);
        }
    }

    public function edit($id = null){
  // لأن الراوتر ينادي edit() بدون id
  $id = $id ?? ($_GET['id'] ?? null);
  $id = (int)$id;

  if($id <= 0){
    flash('part_message', 'معرّف القطعة غير صحيح', 'alert alert-danger');
    redirect('index.php?page=SpareParts/index');
    return;
  }

  $locations = $this->locationModel->getAll();

  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

    $data = [
      'id' => $id,
      'name' => trim($_POST['name'] ?? ''),
      'part_number' => trim($_POST['part_number'] ?? ''),
      'quantity' => (int)($_POST['quantity'] ?? 0),
      'min_quantity' => (int)($_POST['min_quantity'] ?? 0),
      'location_id' => !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null,
      'description' => trim($_POST['description'] ?? ''),
      'locations' => $locations,
      'name_err' => ''
    ];

    if(empty($data['name'])){
      $data['name_err'] = 'الرجاء كتابة اسم القطعة';
      $this->view('spare_parts/edit', $data);
      return;
    }

    if($this->spareModel->update($data)){
      flash('part_message', 'تم تحديث قطعة الغيار بنجاح');
      redirect('index.php?page=SpareParts/index');
      return;
    } else {
      die('حدث خطأ في قاعدة البيانات');
    }

  } else {
    $part = $this->spareModel->getPartById($id);
    if(!$part){
      flash('part_message', 'القطعة غير موجودة', 'alert alert-danger');
      redirect('index.php?page=SpareParts/index');
      return;
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


    public function delete($id = null){
  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $id = $id ?? ($_POST['id'] ?? null);
    $id = (int)$id;

    if($id <= 0){
      flash('part_message', 'معرّف غير صحيح', 'alert alert-danger');
      redirect('index.php?page=SpareParts/index');
      return;
    }

    if($this->spareModel->delete($id)){
      flash('part_message', 'تم حذف القطعة');
      redirect('index.php?page=SpareParts/index');
    } else {
      die('حدث خطأ أثناء الحذف');
    }
  } else {
    redirect('index.php?page=SpareParts/index');
  }
}

public function adjust($id = null){
  if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    redirect('index.php?page=spareParts/index');
    return;
  }

  $id = $id ?? ($_POST['id'] ?? 0);
  $id = (int)$id;

  $delta = (int)($_POST['delta'] ?? 0);
  $locationId = (int)($_POST['location_id'] ?? 0);
  $returnTo = trim($_POST['return_to'] ?? '');

  if($id <= 0 || $delta === 0){
    flash('part_message', 'بيانات غير صحيحة', 'alert alert-danger');
    redirect($returnTo ?: 'index.php?page=spareParts/index');
    return;
  }

  // جيب القطعة وتأكد موجودة
  $part = method_exists($this->spareModel, 'getPartById')
    ? $this->spareModel->getPartById($id)
    : null;

  if(!$part){
    flash('part_message', 'القطعة غير موجودة', 'alert alert-danger');
    redirect($returnTo ?: 'index.php?page=spareParts/index');
    return;
  }

  // إذا القطعة مربوطة بموقع، نتحقق من صلاحية التعديل على الموقع (لو عندك الدالة)
  $partLocId = (int)($part->location_id ?? 0);
  if($partLocId > 0 && function_exists('requireLocationPermission')){
    requireLocationPermission($partLocId, 'edit', $returnTo ?: 'index.php?page=spareParts/index');
  }

  // نفّذ التعديل
  if(method_exists($this->spareModel, 'adjustQuantity') && $this->spareModel->adjustQuantity($id, $delta)){
    flash('part_message', 'تم تحديث الكمية بنجاح');
  } else {
    flash('part_message', 'فشل تحديث الكمية', 'alert alert-danger');
  }

  // رجّع لنفس صفحة الموقع
  if($returnTo){
    redirect($returnTo);
  } elseif($locationId > 0){
    redirect("index.php?page=locations/edit&id={$locationId}");
  } else {
    redirect('index.php?page=spareParts/index');
  }
}


}