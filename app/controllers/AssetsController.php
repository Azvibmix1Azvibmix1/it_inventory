<?php

class AssetsController extends Controller{
  private $assetLogModel;

  private $assetModel;
  private $userModel;
  private $locationModel;

  public function __construct()
  {
    $this->assetModel    = $this->model('Asset');
    $this->userModel     = $this->model('User');
    $this->locationModel = $this->model('Location');
    // ✅ Audit log (اختياري لو الملف موجود)
    $this->assetLogModel = $this->model('AssetLog');


    if (function_exists('requireLogin')) {
      requireLogin();
    }
  }

  public function index(){
    $filters = [
      'location_id'      => isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0,
      'q'                => trim($_GET['q'] ?? ''),
      'include_children' => !empty($_GET['include_children']) ? 1 : 0,
    ];

    $locationsAll = method_exists($this->locationModel, 'getAll') ? $this->locationModel->getAll() : [];
    [$childrenMap, $parentMap] = $this->buildLocationMaps($locationsAll);

    // مواقع مسموحة للمستخدم (عرض/تعديل ... الخ)
    $allowedLocationIds = $this->getAllowedLocationIdsForAssets();
    if (is_array($allowedLocationIds)) {
      $allowedLocationIds = $this->expandIdsWithDescendants($allowedLocationIds, $childrenMap);
    }

    // فلتر “يشمل التوابع”
    if ($filters['location_id'] > 0 && $filters['include_children'] == 1) {
      $desc = $this->getDescendants($filters['location_id'], $childrenMap);
      $filters['location_ids'] = array_values(array_unique(array_merge([(int)$filters['location_id']], $desc)));
    }

    // Dropdown المواقع في الفلتر:
    $locationsForUser = $locationsAll;
    if (is_array($allowedLocationIds)) {
      $locationsForUser = array_values(array_filter($locationsAll, function ($loc) use ($allowedLocationIds) {
        return in_array((int)$loc->id, $allowedLocationIds, true);
      }));
    }

    // جلب الأجهزة بكفاءة
    $assets = method_exists($this->assetModel, 'getAssetsFiltered')
      ? $this->assetModel->getAssetsFiltered($filters, $allowedLocationIds)
      : [];

    // لو فلتر على موقع غير مسموح به -> صفّر
    if ($filters['location_id'] > 0 && is_array($allowedLocationIds)) {
      if (!in_array($filters['location_id'], $allowedLocationIds, true)) {
        $assets = [];
      }
    }

    $data = [
      'assets'       => $assets,
      'locations'    => $locationsForUser,
      'filters'      => $filters,
      'can_add_asset'=> $this->canAddAssetsAnywhere() || $this->userHasAnyAddableLocation($childrenMap),
    ];

    $this->view('assets/index', $data);
  }

