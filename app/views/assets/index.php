<?php require APPROOT . '/views/inc/header.php'; ?>
<style>
  .no-print { display: inline-block; }
  @media print {
    .no-print, .no-print * { display: none !important; }
    .table { font-size: 12px; }
  }
</style>

<?php
  $assets = $data['assets'] ?? [];
  $locations = $data['locations'] ?? [];

  // خريطة أسماء المواقع حسب ID (fallback لو الاستعلام رجّع رقم بدل الاسم)
  $locNameById = [];
  foreach ($locations as $loc) {
    $locNameById[(int)$loc->id] = $loc->name_ar ?? ('موقع #' . $loc->id);
  }

  $filters = $data['filters'] ?? ['location_id'=>0,'q'=>'','include_children'=>0];
  $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');
  $canAddBtn = !empty($data['can_add_asset'] ?? false) || !empty($locations); // UI فقط (التحقق الحقيقي في السيرفر)
?>

<div class="container-fluid py-3" dir="rtl">

  <?php if (function_exists('flash')) { flash('asset_msg'); flash('access_denied'); } ?>

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h4 class="m-0 fw-bold">الأجهزة</h4>
      <div class="text-muted">فلترة الأجهزة حسب الموقع (مثل: معمل A) والبحث بالتاغ/السيريال/الموديل.</div>
    </div>

    <div class="d-flex gap-2 no-print">
      <?php if ($canAddBtn): ?>
        <a class="btn btn-success" href="index.php?page=assets/add">إضافة جهاز</a>
      <?php endif; ?>

      <button class="btn btn-outline-secondary" onclick="window.print()">طباعة القائمة</button>
    </div>
  </div>

  <!-- Filters -->
  <div class="card mb-3 no-print">
    <div class="card-body">
      <form method="get" action="index.php">
        <input type="hidden" name="page" value="assets/index">

        <div class="row g-3 align-items-end">
          <div class="col-12 col-lg-4">
            <label class="form-label">الموقع</label>
            <select class="form-select" name="location_id">
              <option value="0">— كل المواقع —</option>
              <?php if (!empty($locations)): ?>
                <?php foreach ($locations as $loc): ?>
                  <?php
                    $selected = ((int)($filters['location_id'] ?? 0) === (int)$loc->id) ? 'selected' : '';
                    $locLabel = $loc->name_ar ?? ('موقع #'.$loc->id);
                  ?>
                  <option value="<?= (int)$loc->id ?>" <?= $selected ?>>
                    <?= htmlspecialchars($locLabel) ?><?php if (!empty($loc->type)): ?> (<?= htmlspecialchars($loc->type) ?>)<?php endif; ?>
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="0" disabled>لا توجد مواقع متاحة لك حالياً (حسب الصلاحيات).</option>
              <?php endif; ?>
            </select>
          </div>

          <div class="col-12 col-lg-5">
            <label class="form-label">بحث</label>
            <input class="form-control" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Tag / Serial / Brand / Model">
          </div>

          <div class="col-12 col-lg-3">
            <label class="form-label">خيارات</label>
            <div class="form-check">
              <?php $checked = !empty($filters['include_children']) ? 'checked' : ''; ?>
              <input class="form-check-input" type="checkbox" id="include_children" name="include_children" value="1" <?= $checked ?>>
              <label class="form-check-label" for="include_children">يشمل التوابع</label>
            </div>
          </div>

          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">تطبيق</button>
            <a class="btn btn-outline-secondary" href="index.php?page=assets/index">مسح الفلاتر</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead>
            <tr>
              <th>Tag</th>
              <th>النوع</th>
              <th>الماركة / الموديل</th>
              <th>Serial</th>
              <th>الموقع</th>
              <th>الحالة</th>
              <th class="no-print">إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($assets)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted p-4">لا توجد أجهزة مطابقة للفلاتر الحالية.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($assets as $a): ?>
                <?php
                  $locId = (int)($a->location_id ?? 0);
                  $canEdit = function_exists('canManageLocation')
                    ? (canManageLocation($locId, 'edit') || canManageLocation($locId, 'manage'))
                    : true;

                  $canDelete = function_exists('canManageLocation')
                    ? (canManageLocation($locId, 'delete') || canManageLocation($locId, 'manage'))
                    : true;

                  $tag = $a->asset_tag ?? '';
                  $type = $a->type ?? '';
                  $brandModel = trim(($a->brand ?? '') . ' - ' . ($a->model ?? ''));
                  $serial = $a->serial_no ?? '';

                  $rawLocationName = (string)($a->location_name ?? '');
                  $locationName = trim($rawLocationName);
                  // أحياناً الاستعلام قد يرجّع رقم (مثل 1) بدل اسم الموقع
                  if ($locationName === '' || ctype_digit($locationName)) {
                    $locationName = $locNameById[$locId] ?? ('موقع #' . $locId);
                  }

                  $status = $a->status ?? 'Active';
                ?>
                <tr>
                  <td class="fw-bold"><?= htmlspecialchars($tag) ?></td>
                  <td><?= htmlspecialchars($type) ?></td>
                  <td><?= htmlspecialchars($brandModel !== '' ? $brandModel : '-') ?></td>
                  <td><?= htmlspecialchars($serial) ?></td>
                  <td><?= htmlspecialchars($locationName) ?></td>
                  <td>
                    <span class="badge bg-<?= ($status === 'Active') ? 'success' : 'secondary' ?>">
                      <?= htmlspecialchars($status) ?>
                    </span>
                  </td>
                  <td class="no-print">
                    <div class="d-flex gap-2 flex-wrap">
                      <?php if ($canEdit): ?>
                        <a class="btn btn-sm btn-outline-warning btn-round" href="index.php?page=assets/edit&id=<?= (int)$a->id ?>">تعديل</a>
                      <?php endif; ?>
                      <?php if ($canDelete): ?>
                        <a class="btn btn-sm btn-outline-danger btn-round"
                           onclick="return confirm('هل أنت متأكد من الحذف؟')"
                           href="index.php?page=assets/delete&id=<?= (int)$a->id ?>">حذف</a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="p-3 text-muted small">
        ملاحظة: زر “طباعة القائمة” يطبع النتائج الحالية حسب الفلاتر. (صفحة طباعة مع باركود نضيفها بالمرحلة القادمة).
      </div>
    </div>
  </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
