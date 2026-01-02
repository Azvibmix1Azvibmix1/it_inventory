<?php

class LocationsController extends Controller
{
    private $locationModel;

    public function __construct()
    {
        $this->locationModel = $this->model('Location');

        // حماية عامة: لازم تسجيل دخول
        if (function_exists('requireLogin')) {
            requireLogin();
        }
    }

    /**
     * صفحة عرض الهيكل + نموذج إضافة موقع جديد
     */
    public function index()
    {
        // أي مستخدم مسجل دخول يقدر يشوف (تقدر تشددها لو تبغى)
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

    /**
     * إضافة موقع جديد
     * - يسمح: superadmin / admin / manager
     * - إذا كان parent_id موجود و عندك requireLocationPermission: يتحقق من صلاحية الإضافة على الأب
     */
    public function add()
    {
        // صلاحية عامة (Roles)
        $this->requireRoles(['superadmin', 'super_admin', 'admin', 'manager'], 'إضافة المواقع مسموحة للإدارة فقط');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=locations/index');
            return;
        }

        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        // صلاحية على مستوى موقع الأب (إن كانت الدالة موجودة عندك)
        if ($parentId && function_exists('requireLocationPermission')) {
            requireLocationPermission($parentId, 'add', 'index.php?page=locations/index');
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
            flash('location_msg', 'تم إضافة الموقع بنجاح');
            redirect('index.php?page=locations/index');
        } else {
            die('خطأ في قاعدة البيانات أثناء إضافة الموقع');
        }
    }

    /**
     * تعديل موقع
     * يدعم:
     * - حفظ بيانات الموقع (save_location) أو POST عادي
     * - حفظ الصلاحيات (save_permissions) (إذا عندك دوال الصلاحيات في المودل)
     */
    public function edit($id = null)
    {
        // جلب id
        if (empty($id)) {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        }
        $id = (int)$id;

        if ($id <= 0) {
            flash('location_msg', 'معرّف موقع غير صالح', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            return;
        }

        // صلاحية تعديل على مستوى الموقع (إن وجدت)، وإلا صلاحيات عامة
        if (function_exists('requireLocationPermission')) {
            requireLocationPermission($id, 'edit', 'index.php?page=locations/index');
        } else {
            $this->requireRoles(['superadmin', 'super_admin', 'admin', 'manager'], 'تعديل المواقع مسموح للإدارة فقط');
        }

        // GET: بيانات الموقع
        $location = $this->locationModel->getLocationById($id);
        if (!$location) {
            flash('location_msg', 'الموقع غير موجود', 'alert alert-danger');
            redirect('index.php?page=locations/index');
            return;
        }

        // POST handlers
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            // 1) حفظ الصلاحيات
            if (isset($_POST['save_permissions'])) {
                // لازم manage على هذا الموقع
                if (function_exists('requireLocationPermission')) {
                    requireLocationPermission($id, 'manage', "index.php?page=locations/edit&id=$id");
                } else {
                    // بدون نظام per-location: نخليها superadmin/admin فقط
                    $this->requireRoles(['superadmin', 'super_admin', 'admin'], 'تعديل صلاحيات الموقع للسوبر أدمن/الأدمن فقط');
                }

                // إذا المودل ما فيه دوال الصلاحيات، أعطِ تنبيه بدل ما يطيح
                if (
                    !method_exists($this->locationModel, 'saveRolePerms') ||
                    !method_exists($this->locationModel, 'addOrUpdateUserPerm') ||
                    !method_exists($this->locationModel, 'removeUserPerm')
                ) {
                    flash('location_msg', 'نظام صلاحيات المواقع غير مُفعّل بعد (حدّث Location model + الجداول)', 'alert alert-warning');
                    redirect("index.php?page=locations/edit&id=$id");
                    return;
                }

                // صلاحيات حسب الدور (عدّل الأدوار حسب مشروعك)
                $roles = ['admin', 'manager', 'user'];
                foreach ($roles as $role) {
                    $perms = [
                        'can_manage'       => isset($_POST["role_{$role}_manage"]) ? 1 : 0,
                        'can_add_children' => isset($_POST["role_{$role}_add"]) ? 1 : 0,
                        'can_edit'         => isset($_POST["role_{$role}_edit"]) ? 1 : 0,
                        'can_delete'       => isset($_POST["role_{$role}_delete"]) ? 1 : 0,
                    ];
                    $this->locationModel->saveRolePerms($id, $role, $perms);
                }

                // إضافة/تحديث صلاحية لمستخدم معيّن (اختياري)
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

                // سجل (اختياري)
                if (method_exists($this->locationModel, 'audit')) {
                    $uid = $_SESSION['user_id'] ?? null;
                    $this->locationModel->audit($id, $uid, 'update_permissions', 'Updated location permissions');
                }

                flash('location_msg', 'تم حفظ إعدادات الصلاحيات بنجاح');
                redirect("index.php?page=locations/edit&id=$id");
                return;
            }

            // 2) حفظ بيانات الموقع (save_location) أو POST قديم
            // (لو ما أرسل save_location نخليها برضو تحديث بيانات)
            if (isset($_POST['save_location']) || true) {
                $data = [
                    'id'        => $id,
                    'name_ar'   => trim($_POST['name_ar'] ?? ''),
                    'name_en'   => trim($_POST['name_en'] ?? ''),
                    'type'      => trim($_POST['type'] ?? ''),
                    'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                ];

                // منع جعل الموقع أب لنفسه
                if (!empty($data['parent_id']) && (int)$data['parent_id'] === $id) {
                    $data['parent_id'] = null;
                }

                if (empty($data['name_ar'])) {
                    flash('location_msg', 'الاسم العربي مطلوب', 'alert alert-danger');
                    redirect("index.php?page=locations/edit&id=$id");
                    return;
                }

                if ($this->locationModel->update($data)) {
                    // سجل (اختياري)
                    if (method_exists($this->locationModel, 'audit')) {
                        $uid = $_SESSION['user_id'] ?? null;
                        $this->locationModel->audit(
                            $id,
                            $uid,
                            'update_location',
                            json_encode($data, JSON_UNESCAPED_UNICODE)
                        );
                    }

                    flash('location_msg', 'تم تحديث بيانات الموقع بنجاح');
                    redirect("index.php?page=locations/edit&id=$id");
                } else {
                    flash('location_msg', 'حدث خطأ أثناء تحديث بيانات الموقع', 'alert alert-danger');
                    redirect("index.php?page=locations/edit&id=$id");
                }
                return;
            }
        }

        // GET: تجهيز بيانات الصفحة
        $locations = $this->locationModel->getAll();

        // إضافات صفحة edit المتقدمة (لو المودل يدعمها)
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

            // للصفحة المتقدمة
            'children'  => $children,
            'rolePerms' => $rolePerms,
            'userPerms' => $userPerms,
            'users'     => $users,
            'audit'     => $audit,
        ];

        $this->view('locations/edit', $data);
    }

