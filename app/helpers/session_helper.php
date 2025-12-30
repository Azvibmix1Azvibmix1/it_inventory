<?php
// بدء الجلسة إذا لم تكن قد بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// دالة رسائل التنبيه (Flash Messages)
function flash($name = '', $message = '', $class = 'alert alert-success'){
    if(!empty($name)){
        if(!empty($message) && empty($_SESSION[$name])){
            if(!empty($_SESSION[$name. '_class'])){
                unset($_SESSION[$name. '_class']);
            }
            $_SESSION[$name] = $message;
            $_SESSION[$name. '_class'] = $class;
        } elseif(empty($message) && !empty($_SESSION[$name])){
            $class = !empty($_SESSION[$name. '_class']) ? $_SESSION[$name. '_class'] : '';
            echo '<div class="'.$class.'" id="msg-flash">'.$_SESSION[$name].'</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name. '_class']);
        }
    }
}


function redirect($page){
    // نقوم بدمج الرابط الأساسي مع الصفحة المطلوبة
    header('location: ' . URLROOT . '/' . $page);
}


// 1. هل المستخدم مسجل دخول؟
function isLoggedIn(){
    if(isset($_SESSION['user_id'])){
        return true;
    } else {
        return false;
    }
}

// 2. هل المستخدم هو المدير العام (مالك النظام)؟
function isSuperAdmin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin');
}

// 3. هل المستخدم مدير قسم (عنده موظفين)؟
function isManager() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager');
}

// 4. هل المستخدم موظف عادي؟
function isUser() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user');
}

function currentRole() {
    return $_SESSION['user_role'] ?? 'user';
}

function can($permissionCode) {
    if (!isset($_SESSION['user_id'])) return false;

    // superadmin bypass (اختياري)
    if (currentRole() === 'superadmin') return true;

    try {
        $db = new Database();

        // 1) user override (أولوية أعلى)
        $db->query("SELECT allowed FROM user_permissions WHERE user_id = :uid AND permission_code = :p LIMIT 1");
        $db->bind(':uid', (int)$_SESSION['user_id']);
        $db->bind(':p', $permissionCode);
        $row = $db->single();
        if ($row) return (int)$row->allowed === 1;

        // 2) role default
        $db->query("SELECT allowed FROM role_permissions WHERE role = :r AND permission_code = :p LIMIT 1");
        $db->bind(':r', currentRole());
        $db->bind(':p', $permissionCode);
        $row2 = $db->single();
        if ($row2) return (int)$row2->allowed === 1;

        return false;

    } catch (Exception $e) {
        // fallback آمن: ما نعطي صلاحيات إذا صار خطأ
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
