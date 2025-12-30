<?php
// تشغيل الجلسة إذا لم تكن مبدوءة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * فلاش ميسج بسيطة
 */
function flash($name = '', $message = '', $class = 'alert alert-success'){
    if(!empty($name)){
        if(!empty($message) && empty($_SESSION[$name])){
            if(!empty($_SESSION[$name])){
                unset($_SESSION[$name]);
            }
            if(!empty($_SESSION[$name.'_class'])){
                unset($_SESSION[$name.'_class']);
            }

            $_SESSION[$name] = $message;
            $_SESSION[$name.'_class'] = $class;
        } elseif(empty($message) && !empty($_SESSION[$name])){
            $class = !empty($_SESSION[$name.'_class']) ? $_SESSION[$name.'_class'] : '';
            echo '<div class="'.$class.'">'.$_SESSION[$name].'</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name.'_class']);
        }
    }
}

/**
 * التحقق من تسجيل الدخول
 */
function isLoggedIn(){
    return isset($_SESSION['user_id']);
}

function currentRole() {
    return $_SESSION['user_role'] ?? 'user';
}

function isUser() {
    return currentRole() === 'user';
}

function isManager() {
    return currentRole() === 'manager';
}

function isSuperAdmin() {
    return currentRole() === 'superadmin';
}

/**
 * صلاحيات مرنة (RBAC)
 */
function can($permissionCode) {
    if (!isset($_SESSION['user_id'])) return false;

    // superadmin له كل الصلاحيات
    if (currentRole() === 'superadmin') return true;

    try {
        $db = new Database();

        // 1) override على مستوى المستخدم
        $db->query("SELECT allowed FROM user_permissions WHERE user_id = :uid AND permission_code = :p LIMIT 1");
        $db->bind(':uid', (int)$_SESSION['user_id']);
        $db->bind(':p', $permissionCode);
        $row = $db->single();
        if ($row) return (int)$row->allowed === 1;

        // 2) default حسب الدور
        $db->query("SELECT allowed FROM role_permissions WHERE role = :r AND permission_code = :p LIMIT 1");
        $db->bind(':r', currentRole());
        $db->bind(':p', $permissionCode);
        $row2 = $db->single();
        if ($row2) return (int)$row2->allowed === 1;

        return false;

    } catch (Exception $e) {
        return false;
    }
}

function requirePermission($permissionCode, $redirectPage = 'dashboard') {
    if (!can($permissionCode)) {
        flash('access_denied', 'ليس لديك صلاحية للوصول لهذه الصفحة', 'alert alert-danger');
        redirect('index.php?page=' . $redirectPage);
        exit;
    }
}

/**
 * دالة إعادة التوجيه
 */
function redirect($location){
    header('Location: ' . $location);
    exit;
}
