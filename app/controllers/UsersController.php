<?php
class UsersController extends Controller {
    private $userModel;

    public function __construct(){
        if(!isLoggedIn()){
            redirect('index.php?page=login');
        }
        $this->userModel = $this->model('User');
    }

    // عرض المستخدمين
    public function index(){
        $users = [];

        // ✅ الحماية بالصلاحيات (تأكد requirePermission موجودة في session_helper)
        requirePermission('users.manage', 'index.php?page=dashboard');

        if (isSuperAdmin()) {
            $users = $this->userModel->getUsers();
        } elseif (isManager()) {
            $users = $this->userModel->getUsersByManager($_SESSION['user_id']);
        }

        $data = ['users' => $users];
        $this->view('users/index', $data);
    }

    // إضافة مستخدم
    public function add(){
        requirePermission('users.manage', 'index.php?page=dashboard');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

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
            if (isManager()) {
                $data['role'] = 'user';
                $data['manager_id'] = $_SESSION['user_id'];
            } elseif (isSuperAdmin()) {
                $data['role'] = $_POST['role'] ?? 'user';
                $data['manager_id'] = null;
            } else {
                $data['role'] = 'user';
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
    }

    public function profile(){
        $user = $this->userModel->getUserById($_SESSION['user_id']);
        $data = ['user' => $user];
        $this->view('users/profile', $data);
    }

    // ✅ تعديل المستخدم (بدون إلزام باراميتر)
    public function edit($id = null){
        requirePermission('users.manage', 'index.php?page=dashboard');

        // اقرأ id من GET/POST لو ما جاء باراميتر
        if (empty($id)) {
            $id = $_GET['id'] ?? ($_POST['id'] ?? null);
        }
        $id = (int)$id;

        if (!$id) {
            redirect('index.php?page=users/index');
        }

        $user = $this->userModel->getUserById($id);
        if (!$user) {
            redirect('index.php?page=users/index');
        }

        // حماية للمدير: لا يعدل إلا التابعين له
        if (isManager() && isset($user->manager_id) && $user->manager_id != $_SESSION['user_id']) {
            flash('access_denied', 'لا تملك صلاحية تعديل هذا المستخدم', 'alert alert-danger');
            redirect('index.php?page=users/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'id' => $id,
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => trim($_POST['password'] ?? ''),
                'role' => $_POST['role'] ?? $user->role,
                'name_err' => '',
                'email_err' => '',
                'password_err' => ''
            ];

            if (empty($data['email'])) $data['email_err'] = 'البريد مطلوب';
            if (empty($data['name'])) $data['name_err'] = 'الاسم مطلوب';

            if (empty($data['email_err']) && empty($data['name_err'])) {
                if (!empty($data['password'])) {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                } else {
                    // حافظ على كلمة المرور القديمة
                    $data['password'] = $user->password;
                }

                if ($this->userModel->update($data)) {
                    // ✅ لو تعدّل نفسك حدّث السيشن
                    if ((int)$_SESSION['user_id'] === $id) {
                        $_SESSION['user_name']  = $data['name'];
                        $_SESSION['user_email'] = $data['email'];
                        $_SESSION['user_role']  = $data['role'];
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
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'role' => $user->role,
                'name_err' => '',
                'email_err' => '',
                'password_err' => ''
            ];
            $this->view('users/edit', $data);
        }
    }

    // ✅ حذف المستخدم (بدون إلزام باراميتر)
    public function delete($id = null){
        requirePermission('users.manage', 'index.php?page=dashboard');

        if (empty($id)) {
            $id = $_POST['id'] ?? ($_GET['id'] ?? null);
        }
        $id = (int)$id;

        if (!$id) {
            redirect('index.php?page=users/index');
        }

        // لا تحذف نفسك
        if ($id === (int)$_SESSION['user_id']) {
            flash('user_message', 'لا يمكن حذف حسابك الحالي', 'alert alert-danger');
            redirect('index.php?page=users/index');
        }

        if ($this->userModel->delete($id)) {
            flash('user_message', 'تم حذف المستخدم بنجاح');
            redirect('index.php?page=users/index');
        } else {
            die('حدث خطأ أثناء الحذف');
        }
    }
}
