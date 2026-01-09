<?php
// app/helpers/session_helper.php

// ✅ أهم سطر: لازم تفتح Session قبل أي استخدام لـ $_SESSION
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * Flash message helper
 */
function flash($name = '', $message = '', $class = 'alert alert-success')
{
  if ($name === '') return;

  if ($message !== '' && empty($_SESSION[$name])) {
    $_SESSION[$name] = $message;
    $_SESSION[$name . '_class'] = $class;
    return;
  }

  if (!empty($_SESSION[$name])) {
    $msg = $_SESSION[$name];
    $cls = $_SESSION[$name . '_class'] ?? $class;

    echo '<div class="' . htmlspecialchars($cls) . '" id="msg-flash">'
      . htmlspecialchars($msg) .
      '</div>';

    unset($_SESSION[$name], $_SESSION[$name . '_class']);
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

function currentRole()
{
  $r = $_SESSION['user_role'] ?? 'user';
  return ($r === 'super_admin') ? 'superadmin' : $r;
}

function isUser() { return currentRole() === 'user'; }
function isManager()
{
  $role = currentRole();
  return ($role === 'manager' || $role === 'admin');
}
function isSuperAdmin() { return currentRole() === 'superadmin'; }

/**
 * ✅ تحديث role من DB إذا تغير
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
 * ✅ صلاحيات عامة
 */
function requirePermission($permissionOrRoles, $redirectTo = 'index.php?page=dashboard/index')
{
  requireLogin();
  syncSessionRole();
  $role = currentRole();

  // 1) Array roles
  if (is_array($permissionOrRoles)) {
    $allowed = array_map('normalizeRole', $permissionOrRoles);
    if (!in_array($role, $allowed, true)) {
      flash('access_denied', 'ليس لديك صلاحية للوصول لهذه الصفحة', 'alert alert-danger');
      redirect($redirectTo);
    }
    return;
  }

  // 2) Permission key
  $permission = (string)$permissionOrRoles;

  $map = [
    // Users
    // Users
    'users.view'   => ['superadmin', 'admin', 'manager'],
    'users.manage' => ['superadmin'],


    // Locations (عام)
    'locations.view'   => ['superadmin', 'admin', 'manager', 'user'],
    'locations.add'    => ['superadmin', 'admin', 'manager'],
    'locations.edit'   => ['superadmin', 'admin', 'manager'],
    'locations.delete' => ['superadmin', 'admin'],
    'locations.manage' => ['superadmin', 'admin'],

    // Assets
    'assets.manage'    => ['superadmin', 'admin', 'manager'],
    'assets.assign'    => ['superadmin', 'admin', 'manager'],
    'assets.edit'      => ['superadmin', 'admin', 'manager'],
    'assets.delete'    => ['superadmin', 'admin'],

    // Spare parts
    'spareparts.manage'=> ['superadmin', 'admin', 'manager'],

    // Tickets
    'tickets.manage'   => ['superadmin', 'admin', 'manager'],
  ];

  $allowed = $map[$permission] ?? ['superadmin'];
  $allowed = array_map('normalizeRole', $allowed);

  if (!in_array($role, $allowed, true)) {
    flash('access_denied', 'ليس لديك صلاحية للوصول لهذه الصفحة', 'alert alert-danger');
    redirect($redirectTo);
  }
}

function requireUsersView($redirectTo = 'index.php?page=dashboard') {
  requirePermission('users.view', $redirectTo);
}

function requireUsersManage($redirectTo = 'index.php?page=dashboard') {
  requirePermission('users.manage', $redirectTo);
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

  // إذا Database غير محمّل: admin/manager فقط
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

    // افتراضي
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

function canAccessLocationsModule()
{
  if (!isLoggedIn()) return false;
  $role = currentRole();

  if (in_array($role, ['superadmin', 'manager'], true)) return true;
  if (!class_exists('Database')) return false;

  try {
    $db = new Database();
    $db->query("
      SELECT 1
      FROM locations_permissions
      WHERE user_id = :uid
        AND (can_manage=1 OR can_add_children=1 OR can_edit=1 OR can_delete=1)
      LIMIT 1
    ");
    $db->bind(':uid', (int)$_SESSION['user_id']);
    return (bool)$db->single();
  } catch (Exception $e) {
    return false;
  }
}

function requireLocationsAccess($redirectTo = 'index.php?page=dashboard/index')
{
  requireLogin();
  if (!canAccessLocationsModule()) {
    flash('access_denied', 'ليس لديك صلاحية لعرض صفحة المواقع', 'alert alert-danger');
    redirect($redirectTo);
  }


function isLoggedIn(): bool {
  return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUserId(): ?int {
  return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
}

function currentUserRole(): string {
  return (string)($_SESSION['user_role'] ?? '');
}

function isSuperAdmin(): bool {
  return currentUserRole() === 'super_admin';
}

function isAdminOrManager(): bool {
  $r = currentUserRole();
  return in_array($r, ['admin','manager','super_admin'], true);
}

function requireLogin(): void {
  if (!isLoggedIn()) {
    redirect('users/login');
    exit;
  }
}

function requireRole(array $roles): void {
  requireLogin();
  $role = currentUserRole();
  if (!in_array($role, $roles, true)) {
    flash('msg', 'غير مصرح لك بالوصول لهذه الصفحة', 'alert alert-danger');
    redirect('dashboard/index');
    exit;
  }
}

/**
 * للحماية الخاصة بإدارة المستخدمين
 */
function requireManageUsers(): void {
  // كبداية: فقط سوبر أدمن
  requireRole(['super_admin']);
}

}
