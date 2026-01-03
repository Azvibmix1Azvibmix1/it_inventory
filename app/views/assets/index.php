<?php require APPROOT . '/views/inc/header.php'; ?>

<style>
  .page-wrap{ direction: rtl; text-align: right; }
  .card{ border-radius: 12px; }
  .btn-round{ border-radius: 10px !important; }
  .table th{ white-space: nowrap; }
  @media print {
    .no-print{ display:none !important; }
    body{ background:#fff !important; }
    .card{ box-shadow:none !important; border:0 !important; }
  }
</style>

<div class="container-fluid page-wrap py-3">

  <?php if (function_exists('flash')) { flash('asset_msg'); flash('access_denied'); } ?>

  <?php
    $assets   = $data['assets'] ?? [];
    $locations = $data['locations'] ?? [];
    $filters  = $data['filters'] ?? ['location_id'=>0,'q'=>'','include_children'=>0];

    $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');
    $canAddBtn = !empty($data['can_add_asset'] ?? false) || !empty($locations); // UI فقط (التحقق الحقيقي في السيرفر)
  ?>

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 no-print">
    <div>
      <h4 class="m-0 fw-bold">
        <i class="bi bi-pc-display"></i>
        الأجهزة
      </h4>
      <div class="text-muted mt-1">فلترة الأجهزة حسب الموقع (مثل: معمل A) والبحث بالتاغ/السيريال/الموديل.</div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <?php if ($canAddBtn): ?>
        <a href="index.php?page=assets/add" class="btn btn-success btn-round">
          <i class="bi bi-plus-lg"></i> إضافة جهاز
        </a>
      <?php endif; ?>

      <button type="button" class="btn btn-outline-secondary btn-round" onclick="window.print()">
        <i class="bi bi-printer"></i> طباعة القائمة
      </button>
    </div>
  </div>

  <!-- Filters -->
  <div class="card shadow-sm mt-3 no-print">
    <div class="card-body">
      <form method="get" action="index.php" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="assets/index">

        <div class="col-12 col-lg-4">
          <label class="form-label">الموقع</label>
          <select class="form-select" name="location_id">
            <option value="0">— كل المواقع —</option>
            <?php foreach ($locations as $loc): ?>
              <option value="<?= (int)$loc->id ?>" <?= ((int)($filters['location_id'] ?? 0) === (int)$loc->id) ? 'selected' : '' ?>>
                <?= htmlspecialchars($loc->name_ar ?? ('موقع #'.$loc->id)) ?>
                <?php if (!empty($loc->type)): ?> (<?= htmlspecialchars($loc->type) ?>)<?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($locations) && $role === 'user'): ?>
            <div class="text-muted small mt-1">لا توجد مواقع متاحة لك حالياً (حسب الصلاحيات).</div>
          <?php endif; ?>
        </div>

        <div class="col-12 col-lg-5">
          <label class="form-label">بحث</label>
          <input type="text" class="form-control" name="q" placeholder="Tag / Serial / Brand / Model"
                 value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
        </div>

        <div class="col-12 col-lg-2">
          <label class="form-label">خيارات</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="include_children" value="1" id="incKids"
              <?= !empty($filters['include_children']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="incKids">يشمل التوابع</label>
          </div>
        </div>

        <div class="col-12 col-lg-1 d-grid">
          <button class="btn btn-primary btn-round" type="submit">
            <i class="bi bi-funnel"></i>
          </button>
        </div>

        <div class="col-12">
          <a class="text-decoration-none" href="index.php?page=assets/index">
            <i class="bi bi-x-circle"></i> مسح الفلاتر
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card shadow-sm mt-3">
    <div class="card-body">

      <?php if (empty($assets)): ?>
        <div class="text-muted">لا توجد أجهزة مطابقة للفلاتر الحالية.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
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
                  $locationName = $a->location_name ?? 'غير محدد';
                  $status = $a->status ?? 'Active';
                ?>
                <tr>
                  <td class="fw-bold"><?= htmlspecialchars($tag) ?></td>
                  <td><?= htmlspecialchars($type) ?></td>
                  <td><?= htmlspecialchars($brandModel) ?></td>
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
                        <a class="btn btn-sm btn-outline-warning btn-round"
                           href="index.php?page=assets/edit&id=<?= (int)$a->id ?>">
                          <i class="bi bi-pencil"></i> تعديل
                        </a>
                      <?php endif; ?>

                      <?php if ($canDelete): ?>
                        <form method="post" action="index.php?page=assets/delete"
                              onsubmit="return confirm('متأكد من حذف هذا الجهاز؟');">
                          <input type="hidden" name="id" value="<?= (int)$a->id ?>">
                          <button class="btn btn-sm btn-outline-danger btn-round" type="submit">
                            <i class="bi bi-trash"></i> حذف
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <div class="text-muted small mt-2 no-print">
    ملاحظة: زر “طباعة القائمة” يطبع النتائج الحالية حسب الفلاتر. (صفحة طباعة مع باركود نضيفها بالمرحلة القادمة).
  </div>

</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
