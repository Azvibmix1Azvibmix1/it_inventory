<?php

class LocationsController extends Controller
{
  private $locationModel;

  public function __construct()
  {
    $this->locationModel = $this->model('Location');

    if (function_exists('requireLogin')) {
      requireLogin();
    }
  }

  public function index(){

    if (function_exists('requireLocationsAccess')) {
    requireLocationsAccess('index.php?page=dashboard/index');
  }
    // عرض الهيكل لأي مستخدم مسجل دخول
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
    $locations = $this->locationModel->getAll();
  }

  public function add()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect('index.php?page=locations/index');
      return;
    }

    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    // ✅ صلاحية الإضافة:
    // - إذا تابع لموقع أب: لازم صلاحية add على الأب
    // - إذا مستوى أعلى (بدون أب): فقط super_admin/manager
    if ($parentId) {
      if (function_exists('requireLocationPermission')) {
        requireLocationPermission($parentId, 'add', 'index.php?page=locations/index');
      } else {
        // fallback
        if (!in_array(currentRole(), ['superadmin','manager'], true)) {
          flash('access_denied', 'ليس لديك صلاحية لإضافة موقع تابع', 'alert alert-danger');
          redirect('index.php?page=locations/index');
          return;
        }
      }
    } else {
      if (!in_array(currentRole(), ['superadmin','manager'], true)) {
        flash('access_denied', 'إضافة المستوى الأعلى مسموحة للمدير/السوبر أدمن فقط', 'alert alert-danger');
        redirect('index.php?page=locations/index');
        return;
      }
    }

    $data = [
      'name_ar'   => trim($_POST['name_ar'] ?? ''),
      'name_en'   => trim($_POST['name_en'] ?? ''),
      'type'      => trim($_POST['type'] ?? 'Building'),
      'parent_id' => $parentId,
      'name_err'  => '',
    ];

    if (empty($data['name_ar'])) {
      $data['name_err'] = 'الاسم العربي مطلوب';
    }

    if (!empty($data['name_err'])) {
      $data['locations'] = $this->locationModel->getAll();
      $this->view('locations/index', $data);
      return;
    }

    if ($this->locationModel->add($data)) {
      if (method_exists($this->locationModel, 'audit')) {
        $this->locationModel->audit(
          $parentId ?: 0,
          $_SESSION['user_id'] ?? null,
          'add_location',
          json_encode($data, JSON_UNESCAPED_UNICODE)
        );
      }
      flash('location_msg', 'تم إضافة الموقع بنجاح');
      redirect('index.php?page=locations/index');
    } else {
      die('خطأ في قاعدة البيانات أثناء إضافة الموقع');
    }
  }

  public function edit($id = null)
  {
    if (empty($id)) {
      $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    }
    $id = (int)$id;

    if ($id <= 0) {
      flash('location_msg', 'معرّف موقع غير صالح', 'alert alert-danger');
      redirect('index.php?page=locations/index');
      return;
    }

    // ✅ لازم صلاحية تعديل على هذا الموقع (per-location)
    if (function_exists('requireLocationPermission')) {
      requireLocationPermission($id, 'edit', 'index.php?page=locations/index');
    } else {
      // fallback
      if (!in_array(currentRole(), ['superadmin','manager'], true)) {
        flash('access_denied', 'تعديل المواقع مسموح للمدير/السوبر أدمن فقط', 'alert alert-danger');
        redirect('index.php?page=locations/index');
        return;
      }
    }

    $location = $this->locationModel->getLocationById($id);
    if (!$location) {
      flash('location_msg', 'الموقع غير موجود', 'alert alert-danger');
      redirect('index.php?page=locations/index');
      return;
    }

    // POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

      // 1) حفظ بيانات الموقع
      if (isset($_POST['save_location'])) {
        $data = [
          'id'        => $id,
          'name_ar'   => trim($_POST['name_ar'] ?? ''),
          'name_en'   => trim($_POST['name_en'] ?? ''),
          'type'      => trim($_POST['type'] ?? 'Other'),
          'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
        ];

        // ممنوع يخلي نفسه أب لنفسه
        if (!empty($data['parent_id']) && (int)$data['parent_id'] === $id) {
          $data['parent_id'] = null;
        }

        if (empty($data['name_ar'])) {
          flash('location_msg', 'الاسم العربي مطلوب', 'alert alert-danger');
          redirect("index.php?page=locations/edit&id=$id");
          return;
        }

        if ($this->locationModel->update($data)) {
          if (method_exists($this->locationModel, 'audit')) {
            $this->locationModel->audit($id, $_SESSION['user_id'] ?? null, 'update_location', json_encode($data, JSON_UNESCAPED_UNICODE));
          }
          flash('location_msg', 'تم تحديث بيانات الموقع بنجاح');
        } else {
          flash('location_msg', 'حدث خطأ أثناء تحديث بيانات الموقع', 'alert alert-danger');
        }

        redirect("index.php?page=locations/edit&id=$id");
        return;
      }

      // 2) حفظ الصلاحيات
      if (isset($_POST['save_permissions'])) {
        // لازم manage عشان يغير الصلاحيات
        if (function_exists('requireLocationPermission')) {
          requireLocationPermission($id, 'manage', "index.php?page=locations/edit&id=$id");
        } else {
          if (currentRole() !== 'superadmin') {
            flash('access_denied', 'تعديل صلاحيات الموقع للسوبر أدمن فقط', 'alert alert-danger');
            redirect("index.php?page=locations/edit&id=$id");
            return;
          }
        }

        // أدوار نظامك حسب جدول users
        $roles = ['manager', 'user'];

        foreach ($roles as $role) {
          $perms = [
            'can_manage'       => isset($_POST["role_{$role}_manage"]) ? 1 : 0,
            'can_add_children' => isset($_POST["role_{$role}_add"]) ? 1 : 0,
            'can_edit'         => isset($_POST["role_{$role}_edit"]) ? 1 : 0,
            'can_delete'       => isset($_POST["role_{$role}_delete"]) ? 1 : 0,
          ];
          $this->locationModel->saveRolePerms($id, $role, $perms);
        }

        // صلاحية لمستخدم محدد (اختياري)
        $targetUser = !empty($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
        if ($targetUser > 0) {
          $uPerms = [
            'can_manage'       => isset($_POST["user_manage"]) ? 1 : 0,
            'can_add_children' => isset($_POST["user_add"]) ? 1 : 0,
            'can_edit'         => isset($_POST["user_edit"]) ? 1 : 0,
            'can_delete'       => isset($_POST["user_delete"]) ? 1 : 0,
          ];
          $this->locationModel->addOrUpdateUserPerm($id, $targetUser, $uPerms);
        }

        // حذف صلاحية مستخدم
        if (!empty($_POST['remove_user_id'])) {
          $this->locationModel->removeUserPerm($id, (int)$_POST['remove_user_id']);
        }

        if (method_exists($this->locationModel, 'audit')) {
          $this->locationModel->audit($id, $_SESSION['user_id'] ?? null, 'update_permissions', 'Updated location permissions');
        }

        flash('location_msg', 'تم حفظ إعدادات الصلاحيات بنجاح');
        redirect("index.php?page=locations/edit&id=$id");
        return;
      }

      // أي POST غير معروف
      redirect("index.php?page=locations/edit&id=$id");
      return;
    }

    // GET: بيانات الصفحة
    $locations = $this->locationModel->getAll();

    $children  = method_exists($this->locationModel, 'getChildren') ? $this->locationModel->getChildren($id) : [];
    $rolePerms = method_exists($this->locationModel, 'getRolePerms') ? $this->locationModel->getRolePerms($id) : [];
    $userPerms = method_exists($this->locationModel, 'getUserPerms') ? $this->locationModel->getUserPerms($id) : [];
    $users     = method_exists($this->locationModel, 'getUsersLite') ? $this->locationModel->getUsersLite() : [];
    $audit     = method_exists($this->locationModel, 'getAudit') ? $this->locationModel->getAudit($id, 20) : [];

    $data = [
      'id'        => $location->id,
      'name_ar'   => $location->name_ar,
      'name_en'   => $location->name_en,
      'type'      => $location->type,
      'parent_id' => $location->parent_id,

      'locations' => $locations,

      'children'  => $children,
      'rolePerms' => $rolePerms,
      'userPerms' => $userPerms,
      'users'     => $users,
      'audit'     => $audit,
    ];

    $this->view('locations/edit', $data);
  }

  public function delete($id = null)
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      flash('location_msg', 'طريقة طلب غير صحيحة للحذف', 'alert alert-danger');
      redirect('index.php?page=locations/index');
      return;
    }

    if (empty($id)) {
      $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    }
    $id = (int)$id;

    if ($id <= 0) {
      flash('location_msg', 'معرّف موقع غير صالح', 'alert alert-danger');
      redirect('index.php?page=locations/index');
      return;
    }

    // ✅ صلاحية حذف per-location
    if (function_exists('requireLocationPermission')) {
      requireLocationPermission($id, 'delete', 'index.php?page=locations/index');
    } else {
      if (!in_array(currentRole(), ['superadmin','manager'], true)) {
        flash('access_denied', 'حذف المواقع مسموح للمدير/السوبر أدمن فقط', 'alert alert-danger');
        redirect('index.php?page=locations/index');
        return;
      }
    }

    if ($this->locationModel->delete($id)) {
      if (method_exists($this->locationModel, 'audit')) {
        $this->locationModel->audit($id, $_SESSION['user_id'] ?? null, 'delete_location', 'Deleted');
      }
      flash('location_msg', 'تم حذف الموقع');
    } else {
      flash('location_msg', 'فشل الحذف (قد يكون مرتبطًا بعناصر أخرى)', 'alert alert-danger');
    }

    redirect('index.php?page=locations/index');
  }
}
