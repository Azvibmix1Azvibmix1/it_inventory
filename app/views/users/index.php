<?php
require_once APPROOT . '/views/layouts/header.php';

$allUsers = $data['users'] ?? [];

// صلاحيات الواجهة (UI فقط) — الحماية الفعلية بالسيرفر موجودة
$role      = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? '');
$role      = function_exists('normalizeRole') ? normalizeRole($role) : (string)$role;
$canManage = ($role === 'superadmin'); // users.manage (حسب خريطتك)

// فلاتر GET
$q          = trim($_GET['q'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

// Helpers للبحث (مع/بدون mbstring)
$toLower = function ($s) {
  return function_exists('mb_strtolower') ? mb_strtolower((string)$s, 'UTF-8') : strtolower((string)$s);
};
$pos = function ($hay, $needle) {
  return function_exists('mb_strpos') ? mb_strpos((string)$hay, (string)$needle, 0, 'UTF-8') : stripos((string)$hay, (string)$needle);
};

// فلترة محلية (لين نربطها بالكنترولر/الموديل لاحقًا)
$users = $allUsers;
if ($q !== '' || $roleFilter !== '') {
  $users = array_values(array_filter($users, function ($u) use ($q, $roleFilter, $toLower, $pos) {

    $uRole = function_exists('normalizeRole') ? normalizeRole($u->role ?? 'user') : ($u->role ?? 'user');

    if ($roleFilter !== '' && $uRole !== $roleFilter) return false;

    if ($q !== '') {
      $hay = $toLower(($u->name ?? '') . ' ' . ($u->username ?? '') . ' ' . ($u->email ?? ''));
      $needle = $toLower($q);
      if ($pos($hay, $needle) === false) return false;
    }

    return true;
  }));
}

// إحصائيات سريعة
$total = count($users);
$superCount = 0; $adminCount = 0; $managerCount = 0; $userCount = 0;

foreach ($users as $u) {
  $r = function_exists('normalizeRole') ? normalizeRole($u->role ?? 'user') : ($u->role ?? 'user');
  if ($r === 'superadmin') $superCount++;
  elseif ($r === 'admin') $adminCount++;
  elseif ($r === 'manager') $managerCount++;
  else $userCount++;
}
?>

<div class="container py-4">

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h3 class="mb-0">إدارة المستخدمين</h3>

    <div class="d-flex align-items-center gap-2">
      <?php if ($canManage): ?>
        <a href="<?php echo URLROOT; ?>/index.php?page=users/add" class="btn btn-primary">
          <i class="fa fa-user-plus"></i> إضافة مستخدم جديد
        </a>
      <?php else: ?>
        <span class="badge bg-secondary">عرض فقط</span>
      <?php endif; ?>
    </div>
  </div>

  <?php flash('user_message'); ?>
  <?php flash('register_success'); ?>
  <?php flash('access_denied'); ?>

  <!-- فلاتر -->
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <form method="GET" action="<?php echo URLROOT; ?>/index.php" class="row g-2 align-items-center">
        <input type="hidden" name="page" value="users/index">

        <div class="col-md-6">
          <div class="input-group">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" name="q" class="form-control" placeholder="ابحث بالاسم أو البريد..." value="<?php echo htmlspecialchars($q); ?>">
          </div>
        </div>

        <div class="col-md-3">
          <select name="role" class="form-select">
            <option value="">كل الأدوار</option>
            <option value="superadmin" <?php echo ($roleFilter === 'superadmin' ? 'selected' : ''); ?>>سوبر أدمن</option>
            <option value="admin" <?php echo ($roleFilter === 'admin' ? 'selected' : ''); ?>>أدمن</option>
            <option value="manager" <?php echo ($roleFilter === 'manager' ? 'selected' : ''); ?>>مدير</option>
            <option value="user" <?php echo ($roleFilter === 'user' ? 'selected' : ''); ?>>موظف</option>
          </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" type="submit">
            <i class="fa fa-filter"></i> تطبيق
          </button>
          <a class="btn btn-outline-secondary w-100" href="<?php echo URLROOT; ?>/index.php?page=users/index">
            إعادة ضبط
          </a>
        </div>
      </form>

      <div class="d-flex flex-wrap gap-2 mt-3">
        <span class="badge bg-dark">الإجمالي: <?php echo (int)$total; ?></span>
        <span class="badge bg-secondary">سوبر أدمن: <?php echo (int)$superCount; ?></span>
        <span class="badge bg-primary">أدمن: <?php echo (int)$adminCount; ?></span>
        <span class="badge bg-danger">مدير: <?php echo (int)$managerCount; ?></span>
        <span class="badge bg-info text-dark">موظف: <?php echo (int)$userCount; ?></span>
      </div>
    </div>
  </div>

  <!-- جدول المستخدمين -->
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>الاسم</th>
              <th>البريد الإلكتروني</th>
              <th>الدور (الصلاحية)</th>
              <th>تاريخ التسجيل</th>
              <th class="text-center" style="width: 140px;">إجراءات</th>
            </tr>
          </thead>

          <tbody>
          <?php if (!empty($users)): ?>
            <?php foreach ($users as $user): ?>
              <?php
                $displayName = !empty($user->username) ? $user->username : ($user->name ?? '-');
                $email = $user->email ?? '-';
                $r = function_exists('normalizeRole') ? normalizeRole($user->role ?? 'user') : ($user->role ?? 'user');

                // بادج الدور
                if ($r === 'superadmin') {
                  $roleBadge = '<span class="badge bg-dark">سوبر أدمن</span>';
                } elseif ($r === 'admin') {
                  $roleBadge = '<span class="badge bg-primary">أدمن</span>';
                } elseif ($r === 'manager') {
                  $roleBadge = '<span class="badge bg-danger">مدير</span>';
                } else {
                  $roleBadge = '<span class="badge bg-info text-dark">موظف</span>';
                }

                $createdAt = !empty($user->created_at) ? date('Y-m-d', strtotime($user->created_at)) : '-';
                $uid = (int)($user->id ?? 0);
              ?>
              <tr>
                <td><?php echo htmlspecialchars($displayName); ?></td>
                <td dir="ltr"><?php echo htmlspecialchars($email); ?></td>
                <td><?php echo $roleBadge; ?></td>
                <td><span dir="ltr"><?php echo htmlspecialchars($createdAt); ?></span></td>

                <td class="text-center">
                  <?php if ($canManage): ?>
                    <a href="<?php echo URLROOT; ?>/index.php?page=users/edit&id=<?php echo $uid; ?>"
                       class="btn btn-sm btn-outline-primary" title="تعديل">
                      <i class="fa fa-edit"></i>
                    </a>

                    <?php if ($uid !== (int)($_SESSION['user_id'] ?? 0)): ?>
                      <form action="<?php echo URLROOT; ?>/index.php?page=users/delete"
                            method="POST"
                            class="d-inline"
                            onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');">
                        <input type="hidden" name="id" value="<?php echo $uid; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                          <i class="fa fa-trash"></i>
                        </button>
                      </form>
                    <?php else: ?>
                      <button class="btn btn-sm btn-outline-secondary" disabled title="لا يمكنك حذف حسابك">
                        <i class="fa fa-ban"></i>
                      </button>
                    <?php endif; ?>

                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>

          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                لا يوجد مستخدمين مسجلين.
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
