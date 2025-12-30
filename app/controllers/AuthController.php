<?php

class AuthController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = $this->model('User');
    }

    // ---------- Helpers (Views) ----------
    private function viewLogin($data)
    {
        // يفضّل auth/login لو موجود، وإلا users/login
        if (file_exists('../app/views/auth/login.php')) {
            $this->view('auth/login', $data);
        } else {
            $this->view('users/login', $data);
        }
    }

    private function viewRegister($data)
    {
        // يفضّل users/register (حسب مشروعك الحالي)
        if (file_exists('../app/views/users/register.php')) {
            $this->view('users/register', $data);
        } else {
            // احتياط لو عندك auth/register
            $this->view('auth/register', $data);
        }
    }

    // ---------- Register (Internal Use) ----------
    // ملاحظة: في نظام جامعة/جهة داخلية الأفضل منع التسجيل العام
    public function register()
    {
        // لازم يكون مسجل دخول (أدمن/سوبر أدمن) عشان يضيف مستخدم
        if (!isLoggedIn()) {
            flash('auth_error', 'يجب تسجيل الدخول أولاً');
            redirect('index.php?page=login');
            exit;
        }

        // فقط manager أو super_admin
        if (!isManager() && !isSuperAdmin()) {
            flash('access_denied', 'ليس لديك صلاحية لإضافة مستخدمين', 'alert alert-danger');
            redirect('index.php?page=dashboard/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'username'             => trim($_POST['username'] ?? ''),
                'email'                => trim($_POST['email'] ?? ''),
                'password'             => trim($_POST['password'] ?? ''),
                'confirm_password'     => trim($_POST['confirm_password'] ?? ''),
                'role'                 => 'user',   // افتراضي
                'manager_id'           => null,     // افتراضي

                'name_err'             => '',
                'email_err'            => '',
                'password_err'         => '',
                'confirm_password_err' => ''
            ];

            // اسم
            if (empty($data['username'])) {
                $data['name_err'] = 'الرجاء إدخال الاسم';
            }

            // بريد
            if (empty($data['email'])) {
                $data['email_err'] = 'الرجاء إدخال البريد الإلكتروني';
            } elseif ($this->userModel->findUserByEmail($data['email'])) {
                $data['email_err'] = 'البريد الإلكتروني مسجل مسبقاً';
            }

            // كلمة مرور
            if (empty($data['password'])) {
                $data['password_err'] = 'الرجاء إدخال كلمة المرور';
            } elseif (strlen($data['password']) < 6) {
                $data['password_err'] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
            }

            // تطابق
            if ($data['password'] !== $data['confirm_password']) {
                $data['confirm_password_err'] = 'كلمات المرور غير متطابقة';
            }

            // منطق الرتبة والمدير
            if (isManager()) {
                // المدير يضيف user فقط ويتبع له
                $data['role'] = 'user';
                $data['manager_id'] = $_SESSION['user_id'];
            } elseif (isSuperAdmin()) {
                // السوبر أدمن يحدد الدور (لو موجود في الفورم)
                $data['role'] = isset($_POST['role']) ? trim($_POST['role']) : 'user';
                // يقدر يحدد manager_id (اختياري)
                $data['manager_id'] = isset($_POST['manager_id']) && $_POST['manager_id'] !== ''
                    ? (int) $_POST['manager_id']
                    : null;
            }

            if (
                empty($data['email_err']) &&
                empty($data['name_err']) &&
                empty($data['password_err']) &&
                empty($data['confirm_password_err'])
            ) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

                if ($this->userModel->register($data)) {
                    flash('register_success', 'تم إضافة المستخدم بنجاح');
                    redirect('index.php?page=users/index');
                    exit;
                }

                die('حدث خطأ أثناء التسجيل');
            }

            $this->viewRegister($data);

        } else {
            $data = [
                'username'             => '',
                'email'                => '',
                'password'             => '',
                'confirm_password'     => '',
                'role'                 => 'user',

                'name_err'             => '',
                'email_err'            => '',
                'password_err'         => '',
                'confirm_password_err' => ''
            ];

            $this->viewRegister($data);
        }
    }

    // ---------- Login ----------
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'email'        => trim($_POST['email'] ?? ''),
                'password'     => trim($_POST['password'] ?? ''),
                'email_err'    => '',
                'password_err' => '',
            ];

            if (empty($data['email'])) {
                $data['email_err'] = 'الرجاء إدخال البريد الإلكتروني';
            }

            if (empty($data['password'])) {
                $data['password_err'] = 'الرجاء إدخال كلمة المرور';
            }

            // تحقق المستخدم موجود
            if (empty($data['email_err']) && !$this->userModel->findUserByEmail($data['email'])) {
                $data['email_err'] = 'هذا البريد الإلكتروني غير مسجل';
            }

            if (empty($data['email_err']) && empty($data['password_err'])) {

                $loggedInUser = $this->userModel->login($data['email'], $data['password']);

                if ($loggedInUser) {
                    $this->createUserSession($loggedInUser);
                    return;
                }

                $data['password_err'] = 'كلمة المرور غير صحيحة';
            }

            $this->viewLogin($data);

        } else {
            $data = [
                'email'        => '',
                'password'     => '',
                'email_err'    => '',
                'password_err' => '',
            ];

            $this->viewLogin($data);
        }
    }

    // ---------- Session ----------
    private function createUserSession($user)
    {
        // حماية من Session Fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id']    = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name']  = isset($user->username) ? $user->username : ($user->name ?? '');
        $_SESSION['user_role']  = $user->role;

        // التوجيه للوحة التحكم
        redirect('index.php?page=dashboard/index');
        exit;
    }

    // ---------- Logout ----------
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        redirect('index.php?page=login');
        exit;
    }
}
?>
