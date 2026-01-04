<?php require APPROOT . '/views/inc/header.php'; ?>

<style>
  .no-print { display: inline-block; }

  .assets-table { table-layout: fixed; width: 100%; }
  .assets-table th, .assets-table td { vertical-align: middle; }
  .assets-table th { white-space: nowrap; }
  .assets-table td { overflow: hidden; text-overflow: ellipsis; }

  /* أحجام الأعمدة */
  .col-actions { width: 140px; }
  .col-status  { width: 90px; }
  .col-loc     { width: 140px; }
  .col-serial  { width: 160px; }
  .col-warranty { width: 120px; }
  .col-brand   { width: 190px; }
  .col-type    { width: 120px; }
  .col-tag     { width: 280px; font-weight: 700; }
  .badge { font-size: 11px; padding: 4px 8px; }

  /* QR */
  .col-qr { width: 90px; text-align: center; }
  .qr { width: 64px; height: 64px; margin: 0 auto; }
  .qr img, .qr canvas { width: 64px !important; height: 64px !important; display:block; margin:0 auto; }

  /* إجراءات مرتبة */
  .actions-wrap { display: flex; gap: 6px; justify-content: flex-start; align-items: center; }
  .actions-wrap form { margin: 0; }

  /* خلّي التاغ ينكسر سطرين بدل ما يخرب الصف */
  .tag-wrap { white-space: normal; line-height: 1.2; }

  /* خط فاصل خفيف */
  .assets-table tbody tr { border-bottom: 1px solid rgba(0,0,0,.06); }

  /* ✅ توحيد خلفية كل الخلايا (حل مشكلة عمود الإجراءات/الـ QR اللي يطلع كأنه منفصل) */
  .assets-table tbody tr > td,
  .assets-table tbody tr > th {
    background-color: inherit !important;
  }

  /* ✅ طبّق الـ striped على كل الخلايا فعليًا (مش بس بعض الأعمدة) */
  .assets-table.table-striped > tbody > tr:nth-of-type(odd) > * {
    background-color: rgba(0,0,0,.03) !important;
  }
  .assets-table.table-striped > tbody > tr:nth-of-type(even) > * {
    background-color: transparent !important;
  }

  /* ✅ امنع العناصر داخل الخلايا من عمل خلفية مختلفة */
  .assets-table .actions-wrap,
  .assets-table .actions-wrap a,
  .assets-table .actions-wrap button,
  .assets-table .qr,
  .assets-table .qr img,
  .assets-table .qr canvas {
    background-color: transparent !important;
  }

  /* طباعة */
  @media print {
    .no-print, .no-print * { display: none !important; }
    .assets-table { font-size: 12px; }
    .qr { width: 80px; height: 80px; }
    .qr img, .qr canvas { width: 80px !important; height: 80px !important; }
  }


  /* ✅ Stripes من الجذور (بدون Bootstrap) */
.assets-table tbody tr.row-odd > td,
.assets-table tbody tr.row-odd > th {
  background: rgba(0,0,0,.04) !important;
}

.assets-table tbody tr.row-even > td,
.assets-table tbody tr.row-even > th {
  background: #fff !important;
}

/* حتى العناصر داخل الإجراءات/QR ما تغطي الخلفية */
.assets-table td, .assets-table th { background-clip: padding-box; }
.assets-table .actions-wrap,
.assets-table .actions-wrap * ,
.assets-table .qr,
.assets-table .qr * {
  background: transparent !important;
}

</style>


<?php
  $assets = $data['assets'] ?? [];
  $locations = $data['locations'] ?? [];
  $filters = $data['filters'] ?? ['location_id'=>0,'q'=>'','include_children'=>0];

  // خريطة أسماء المواقع
  $locNameById = [];
  foreach ($locations as $loc) {
    $locNameById[(int)$loc->id] = $loc->name_ar ?? ('موقع #' . $loc->id);
  }

  $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');
  $canAddBtn = !empty($data['can_add_asset'] ?? false) || !empty($locations);

  // base path لبناء روابط كاملة (عشان الجوال يفتحها)
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath;
  
  function warrantyBadgeHtml($dateStr) {
  $dateStr = trim((string)$dateStr);
  if ($dateStr === '' || $dateStr === '-') {
    return '<span class="text-muted">-</span>';
  }

  try {
    $wDate = new DateTime($dateStr);
    $today = new DateTime('today');
    $days = (int)$today->diff($wDate)->format('%r%a');

    $safeDate = htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8');

    if ($days < 0) {
      return '<span class="badge bg-danger" title="انتهى: '.$safeDate.'">منتهي</span>';
    } elseif ($days <= 30) {
      return '<span class="badge bg-warning text-dark" title="ينتهي: '.$safeDate.'">قريب ('.$days.' يوم)</span>';
    } else {
      return '<span class="badge bg-success" title="ينتهي: '.$safeDate.'">سليم ('.$days.' يوم)</span>';
    }
  } catch (Exception $e) {
    return '<span class="text-muted">-</span>';
  }
}

?>



