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
        // --- تعريف المتغير بقيمة فارغة مبدئياً ---
        $users = [];

        // ✅ الحماية بالصلاحيات بدل isUser فقط
        // أي شخص لا يملك users.manage يرجع للداشبورد
        requirePermission('users.manage', 'dashboard');

        // 1) إذا كان سوبر أدمن -> هات كل المستخدمين
        if (isSuperAdmin()) {
            $users = $this->userModel->getUsers();
        }
        // 2) إذا كان مدير قسم -> هات موظفيه فقط (التابعين له)
        elseif (isManager()) {
            $users = $this->userModel->getUsersByManager($_SESSION['user_id']);
        }

        $data = [
            'users' => $users
        ];

        $this->view('users/index', $data);
    }

    // صفحة إضافة مستخدم جديد
    public function add(){
        // ✅ الحماية: فقط من يملك صلاحية إدارة المستخدمين
        requirePermission('users.manage', 'dashboard');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // تعقيم المدخلات
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'name' => trim($_POST['name']), // تأكد أن الحقل في الـ View اسمه name
                'email' => trim($_POST['email']),
                'password' => trim($_POST['password']),
                'role' => '',        // سيتم تحديده برمجياً بالأسفل
                'manager_id' => null, // سيتم تحديده برمجياً بالأسفل
                'name_err' => '',
                'email_err' => '',
                'password_err' => ''
            ];

            // التحقق من البيانات
            if (empty($data['email'])) {
                $data['email_err'] = 'الرجاء إدخال البريد الإلكتروني';
            }
            if (empty($data['name'])) {
                $data['name_err'] = 'الرجاء إدخال الاسم';
            }
            if (empty($data['password'])) {
                $data['password_err'] = 'الرجاء إدخال كلمة المرور';
            }

            // التحقق من عدم تكرار البريد
            if ($this->userModel->findUserByEmail($data['email'])) {
                $data['email_err'] = 'هذا البريد مسجل مسبقاً';
            }

            // --- منطق تحديد الرتبة والمدير (Hierarchy Logic) ---
            if (isManager()) {
                // إذا كان المضيف "مدير قسم":
                // 1. الموظف الجديد يكون "user" إجبارياً
                $data['role'] = 'user';
                // 2. مديره هو المدير الحالي المسجل دخول
                $data['manager_id'] = $_SESSION['user_id'];
            } elseif (isSuperAdmin()) {
                // إذا كان المضيف "سوبر أدمن":
                // يأخذ الرتبة من الفورم (إن وجدت)، وإلا user
                $data['role'] = isset($_POST['role']) ? $_POST['role'] : 'user';
                // المدير يكون NULL (مثلاً مدير عام أو مدير قسم أعلى)
                $data['manager_id'] = null;
            } else {
                // في حال كان الدور شيء آخر لكن معه users.manage (احتياط)
                $data['role'] = 'user';
                $data['manager_id'] = $_SESSION['user_id'];
            }

            // إذا لم تكن هناك أخطاء
            if (empty($data['email_err']) && empty($data['name_err']) && empty($data['password_err'])) {

                // تشفير كلمة المرور
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

                // التنفيذ عبر الموديل
                if ($this->userModel->register($data)) {
                    flash('register_success', 'تم إضافة المستخدم بنجاح');
                    redirect('index.php?page=users/index');
                } else {
                    die('حدث خطأ أثناء الاتصال بقاعدة البيانات');
                }
            } else {
                // عرض الأخطاء
                $this->view('users/add', $data);
            }

        } else {
            // تحميل النموذج فارغاً (GET Request)
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

    // صفحة الملف الشخصي (مفتوحة لأي مستخدم مسجل)
    public function profile(){
        $user = $this->userModel->getUserById($_SESSION['user_id']);
        $data = [
            'user' => $user
        ];
        $this->view('users/profile', $data);
    }

    // صفحة تعديل المستخدم
    public function edit($id){
        // ✅ الحماية: فقط من يملك صلاحية إدارة المستخدمين
        requirePermission('users.manage', 'dashboard');

        // جلب بيانات المستخدم المراد تعديله
        $user = $this->userModel->getUserById($id);

        // حماية إضافية: إذا كان "مدير قسم"، ممنوع يعدل مستخدم لا يتبعه
        if (isManager() && $user->manager_id != $_SESSION['user_id']) {
            flash('access_denied', 'لا تملك صلاحية تعديل هذا المستخدم', 'alert alert-danger');
            redirect('index.php?page=users/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'id' => $id,
                'name' => trim($_POST['name']),
                'email' => trim($_POST['email']),
                'password' => trim($_POST['password']),
                'role' => isset($_POST['role']) ? $_POST['role'] : $user->role,
                'name_err' => '',
                'email_err' => '',
                'password_err' => ''
            ];

            // التحقق
            if (empty($data['email'])) {
                $data['email_err'] = 'البريد مطلوب';
            }
            if (empty($data['name'])) {
                $data['name_err'] = 'الاسم مطلوب';
            }

            if (empty($data['email_err']) && empty($data['name_err'])) {
                // تشفير الباسورد الجديد إذا وجد
                if (!empty($data['password'])) {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                } else {
                    // لو ما دخل كلمة مرور جديدة نحافظ على القديمة
                    $data['password'] = $user->password;
                }

                if ($this->userModel->update($data)) {
                    flash('user_message', 'تم تحديث البيانات بنجاح');
                    redirect('index.php?page=users/index');
                } else {
                    die('حدث خطأ ما');
                }
            } else {
                $this->view('users/edit', $data);
            }

        } else {
            // عرض النموذج مع البيانات الحالية
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password' => '', // نتركه فارغ للأمان
                'role' => $user->role,
                'name_err' => '',
                'email_err' => '',
                'password_err' => ''
            ];
            $this->view('users/edit', $data);
        }
    }

    // حذف المستخدم
    public function delete($id){
        // ✅ الحماية: فقط من يملك صلاحية إدارة المستخدمين
        requirePermission('users.manage', 'dashboard');

        if ($this->userModel->delete($id)) {
            flash('user_message', 'تم حذف المستخدم بنجاح');
            redirect('index.php?page=users/index');
        } else {
            die('حدث خطأ أثناء الحذف');
        }
    }
}
