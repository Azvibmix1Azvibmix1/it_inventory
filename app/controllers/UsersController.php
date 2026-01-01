<?php
class UsersController extends Controller {
    private $userModel;

    public function __construct(){
        // الحماية: لا يدخل هنا إلا المسجل دخول
        if (!isLoggedIn()) {
            redirect('index.php?page=login');
            exit;
        }

        $this->userModel = $this->model('User');
    }

    // صفحة عرض المستخدمين (الجدول)
    public function index(){
        $users = [];

        // ✅ الحماية بالصلاحيات
        requirePermission('users.manage', 'dashboard');

        if (isSuperAdmin()) {
            $users = $this->userModel->getUsers();
        } elseif (isManager()) {
            $users = $this->userModel->getUsersByManager($_SESSION['user_id']);
        }

        $data = [
            'users' => $users
        ];

        $this->view('users/index', $data);
    }

    // صفحة إضافة مستخدم جديد
    public function add(){
        requirePermission('users.manage', 'dashboard');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'name'       => trim($_POST['name'] ?? ''),
                'email'      => trim($_POST['email'] ?? ''),
                'password'   => trim($_POST['password'] ?? ''),
                'role'       => '',
                'manager_id' => null,
                'name_err'   => '',
                'email_err'  => '',
                'password_err' => ''
            ];

            if (empty($data['email'])) {
                $data['email_err'] = 'الرجاء إدخال البريد الإلكتروني';
            }
            if (empty($data['name'])) {
                $data['name_err'] = 'الرجاء إدخال الاسم';
            }
            if (empty($data['password'])) {
                $data['password_err'] = 'الرجاء إدخال كلمة المرور';
            }

            if ($this->userModel->findUserByEmail($data['email'])) {
                $data['email_err'] = 'هذا البريد مسجل مسبقاً';
            }

            // منطق الرتبة والمدير
            if (isManager()) {
                $data['role']       = 'user';
                $data['manager_id'] = $_SESSION['user_id'];
            } elseif (isSuperAdmin()) {
                $data['role']       = isset($_POST['role']) ? $_POST['role'] : 'user';
                $data['manager_id'] = null;
            } else {
                $data['role']       = 'user';
                $data['manager_id'] = $_SESSION['user_id'];
            }

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
                'name'       => '',
                'email'      => '',
                'password'   => '',
                'role'       => 'user',
                'name_err'   => '',
                'email_err'  => '',
                'password_err' => ''
            ];
            $this->view('users/add', $data);
        }
    }

    // صفحة الملف الشخصي (للـ user الحالي)
    public function profile(){
        $user = $this->userModel->getUserById($_SESSION['user_id']);
        $data = [
            'user' => $user
        ];
        $this->view('users/profile', $data);
    }

    // صفحة تعديل المستخدم
    public function edit($id){
        requirePermission('users.manage', 'dashboard');

        $user = $this->userModel->getUserById($id);

        if (isManager() && $user->manager_id != $_SESSION['user_id']) {
            flash('access_denied', 'لا تملك صلاحية تعديل هذا المستخدم', 'alert alert-danger');
            redirect('index.php?page=users/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'id'       => $id,
                'name'     => trim($_POST['name'] ?? ''),
                'email'    => trim($_POST['email'] ?? ''),
                'password' => trim($_POST['password'] ?? ''),
                'role'     => isset($_POST['role']) ? $_POST['role'] : $user->role,
                'name_err'   => '',
                'email_err'  => '',
                'password_err' => ''
            ];

            if (empty($data['email'])) {
                $data['email_err'] = 'البريد مطلوب';
            }
            if (empty($data['name'])) {
                $data['name_err'] = 'الاسم مطلوب';
            }

            if (empty($data['email_err']) && empty($data['name_err'])) {
                if (!empty($data['password'])) {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                } else {
                    $data['password'] = $user->password; // الحفاظ على الباسورد القديم
                }

                if($this->userModel->update($data)){

    // ✅ لو كنت تعدّل بياناتك أنت، حدّث الجلسة مباشرة
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
                $_SESSION['user_name']  = $data['name'];
                $_SESSION['user_email'] = $data['email'];
                $_SESSION['user_role']  = $data['role'];  // أهم شيء الصلاحية
                     }

    flash('user_message', 'تم تحديث البيانات بنجاح');
    redirect('index.php?page=users/index');
            } else {
                die('حدث خطأ ما');
            }

            } else {
                $this->view('users/edit', $data);
            }

        } else {
            $data = [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'password' => '',
                'role'     => $user->role,
                'name_err'   => '',
                'email_err'  => '',
                'password_err' => ''
            ];
            $this->view('users/edit', $data);
        }
    }

    // حذف المستخدم
    public function delete($id){
        requirePermission('users.manage', 'dashboard');

        if ($this->userModel->delete($id)) {
            flash('user_message', 'تم حذف المستخدم بنجاح');
            redirect('index.php?page=users/index');
        } else {
            die('حدث خطأ أثناء الحذف');
        }
    }
}
