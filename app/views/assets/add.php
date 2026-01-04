<?php require APPROOT . '/views/inc/header.php'; ?>

<?php
// بيانات من الكنترولر
$locations   = $data['locations']   ?? [];
$users_list  = $data['users_list']  ?? [];
$asset_err   = $data['asset_err']   ?? '';

if (!function_exists('buildLocationPath')) {
  function buildLocationPath($loc, $locById) {
    $parts = [ $loc->name_ar ?? ('موقع#'.$loc->id) ];
    $current = $loc;
    while (!empty($current->parent_id) && isset($locById[$current->parent_id])) {
      $current = $locById[$current->parent_id];
      array_unshift($parts, $current->name_ar ?? ('موقع#'.$current->id));
    }
    return implode(' › ', $parts);
  }
}

$locById = [];
foreach ($locations as $loc) { $locById[$loc->id] = $loc; }

$allowedTypes = ['Laptop','Desktop','Printer','Monitor','Server','Network','Other'];
?>

<div class="container-fluid py-3" dir="rtl">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">إضافة جهاز</h4>
    <a class="btn btn-outline-secondary" href="index.php?page=assets/index">رجوع</a>
  </div>

  <?php if (!empty($asset_err)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($asset_err) ?></div>
  <?php endif; ?>

  <?php if (empty($locations)): ?>
    <div class="alert alert-warning">
      لا توجد لديك مواقع مسموح لك الإضافة عليها. اطلب من السوبر أدمن منحك صلاحية على موقع.
    </div>
  <?php else: ?>

    <div class="card">
      <div class="card-body">

        <form method="post" action="index.php?page=assets/add" autocomplete="off">

          <!-- نخليه مخفي: التاق يتولد تلقائيًا بعد الحفظ -->
          <input type="hidden" name="asset_tag" value="">

          <div class="mb-3">
            <label class="form-label">Tag (رقم الجهاز) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" value="(AST-000001)" readonly>
            <div class="form-text text-muted">
              يتم توليد التاق تلقائيًا لتفادي التكرار.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Serial (اختياري)</label>
            <input type="text" name="serial_no" class="form-control"
                   value="<?= htmlspecialchars($data['serial_no'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">النوع <span class="text-danger">*</span></label>
            <select name="type" class="form-select" required>
              <option value="">— اختر النوع —</option>
              <?php foreach ($allowedTypes as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>"
                  <?= (!empty($data['type']) && $data['type'] === $t) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">الماركة (اختياري)</label>
              <input type="text" name="brand" class="form-control"
                     value="<?= htmlspecialchars($data['brand'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">الموديل (اختياري)</label>
              <input type="text" name="model" class="form-control"
                     value="<?= htmlspecialchars($data['model'] ?? '') ?>">
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">تاريخ الشراء (اختياري)</label>
              <div class="form-text text-muted">الصيغة: YYYY-MM-DD</div>

              <input type="date" name="purchase_date" class="form-control"
                     value="<?= htmlspecialchars($data['purchase_date'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">انتهاء الضمان (اختياري)</label>
              <div class="form-text text-muted">الصيغة: YYYY-MM-DD</div>

              <input type="date" name="warranty_expiry" class="form-control"
              
                     value="<?= htmlspecialchars($data['warranty_expiry'] ?? '') ?>">
                     
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">الموقع <span class="text-danger">*</span></label>
              <select name="location_id" class="form-select" required>
                <option value="">— اختر موقع الجهاز —</option>
                <?php foreach ($locations as $loc): ?>
                  <?php
                    $label = buildLocationPath($loc, $locById);
                    $selected = (!empty($data['location_id']) && (int)$data['location_id'] === (int)$loc->id) ? 'selected' : '';
                  ?>
                  <option value="<?= (int)$loc->id ?>" <?= $selected ?>>
                    <?= htmlspecialchars($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text text-muted">يتم جلب المواقع من صفحة “المواقع والمباني”.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">الحالة</label>
              <select name="status" class="form-select">
                <?php $st = $data['status'] ?? 'Active'; ?>
                <option value="Active"  <?= ($st === 'Active') ? 'selected' : '' ?>>Active</option>
                <option value="Retired" <?= ($st === 'Retired') ? 'selected' : '' ?>>Retired</option>
                <option value="Repair"  <?= ($st === 'Repair') ? 'selected' : '' ?>>Repair</option>
              </select>
            </div>
          </div>

          <?php if (!empty($users_list)): ?>
            <div class="mt-3">
              <label class="form-label">الموظف المستلم (اختياري)</label>
              <select name="assigned_to" class="form-select">
                <option value="">— بدون تعيين / في المخزن —</option>
                <?php foreach ($users_list as $u): ?>
                  <?php
                    $name = $u->name ?? $u->username ?? $u->email ?? ('User#'.$u->id);
                    $role = $u->role ?? '';
                    $selected = (!empty($data['assigned_to']) && (int)$data['assigned_to'] === (int)$u->id) ? 'selected' : '';
                  ?>
                  <option value="<?= (int)$u->id ?>" <?= $selected ?>>
                    <?= htmlspecialchars($name) ?><?= $role ? ' ('.$role.')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text text-muted">للسوبر أدمن/المدير فقط.</div>
            </div>
          <?php endif; ?>

          <div class="mt-3">
            <label class="form-label">ملاحظات (اختياري)</label>
            <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($data['notes'] ?? '') ?></textarea>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-primary">حفظ الجهاز</button>
            <a class="btn btn-outline-secondary" href="index.php?page=assets/index">إلغاء</a>
          </div>

        </form>
      </div>
    </div>

  <?php endif; ?>

</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