  public function add(){
  $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');

  $locationsAll = method_exists($this->locationModel, 'getAll') ? $this->locationModel->getAll() : [];
  [$childrenMap, $parentMap] = $this->buildLocationMaps($locationsAll);

  // المواقع المسموح الإضافة عليها (مع توابعها)
  $addableLocationIds = $this->getAddableLocationIdsForUser();
  if (is_array($addableLocationIds)) {
    $addableLocationIds = $this->expandIdsWithDescendants($addableLocationIds, $childrenMap);
  }

  $locationsForAdd = $locationsAll;
  if (is_array($addableLocationIds)) {
    $locationsForAdd = array_values(array_filter($locationsAll, function ($loc) use ($addableLocationIds) {
      return in_array((int)$loc->id, $addableLocationIds, true);
    }));
  }

  $users_list = [];
  if (in_array($role, ['superadmin', 'manager'], true) && method_exists($this->userModel, 'getUsers')) {
    $users_list = $this->userModel->getUsers();
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $_POST = filter_input_array(INPUT_POST, [
      'asset_tag' => FILTER_UNSAFE_RAW,
      'serial_no' => FILTER_UNSAFE_RAW,
      'brand' => FILTER_UNSAFE_RAW,
      'model' => FILTER_UNSAFE_RAW,
      'type' => FILTER_UNSAFE_RAW,
      'status' => FILTER_UNSAFE_RAW,
      'purchase_date' => FILTER_UNSAFE_RAW,
      'warranty_expiry' => FILTER_UNSAFE_RAW,
      'notes' => FILTER_UNSAFE_RAW,
      'location_id' => FILTER_VALIDATE_INT,
      'assigned_to' => FILTER_VALIDATE_INT,
    ]);

    $locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : 0;

    if (!$this->canAddAssetToLocation($locationId, $parentMap)) {
      flash('asset_msg', 'ليس لديك صلاحية لإضافة جهاز لهذا الموقع', 'alert alert-danger');
      redirect('index.php?page=assets/index');
      return;
    }

    $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : 0;
    if (!in_array($role, ['superadmin', 'manager'], true)) {
      $assignedTo = (int)($_SESSION['user_id'] ?? 0);
    }

    $data = [
      'asset_tag'       => trim($_POST['asset_tag'] ?? ''),
      'serial_no'       => trim($_POST['serial_no'] ?? ($_POST['serial'] ?? '')),
      'brand'           => trim($_POST['brand'] ?? ''),
      'model'           => trim($_POST['model'] ?? ''),
      'type'            => trim($_POST['type'] ?? ''),

      'purchase_date'   => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
      'warranty_expiry' => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,

      'status'          => $_POST['status'] ?? 'Active',
      'location_id'     => $locationId > 0 ? $locationId : null,
      'assigned_to'     => $assignedTo > 0 ? $assignedTo : null,

      'notes'           => isset($_POST['notes']) ? trim($_POST['notes']) : null,
      'created_by'      => !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,

      // عشان لو رجعنا الفورم لوجود خطأ
      'users_list'      => $users_list,
      'locations'       => $locationsForAdd,
      'asset_err'       => ''
    ];

    // ✅ لو التاق فاضي: ولّده تلقائيًا
    if (empty($data['asset_tag'])) {
      $data['asset_tag'] = $this->generateUniqueAssetTag();
    }

    if (empty($data['type']) || empty($locationId)) {
      $data['asset_err'] = 'الرجاء تعبئة الحقول الأساسية (النوع/الموقع)';
    }

    if (!empty($data['asset_err'])) {
      $this->view('assets/add', $data);
      return;
    }

    // ✅ محاولة إدخال مع معالجة duplicate asset_tag (اختياري لكن مفيد)
    for ($try = 0; $try < 5; $try++) {
      try {
        if ($try > 0) {
          $data['asset_tag'] = $this->generateUniqueAssetTag();
        }

        $newId = $this->assetModel->add($data);
      if ($newId) {
  $details = "إضافة جهاز | Tag={$data['asset_tag']} | Type={$data['type']} | LocationID={$locationId}";
  $this->logAssetAction((int)$newId, 'create', $details);

  redirect('index.php?page=assets/show&id=' . (int)$newId);
  return;
}


        $data['asset_err'] = 'حدث خطأ أثناء الإضافة في قاعدة البيانات';
        $this->view('assets/add', $data);
        return;

      } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'Duplicate entry') !== false && stripos($msg, 'asset_tag') !== false) {
          continue;
        }
        throw $e;
      }
    }

    $data['asset_err'] = 'تعذر توليد Tag فريد، حاول مرة أخرى';
    $this->view('assets/add', $data);
    return;
  }

  // ✅ GET: يولّد Tag جاهز للفورم
  $data = [
    'asset_tag'   => $this->generateUniqueAssetTag(),
    'serial_no'   => '',
    'brand'       => '',
    'model'       => '',
    'type'        => '',
    'location_id' => '',
    'assigned_to' => '',
    'users_list'  => $users_list,
    'locations'   => $locationsForAdd,
    'asset_err'   => ''
  ];

  $this->view('assets/add', $data);
}

  
public function show($id = null)
{
  if (empty($id)) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  }
  $assetId = (int)$id;

  if ($assetId <= 0) {
    flash('asset_msg', 'معرّف الجهاز غير صحيح', 'alert alert-danger');
    redirect('index.php?page=assets/index');
    return;
  }

  // جلب الجهاز (مع اسم الموقع والموظف إن وجد)
  $asset = method_exists($this->assetModel, 'getAssetById') ? $this->assetModel->getAssetById($assetId) : null;
  if (!$asset) {
    flash('asset_msg', 'الجهاز غير موجود', 'alert alert-danger');
    redirect('index.php?page=assets/index');
    return;
  }

  // صلاحيات العرض: نفس منطق الصفحة الرئيسية (مواقع مسموحة للمستخدم)
  $locationsAll = method_exists($this->locationModel, 'getAll') ? $this->locationModel->getAll() : [];
  [$childrenMap, $parentMap] = $this->buildLocationMaps($locationsAll);

  $allowedLocationIds = $this->getAllowedLocationIdsForAssets();
  if (is_array($allowedLocationIds)) {
    $allowedLocationIds = $this->expandIdsWithDescendants($allowedLocationIds, $childrenMap);
    $locId = (int)($asset->location_id ?? 0);
    if ($locId > 0 && !in_array($locId, $allowedLocationIds, true)) {
      flash('asset_msg', 'ليس لديك صلاحية لعرض هذا الجهاز', 'alert alert-danger');
      redirect('index.php?page=assets/index');
      return;
    }
  }

  // رابط كامل للـ QR (يعرض صفحة التفاصيل)
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  $baseUrl  = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath;
  $qrUrl    = $baseUrl . '/index.php?page=assets/show&id=' . (int)$assetId;

  $logs = [];
