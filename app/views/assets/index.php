<?php require APPROOT . '/views/inc/header.php'; ?>

<style>

  .no-print { display: inline-block; }
  .barcode-cell svg { max-width: 220px; height: 48px; }
  @media print {
    .no-print, .no-print * { display: none !important; }
    .table { font-size: 12px; }
    .barcode-cell svg { max-width: 260px; height: 56px; }
  }
  .qr-cell { width: 110px; }
.qr { width: 90px; height: 90px; margin: 0 auto; }
.qr img, .qr canvas { width: 90px !important; height: 90px !important; }

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
?>

<div class="container-fluid py-3" dir="rtl">

  <?php if (function_exists('flash')) { flash('asset_msg'); flash('access_denied'); } ?>

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h4 class="m-0 fw-bold">الأجهزة</h4>
      <div class="text-muted">فلترة الأجهزة حسب الموقع والبحث بالتاغ/السيريال/الموديل.</div>
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
        <table class="table table-striped align-middle mb-0">
          <thead>
  <tr>
    <th>إجراءات</th>
    <th>الحالة</th>
    <th>الموقع</th>
    <th>Serial</th>
    <th>الماركة / الموديل</th>
    <th>النوع</th>
    <th>Tag</th>
    <th>QR</th>
  </tr>
</thead>


          <tbody>
            <?php if (empty($assets)): ?>
              <tr>
                <td colspan="8" class="text-center text-muted p-4">لا توجد أجهزة مطابقة للفلاتر الحالية.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($assets as $a): ?>
                <?php
                  $id = (int)($a->id ?? 0);
                  $locId = (int)($a->location_id ?? 0);

                  $tag = (string)($a->asset_tag ?? '');
                  $type = (string)($a->type ?? '');
                  $brandModel = trim(($a->brand ?? '') . ' - ' . ($a->model ?? ''));
                  $serial = (string)($a->serial_no ?? '');

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
                ?>

                <tr>
  <td class="no-print">
    <div class="d-flex gap-2 flex-wrap">
      <?php if ($canEdit): ?>
        <a class="btn btn-sm btn-outline-warning btn-round" href="index.php?page=assets/edit&id=<?= (int)$a->id ?>">تعديل</a>
      <?php endif; ?>

      <?php if ($canDelete): ?>
        <form method="post" action="index.php?page=assets/delete"
              onsubmit="return confirm('هل أنت متأكد من الحذف؟');"
              style="display:inline-block; margin:0;">
          <input type="hidden" name="id" value="<?= (int)$a->id ?>">
          <button type="submit" class="btn btn-sm btn-outline-danger btn-round">حذف</button>
        </form>
      <?php endif; ?>
    </div>
  </td>

  <td>
    <span class="badge bg-<?= (($a->status ?? 'Active') === 'Active') ? 'success' : 'secondary' ?>">
      <?= htmlspecialchars($a->status ?? 'Active') ?>
    </span>
  </td>

  <td><?= htmlspecialchars($locationName) ?></td>
  <td><?= htmlspecialchars($a->serial_no ?? '') ?></td>
  <td><?= htmlspecialchars(trim(($a->brand ?? '').' - '.($a->model ?? '')) ?: '-') ?></td>
  <td><?= htmlspecialchars($a->type ?? '') ?></td>
  <td class="fw-bold"><?= htmlspecialchars($a->asset_tag ?? '') ?></td>

  <!-- QR آخر عمود (يسار) -->
  <td class="qr-cell">
    <div class="qr" data-text="<?= htmlspecialchars($a->asset_tag ?? '') ?>"></div>
  </td>
</tr>


              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="p-3 text-muted small">
        ملاحظة: “طباعة القائمة” تطبع النتائج الحالية. (نقدر نضيف صفحة ملصقات باركود للطباعة لاحقًا).
      </div>

    </div>
  </div>
</div>

<!-- JsBarcode محلي (لا يعتمد على إنترنت) -->
<script src="js/JsBarcode.all.min.js"></script>
<script>
  (function(){
    const els = document.querySelectorAll('svg.barcode');
    els.forEach(el => {
      const tag = (el.getAttribute('data-tag') || '').trim();
      if (!tag) return;
      try {
        JsBarcode(el, tag, { format: "CODE128", displayValue: false });
      } catch (e) {
        console.error('Barcode error', e);
      }
    });
  })();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  window.addEventListener('load', function () {
    document.querySelectorAll('.qr').forEach(el => {
      const text = (el.getAttribute('data-text') || '').trim();
      if (!text) return;

      // نظّف العنصر قبل إعادة الرسم
      el.innerHTML = '';

      new QRCode(el, {
        text: text,
        width: 90,
        height: 90,
        correctLevel: QRCode.CorrectLevel.M
      });
    });
  });
</script>



<?php require APPROOT . '/views/inc/footer.php'; ?>
