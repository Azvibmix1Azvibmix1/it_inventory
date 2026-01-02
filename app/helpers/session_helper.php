<?php
// app/helpers/session_helper.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Flash messages
function flash($name = '', $message = '', $class = 'alert alert-success')
{
    if (!empty($name)) {
        if (!empty($message) && empty($_SESSION[$name])) {
            $_SESSION[$name] = $message;
            $_SESSION[$name . '_class'] = $class;
        } elseif (empty($message) && !empty($_SESSION[$name])) {
            $class = $_SESSION[$name . '_class'] ?? 'alert alert-success';
            echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);
        }
    }
}

function redirect($location)
{
    header('Location: ' . $location);
    exit;
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function currentRole()
{
    return $_SESSION['user_role'] ?? 'user';
}

function isUser()
{
    return currentRole() === 'user';
}

function isManager()
{
    $role = currentRole();
    return $role === 'manager' || $role === 'admin';
}

function isSuperAdmin()
{
    return currentRole() === 'superadmin';
}

/**
 * ✅ تحديث دور المستخدم من قاعدة البيانات لو تغيّر
 * مفيد لما تغيّر دور نفسك من صفحة المستخدمين وما تبغى تسوي logout/login.
 */
function syncSessionRole()
{
    if (!isLoggedIn()) return;

    try {
        $db = new Database();
        $db->query("SELECT role FROM users WHERE id = :id LIMIT 1");
        $db->bind(':id', (int)$_SESSION['user_id']);
        $row = $db->single();

        if ($row && isset($row->role)) {
            if (($_SESSION['user_role'] ?? null) !== $row->role) {
                $_SESSION['user_role'] = $row->role;
            }
        }
    } catch (Exception $e) {
        // لو DB فيها مشكلة ما نكسر الموقع
    }

    // ✅ صلاحيات مرنة: تمرير أدوار مسموحة
// ✅ صلاحيات مرنة: تقبل (roles array) أو (permission string مثل users.manage)
function requirePermission($permissionOrRoles, $redirectTo = 'index.php?page=dashboard')
{
    if (!isLoggedIn()) {
        redirect('index.php?page=login');
    }

    $role = $_SESSION['user_role'] ?? 'user';

    // 1) لو Array => أدوار مباشرة
    if (is_array($permissionOrRoles)) {
        if (!in_array($role, $permissionOrRoles, true)) {
            flash('access_denied', 'ليس لديك صلاحية للوصول لهذه الصفحة', 'alert alert-danger');
            redirect($redirectTo);
        }
        return;
    }

    // 2) لو String => Permission key (مبدئيًا)
    $permission = (string)$permissionOrRoles;

    // خريطة صلاحيات سريعة (نطورها لاحقًا)
    $map = [
        'users.manage'     => ['superadmin', 'admin', 'manager'],
        'locations.manage' => ['superadmin', 'admin'],
        'assets.manage'    => ['superadmin', 'admin', 'manager'],
        'spareparts.manage'=> ['superadmin', 'admin', 'manager'],
        'tickets.manage'   => ['superadmin', 'admin', 'manager'],
    ];

    $allowed = $map[$permission] ?? ['superadmin']; // افتراضي: سوبر أدمن فقط

    if (!in_array($role, $allowed, true)) {
        flash('access_denied', 'ليس لديك صلاحية للوصول لهذه الصفحة', 'alert alert-danger');
        redirect($redirectTo);
    }
}


function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . URLROOT . '/users/login');
        exit;
    }
}


}
