<?php

class AssetsController extends Controller
{
  private $assetModel;
  private $userModel;
  private $locationModel;

  public function __construct()
  {
    $this->assetModel    = $this->model('Asset');
    $this->userModel     = $this->model('User');
    $this->locationModel = $this->model('Location');

    if (function_exists('requireLogin')) {
      requireLogin();
    }
  }

  public function index()
  {
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

  public function add()
  {
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
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

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
        'asset_tag'   => trim($_POST['asset_tag'] ?? ''),
        'serial_no'   => trim($_POST['serial_no'] ?? ''),
        'brand'       => trim($_POST['brand'] ?? ''),
        'model'       => trim($_POST['model'] ?? ''),
        'type'        => trim($_POST['type'] ?? ''),
        'location_id' => $locationId > 0 ? $locationId : null,
        'assigned_to' => $assignedTo > 0 ? $assignedTo : null,
        'status'      => 'Active',
        'created_by'  => (int)($_SESSION['user_id'] ?? 0),

        'users_list'  => $users_list,
        'locations'   => $locationsForAdd,
        'asset_err'   => ''
      ];

      if (empty($data['asset_tag']) || empty($data['type']) || empty($locationId)) {
        $data['asset_err'] = 'الرجاء تعبئة الحقول الأساسية (التاق/النوع/الموقع)';
      }

      if (!empty($data['asset_err'])) {
        $this->view('assets/add', $data);
        return;
      }

      if ($this->assetModel->add($data)) {
        flash('asset_msg', 'تمت إضافة الجهاز بنجاح');
        redirect('index.php?page=assets/index');
      } else {
        die('حدث خطأ أثناء الإضافة في قاعدة البيانات');
      }
      return;
    }

    $data = [
      'asset_tag'   => '',
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

  public function edit($id = null)
  {
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
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

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
        flash('asset_msg', 'تم تحديث بيانات الجهاز');
        redirect('index.php?page=assets/index');
      } else {
        die('خطأ في التعديل');
      }
      return;
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
}
