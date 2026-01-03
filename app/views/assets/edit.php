<?php require APPROOT . '/views/inc/header.php'; ?>

<style>
  .wrap{ direction: rtl; text-align: right; }
  .card{ border-radius:12px; }
  .btn-round{ border-radius:10px !important; }
</style>

<div class="container-fluid wrap py-3">
  <?php if (function_exists('flash')) { flash('asset_msg'); flash('access_denied'); } ?>

  <?php
    $locations = $data['locations'] ?? [];
    $users = $data['users_list'] ?? [];
    $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');

    $locById = [];
    foreach ($locations as $loc) { $locById[$loc->id] = $loc; }

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

    $allowedTypes = ['Laptop','Desktop','Printer','Monitor','Server','Network','Other'];
    $statuses = ['Active','Broken','Repair','Retired','Lost'];
  ?>

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h4 class="m-0 fw-bold"><i class="bi bi-pencil"></i> تعديل جهاز (#<?= (int)($data['id'] ?? 0) ?>)</h4>
    <a class="btn btn-outline-secondary btn-round" href="index.php?page=assets/index">
      <i class="bi bi-arrow-right"></i> رجوع
    </a>
  </div>

  <div class="card shadow-sm mt-3">
    <div class="card-body">
      <form method="post" action="index.php?page=assets/edit&id=<?= (int)($data['id'] ?? 0) ?>">

        <div class="row g-3">

          <div class="col-12 col-lg-4">
            <label class="form-label">Tag (رقم الجهاز) <span class="text-danger">*</span></label>
            <input class="form-control" name="asset_tag" required
                   value="<?= htmlspecialchars($data['asset_tag'] ?? '') ?>">
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">Serial</label>
            <input class="form-control" name="serial_no"
                   value="<?= htmlspecialchars($data['serial_no'] ?? '') ?>">
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">النوع <span class="text-danger">*</span></label>
            <select class="form-select" name="type" required>
              <option value="">— اختر النوع —</option>
              <?php foreach ($allowedTypes as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= (($data['type'] ?? '') === $t) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">الماركة</label>
            <input class="form-control" name="brand"
                   value="<?= htmlspecialchars($data['brand'] ?? '') ?>">
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">الموديل</label>
            <input class="form-control" name="model"
                   value="<?= htmlspecialchars($data['model'] ?? '') ?>">
          </div>

          <div class="col-12">
            <label class="form-label">الموقع</label>
            <select class="form-select" name="location_id" required>
              <option value="">— اختر موقع الجهاز —</option>
              <?php foreach ($locations as $loc): ?>
                <?php
                  $label = buildLocationPath($loc, $locById);
                  $selected = (!empty($data['location_id']) && (int)$data['location_id'] === (int)$loc->id) ? 'selected' : '';
                ?>
                <option value="<?= (int)$loc->id ?>" <?= $selected ?>>
                  <?= htmlspecialchars($label) ?><?php if (!empty($loc->type)): ?> (<?= htmlspecialchars($loc->type) ?>)<?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php if (in_array($role, ['superadmin','manager'], true)): ?>
            <div class="col-12">
              <label class="form-label">الموظف المستلم (اختياري)</label>
              <select class="form-select" name="assigned_to">
                <option value="">— بدون تعيين / في المخزن —</option>
                <?php foreach ($users as $u): ?>
                  <?php
                    $name = $u->name ?? $u->username ?? $u->email ?? ('User#'.$u->id);
                    $selected = (!empty($data['assigned_to']) && (int)$data['assigned_to'] === (int)$u->id) ? 'selected' : '';
                  ?>
                  <option value="<?= (int)$u->id ?>" <?= $selected ?>>
                    <?= htmlspecialchars($name) ?><?php if (!empty($u->role)): ?> (<?= htmlspecialchars($u->role) ?>)<?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="col-12 col-lg-4">
            <label class="form-label">الحالة</label>
            <select class="form-select" name="status">
              <?php foreach ($statuses as $s): ?>
                <option value="<?= htmlspecialchars($s) ?>" <?= (($data['status'] ?? 'Active') === $s) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

        </div>

        <div class="mt-3 d-flex gap-2 flex-wrap">
          <button class="btn btn-success btn-round" type="submit">
            <i class="bi bi-save"></i> حفظ التعديلات
          </button>

          <form method="post" action="index.php?page=assets/delete"
                onsubmit="return confirm('متأكد من حذف هذا الجهاز؟');">
            <input type="hidden" name="id" value="<?= (int)($data['id'] ?? 0) ?>">
            <button class="btn btn-outline-danger btn-round" type="submit">
              <i class="bi bi-trash"></i> حذف الجهاز
            </button>
          </form>
        </div>

      </form>
    </div>
  </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
