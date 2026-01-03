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

function redirect($location)
{
  header('Location: ' . $location);
  exit;
}

/**
 * Auth
 */
function isLoggedIn()
{
  return isset($_SESSION['user_id']);
}

function normalizeRole($role)
{
  $role = $role ?? 'user';
  if ($role === 'super_admin') return 'superadmin';
  return $role;
}

function currentRole()
{
  return normalizeRole($_SESSION['user_role'] ?? 'user');
}

function requireLogin()
{
  if (!isLoggedIn()) {
    // ✅ عندك صفحة دخول على page=login
    redirect('index.php?page=login');
  }
}

/**
 * Role checks (حسب جدول users عندك: super_admin, manager, user)
 */
function isUser()
{
  return currentRole() === 'user';
}

function isManager()
{
  return currentRole() === 'manager';
}

function isSuperAdmin()
{
  return currentRole() === 'superadmin';
}

/**
 * تحديث الرول من DB (اختياري، آمن)
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
      $_SESSION['user_role'] = normalizeRole($row->role);
    }
  } catch (Exception $e) {
    // لا نكسر الموقع
  }
}

/**
 * صلاحيات عامة (لو احتجتها)
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
    'users.manage'      => ['superadmin', 'manager'],
    'locations.manage'  => ['superadmin', 'manager'],
    'assets.manage'     => ['superadmin', 'manager'],
    'spareparts.manage' => ['superadmin', 'manager'],
    'tickets.manage'    => ['superadmin', 'manager'],
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
 * صلاحيات المواقع (Per-Location)
 * ===========================
 */
function canManageLocation($locationId, $action = 'manage')
{
  if (!isLoggedIn()) return false;

  $role = currentRole();
  if ($role === 'superadmin') return true;

  $locationId = (int)$locationId;
  if ($locationId <= 0) return false;

  // لو Database غير متاح لأي سبب
  if (!class_exists('Database')) {
    return ($role === 'manager');
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

    // ✅ افتراضي: manager يقدر إذا ما فيه إعدادات (تقدر تخليها false لو تبغاها صارمة)
    return ($role === 'manager');

  } catch (Exception $e) {
    return ($role === 'manager');
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

/**
 * ✅ صلاحية “دخول صفحة المواقع” كـ Module
 * - superadmin/manager: دايم
 * - user: لازم يكون عنده صلاحية على أي موقع (user_id) داخل locations_permissions
 */
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
}
