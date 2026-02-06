<?php

class UsersController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = $this->model('User');
        // لا تضع حمايات تعتمد على $_GET['page'] هنا (تسبب مشاكل).
        // كل دالة تحمي نفسها بـ requirePermission/requireLogin.
    }

    // تسجيل الدخول
    public function login()
{
    // إذا المستخدم مسجل دخول، ودّه للداشبورد
    if (isLoggedIn()) {
        redirect('index.php?page=dashboard/index');
        return;
    }

    // GET request → عرض صفحة اللوقن
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $data = [
            'email' => '',
            'password' => '',
            'email_err' => '',
            'password_err' => ''
        ];

        $this->view('users/login', $data);
        return;
    }

    // POST request → معالجة تسجيل الدخول
    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

    $data = [
        'email' => trim($_POST['email'] ?? ''),
        'password' => trim($_POST['password'] ?? ''),
        'email_err' => '',
        'password_err' => ''
    ];

    // تحقق من المدخلات
    if (empty($data['email'])) {
        $data['email_err'] = 'الرجاء إدخال البريد الإلكتروني';
    }

    if (empty($data['password'])) {
        $data['password_err'] = 'الرجاء إدخال كلمة المرور';
    }

    // إذا فيه أخطاء → رجّع صفحة اللوقن
    if (!empty($data['email_err']) || !empty($data['password_err'])) {
        $this->view('users/login', $data);
        return;
    }

    // محاولة تسجيل الدخول
    $loggedInUser = $this->userModel->login($data['email'], $data['password']);

    if ($loggedInUser) {
        $this->createUserSession($loggedInUser);
        redirect('index.php?page=dashboard/index');
    } else {
        $data['password_err'] = 'بيانات الدخول غير صحيحة';
        $this->view('users/login', $data);
    }
}
private function createUserSession($user): void
{
    $_SESSION['user_id']    = $user->id;
    $_SESSION['user_email'] = $user->email;
    $_SESSION['user_name']  = $user->name ?? $user->email;
    $_SESSION['user_role']  = $user->role ?? 'user';
}

    // عرض المستخدمين
    public function index()
    {
        requirePermission('users.view', 'index.php?page=dashboard');

        $role = normalizeRole(currentRole());
        $users = [];

        if ($role === 'super_admin') {
            $users = $this->userModel->getUsers();
        } elseif ($role === 'manager') {
            $users = $this->userModel->getUsersByManager((int)($_SESSION['user_id'] ?? 0));
        } else {
            flash('access_denied', 'ليس لديك صلاحية لعرض المستخدمين', 'alert alert-danger');
            redirect('index.php?page=dashboard');
            exit;
        }

        $this->view('users/index', ['users' => $users]);
        exit;
    }

    // إضافة مستخدم (سوبر أدمن فقط)
    public function add()
    {
        requirePermission('users.manage', 'index.php?page=dashboard');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $data = [
                'username'   => trim($_POST['username'] ?? ''),
                'name'       => trim($_POST['name'] ?? ''),
                'email'      => trim($_POST['email'] ?? ''),
                'password'   => trim($_POST['password'] ?? ''),
                'role'       => normalizeRole($_POST['role'] ?? 'user'),
                'manager_id' => null,

                'username_err' => '',
                'name_err'     => '',
                'email_err'    => '',
                'password_err' => '',
                'role_err'     => ''
            ];

            // توليد username تلقائياً لو فاضي
            if ($data['username'] === '' && $data['email'] !== '') {
                $base = strstr($data['email'], '@', true);
                $base = is_string($base) ? $base : '';
                $base = preg_replace('/[^a-zA-Z0-9._-]/', '', $base);
                $data['username'] = $base ?: ('user_' . time());
            }

            if ($data['username'] === '') $data['username_err'] = 'اسم المستخدم مطلوب';
            if ($data['name'] === '')     $data['name_err']     = 'الرجاء إدخال الاسم';
            if ($data['email'] === '')    $data['email_err']    = 'الرجاء إدخال البريد الإلكتروني';
            if ($data['password'] === '') $data['password_err'] = 'الرجاء إدخال كلمة المرور';

            // تحقق الدور حسب DB
            $allowedRoles = ['user', 'manager', 'super_admin'];
            if (!in_array($data['role'], $allowedRoles, true)) {
                $data['role'] = 'user';
            }

            // تحقق تكرار البريد
            if ($data['email'] !== '' && $this->userModel->findUserByEmail($data['email'])) {
                $data['email_err'] = 'هذا البريد مسجل مسبقاً';
            }

            if ($data['password'] !== '' && strlen($data['password']) < 6) {
                $data['password_err'] = 'كلمة المرور لازم تكون 6 أحرف على الأقل';
            }

            if ($data['username_err'] === '' && $data['name_err'] === '' && $data['email_err'] === '' && $data['password_err'] === '') {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

                if ($this->userModel->register($data)) {
                    flash('register_success', 'تم إضافة المستخدم بنجاح');
                    redirect('index.php?page=users/index');
                    exit;
                }

                die('حدث خطأ أثناء الاتصال بقاعدة البيانات');
            }

            $this->view('users/add', $data);
            exit;
        }

        // GET
        $data = [
            'username' => '',
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => 'user',
            'username_err' => '',
            'name_err' => '',
            'email_err' => '',
            'password_err' => '',
            'role_err' => ''
        ];
        $this->view('users/add', $data);
        exit;
    }

    public function profile()
    {
        requireLogin();
        $user = $this->userModel->getUserById((int)($_SESSION['user_id'] ?? 0));
        $this->view('users/profile', ['user' => $user]);
        exit;
    }

    // تعديل المستخدم
    public function edit($id = null)
    {
        requirePermission('users.manage', 'index.php?page=dashboard');

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

        // منع تعديل سوبر أدمن لغير سوبر أدمن
        if (normalizeRole($user->role ?? 'user') === 'super_admin' && !isSuperAdmin()) {
            flash('access_denied', 'لا يمكنك تعديل حساب سوبر أدمن', 'alert alert-danger');
            redirect('index.php?page=users/index');
            exit;
        }

        $isSelf = ((int)($_SESSION['user_id'] ?? 0) === $id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $data = [
                'id'       => $id,
                'username' => trim($_POST['username'] ?? ''),
                'name'     => trim($_POST['name'] ?? ''),
                'email'    => trim($_POST['email'] ?? ''),
                'password' => trim($_POST['password'] ?? ''),
                'role'     => normalizeRole($_POST['role'] ?? ($user->role ?? 'user')),

                'username_err' => '',
                'name_err'     => '',
                'email_err'    => '',
                'password_err' => ''
            ];

            if ($data['username'] === '') $data['username_err'] = 'اسم المستخدم مطلوب';
            if ($data['name'] === '')     $data['name_err']     = 'الاسم مطلوب';
            if ($data['email'] === '')    $data['email_err']    = 'البريد مطلوب';

            // تحقق: الإيميل غير مكرر (استثناء نفس المستخدم)
            if ($data['email_err'] === '' && $this->userModel->emailExistsForOtherUser($data['email'], $id)) {
                $data['email_err'] = 'هذا البريد مسجل لمستخدم آخر';
            }

            // تحقق الدور حسب DB
            $allowedRoles = ['user', 'manager', 'super_admin'];
            if (!in_array($data['role'], $allowedRoles, true)) {
                $data['role'] = 'user';
            }

            // منع تغيير دور نفسك
            if ($isSelf) {
                $data['role'] = $user->role;
            }

            // كلمة المرور اختيارية
            if ($data['password'] !== '' && strlen($data['password']) < 6) {
                $data['password_err'] = 'كلمة المرور لازم تكون 6 أحرف على الأقل';
            }

            if ($data['username_err'] === '' && $data['name_err'] === '' && $data['email_err'] === '' && $data['password_err'] === '') {

                if ($data['password'] !== '') {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                } else {
                    $data['password'] = $user->password;
                }

                if ($this->userModel->update($data)) {

                    if ($isSelf) {
                        $_SESSION['user_name']  = $data['name'];
                        $_SESSION['user_email'] = $data['email'];
                        // الدور ما يتغير فعليًا لأننا منعناه
                    }

                    flash('user_message', 'تم تحديث البيانات بنجاح');
                    redirect('index.php?page=users/index');
                    exit;
                }

                die('حدث خطأ ما');
            }

            $this->view('users/edit', $data);
            exit;
        }

        // GET
        $data = [
            'id'       => $user->id,
            'username' => $user->username ?? '',
            'name'     => $user->name ?? '',
            'email'    => $user->email ?? '',
            'password' => '',
            'role'     => $user->role ?? 'user',
            'username_err' => '',
            'name_err' => '',
            'email_err' => '',
            'password_err' => ''
        ];

        $this->view('users/edit', $data);
        exit;
    }

    // تعطيل/تفعيل المستخدم (بدل الحذف)
    public function delete($id = null)
    {
        requirePermission('users.manage', 'index.php?page=dashboard');

        if (empty($id)) {
            $id = $_POST['id'] ?? ($_GET['id'] ?? null);
        }
        $id = (int)$id;

        if (!$id) {
            redirect('index.php?page=users/index');
            exit;
        }

        // لا تعطل نفسك
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

        // منع تعطيل سوبر أدمن لغير سوبر أدمن
        if (normalizeRole($u->role ?? 'user') === 'super_admin' && !isSuperAdmin()) {
            flash('user_message', 'لا يمكنك تعطيل حساب سوبر أدمن', 'alert alert-danger');
            redirect('index.php?page=users/index');
            exit;
        }

        $current = isset($u->is_active) ? (int)$u->is_active : 1;
        $newVal  = ($current === 1) ? 0 : 1;

        if ($this->userModel->setActive($id, $newVal)) {
            flash('user_message', $newVal ? 'تم تفعيل المستخدم' : 'تم تعطيل المستخدم');
            redirect('index.php?page=users/index');
            exit;
        }

        die('حدث خطأ أثناء تحديث الحالة');
    }
}
