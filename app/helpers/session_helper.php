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
function redirect($target)
{
  // target ممكن يكون:
  // - "login"
  // - "users/add"
  // - "index.php?page=login"
  // - "/it_inventory/public/index.php?page=login"
  // - "http://..."

  $target = (string)$target;

  // 1) إذا رابط كامل
  if (preg_match('#^https?://#i', $target)) {
    if (!headers_sent()) { header('Location: ' . $target); exit; }
    echo '<script>location.href=' . json_encode($target) . ';</script>'; exit;
  }

  // 2) إذا مرر "index.php?page=..." لا تضيف عليه page مرة ثانية
  if (stripos($target, 'index.php') !== false) {
    $url = (defined('URLROOT') ? rtrim(URLROOT, '/') . '/' : '') . ltrim($target, '/');
    if (!headers_sent()) { header('Location: ' . $url); exit; }
    echo '<script>location.href=' . json_encode($url) . ';</script>'; exit;
  }

  // 3) إذا مرر "?page=..." أو "page=..."
  if (strpos($target, '?page=') === 0) {
    $url = (defined('URLROOT') ? rtrim(URLROOT, '/') : '') . '/index.php' . $target;
    if (!headers_sent()) { header('Location: ' . $url); exit; }
    echo '<script>location.href=' . json_encode($url) . ';</script>'; exit;
  }
  if (stripos($target, 'page=') !== false && strpos($target, '?') === 0) {
    $url = (defined('URLROOT') ? rtrim(URLROOT, '/') : '') . '/index.php' . $target;
    if (!headers_sent()) { header('Location: ' . $url); exit; }
    echo '<script>location.href=' . json_encode($url) . ';</script>'; exit;
  }

  // 4) الحالة الطبيعية: "login" أو "users/index"
  $url = (defined('URLROOT') ? rtrim(URLROOT, '/') : '') . '/index.php?page=' . ltrim($target, '/');
  if (!headers_sent()) { header('Location: ' . $url); exit; }
  echo '<script>location.href=' . json_encode($url) . ';</script>'; exit;
}



/**
 * Auth helpers
 */



/**
 * Role helpers
 */






function currentRole(): string {
  return normalizeRole($_SESSION['user_role'] ?? 'user');
}


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

  
if (!function_exists('requireManageUsers')) {
  

  // 2) Permission key
  $permission = (string)$permissionOrRoles;

  $map = [
    // Users
    // Users
    'users.view'   => ['super_admin', 'manager'],
    'users.manage' => ['super_admin'],



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
}

function requireManageUsers($redirectTo = 'index.php?page=dashboard'): void {
    requirePermission('users.manage', $redirectTo);
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
  if (in_array($role, ['super_admin', 'superadmin'], true)) return true;


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

  if (in_array($role, ['super_admin', 'superadmin', 'manager'], true)) return true;
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

function requireLocationsAccess($redirectTo = 'index.php?page=dashboard/index') {
  requireLogin();

  if (!canAccessLocationsModule()) {
    flash('access_denied', 'ليس لديك صلاحية لعرض صفحة المواقع', 'alert alert-danger');
    redirect($redirectTo);
  }
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



function isAdminOrManager(): bool {
  $r = currentUserRole();
  return in_array($r, ['admin','manager','super_admin'], true);
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

<<<<<<< HEAD
function requireLogin(): void
{
    $page = $_GET['page'] ?? '';

    // اسمح بصفحة تسجيل الدخول
    if (stripos($page, 'login') !== false) {
        return;
    }

    if (!isLoggedIn()) {
        header('Location: index.php?page=login');
        exit;
    }
=======
function requireLogin($redirectTo = 'login')
{
  $current = strtolower(trim((string)($_GET['page'] ?? '')));
  if (!isLoggedIn()) {
    if ($current !== 'login') {
      redirect($redirectTo);
    }
    return;
  }
>>>>>>> 6e0ca0b6a9a64f7b51659118ef13d7bd8489e099
}


function normalizeRole($role): string {
  $r = strtolower(trim((string)($role ?? 'user')));

  // وحّد كل الصيغ لقيم DB
  if ($r === 'superadmin') return 'super_admin';
  if ($r === 'super_admin') return 'super_admin';

  if ($r === 'admin') return 'manager'; // إذا عندك بقايا admin قديم
  if ($r === 'manager') return 'manager';

  return 'user';
}





function isManager(): bool {
  return currentRole() === 'manager';
}



function isSuperAdmin(): bool {
  return currentRole() === 'super_admin';
}
