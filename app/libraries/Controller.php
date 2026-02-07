<?php
/*
 * الكلاس الأساسي (Base Controller)
 * وظيفته: تحميل الموديلات والواجهات
 */

require_once '../app/helpers/session_helper.php';

class Controller {

    // دالة تحميل الموديل
    public function model($model){
        $path = '../app/models/' . $model . '.php';

        if (file_exists($path)) {
            require_once $path;
            return new $model();
        } else {
            // رسالة أوضح بدل die بسيطة
            die("Model file not found: {$path}");
        }
    }

    // دالة تحميل الواجهة (View)
    public function view($view, $data = []){
        $path = '../app/views/' . $view . '.php';

        if (file_exists($path)) {
            // نخلي البيانات متاحة داخل الملف كـ $data
            require $path;
        } else {
            die("View file not found: {$path}");
        }
    }

    /**
     * التأكد من أن المستخدم مسجل دخول
     * تستخدم داخل الكنترولر في __construct أو في دوال معيّنة
     */
 // شغل الراوتر مرة واحدة اعتماداً على الكلاس الموجود
if (class_exists('Core')) {
  new Core();
} elseif (class_exists('App')) {
  new App();
} elseif (class_exists('Router')) {
  new Router();
} else {
  die('Router class not found. Check app/libraries for Core/App/Router.');
}


    /**
     * التأكد من أن المستخدم يملك أحد الأدوار المسموح بها
     * مثال: $this->requireRole(['super_admin', 'manager']);
     */
    protected function requireRole(array $roles)
    {
        // نضمن أنه مسجل قبل ما نتحقق من الدور
        $this->requireLogin();

        $userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;

        if ($userRole === null || !in_array($userRole, $roles, true)) {
            if (function_exists('flash')) {
                flash('auth_error', 'ليس لديك صلاحية للوصول لهذه الصفحة');
            }
            if (function_exists('redirect')) {
                redirect('index.php?page=dashboard/index');
            } else {
                header('Location: index.php?page=dashboard/index');
            }
            exit;
        }
    }
}