if ($this->assetLogModel && method_exists($this->assetLogModel, 'getByAsset')) {
  $logs = $this->assetLogModel->getByAsset($assetId, 30);
}

$data = [
  'asset' => $asset,
  'qrUrl' => $qrUrl,
  'logs'  => $logs,
];


  $this->view('assets/show', $data);
}

  public function edit($id = null){
    if (empty($id)) {
      $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    }
    $assetId = (int)$id;

    if ($assetId <= 0) {
      redirect('index.php?page=assets/index');
      return;
    }

    $asset = method_exists($this->assetModel, 'getAssetById') ? $this->assetModel->getAssetById($assetId) : null;
    if (!$asset) {
      flash('asset_msg', 'الجهاز غير موجود', 'alert alert-danger');
      redirect('index.php?page=assets/index');
      return;
    }

    $locationsAll = method_exists($this->locationModel, 'getAll') ? $this->locationModel->getAll() : [];
    [$childrenMap, $parentMap] = $this->buildLocationMaps($locationsAll);

    $assetLocationId = (int)($asset->location_id ?? 0);
    if (!$this->canEditAssetAtLocation($assetLocationId, $parentMap)) {
      flash('asset_msg', 'ليس لديك صلاحية لتعديل جهاز في هذا الموقع', 'alert alert-danger');
      redirect('index.php?page=assets/index');
      return;
    }

    $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');

    $addableLocationIds = $this->getAddableLocationIdsForUser();
    if (is_array($addableLocationIds)) {
      $addableLocationIds = $this->expandIdsWithDescendants($addableLocationIds, $childrenMap);
    }

    $locationsForEdit = $locationsAll;
    if (is_array($addableLocationIds)) {
      $locationsForEdit = array_values(array_filter($locationsAll, function ($loc) use ($addableLocationIds) {
        return in_array((int)$loc->id, $addableLocationIds, true);
      }));
    }

    $users_list = [];
    if (in_array($role, ['superadmin', 'manager'], true) && method_exists($this->userModel, 'getUsers')) {
      $users_list = $this->userModel->getUsers();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$_POST = filter_input_array(INPUT_POST, [
  'asset_tag' => FILTER_UNSAFE_RAW,
  'serial_no' => FILTER_UNSAFE_RAW,
  'brand' => FILTER_UNSAFE_RAW,
  'model' => FILTER_UNSAFE_RAW,
  'type' => FILTER_UNSAFE_RAW,
  'status' => FILTER_UNSAFE_RAW,
  'purchase_date' => FILTER_UNSAFE_RAW,
  'warranty_expiry' => FILTER_UNSAFE_RAW,
  'notes' => FILTER_UNSAFE_RAW,
  'location_id' => FILTER_VALIDATE_INT,
  'assigned_to' => FILTER_VALIDATE_INT,
]);

      $newLocationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : 0;

      if (!$this->canAddAssetToLocation($newLocationId, $parentMap)) {
        flash('asset_msg', 'لا يمكنك نقل/تعديل جهاز إلى هذا الموقع (صلاحيات غير كافية)', 'alert alert-danger');
        redirect('index.php?page=assets/edit&id=' . $assetId);
        return;
      }

      $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : 0;
      if (!in_array($role, ['superadmin', 'manager'], true)) {
        $assignedTo = (int)($_SESSION['user_id'] ?? 0);
      }

      $data = [
        'id'          => $assetId,
        'asset_tag'   => trim($_POST['asset_tag'] ?? ''),
        'serial_no'   => trim($_POST['serial_no'] ?? ''),
        'brand'       => trim($_POST['brand'] ?? ''),
        'model'       => trim($_POST['model'] ?? ''),
        'type'        => trim($_POST['type'] ?? ''),
        'location_id' => $newLocationId > 0 ? $newLocationId : null,
        'assigned_to' => $assignedTo > 0 ? $assignedTo : null,
        'status'      => trim($_POST['status'] ?? 'Active'),
      ];

      if ($this->assetModel->update($data)) {

  $action  = 'update';
  $details = $this->buildAssetUpdateDetails($asset, $data, $locationsAll);

  $oldLoc = (int)($asset->location_id ?? 0);
  $newLoc = (int)($data['location_id'] ?? 0);
  $oldStatus = trim((string)($asset->status ?? ''));
  $newStatus = trim((string)($data['status'] ?? ''));

  if ($oldLoc !== $newLoc) $action = 'transfer';
  elseif ($oldStatus !== $newStatus) $action = 'status';

  $this->logAssetAction((int)$assetId, $action, $details);

  flash('asset_msg', 'تم تحديث بيانات الجهاز');
  redirect('index.php?page=assets/index');
} else {
  die('خطأ في التعديل');
}

    }

    $data = [
      'id'          => $assetId,
      'asset_tag'   => $asset->asset_tag ?? '',
      'serial_no'   => $asset->serial_no ?? '',
      'brand'       => $asset->brand ?? '',
      'model'       => $asset->model ?? '',
      'type'        => $asset->type ?? '',
      'location_id' => $asset->location_id ?? '',
      'assigned_to' => $asset->assigned_to ?? '',
      'status'      => $asset->status ?? 'Active',
      'users_list'  => $users_list,
      'locations'   => $locationsForEdit
    ];

    $this->view('assets/edit', $data);
  }

  public function delete()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      flash('asset_msg', 'طريقة طلب غير صحيحة للحذف', 'alert alert-danger');
      redirect('index.php?page=assets/index');
      return;
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
      flash('asset_msg', 'معرّف جهاز غير صالح', 'alert alert-danger');
      redirect('index.php?page=assets/index');
      return;
    }

    $asset = method_exists($this->assetModel, 'getAssetById') ? $this->assetModel->getAssetById($id) : null;
    if (!$asset) {
      flash('asset_msg', 'الجهاز غير موجود', 'alert alert-danger');
      redirect('index.php?page=assets/index');
      return;
    }

    $locationsAll = method_exists($this->locationModel, 'getAll') ? $this->locationModel->getAll() : [];
    [$childrenMap, $parentMap] = $this->buildLocationMaps($locationsAll);

    $locId = (int)($asset->location_id ?? 0);
    if (!$this->canDeleteAssetAtLocation($locId, $parentMap)) {
      flash('asset_msg', 'ليس لديك صلاحية لحذف جهاز في هذا الموقع', 'alert alert-danger');
      redirect('index.php?page=assets/index');
      return;
    }

    if ($this->assetModel->delete($id)) {
  $tag = (string)($asset->asset_tag ?? '');
  $type = (string)($asset->type ?? '');
  $details = "حذف جهاز | Tag={$tag} | Type={$type} | AssetID={$id}";
  $this->logAssetAction((int)$id, 'delete', $details);

  flash('asset_msg', 'تم حذف الجهاز');
} else {
  flash('asset_msg', 'فشل الحذف، حاول مرة أخرى', 'alert alert-danger');
}


    redirect('index.php?page=assets/index');
  }

  public function my_assets()
  {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $myAssets = method_exists($this->assetModel, 'getAssetsByUserId') ? $this->assetModel->getAssetsByUserId($userId) : [];
    $data = ['assets' => $myAssets];
    $this->view('assets/my_assets', $data);
  }

  /* =========================
   * ✅ طباعة (قائمة + ملصقات)
   * ========================= */

  public function print_list()
  {
    $filters = [
      'location_id'      => isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0,
      'q'                => trim($_GET['q'] ?? ''),
      'include_children' => !empty($_GET['include_children']) ? 1 : 0,
    ];

    $locationsAll = method_exists($this->locationModel, 'getAll') ? $this->locationModel->getAll() : [];
    [$childrenMap, $parentMap] = $this->buildLocationMaps($locationsAll);

    $allowedLocationIds = $this->getAllowedLocationIdsForAssets();
    if (is_array($allowedLocationIds)) {
      $allowedLocationIds = $this->expandIdsWithDescendants($allowedLocationIds, $childrenMap);
    }

    if ($filters['location_id'] > 0 && $filters['include_children'] == 1) {
      $desc = $this->getDescendants($filters['location_id'], $childrenMap);
      $filters['location_ids'] = array_values(array_unique(array_merge([(int)$filters['location_id']], $desc)));
    }

    $assets = method_exists($this->assetModel, 'getAssetsFiltered')
      ? $this->assetModel->getAssetsFiltered($filters, $allowedLocationIds)
      : [];

    $data = [
      'assets'  => $assets,
      'filters' => $filters,
      'mode'    => 'list'
    ];
    $this->view('assets/print', $data);
  }

  public function print_labels()
  {
    $filters = [
      'location_id'      => isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0,
      'q'                => trim($_GET['q'] ?? ''),
      'include_children' => !empty($_GET['include_children']) ? 1 : 0,
    ];

    $locationsAll = method_exists($this->locationModel, 'getAll') ? $this->locationModel->getAll() : [];
    [$childrenMap, $parentMap] = $this->buildLocationMaps($locationsAll);

    $allowedLocationIds = $this->getAllowedLocationIdsForAssets();
    if (is_array($allowedLocationIds)) {
      $allowedLocationIds = $this->expandIdsWithDescendants($allowedLocationIds, $childrenMap);
    }

    if ($filters['location_id'] > 0 && $filters['include_children'] == 1) {
      $desc = $this->getDescendants($filters['location_id'], $childrenMap);
      $filters['location_ids'] = array_values(array_unique(array_merge([(int)$filters['location_id']], $desc)));
    }

    $assets = method_exists($this->assetModel, 'getAssetsFiltered')
      ? $this->assetModel->getAssetsFiltered($filters, $allowedLocationIds)
      : [];

    $data = [
      'assets'  => $assets,
      'filters' => $filters,
      'mode'    => 'labels'
    ];
    $this->view('assets/print', $data);
  }
  public function generate_tag()
{
  requireLogin();

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['tag' => $this->generateUniqueAssetTag()]);
  exit;
}

  /* ==========================
   * Helpers (صلاحيات + مواقع)
   * ========================== */

  private function canAddAssetsAnywhere()
  {
    $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');
    return in_array($role, ['superadmin', 'manager'], true);
  }

  private function getAllowedLocationIdsForAssets()
  {
    $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');
    if (in_array($role, ['superadmin', 'manager'], true)) return null;

    if (!class_exists('Database')) return [];

    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) return [];

    try {
      $db = new Database();
      $db->query("
        SELECT DISTINCT location_id
        FROM locations_permissions
        WHERE (user_id = :uid OR role = :role)
          AND (can_manage=1 OR can_add_children=1 OR can_edit=1 OR can_delete=1)
      ");
      $db->bind(':uid', $uid);
      $db->bind(':role', $role);
      $rows = $db->resultSet();

      $ids = [];
      foreach ($rows as $r) $ids[] = (int)$r->location_id;
      return array_values(array_unique($ids));
    } catch (Exception $e) {
      return [];
    }
  }

  private function getAddableLocationIdsForUser()
  {
    $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');
    if (in_array($role, ['superadmin', 'manager'], true)) return null;

    if (!class_exists('Database')) return [];

    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) return [];

    try {
      $db = new Database();
      $db->query("
        SELECT DISTINCT location_id
        FROM locations_permissions
        WHERE (user_id = :uid OR role = :role)
          AND (can_manage=1 OR can_add_children=1)
      ");
      $db->bind(':uid', $uid);
      $db->bind(':role', $role);
      $rows = $db->resultSet();

      $ids = [];
      foreach ($rows as $r) $ids[] = (int)$r->location_id;
      return array_values(array_unique($ids));
    } catch (Exception $e) {
      return [];
    }
  }

  private function userHasAnyAddableLocation($childrenMap)
  {
    $ids = $this->getAddableLocationIdsForUser();
    if ($ids === null) return true;
    if (!is_array($ids) || count($ids) === 0) return false;
    $ids = $this->expandIdsWithDescendants($ids, $childrenMap);
    return count($ids) > 0;
  }

  // ✅ السماح إذا عنده صلاحية على الموقع نفسه أو على أحد الآباء
  private function canAddAssetToLocation($locationId, $parentMap)
  {
    $locationId = (int)$locationId;
    if ($locationId <= 0) return false;

    $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');
    if (in_array($role, ['superadmin', 'manager'], true)) return true;

    if (function_exists('canManageLocation')) {
      if (canManageLocation($locationId, 'add') || canManageLocation($locationId, 'manage')) return true;

      foreach ($this->getAncestors($locationId, $parentMap) as $anc) {
        if (canManageLocation($anc, 'add') || canManageLocation($anc, 'manage')) return true;
      }
      return false;
    }

    return false;
  }

  private function canEditAssetAtLocation($locationId, $parentMap)
  {
    $locationId = (int)$locationId;
    if ($locationId <= 0) return false;

    $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');
    if (in_array($role, ['superadmin', 'manager'], true)) return true;

    if (function_exists('canManageLocation')) {
      if (canManageLocation($locationId, 'edit') || canManageLocation($locationId, 'manage')) return true;

      foreach ($this->getAncestors($locationId, $parentMap) as $anc) {
        if (canManageLocation($anc, 'edit') || canManageLocation($anc, 'manage')) return true;
      }
    }
    return false;
  }

  private function canDeleteAssetAtLocation($locationId, $parentMap)
  {
    $locationId = (int)$locationId;
    if ($locationId <= 0) return false;

    $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');
    if (in_array($role, ['superadmin', 'manager'], true)) return true;

    if (function_exists('canManageLocation')) {
      if (canManageLocation($locationId, 'delete') || canManageLocation($locationId, 'manage')) return true;

      foreach ($this->getAncestors($locationId, $parentMap) as $anc) {
        if (canManageLocation($anc, 'delete') || canManageLocation($anc, 'manage')) return true;
      }
    }
    return false;
  }

  private function buildLocationMaps($locations)
  {
    $childrenMap = [];
    $parentMap = [];
    foreach ($locations as $l) {
      $id = (int)$l->id;
      $pid = !empty($l->parent_id) ? (int)$l->parent_id : 0;
      $parentMap[$id] = $pid;
      if (!isset($childrenMap[$pid])) $childrenMap[$pid] = [];
      $childrenMap[$pid][] = $id;
    }
    return [$childrenMap, $parentMap];
  }

  private function getDescendants($rootId, $childrenMap)
  {
    $rootId = (int)$rootId;
    $out = [];
    $queue = [$rootId];
    while (!empty($queue)) {
      $cur = array_shift($queue);
      if (!isset($childrenMap[$cur])) continue;
      foreach ($childrenMap[$cur] as $child) {
        $out[] = (int)$child;
        $queue[] = (int)$child;
      }
    }
    return array_values(array_unique($out));
  }

  private function expandIdsWithDescendants($ids, $childrenMap)
  {
    $set = [];
    foreach ($ids as $id) {
      $id = (int)$id;
      if ($id <= 0) continue;
      $set[$id] = true;
      foreach ($this->getDescendants($id, $childrenMap) as $d) {
        $set[(int)$d] = true;
      }
    }
    return array_map('intval', array_keys($set));
  }

  private function getAncestors($id, $parentMap)
  {
    $id = (int)$id;
    $out = [];
    $seen = [];
    while (isset($parentMap[$id]) && (int)$parentMap[$id] > 0) {
      $id = (int)$parentMap[$id];
      if (isset($seen[$id])) break;
      $seen[$id] = true;
      $out[] = $id;
    }
    return $out;
  }

 private function generateUniqueAssetTag(): string
{
  // AST-20260103-182112-A1B2
  for ($i = 0; $i < 10; $i++) {
    $rand = strtoupper(bin2hex(random_bytes(2)));
    $tag  = 'AST-' . date('Ymd-His') . '-' . $rand;

    // لو عندك دالة في الموديل تفحص وجود التاق
    if (method_exists($this->assetModel, 'assetTagExists')) {
      if (!$this->assetModel->assetTagExists($tag)) return $tag;
    } else {
      // إذا ما عندك فحص، رجّعه (التصادم شبه مستحيل)
      return $tag;
    }
  }
  return 'AST-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

private function logAssetAction(int $assetId, string $action, ?string $details = null): void
{
  if (!$this->assetLogModel || !method_exists($this->assetLogModel, 'add')) return;

  $userId = (int)($_SESSION['user_id'] ?? 0);
  try {
    $this->assetLogModel->add($assetId, $userId > 0 ? $userId : null, $action, $details);
  } catch (Throwable $e) {
    // لا نكسر النظام إذا فشل اللوج
  }
}

private function buildAssetUpdateDetails($oldAsset, array $newData, array $locationsAll): string
{
  $locMap = [];
  foreach ($locationsAll as $l) {
    $locMap[(int)($l->id ?? 0)] = $l->name_ar ?? ($l->name ?? ('موقع#'.(int)($l->id ?? 0)));
  }

  $pairs = [
    'asset_tag'      => 'Tag',
    'type'           => 'النوع',
    'brand'          => 'الماركة',
    'model'          => 'الموديل',
    'serial_no'      => 'Serial',
    'status'         => 'الحالة',
    'purchase_date'  => 'تاريخ الشراء',
    'warranty_expiry'=> 'انتهاء الضمان',
    'notes'          => 'ملاحظات',
    'location_id'    => 'الموقع',
    'assigned_to'    => 'المستلم',
  ];

  $changes = [];
  foreach ($pairs as $k => $label) {
    $oldVal = $oldAsset->$k ?? null;
    $newVal = $newData[$k] ?? null;

    // تنظيف
    $oldVal = is_string($oldVal) ? trim($oldVal) : $oldVal;
    $newVal = is_string($newVal) ? trim($newVal) : $newVal;

    // أسماء الموقع بدل ID
    if ($k === 'location_id') {
      $oldVal = $locMap[(int)$oldVal] ?? (string)(int)$oldVal;
      $newVal = $locMap[(int)$newVal] ?? (string)(int)$newVal;
    }

    if ((string)$oldVal !== (string)$newVal) {
      $changes[] = "{$label}: {$oldVal} → {$newVal}";
    }
  }

  if (empty($changes)) return "تم حفظ التعديل بدون تغييرات واضحة";
  return implode(" | ", $changes);
}


}
