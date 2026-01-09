<?php
class UsersController extends Controller {
    private $userModel;

    public function __construct() {
  $this->userModel = $this->model('User');

  // حماية عامة: إذا رايح لأي شيء إداري
  $page = strtolower(trim($_GET['page'] ?? ''));

  // عدّل أسماء الأكشنز حسب اللي عندك
  if (strpos($page, 'users/index') === 0
   || strpos($page, 'users/register') === 0
   || strpos($page, 'users/edit') === 0
   || strpos($page, 'users/delete') === 0) {
    requirePermission('users.manage', 'index.php?page=dashboard');

  }
}


    // عرض المستخدمين
    public function index(){
        $users = [];

        // ✅ الحماية بالصلاحيات (تأكد requirePermission موجودة في session_helper)
        requirePermission('users.view', 'index.php?page=dashboard');

        $role = currentRole();

        if ($role === 'superadmin' || $role === 'admin') {
        $users = $this->userModel->getUsers();
        } elseif ($role === 'manager') {
        $users = $this->userModel->getUsersByManager((int)$_SESSION['user_id']);
        } else {
        flash('access_denied', 'ليس لديك صلاحية لعرض المستخدمين', 'alert alert-danger');
        redirect('index.php?page=dashboard');
        exit;
        }

        $data = ['users' => $users];
        $this->view('users/index', $data);
          requirePermission('users.manage', 'index.php?page=dashboard');


    }

    // إضافة مستخدم
    public function add(){
        requirePermission('users.manage', 'index.php?page=dashboard');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            

            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => trim($_POST['password'] ?? ''),
                'role' => '',
                'manager_id' => null,
                'name_err' => '',
                'email_err' => '',
                'password_err' => ''
            ];

            if (empty($data['email'])) $data['email_err'] = 'الرجاء إدخال البريد الإلكتروني';
            if (empty($data['name'])) $data['name_err'] = 'الرجاء إدخال الاسم';
            if (empty($data['password'])) $data['password_err'] = 'الرجاء إدخال كلمة المرور';

            if (!empty($data['email']) && $this->userModel->findUserByEmail($data['email'])) {
                $data['email_err'] = 'هذا البريد مسجل مسبقاً';
            }

            // منطق الرتبة والمدير
            // users.manage = superadmin (افتراضيًا)
            $data['role'] = $_POST['role'] ?? 'user';
            $data['manager_id'] = null;

            if (empty($data['email_err']) && empty($data['name_err']) && empty($data['password_err'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

                if ($this->userModel->register($data)) {
                    flash('register_success', 'تم إضافة المستخدم بنجاح');
                    redirect('index.php?page=users/index');
                } else {
                    die('حدث خطأ أثناء الاتصال بقاعدة البيانات');
                }
            } else {
                $this->view('users/add', $data);
            }
        } else {
            $data = [
                'name' => '',
                'email' => '',
                'password' => '',
                'role' => 'user',
                'name_err' => '',
                'email_err' => '',
                'password_err' => ''
            ];
            $this->view('users/add', $data);
        }

        $data['role'] = $_POST['role'] ?? 'user';

// احتياط أمان: سوبر أدمن فقط يحدد أدوار عالية
if (!isSuperAdmin() && $data['role'] !== 'user') {
  $data['role'] = 'user';
}

    }

    public function profile(){
        requireLogin();
        $user = $this->userModel->getUserById($_SESSION['user_id']);
        $data = ['user' => $user];
        $this->view('users/profile', $data);
    }

