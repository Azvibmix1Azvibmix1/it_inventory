<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
// users تأتي عادة من الكنترولر
$users = $data['users'] ?? ($users ?? []);
if (!is_array($users)) $users = [];

// ===== صلاحيات الصفحة (حل ثاني بدون الاعتماد على session_helper) =====
$sessionRole = strtolower(trim((string)($_SESSION['user_role'] ?? 'user')));
$canManageUsers = in_array($sessionRole, ['super_admin', 'superadmin'], true); // سوبر أدمن فقط
$canViewUsers   = in_array($sessionRole, ['super_admin', 'superadmin', 'manager'], true);

// ===== فلاتر =====
$q = trim((string)($_GET['q'] ?? ''));
$roleFilter = trim((string)($_GET['role'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));

$toLower = function($s) { return mb_strtolower((string)$s, 'UTF-8'); };
$pos = function($hay, $needle) { return mb_stripos((string)$hay, (string)$needle, 0, 'UTF-8'); };

$normRole = function($r) {
  $r = strtolower(trim((string)$r));
  if ($r === 'superadmin') return 'super_admin';
  if ($r === 'super_admin') return 'super_admin';
  if ($r === 'manager') return 'manager';
  return 'user';
};

$filtered = $users;
if ($q !== '' || $roleFilter !== '' || $statusFilter !== '') {
  $filtered = array_values(array_filter($users, function($u) use ($q, $roleFilter, $statusFilter, $toLower, $pos, $normRole) {
    $uRole = $normRole($u->role ?? 'user');
    $active = isset($u->is_active) ? (int)$u->is_active : 1;

    if ($roleFilter !== '' && $uRole !== $roleFilter) return false;
    if ($statusFilter !== '' && (string)$active !== (string)$statusFilter) return false;

    if ($q !== '') {
      $hay = $toLower(($u->name ?? '') . ' ' . ($u->username ?? '') . ' ' . ($u->email ?? ''));
      $needle = $toLower($q);
      if ($pos($hay, $needle) === false) return false;
    }
    return true;
  }));
}

// ===== إحصائيات =====
$total = count($users);
$superCount = $managerCount = $userCount = 0;
$activeCount = $inactiveCount = 0;

foreach ($users as $u) {
  $r = $normRole($u->role ?? 'user');
  if ($r === 'super_admin') $superCount++;
  elseif ($r === 'manager') $managerCount++;
  else $userCount++;

  $a = isset($u->is_active) ? (int)$u->is_active : 1;
  if ($a === 1) $activeCount++; else $inactiveCount++;
}

function roleBadgeText($r) {
  if ($r === 'super_admin') return 'سوبر أدمن';
  if ($r === 'manager') return 'مدير';
  return 'موظف';
}
?>

<div class="container py-4">

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0">إدارة المستخدمين</h3>

    <div class="d-flex gap-2 align-items-center">
      <?php if ($canManageUsers): ?>
        <a class="btn btn-primary" href="<?php echo URLROOT; ?>/index.php?page=users/add">
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
      <form class="row g-2 align-items-center" method="GET" action="<?php echo URLROOT; ?>/index.php">
        <input type="hidden" name="page" value="users/index">

        <div class="col-md-5">
          <div class="input-group">
            <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="ابحث بالاسم أو البريد...">
            <button class="btn btn-outline-secondary" type="submit"><i class="fa fa-search"></i></button>
          </div>
        </div>

        <div class="col-md-3">
          <select name="role" class="form-select">
            <option value="">كل الأدوار</option>
            <option value="super_admin" <?php echo ($roleFilter==='super_admin')?'selected':''; ?>>سوبر أدمن</option>
            <option value="manager" <?php echo ($roleFilter==='manager')?'selected':''; ?>>مدير</option>
            <option value="user" <?php echo ($roleFilter==='user')?'selected':''; ?>>موظف</option>
          </select>
        </div>

        <div class="col-md-2">
          <select name="status" class="form-select">
            <option value="">كل الحالات</option>
            <option value="1" <?php echo ($statusFilter==='1')?'selected':''; ?>>نشط</option>
            <option value="0" <?php echo ($statusFilter==='0')?'selected':''; ?>>مُعطّل</option>
          </select>
        </div>

        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-primary w-100" type="submit"><i class="fa fa-filter"></i> تطبيق</button>
          <a class="btn btn-outline-secondary w-100" href="<?php echo URLROOT; ?>/index.php?page=users/index">إعادة ضبط</a>
        </div>
      </form>

      <!-- إحصائيات -->
      <div class="mt-3 d-flex flex-wrap gap-2">
        <span class="badge bg-dark">الإجمالي: <?php echo (int)$total; ?></span>
        <span class="badge bg-success">نشط: <?php echo (int)$activeCount; ?></span>
        <span class="badge bg-secondary">مُعطّل: <?php echo (int)$inactiveCount; ?></span>
        <span class="badge bg-primary">سوبر أدمن: <?php echo (int)$superCount; ?></span>
        <span class="badge bg-danger">مدير: <?php echo (int)$managerCount; ?></span>
        <span class="badge bg-info text-dark">موظف: <?php echo (int)$userCount; ?></span>
      </div>
    </div>
  </div>

  <!-- جدول -->
  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th class="text-end">الاسم</th>
              <th class="text-end">البريد الإلكتروني</th>
              <th class="text-end">(الصلاحية) الدور</th>
              <th class="text-end">تاريخ التسجيل</th>
              <th class="text-end">الحالة</th>
              <th class="text-center">إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($filtered)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">لا يوجد مستخدمين مسجلين.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($filtered as $u): ?>
                <?php
                  $name = $u->name ?? ($u->username ?? '-');
                  $email = $u->email ?? '-';
                  $r = $normRole($u->role ?? 'user');
                  $createdAt = !empty($u->created_at) ? date('Y-m-d', strtotime($u->created_at)) : '-';
                  $uid = (int)($u->id ?? 0);
                  $active = isset($u->is_active) ? (int)$u->is_active : 1;
                ?>
                <tr>
                  <td class="text-end"><?php echo htmlspecialchars($name); ?></td>
                  <td class="text-end" dir="ltr"><?php echo htmlspecialchars($email); ?></td>
                  <td class="text-end">
                    <?php
                      $txt = roleBadgeText($r);
                      $cls = ($r==='super_admin') ? 'bg-dark' : (($r==='manager') ? 'bg-danger' : 'bg-info text-dark');
                    ?>
                    <span class="badge <?php echo $cls; ?>"><?php echo $txt; ?></span>
                  </td>
                  <td class="text-end" dir="ltr"><?php echo htmlspecialchars($createdAt); ?></td>
                  <td class="text-end">
                    <?php if ($active === 1): ?>
                      <span class="badge bg-success">نشط</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">مُعطّل</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if ($canManageUsers): ?>
                      <a class="btn btn-sm btn-outline-primary" href="<?php echo URLROOT; ?>/index.php?page=users/edit&id=<?php echo $uid; ?>">
                        <i class="fa fa-edit"></i>
                      </a>

                      <?php if ((int)($_SESSION['user_id'] ?? 0) !== $uid): ?>
                        <form method="POST" action="<?php echo URLROOT; ?>/index.php?page=users/delete" class="d-inline">
                          <input type="hidden" name="id" value="<?php echo $uid; ?>">
                          <button type="submit" class="btn btn-sm <?php echo ($active===1)?'btn-outline-danger':'btn-outline-success'; ?>"
                                  onclick="return confirm('<?php echo ($active===1)?'تعطيل المستخدم؟':'تفعيل المستخدم؟'; ?>');">
                            <i class="fa <?php echo ($active===1)?'fa-ban':'fa-check'; ?>"></i>
                          </button>
                        </form>
                      <?php else: ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="لا يمكنك تعطيل حسابك">
                          <i class="fa fa-ban"></i>
                        </button>
                      <?php endif; ?>
                    <?php else: ?>
                      —
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