    /**
     * حذف موقع (POST فقط)
     */
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

        // صلاحية حذف على مستوى الموقع (إن وجدت)، وإلا صلاحيات عامة
        if (function_exists('requireLocationPermission')) {
            requireLocationPermission($id, 'delete', 'index.php?page=locations/index');
        } else {
            $this->requireRoles(['superadmin', 'super_admin', 'admin', 'manager'], 'حذف المواقع مسموح للإدارة فقط');
        }

        if ($this->locationModel->delete($id)) {
            // سجل (اختياري)
            if (method_exists($this->locationModel, 'audit')) {
                $uid = $_SESSION['user_id'] ?? null;
                $this->locationModel->audit($id, $uid, 'delete_location', 'Deleted');
            }

            flash('location_msg', 'تم حذف الموقع');
        } else {
            flash('location_msg', 'فشل الحذف (قد يكون مرتبطًا بعناصر أخرى)', 'alert alert-danger');
        }

        redirect('index.php?page=locations/index');
    }

    /**
     * Helper: تحقق أدوار بسرعة (بدون ما نعتمد على requirePermission map)
     */
    private function requireRoles(array $roles, $msg = 'ليس لديك صلاحية')
    {
        $role = $_SESSION['user_role'] ?? 'user';

        // تطبيع role
        if ($role === 'super_admin') $role = 'superadmin';

        // تطبيع قائمة roles
        $normalized = [];
        foreach ($roles as $r) {
            $normalized[] = ($r === 'super_admin') ? 'superadmin' : $r;
        }

        if (!in_array($role, $normalized, true)) {
            flash('access_denied', $msg, 'alert alert-danger');
            redirect('index.php?page=locations/index');
            exit;
        }
    }
}
