<?php
class AuthController extends Controller {
    private $userModel;

    public function __construct(){
        $this->userModel = $this->model('User');
    }

    // --- دالة التسجيل (تسجيل جديد / إضافة موظف) ---
    public function register(){
        // التحقق من نوع الطلب (POST)
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            
            // تنظيف البيانات
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            // تجهيز البيانات
            $data = [
                'username' => trim($_POST['username']),
                'email' => trim($_POST['email']),
                'password' => trim($_POST['password']),
                'confirm_password' => trim($_POST['confirm_password']),
                'role' => isset($_POST['role']) ? trim($_POST['role']) : 'user',
                
                'name_err' => '',
                'email_err' => '',
                'password_err' => '',
                'confirm_password_err' => ''
            ];

            // 1. التحقق من البريد
            if(empty($data['email'])){
                $data['email_err'] = 'الرجاء إدخال البريد الإلكتروني';
            } else {
                if($this->userModel->findUserByEmail($data['email'])){
                    $data['email_err'] = 'البريد الإلكتروني مسجل مسبقاً';
                }
            }

            // 2. التحقق من الاسم
            if(empty($data['username'])){
                $data['name_err'] = 'الرجاء إدخال الاسم';
            }

            // 3. التحقق من كلمة المرور
            if(empty($data['password'])){
                $data['password_err'] = 'الرجاء إدخال كلمة المرور';
            } elseif(strlen($data['password']) < 6){
                $data['password_err'] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
            }

            // 4. تطابق كلمة المرور
            if($data['password'] != $data['confirm_password']){
                $data['confirm_password_err'] = 'كلمات المرور غير متطابقة';
            }

            // إذا لم تكن هناك أخطاء
            if(empty($data['email_err']) && empty($data['name_err']) && empty($data['password_err']) && empty($data['confirm_password_err'])){
                
                // تشفير كلمة المرور
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

                // حفظ المستخدم
                if($this->userModel->register($data)){
                    
                    // ✅ التعديل هنا: التوجيه الذكي
                    if(isset($_SESSION['user_id'])) {
                        // إذا كان المستخدم مسجلاً للدخول (Admin يضيف موظفاً) -> نعود لقائمة المستخدمين
                        redirect('index.php?page=users/index');
                    } else {
                        // إذا كان زائراً جديداً -> نوجهه لصفحة الدخول
                        flash('register_success', 'تم التسجيل بنجاح، يمكنك تسجيل الدخول الآن');
                        redirect('index.php?page=users/login');
                    }

                } else {
                    die('حدث خطأ أثناء التسجيل');
                }

            } else {
                // تحميل الصفحة مع الأخطاء
                $this->view('users/register', $data);
            }

        } else {
            // تحميل الصفحة لأول مرة (GET)
            $data = [
                'username' => '',
                'email' => '',
                'password' => '',
                'confirm_password' => '',
                'role' => 'user',
                'name_err' => '',
                'email_err' => '',
                'password_err' => '',
                'confirm_password_err' => ''
            ];
            $this->view('users/register', $data);
        }
    }

    // --- دالة تسجيل الدخول ---
    public function login(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            $data = [
                'email' => trim($_POST['email']),
                'password' => trim($_POST['password']),
                'email_err' => '',
                'password_err' => '',      
            ];

            if(empty($data['email'])){
                $data['email_err'] = 'الرجاء إدخال البريد الإلكتروني';
            }

            if(empty($data['password'])){
                $data['password_err'] = 'الرجاء إدخال كلمة المرور';
            }

            // التحقق من وجود المستخدم
            if($this->userModel->findUserByEmail($data['email'])){
                // المستخدم موجود
            } else {
                $data['email_err'] = 'هذا البريد الإلكتروني غير مسجل';
            }

            if(empty($data['email_err']) && empty($data['password_err'])){
                // محاولة تسجيل الدخول
                $loggedInUser = $this->userModel->login($data['email'], $data['password']);

                if($loggedInUser){
                    // إنشاء الجلسة
                    $this->createUserSession($loggedInUser);
                } else {
                    $data['password_err'] = 'كلمة المرور غير صحيحة';
                    $this->view('users/login', $data);
                }
            } else {
                $this->view('users/login', $data);
            }

        } else {
            $data = [    
                'email' => '',
                'password' => '',
                'email_err' => '',
                'password_err' => '',        
            ];
            $this->view('users/login', $data);
        }
    }

    // --- إنشاء الجلسة ---
    public function createUserSession($user){
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = isset($user->username) ? $user->username : $user->name; 
        $_SESSION['user_role'] = $user->role;
        
        // التوجيه للوحة التحكم
        redirect('index.php?page=pages/index');
    }

    // --- تسجيل الخروج ---
    public function logout() {
    // 1. بدء الجلسة إذا لم تكن مبدوءة (لضمان القدرة على تدميرها)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 2. تفريغ جميع المتغيرات
    $_SESSION = array();

    // 3. تدمير كعكة الجلسة (Cookie) إذا كانت موجودة
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // 4. تدمير الجلسة نهائياً
    session_destroy();

    // 5. التوجيه الإجباري لصفحة الدخول
    // نستخدم JS و PHP لضمان التحويل حتى لو الهيدر مرسل
    if (!headers_sent()) {
        header("Location: " . URLROOT . "/index.php?page=login");
    } else {
        echo '<script>window.location.href = "' . URLROOT . '/index.php?page=login";</script>';
    }
    exit;
}
}
?>