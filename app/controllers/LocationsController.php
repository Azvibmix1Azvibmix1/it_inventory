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

  public function index()
  {
    // ✅ ما يدخل صفحة المواقع إلا إذا كان (superadmin/manager) أو عنده صلاحية موقع
    if (function_exists('requireLocationsAccess')) {
      requireLocationsAccess('index.php?page=dashboard/index');
    }

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
    // - إذا تابع لموقع أب => لازم صلاحية add على الأب
    // - إذا Root (بدون أب) => فقط manager/superadmin
    if ($parentId) {
      requireLocationPermission($parentId, 'add', 'index.php?page=locations/index');
    } else {
      if (!in_array(currentRole(), ['superadmin', 'manager'], true)) {
        flash('access_denied', 'إضافة مستوى أعلى مسموحة للمدير/السوبر أدمن فقط', 'alert alert-danger');
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
        $this->locationModel->audit($parentId ?: 0, $_SESSION['user_id'] ?? null, 'add_location', json_encode($data, JSON_UNESCAPED_UNICODE));
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

    // ✅ لازم صلاحية edit على الموقع
    requireLocationPermission($id, 'edit', 'index.php?page=locations/index');

    $location = $this->locationModel->getLocationById($id);
    if (!$location) {
      flash('location_msg', 'الموقع غير موجود', 'alert alert-danger');
      redirect('index.php?page=locations/index');
      return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

      // حفظ بيانات الموقع
      if (isset($_POST['save_location'])) {
        $payload = [
          'id'        => $id,
          'name_ar'   => trim($_POST['name_ar'] ?? ''),
          'name_en'   => trim($_POST['name_en'] ?? ''),
          'type'      => trim($_POST['type'] ?? 'Other'),
          'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
        ];

        if (!empty($payload['parent_id']) && (int)$payload['parent_id'] === $id) {
          $payload['parent_id'] = null;
        }

        if (empty($payload['name_ar'])) {
          flash('location_msg', 'الاسم العربي مطلوب', 'alert alert-danger');
          redirect("index.php?page=locations/edit&id=$id");
          return;
        }

        if ($this->locationModel->update($payload)) {
          if (method_exists($this->locationModel, 'audit')) {
            $this->locationModel->audit($id, $_SESSION['user_id'] ?? null, 'update_location', json_encode($payload, JSON_UNESCAPED_UNICODE));
          }
          flash('location_msg', 'تم تحديث بيانات الموقع بنجاح');
        } else {
          flash('location_msg', 'حدث خطأ أثناء تحديث بيانات الموقع', 'alert alert-danger');
        }

        redirect("index.php?page=locations/edit&id=$id");
        return;
      }

      // حفظ الصلاحيات (لازم manage)
      if (isset($_POST['save_permissions'])) {
        requireLocationPermission($id, 'manage', "index.php?page=locations/edit&id=$id");

        // أدوارك حسب DB: manager, user
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

        // صلاحية لمستخدم محدد
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

      redirect("index.php?page=locations/edit&id=$id");
      return;
    }

    // GET بيانات الصفحة (للـ edit.php)
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

    // ✅ لازم delete على الموقع
    requireLocationPermission($id, 'delete', 'index.php?page=locations/index');

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
