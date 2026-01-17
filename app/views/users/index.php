<?php
require APPROOT . '/views/inc/header.php';

/**
 * صفحة: إدارة المستخدمين (UI/UX رسمي)
 * - لا تغييرات DB
 * - فلترة بالـ GET (q/role/status)
 * - إحصائيات أعلى الجدول
 */

$users = $data['users'] ?? $data['users_list'] ?? $data['users'] ?? [];
if (!is_array($users) && !is_object($users)) $users = [];

$q            = trim((string)($_GET['q'] ?? ''));
$roleFilter   = trim((string)($_GET['role'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));

$normRole = function($r) {
  $r = strtolower(trim((string)$r));
  if (in_array($r, ['super', 'superadmin', 'super_admin', 'super-admin', 'admin'])) return 'super_admin';
  if (in_array($r, ['manager', 'mgr'])) return 'manager';
  return 'user';
};

$roleText = function($r) {
  if ($r === 'super_admin') return 'سوبر أدمن';
  if ($r === 'manager')    return 'مدير';
  return 'موظف';
};

$statusText = function($a) {
  return ((int)$a === 1) ? 'نشط' : 'مُعطّل';
};

$pageUrl = function(string $page, array $params = []) {
  $base = rtrim(URLROOT, '/') . '/index.php?page=' . urlencode($page);
  if (!empty($params)) {
    foreach ($params as $k => $v) {
      if ($v === '' || $v === null) continue;
      $base .= '&' . urlencode((string)$k) . '=' . urlencode((string)$v);
    }
  }
  return $base;
};

$filtered = array_values(array_filter((array)$users, function($u) use ($q, $roleFilter, $statusFilter, $normRole) {
  $uRole  = $normRole($u->role ?? 'user');
  $active = isset($u->is_active) ? (int)$u->is_active : 1;

  if ($roleFilter !== '' && $uRole !== $roleFilter) return false;
  if ($statusFilter !== '' && (string)$active !== (string)$statusFilter) return false;

  if ($q !== '') {
    $hay = strtolower(trim(($u->name ?? '') . ' ' . ($u->username ?? '') . ' ' . ($u->email ?? '')));
    $needle = strtolower($q);
    if (mb_strpos($hay, $needle) === false) return false;
  }
  return true;
}));

// إحصائيات
$total = count($users);
$superCount = $managerCount = $userCount = 0;
$activeCount = $inactiveCount = 0;

foreach ((array)$users as $u) {
  $r = $normRole($u->role ?? 'user');
  if ($r === 'super_admin') $superCount++;
  elseif ($r === 'manager') $managerCount++;
  else $userCount++;

  $a = isset($u->is_active) ? (int)$u->is_active : 1;
  if ($a === 1) $activeCount++;
  else $inactiveCount++;
}

$addUrl   = $pageUrl('users/add');
$resetUrl = $pageUrl('users/index');
$applyUrl = $pageUrl('users/index'); // نفس الصفحة

// Chips
$chips = [];
if ($q !== '') $chips[] = ['label' => 'بحث', 'value' => $q, 'key' => 'q'];
if ($roleFilter !== '') $chips[] = ['label' => 'الدور', 'value' => $roleText($roleFilter), 'key' => 'role'];
if ($statusFilter !== '') $chips[] = ['label' => 'الحالة', 'value' => ((string)$statusFilter === '1' ? 'نشط' : 'مُعطّل'), 'key' => 'status'];
?>

<style>
  /* Official neutral theme (soft gray) */
  .u-page{max-width:1200px;margin:0 auto}
  .u-card{background:#fff;border:1px solid #e9ecef;border-radius:14px;box-shadow:0 8px 24px rgba(16,24,40,.06)}
  .u-toolbar{background:#f8f9fb;border:1px solid #e9ecef;border-radius:12px;padding:10px}
  .u-chip{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:#f1f3f5;border:1px solid #e9ecef;font-size:.9rem}
  .u-chip a{color:#6c757d;text-decoration:none}
  .u-stat{background:#fff;border:1px solid #e9ecef;border-radius:12px;padding:10px 12px}
  .u-stat .n{font-weight:800;font-size:1.1rem}
  .u-sub{color:#6c757d;font-size:.9rem}
  .table thead th{background:#f8f9fb}
  .badge-role{border-radius:999px;padding:.35rem .6rem;font-weight:700}
  .badge-soft-dark{background:#111827;color:#fff}
  .badge-soft-gray{background:#e9ecef;color:#111827}
  .badge-soft-blue{background:#e7f1ff;color:#0b5ed7}
  .btn-ghost{background:#fff;border:1px solid #e9ecef}
  .btn-ghost:hover{background:#f8f9fb}
</style>

<div class="u-page py-4">

  <!-- Page header -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h3 class="mb-1">إدارة المستخدمين</h3>
      <div class="u-sub">إدارة الحسابات، الأدوار، وحالة التفعيل بشكل منظم.</div>
    </div>

    <div class="d-flex gap-2">
      <a href="<?= $addUrl; ?>" class="btn btn-dark">
        <i class="bi bi-person-plus me-1"></i> إضافة مستخدم
      </a>
      <a href="<?= $resetUrl; ?>" class="btn btn-ghost">
        <i class="bi bi-arrow-counterclockwise me-1"></i> إعادة ضبط
      </a>
    </div>
  </div>

  <!-- Main card -->
  <div class="u-card p-3 p-md-4">

    <!-- Filters toolbar -->
    <form class="u-toolbar mb-3" method="get" action="<?= $applyUrl; ?>">
      <input type="hidden" name="page" value="users/index">

      <div class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q); ?>" placeholder="ابحث بالاسم أو اسم المستخدم أو البريد...">
          </div>
        </div>

        <div class="col-6 col-md-3">
          <select class="form-select" name="role">
            <option value="">كل الأدوار</option>
            <option value="super_admin" <?= $roleFilter==='super_admin'?'selected':''; ?>>سوبر أدمن</option>
            <option value="manager" <?= $roleFilter==='manager'?'selected':''; ?>>مدير</option>
            <option value="user" <?= $roleFilter==='user'?'selected':''; ?>>موظف</option>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <select class="form-select" name="status">
            <option value="">كل الحالات</option>
            <option value="1" <?= (string)$statusFilter==='1'?'selected':''; ?>>نشط</option>
            <option value="0" <?= (string)$statusFilter==='0'?'selected':''; ?>>مُعطّل</option>
          </select>
        </div>

        <div class="col-12 col-md-2 d-grid">
          <button class="btn btn-primary" type="submit">
            <i class="bi bi-funnel me-1"></i> تطبيق
          </button>
        </div>
      </div>

      <?php if (!empty($chips)): ?>
        <div class="mt-2 d-flex flex-wrap gap-2">
          <?php foreach ($chips as $c): ?>
            <?php
              $params = ['q'=>$q,'role'=>$roleFilter,'status'=>$statusFilter];
              $params[$c['key']] = '';
              $clearUrl = $pageUrl('users/index', $params);
            ?>
            <span class="u-chip">
              <span class="text-muted"><?= htmlspecialchars($c['label']); ?>:</span>
              <strong><?= htmlspecialchars($c['value']); ?></strong>
              <a href="<?= $clearUrl; ?>" title="إزالة">×</a>
            </span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </form>

    <!-- Summary -->
    <div class="row g-2 mb-3">
      <div class="col-6 col-md-2"><div class="u-stat"><div class="u-sub">الإجمالي</div><div class="n"><?= (int)$total; ?></div></div></div>
      <div class="col-6 col-md-2"><div class="u-stat"><div class="u-sub">نشط</div><div class="n"><?= (int)$activeCount; ?></div></div></div>
      <div class="col-6 col-md-2"><div class="u-stat"><div class="u-sub">مُعطّل</div><div class="n"><?= (int)$inactiveCount; ?></div></div></div>
      <div class="col-6 col-md-2"><div class="u-stat"><div class="u-sub">سوبر أدمن</div><div class="n"><?= (int)$superCount; ?></div></div></div>
      <div class="col-6 col-md-2"><div class="u-stat"><div class="u-sub">مدير</div><div class="n"><?= (int)$managerCount; ?></div></div></div>
      <div class="col-6 col-md-2"><div class="u-stat"><div class="u-sub">موظف</div><div class="n"><?= (int)$userCount; ?></div></div></div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th style="width:32%">المستخدم</th>
            <th style="width:18%">الدور</th>
            <th style="width:16%">تاريخ التسجيل</th>
            <th style="width:14%">الحالة</th>
            <th style="width:20%" class="text-end">إجراءات</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($filtered)): ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">لا توجد نتائج مطابقة.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($filtered as $u): ?>
            <?php
              $uid   = (int)($u->id ?? 0);
              $name  = $u->name ?? ($u->username ?? '-');
              $email = $u->email ?? '-';
              $r     = $normRole($u->role ?? 'user');
              $active = isset($u->is_active) ? (int)$u->is_active : 1;
              $createdAt = !empty($u->created_at) ? date('Y-m-d', strtotime($u->created_at)) : '-';

              $editUrl = $pageUrl('users/edit', ['id'=>$uid]);

              // غيّر المسار هنا لو عندك اسم مختلف (مثلاً users/toggleStatus)
              $toggleUrl = $pageUrl('users/toggle', ['id'=>$uid]);
            ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width:38px;height:38px;font-weight:800;">
                    <?= strtoupper(mb_substr((string)$name, 0, 1)); ?>
                  </div>
                  <div>
                    <div class="fw-bold"><?= htmlspecialchars($name); ?></div>
                    <div class="u-sub"><?= htmlspecialchars($email); ?></div>
                  </div>
                </div>
              </td>

              <td>
                <?php if ($r === 'super_admin'): ?>
                  <span class="badge badge-role badge-soft-dark">سوبر أدمن</span>
                <?php elseif ($r === 'manager'): ?>
                  <span class="badge badge-role badge-soft-blue">مدير</span>
                <?php else: ?>
                  <span class="badge badge-role badge-soft-gray">موظف</span>
                <?php endif; ?>
              </td>

              <td class="text-muted"><?= htmlspecialchars($createdAt); ?></td>

              <td>
                <?php if ($active === 1): ?>
                  <span class="badge bg-success-subtle text-success border border-success-subtle">نشط</span>
                <?php else: ?>
                  <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">مُعطّل</span>
                <?php endif; ?>
              </td>

              <td class="text-end">
                <div class="btn-group">
                  <a href="<?= $editUrl; ?>" class="btn btn-ghost btn-sm">
                    <i class="bi bi-pencil-square me-1"></i> تعديل
                  </a>

                  <!-- إجراء رسمي: تفعيل/تعطيل -->
                  <a href="<?= $toggleUrl; ?>" class="btn btn-ghost btn-sm">
                    <?php if ($active === 1): ?>
                      <i class="bi bi-slash-circle me-1"></i> تعطيل
                    <?php else: ?>
                      <i class="bi bi-check2-circle me-1"></i> تفعيل
                    <?php endif; ?>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
