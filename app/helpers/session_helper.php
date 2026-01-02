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

function isSuperAdmin() {
  $role = currentRole();
  return $role === 'superadmin' || $role === 'super_admin';
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
    redirect('index.php?page=login');
  }
}

function canManageLocation($locationId, $action = 'manage') {
  if (!isLoggedIn()) return false;

  $role = currentRole();
  if ($role === 'superadmin' || $role === 'super_admin') return true; // سوبر أدمن دائمًا

  $locationId = (int)$locationId;
  if ($locationId <= 0) return false;

  // admin: يقدر يشوف كل المواقع، لكن نخلّيه يخضع للـ permissions لو تبغى
  // إذا تبغاه دائمًا = فك التعليق:
  // if ($role === 'admin') return true;

  try {
    $db = new Database();

    // 1) صلاحية مباشرة للمستخدم
    $db->query("SELECT can_manage, can_add_children, can_edit, can_delete
                FROM locations_permissions
                WHERE location_id = :loc AND user_id = :uid
                LIMIT 1");
    $db->bind(':loc', $locationId);
    $db->bind(':uid', (int)$_SESSION['user_id']);
    $u = $db->single();

    if ($u) {
      return match ($action) {
        'manage' => (bool)$u->can_manage,
        'add'    => (bool)$u->can_add_children,
        'edit'   => (bool)$u->can_edit,
        'delete' => (bool)$u->can_delete,
        default  => (bool)$u->can_manage,
      };
    }

    // 2) صلاحية حسب الدور
    $db->query("SELECT can_manage, can_add_children, can_edit, can_delete
                FROM locations_permissions
                WHERE location_id = :loc AND role = :role
                LIMIT 1");
    $db->bind(':loc', $locationId);
    $db->bind(':role', $role);
    $r = $db->single();

    if ($r) {
      return match ($action) {
        'manage' => (bool)$r->can_manage,
        'add'    => (bool)$r->can_add_children,
        'edit'   => (bool)$r->can_edit,
        'delete' => (bool)$r->can_delete,
        default  => (bool)$r->can_manage,
      };
    }

    // افتراضي: المدير (manager/admin) مسموح له إداريًا لو ما فيه إعدادات
    return ($role === 'admin' || $role === 'manager');

  } catch (Exception $e) {
    // لو DB فيها مشكلة: نخليها سياسة آمنة (deny) أو allow للمدير
    return ($role === 'admin' || $role === 'manager');
  }
}

function requireLocationPermission($locationId, $action = 'manage', $redirectTo = 'index.php?page=locations/index') {
  requireLogin();
  if (!canManageLocation($locationId, $action)) {
    flash('access_denied', 'ليس لديك صلاحية لإدارة هذا الموقع', 'alert alert-danger');
    redirect($redirectTo);
  }
}


}
