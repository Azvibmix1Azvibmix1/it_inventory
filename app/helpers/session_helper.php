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