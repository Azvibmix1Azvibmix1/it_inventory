<?php

class UsersController extends Controller 
{
    private $userModel;

    public function __construct()
    {
        // لا يدخل هنا إلا المسجل دخول
        $this->requireLogin();

        // لا يدخل هنا إلا الأدمن أو السوبر أدمن
        $this->requireRole(['super_admin', 'manager']);

        $this->userModel = $this->model('User');
    }

    // صفحة عرض المستخدمين (الجدول)
    public function index(){
        requirePermission('users.manage', 'dashboard');


        $users = [];

        // إذا كان سوبر أدمن -> هات كل المستخدمين
        if (isSuperAdmin()) {
            $users = $this->userModel->getUsers();
        } 
        // إذا كان مدير قسم -> هات موظفيه فقط (التابعين له)
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

        requirePermission('users.manage', 'dashboard');

        // الموظف العادي أصلاً ما يقدر يوصل هنا بسبب requireRole في __construct
        // هنا نسمح فقط لـ super_admin و manager (مع اختلاف الصلاحيات بينهم)

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // تعقيم المدخلات
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'name'         => trim($_POST['name'] ?? ''),
                'email'        => trim($_POST['email'] ?? ''),
                'password'     => trim($_POST['password'] ?? ''),
                'role'         => '',   // سيتم تحديده برمجياً
                'manager_id'   => null, // سيتم تحديده برمجياً
                'name_err'     => '',
                'email_err'    => '',
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

            // التحقق من عدم تكرار البريد إذا لم يكن فارغ
            if (empty($data['email_err']) && $this->userModel->findUserByEmail($data['email'])) {
                $data['email_err'] = 'هذا البريد مسجل مسبقاً';
            }

            // منطق تحديد الرتبة والمدير (Hierarchy Logic)
            if (isManager()) {
                // إذا كان المضيف "مدير قسم":
                // 1) الموظف الجديد يكون "user" دائماً
                $data['role'] = 'user';
                // 2) مديره هو المدير الحالي المسجل دخول
                $data['manager_id'] = $_SESSION['user_id'];
            } elseif (isSuperAdmin()) {
                // إذا كان المضيف "سوبر أدمن":
                // يأخذ الرتبة من الفورم (مع افتراض أن القيم صحيحة من الواجهة)
                $data['role'] = isset($_POST['role']) ? $_POST['role'] : 'user';
                // المدير يمكن أن يكون:
                // - null (بدون مدير)
                // - أو رقم مدير معيّن (إذا وفرت هذا الخيار لاحقاً في الواجهة)
                $data['manager_id'] = isset($_POST['manager_id']) && $_POST['manager_id'] !== ''
                    ? (int) $_POST['manager_id']
                    : null;
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
                'name'         => '',
                'email'        => '',
                'password'     => '',
                'role'         => 'user',
                'name_err'     => '',
                'email_err'    => '',
                'password_err' => ''
            ];

            $this->view('users/add', $data);
        }
    }
    
    // صفحة الملف الشخصي
    public function profile()
    {
        // هذه يمكن الوصول لها من المستخدم نفسه غالباً، 
        // لكن هنا UsersController مقفول على الأدمن والمدير
        // الأفضل يكون عندك Profile في Controller آخر (مثلاً Auth أو UserSelf)
        // لكن سنتركها هنا حسب تصميمك الحالي.

        $user = $this->userModel->getUserById($_SESSION['user_id']);

        $data = [
            'user' => $user
        ];

        $this->view('users/profile', $data);
    }

    // صفحة تعديل المستخدم
    public function edit($id){
        requirePermission('users.manage', 'dashboard');


        $userId = (int) $id;
        if ($userId <= 0) {
            redirect('index.php?page=users/index');
        }

        // جلب بيانات المستخدم المراد تعديله
        $user = $this->userModel->getUserById($userId);
        if (!$user) {
            redirect('index.php?page=users/index');
        }

        // حماية: إذا كان "مدير قسم"، ممنوع يعدل مستخدم لا يتبعه
        if (isManager() && $user->manager_id != $_SESSION['user_id']) {
            flash('access_denied', 'لا تملك صلاحية تعديل هذا المستخدم', 'alert alert-danger');
            redirect('index.php?page=users/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'id'           => $userId,
                'name'         => trim($_POST['name'] ?? ''),
                'email'        => trim($_POST['email'] ?? ''),
                'password'     => trim($_POST['password'] ?? ''),
                'role'         => $user->role, // سنحدّثه فقط إذا كان سوبر أدمن
                'name_err'     => '',
                'email_err'    => '',
                'password_err' => ''
            

            ];

            // التحقق
            if (empty($data['email'])) {
                $data['email_err'] = 'البريد مطلوب';
            }

            if (empty($data['name'])) {
                $data['name_err'] = 'الاسم مطلوب';
            }

            // فقط السوبر أدمن يقدر يغير دور المستخدم
            if (isSuperAdmin()) {
                if (isset($_POST['role']) && $_POST['role'] !== '') {
                    $data['role'] = $_POST['role'];
                }
            }

            if (empty($data['email_err']) && empty($data['name_err'])) {
                // تشفير الباسورد الجديد إذا وجد
                if (!empty($data['password'])) {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                } else {
                    // نخلي الباسوورد كما هو (الموديل لازم يتعامل مع هذا)
                    $data['password'] = '';
                }

                if ($this->userModel->update($data)) {
                    flash('user_message', 'تم تحديث البيانات بنجاح');
                    redirect('index.php?page=users/index');
                } else {
                    die('حدث خطأ ما أثناء التحديث');
                }
            } else {
                $this->view('users/edit', $data);
            }

        } else {
            // عرض النموذج مع البيانات الحالية
            $data = [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'password'     => '', // نتركه فارغ للأمان
                'role'         => $user->role,
                'name_err'     => '',
                'email_err'    => '',
                'password_err' => ''
            ];

            $this->view('users/edit', $data);
        }
    }

    // حذف المستخدم
    public function delete(){

        requirePermission('users.manage', 'dashboard');

        // هنا نسمح فقط لـ super_admin و manager (بسبب requireRole في __construct)
        // لكن نضيف حماية إضافية للمدير

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            flash('user_message', 'طريقة طلب غير صحيحة للحذف');
            redirect('index.php?page=users/index');
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            flash('user_message', 'معرّف مستخدم غير صالح');
            redirect('index.php?page=users/index');
            return;
        }

        $user = $this->userModel->getUserById($id);
        if (!$user) {
            flash('user_message', 'المستخدم غير موجود');
            redirect('index.php?page=users/index');
            return;
        }

        // إذا كان مدير -> لا يحذف إلا مستخدمين يتبعونه
        if (isManager() && $user->manager_id != $_SESSION['user_id']) {
            flash('access_denied', 'لا تملك صلاحية حذف هذا المستخدم', 'alert alert-danger');
            redirect('index.php?page=users/index');
            return;
        }

        // (اختياري) منع حذف نفسه
        if ($id === (int) $_SESSION['user_id']) {
            flash('user_message', 'لا يمكنك حذف حسابك بنفسك');
            redirect('index.php?page=users/index');
            return;
        }

        if ($this->userModel->delete($id)) {
            flash('user_message', 'تم حذف المستخدم بنجاح');
        } else {
            flash('user_message', 'حدث خطأ أثناء الحذف، حاول مرة أخرى');
        }

        redirect('index.php?page=users/index');
    }
}