<div class="container-fluid py-3" dir="rtl">

  <?php if (function_exists('flash')) { flash('asset_msg'); flash('access_denied'); } ?>

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h4 class="m-0 fw-bold">الأجهزة</h4>
      <div class="text-muted">   </div>
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
              <?php foreach ($locations as $loc): ?>
                <?php
                  $selected = ((int)($filters['location_id'] ?? 0) === (int)$loc->id) ? 'selected' : '';
                  $locLabel = $loc->name_ar ?? ('موقع #'.$loc->id);
                ?>
                <option value="<?= (int)$loc->id ?>" <?= $selected ?>>
                  <?= htmlspecialchars($locLabel) ?><?php if (!empty($loc->type)): ?> (<?= htmlspecialchars($loc->type) ?>)<?php endif; ?>
                </option>
              <?php endforeach; ?>
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
          <table class="table table-hover table-sm align-middle mb-0 assets-table">
          <thead class="table-light">
            <tr>
             <tr>
  <th class="col-actions">إجراءات</th>
  <th class="col-status">الحالة</th>
  <th class="col-loc">الموقع</th>
  <th class="col-serial">Serial</th>
  <th class="col-brand">الماركة / الموديل</th>
  <th class="col-type">النوع</th>
  <th class="col-tag">Tag</th>
  <th class="col-qr">QR</th>
</tr>

          </thead>

          <tbody>
            <?php if (empty($assets)): ?>
              <tr>
                <td colspan="8" class="text-center text-muted p-4">لا توجد أجهزة مطابقة للفلاتر الحالية.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($assets as $idx => $a): ?>
              <tr class="<?= ($idx % 2 === 0) ? 'row-even' : 'row-odd' ?>">

                <?php
                  $id = (int)($a->id ?? 0);
                  $locId = (int)($a->location_id ?? 0);

                  $tag = trim((string)($a->asset_tag ?? ''));
                  $type = (string)($a->type ?? '');
                  $brandModel = trim(($a->brand ?? '') . ' - ' . ($a->model ?? ''));
                  $serial = (string)($a->serial_no ?? '');
                  $warrantyExpiry = (string)($a->warranty_expiry
                  ?? ($a->warranty_expiry_date
                  ?? ($a->warranty_end ?? '')));
                  $warrantyHtml = warrantyBadgeHtml($warrantyExpiry);


                  $locationName = trim((string)($a->location_name ?? ''));
                  if ($locationName === '' || ctype_digit($locationName)) {
                    $locationName = $locNameById[$locId] ?? ('موقع #' . $locId);
                  }

                  $status = (string)($a->status ?? 'Active');

                  $canEdit = function_exists('canManageLocation')
                    ? (canManageLocation($locId, 'edit') || canManageLocation($locId, 'manage'))
                    : true;

                  $canDelete = function_exists('canManageLocation')
                    ? (canManageLocation($locId, 'delete') || canManageLocation($locId, 'manage'))
                    : true;

                  // ✅ QR يفتح رابط فلترة الأجهزة بالتاغ (مضمون موجود)
                  $qrUrl = $baseUrl . '/index.php?page=assets/show&id=' . $id;

                ?>

                <tr>
                  <td class="no-print col-actions">
                    <div class="actions-wrap">
                      <?php if ($canEdit): ?>
                        <a class="btn btn-sm btn-outline-warning" href="index.php?page=assets/edit&id=<?= $id ?>">تعديل</a>
                      <?php endif; ?>

                      <?php if ($canDelete): ?>
                        <form method="post" action="index.php?page=assets/delete"
                              onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                          <input type="hidden" name="id" value="<?= $id ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>

                  <td>
                    <span class="badge bg-<?= ($status === 'Active') ? 'success' : 'secondary' ?>">
                      <?= htmlspecialchars($status) ?>
                    </span>
                  </td>

                  <td><?= htmlspecialchars($locationName) ?></td>

                  <td class="d-none d-lg-table-cell">
  <div class="ltr"><?= htmlspecialchars($serial ?: '-') ?></div>
  <div class="mt-1"><?= $warrantyHtml ?></div>
</td>


                  <td class="d-none d-md-table-cell">
                    <?= htmlspecialchars($brandModel !== '' ? $brandModel : '-') ?>
                  </td>

                  <td><?= htmlspecialchars($type) ?></td>

                  <td class="fw-bold col-tag"><?= htmlspecialchars($tag) ?></td>

                  <td class="col-qr">
                    <div class="qr" data-text="<?= htmlspecialchars($qrUrl) ?>" title="<?= htmlspecialchars($qrUrl) ?>"></div>
                  </td>
                </tr>

              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="p-3 text-muted small">
        ملاحظة: “طباعة القائمة” تطبع النتائج الحالية. (نقدر نضيف صفحة ملصقات QR للطباعة لاحقًا).
      </div>
    </div>
  </div>
</div>

<!-- QR Code (CDN) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  window.addEventListener('load', function () {
    document.querySelectorAll('.qr').forEach(el => {
      const text = (el.getAttribute('data-text') || '').trim();
      if (!text) return;
      el.innerHTML = '';
      new QRCode(el, { text, width: 70, height: 70, correctLevel: QRCode.CorrectLevel.M });
    });
  });
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
