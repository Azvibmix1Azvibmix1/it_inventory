<?php

/**
 * Flash messages
 */
function flash($name, $message = '', $class = 'alert alert-success')
{
  if (!empty($message) && empty($_SESSION[$name])) {
    unset($_SESSION[$name]);
    unset($_SESSION[$name . '_class']);
    $_SESSION[$name] = $message;
    $_SESSION[$name . '_class'] = $class;
    return;
  }

  if (empty($message) && !empty($_SESSION[$name])) {
    $class = $_SESSION[$name . '_class'] ?? 'alert alert-success';
    echo '<div class="' . $class . '">' . $_SESSION[$name] . '</div>';
    unset($_SESSION[$name]);
    unset($_SESSION[$name . '_class']);
  }
}

/**
 * Redirect helper
 */
function redirect($location)
{
  header('Location: ' . $location);
  exit;
}

/**
 * Auth helpers
 */
function isLoggedIn()
{
  return isset($_SESSION['user_id']);
}

function requireLogin()
{
  if (!isLoggedIn()) {
    redirect('index.php?page=login');
  }
}

/**
 * Role helpers
 */
function normalizeRole($role)
{
  $role = $role ?? 'user';
  if ($role === 'super_admin') return 'superadmin';
  return $role;
}

function currentRole() {
  $r = $_SESSION['user_role'] ?? 'user';
  return ($r === 'super_admin') ? 'superadmin' : $r;
}


function isUser()
{
  return currentRole() === 'user';
}

function isManager()
{
  $role = currentRole();
  return ($role === 'manager' || $role === 'admin');
}

function isSuperAdmin()
{
  return currentRole() === 'superadmin';
}

/**
 * ✅ تحديث دور المستخدم من قاعدة البيانات لو تغيّر
 * مهم: لا ننفّذه إلا إذا Database محمّل
 */
function syncSessionRole()
{
  if (!isLoggedIn()) return;
  if (!class_exists('Database')) return;

  try {
    $db = new Database();
    $db->query("SELECT role FROM users WHERE id = :id LIMIT 1");
    $db->bind(':id', (int)$_SESSION['user_id']);
    $row = $db->single();

    if ($row && isset($row->role)) {
      $newRole = normalizeRole($row->role);
      if (normalizeRole($_SESSION['user_role'] ?? null) !== $newRole) {
        $_SESSION['user_role'] = $newRole;
      }
    }
  } catch (Exception $e) {
    // لا نكسر الموقع
  }
}

/**
 * ✅ صلاحيات عامة:
 * - تمرير Array أدوار: requirePermission(['admin','manager'])
 * - تمرير Permission key: requirePermission('locations.edit')
 */
function requirePermission($permissionOrRoles, $redirectTo = 'index.php?page=dashboard/index')
{
  requireLogin();
  syncSessionRole();

  $role = currentRole();

  // 1) لو Array => أدوار مباشرة
  if (is_array($permissionOrRoles)) {
    $allowed = array_map('normalizeRole', $permissionOrRoles);
    if (!in_array($role, $allowed, true)) {
      flash('access_denied', 'ليس لديك صلاحية للوصول لهذه الصفحة', 'alert alert-danger');
      redirect($redirectTo);
    }
    return;
  }

  // 2) لو String => Permission key
  $permission = (string)$permissionOrRoles;

  $map = [
    // Users
    'users.manage' => ['superadmin', 'admin', 'manager'],

    // Locations (عام)
    'locations.view'   => ['superadmin', 'admin', 'manager', 'user'],
    'locations.add'    => ['superadmin', 'admin', 'manager'],
    'locations.edit'   => ['superadmin', 'admin', 'manager'],
    'locations.delete' => ['superadmin', 'admin'],
    'locations.manage' => ['superadmin', 'admin'],

    // Assets
    'assets.manage' => ['superadmin', 'admin', 'manager'],
    'assets.assign' => ['superadmin', 'admin', 'manager'],
    'assets.edit'   => ['superadmin', 'admin', 'manager'],
    'assets.delete' => ['superadmin', 'admin'],

    // Spare parts
    'spareparts.manage' => ['superadmin', 'admin', 'manager'],

    // Tickets
    'tickets.manage' => ['superadmin', 'admin', 'manager'],
  ];

  $allowed = $map[$permission] ?? ['superadmin'];
  $allowed = array_map('normalizeRole', $allowed);

  if (!in_array($role, $allowed, true)) {
    flash('access_denied', 'ليس لديك صلاحية للوصول لهذه الصفحة', 'alert alert-danger');
    redirect($redirectTo);
  }
}

/**
 * ===========================
 * ✅ صلاحيات على مستوى "الموقع"
 * ===========================
 * تعتمد على جدول locations_permissions
 */
function canManageLocation($locationId, $action = 'manage')
{
  if (!isLoggedIn()) return false;

  $role = currentRole();

  // سوبر أدمن دائمًا
  if ($role === 'superadmin') return true;

  $locationId = (int)$locationId;
  if ($locationId <= 0) return false;

  // إذا Database غير محمّل: لا نكسر الصفحة، نخلي admin/manager فقط
  if (!class_exists('Database')) {
    return ($role === 'admin' || $role === 'manager');
  }

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
      switch ($action) {
        case 'add':    return (bool)$u->can_add_children;
        case 'edit':   return (bool)$u->can_edit;
        case 'delete': return (bool)$u->can_delete;
        case 'manage':
        default:       return (bool)$u->can_manage;
      }
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
      switch ($action) {
        case 'add':    return (bool)$r->can_add_children;
        case 'edit':   return (bool)$r->can_edit;
        case 'delete': return (bool)$r->can_delete;
        case 'manage':
        default:       return (bool)$r->can_manage;
      }
    }

    // افتراضي: admin/manager مسموح لهم إذا ما فيه إعدادات
    return ($role === 'admin' || $role === 'manager');

  } catch (Exception $e) {
    return ($role === 'admin' || $role === 'manager');
  }
}

function requireLocationPermission($locationId, $action = 'manage', $redirectTo = 'index.php?page=locations/index')
{
  requireLogin();
  if (!canManageLocation($locationId, $action)) {
    flash('access_denied', 'ليس لديك صلاحية لإدارة هذا الموقع', 'alert alert-danger');
    redirect($redirectTo);
  }
}