    // ✅ تعديل المستخدم (بدون إلزام باراميتر)
    public function edit($id = null)
{
    // إدارة المستخدمين: سوبر أدمن فقط (حسب خريطة الصلاحيات)
    requirePermission('users.manage', 'index.php?page=dashboard');

    // اقرأ id من GET/POST لو ما جاء باراميتر
    if (empty($id)) {
        $id = $_GET['id'] ?? ($_POST['id'] ?? null);
    }
    $id = (int)$id;

    if (!$id) {
        redirect('index.php?page=users/index');
        exit;
    }

    $user = $this->userModel->getUserById($id);
    if (!$user) {
        redirect('index.php?page=users/index');
        exit;
    }

    // منع تعديل حساب سوبر أدمن لغير سوبر أدمن
    if (normalizeRole($user->role ?? 'user') === 'superadmin' && !isSuperAdmin()) {
        flash('access_denied', 'لا يمكنك تعديل حساب سوبر أدمن', 'alert alert-danger');
        redirect('index.php?page=users/index');
        exit;
    }

    // (اختياري/للمستقبل) لو فتحت users.manage للمدير لاحقًا:
    /*
    if (currentRole() === 'manager' && (int)($user->manager_id ?? 0) !== (int)$_SESSION['user_id']) {
        flash('access_denied', 'لا تملك صلاحية تعديل هذا المستخدم', 'alert alert-danger');
        redirect('index.php?page=users/index');
        exit;
    }
    */

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $data = [
            'id'       => $id,
            'name'     => trim($_POST['name'] ?? ''),
            'email'    => trim($_POST['email'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'role'     => $_POST['role'] ?? $user->role,

            'name_err'     => '',
            'email_err'    => '',
            'password_err' => ''
        ];

        // 1) تحقق: الإيميل مطلوب
        if (empty($data['email'])) {
            $data['email_err'] = 'البريد مطلوب';
        }

        // 2) تحقق: الاسم مطلوب
        if (empty($data['name'])) {
            $data['name_err'] = 'الاسم مطلوب';
        }

        // 3) تحقق: الإيميل غير مكرر (استثناء نفس المستخدم)
        if (empty($data['email_err']) && $this->userModel->emailExistsForOtherUser($data['email'], $id)) {
            $data['email_err'] = 'هذا البريد مسجل لمستخدم آخر';
        }

        // 4) منع تغيير دور نفسك (احترافي)
        if ((int)$_SESSION['user_id'] === $id) {
            $data['role'] = $user->role;
        }

        // 5) (اختياري) تحقق كلمة المرور إذا كتبها
        // إذا تبغى شرط حد أدنى:
        // if (!empty($data['password']) && strlen($data['password']) < 6) {
        //     $data['password_err'] = 'كلمة المرور لازم تكون 6 أحرف على الأقل';
        // }

        if (empty($data['name_err']) && empty($data['email_err']) && empty($data['password_err'])) {

            // لو المستخدم كتب كلمة مرور جديدة
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                // حافظ على كلمة المرور القديمة (موجودة في $user)
                $data['password'] = $user->password;
            }

            if ($this->userModel->update($data)) {

                // لو عدّل نفسه: حدّث السيشن (بدون تغيير الدور)
                if ((int)$_SESSION['user_id'] === $id) {
                    $_SESSION['user_name']  = $data['name'];
                    $_SESSION['user_email'] = $data['email'];
                    // $_SESSION['user_role']  = $data['role']; // لا داعي (ومنعناه أصلاً)
                }

                flash('user_message', 'تم تحديث البيانات بنجاح');
                redirect('index.php?page=users/index');
                exit;
            } else {
                die('حدث خطأ ما');
            }
        } else {
            $this->view('users/edit', $data);
            exit;
        }
    }

    // GET request: عرض النموذج
    $data = [
        'id'       => $user->id,
        'name'     => $user->name,
        'email'    => $user->email,
        'password' => '',
        'role'     => $user->role,

        'name_err'     => '',
        'email_err'    => '',
        'password_err' => ''
    ];

    $this->view('users/edit', $data);
    exit;
}


    // ✅ حذف المستخدم (بدون إلزام باراميتر)
   public function delete($id = null)
{
    requirePermission('users.manage', 'index.php?page=dashboard');

    // اقرأ id من GET/POST لو ما جاء باراميتر
    if (empty($id)) {
        $id = $_GET['id'] ?? ($_POST['id'] ?? null);
    }
    $id = (int)$id;

    if (!$id) {
        redirect('index.php?page=users/index');
        exit;
    }

    // منع تعطيل نفسك
    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
        flash('user_message', 'لا يمكنك تعطيل حسابك', 'alert alert-warning');
        redirect('index.php?page=users/index');
        exit;
    }

    $u = $this->userModel->getUserById($id);
    if (!$u) {
        redirect('index.php?page=users/index');
        exit;
    }

    // منع تعطيل السوبر أدمن إلا بسوبر أدمن (أنت أصلاً سوبر هنا، بس احتياط)
    if (normalizeRole($u->role ?? 'user') === 'superadmin' && !isSuperAdmin()) {
        flash('user_message', 'لا يمكنك تعطيل حساب سوبر أدمن', 'alert alert-danger');
        redirect('index.php?page=users/index');
        exit;
    }

    // Toggle
    $current = isset($u->is_active) ? (int)$u->is_active : 1;
    $newVal  = ($current === 1) ? 0 : 1;

    if ($this->userModel->setActive($id, $newVal)) {
        flash('user_message', $newVal ? 'تم تفعيل المستخدم' : 'تم تعطيل المستخدم');
        redirect('index.php?page=users/index');
        exit;
    }

    die('حدث خطأ ما');
}

}
