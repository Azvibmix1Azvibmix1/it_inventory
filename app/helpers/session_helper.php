<?php
// app/helpers/session_helper.php

// Session start (safe)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * Flash messages
 */
if (!function_exists('flash')) {
  function flash($name = '', $msg = '', $class = 'alert alert-success')
  {
    if (!empty($name)) {
      if (!empty($msg) && empty($_SESSION[$name])) {
        $_SESSION[$name] = $msg;
        $_SESSION[$name . '_class'] = $class;
      } elseif (empty($msg) && !empty($_SESSION[$name])) {
        $class = $_SESSION[$name . '_class'] ?? $class;
        echo '<div class="' . htmlspecialchars($class) . '">' . htmlspecialchars($_SESSION[$name]) . '</div>';
        unset($_SESSION[$name], $_SESSION[$name . '_class']);
      }
    }
  }
}

/**
 * Redirect helper
 */
if (!function_exists('redirect')) {
  function redirect($location)
  {
    header('Location: ' . $location);
    exit;
  }
}

/**
 * Auth helpers
 */
if (!function_exists('isLoggedIn')) {
  function isLoggedIn(): bool
  {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
  }
}

if (!function_exists('currentUserId')) {
  function currentUserId(): ?int
  {
    return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
  }
}

if (!function_exists('normalizeRole')) {
  function normalizeRole($role): string
  {
    $role = $role ?: 'user';
    // توحيد التسميات
    if ($role === 'superadmin') return 'super_admin';
    return (string)$role; // super_admin / admin / manager / user
  }
}

if (!function_exists('currentRole')) {
  function currentRole(): string
  {
    return normalizeRole($_SESSION['user_role'] ?? 'user');
  }
}

if (!function_exists('isSuperAdmin')) {
  function isSuperAdmin(): bool
  {
    return currentRole() === 'super_admin';
  }
}

if (!function_exists('isAdminOrManager')) {
  function isAdminOrManager(): bool
  {
    return in_array(currentRole(), ['admin', 'manager', 'super_admin'], true);
  }
}

/**
 * ✅ Require login (مع استثناء صفحة اللوجين لمنع loop)
 */
if (!function_exists('requireLogin')) {
  function requireLogin(): void
  {
    $page = strtolower(trim((string)($_GET['page'] ?? '')));

    // استثناء صفحات تسجيل الدخول من الحماية
    if ($page === 'login' || $page === 'users/login') {
      return;
    }

    if (!isLoggedIn()) {
      redirect('index.php?page=users/login');
    }
  }
}

/**
 * ✅ تحديث الدور من DB إذا تغير (اختياري)
 */
if (!function_exists('syncSessionRole')) {
  function syncSessionRole(): void
  {
    if (!isLoggedIn() || !class_exists('Database')) return;

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
}

/**
 * ✅ صلاحيات عامة
 */
if (!function_exists('requirePermission')) {
  function requirePermission($permissionOrRoles, $redirectTo = 'index.php?page=dashboard/index'): void
  {
    requireLogin();
    syncSessionRole();

    $role = currentRole();

    // 1) لو جاك array roles مباشرة
    if (is_array($permissionOrRoles)) {
      $allowed = array_map('normalizeRole', $permissionOrRoles);
      if (!in_array($role, $allowed, true)) {
        flash('access_denied', 'ليس لديك صلاحية للوصول لهذه الصفحة', 'alert alert-danger');
        redirect($redirectTo);
      }
      return;
    }

    // 2) Permission key map
    $permission = (string)$permissionOrRoles;

    $map = [
      // Users
      'users.view'   => ['super_admin', 'admin', 'manager'],
      'users.manage' => ['super_admin'],

      // Locations
      'locations.view'   => ['super_admin', 'admin', 'manager', 'user'],
      'locations.add'    => ['super_admin', 'admin', 'manager'],
      'locations.edit'   => ['super_admin', 'admin', 'manager'],
      'locations.delete' => ['super_admin', 'admin'],
      'locations.manage' => ['super_admin', 'admin'],

      // Assets
      'assets.manage' => ['super_admin', 'admin', 'manager'],
      'assets.assign' => ['super_admin', 'admin', 'manager'],
      'assets.edit'   => ['super_admin', 'admin', 'manager'],
      'assets.delete' => ['super_admin', 'admin'],

      // Spare parts
      'spareparts.manage' => ['super_admin', 'admin', 'manager'],

      // Tickets
      'tickets.manage' => ['super_admin', 'admin', 'manager'],
    ];

    $allowed = $map[$permission] ?? ['super_admin'];
    $allowed = array_map('normalizeRole', $allowed);

    if (!in_array($role, $allowed, true)) {
      flash('access_denied', 'ليس لديك صلاحية للوصول لهذه الصفحة', 'alert alert-danger');
      redirect($redirectTo);
    }
  }
}

/**
 * Helpers shortcuts
 */
if (!function_exists('requireUsersView')) {
  function requireUsersView($redirectTo = 'index.php?page=dashboard/index'): void
  {
    requirePermission('users.view', $redirectTo);
  }
}
if (!function_exists('requireUsersManage')) {
  function requireUsersManage($redirectTo = 'index.php?page=dashboard/index'): void
  {
    requirePermission('users.manage', $redirectTo);
  }
}

/**
 * Locations module gate (بسيط)
 */
if (!function_exists('canAccessLocationsModule')) {
  function canAccessLocationsModule(): bool
  {
    if (!isLoggedIn()) return false;
    $role = currentRole();
    if (in_array($role, ['super_admin', 'admin', 'manager'], true)) return true;
    return false;
  }
}
if (!function_exists('requireLocationsAccess')) {
  function requireLocationsAccess($redirectTo = 'index.php?page=dashboard/index'): void
  {
    requireLogin();
    if (!canAccessLocationsModule()) {
      flash('access_denied', 'ليس لديك صلاحية لعرض صفحة المواقع', 'alert alert-danger');
      redirect($redirectTo);
    }
  }
}
