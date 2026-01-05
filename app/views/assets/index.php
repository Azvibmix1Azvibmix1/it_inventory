<?php require APPROOT . '/views/inc/header.php'; ?>
<?php // باقي كود الصفحة تحت ?>


<?php
// app/views/assets/index.php

// يفترض أن الكنترولر يمرر: $assets, $locations (مثل الموجود عندك)
$assets    = $assets    ?? ($data['assets']    ?? []);
$locations = $locations ?? ($data['locations'] ?? []);

// ===== Helpers =====
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/** يبني رابط ويحافظ على الموجود في GET + يضيف/يحذف مفاتيح */
function buildUrlWith(array $add = [], array $remove = []) {
  $q = $_GET;
  foreach ($remove as $k) unset($q[$k]);
  foreach ($add as $k => $v) {
    if ($v === null || $v === '') unset($q[$k]);
    else $q[$k] = $v;
  }
  if (empty($q['page'])) $q['page'] = 'assets/index';
  return 'index.php?' . http_build_query($q);
}

// خريطة أسماء المواقع (لو رجعت أرقام)
$locNameById = [];
foreach ($locations as $loc) {
  $id = (int)($loc->id ?? 0);
  $name = $loc->name_ar ?? ($loc->name ?? ('موقع #'.$id));
  $locNameById[$id] = $name;
}

// baseUrl للـ QR (يطلع /it_inventory/public)
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
$baseUrl  = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath;

// ===== Warranty helpers =====
function getWarrantyDate($a): string {
  return (string)($a->warranty_expiry ?? ($a->warranty_expiry_date ?? ($a->warranty_end ?? '')));
}
function warrantyMeta($dateStr): ?array {
  $dateStr = trim((string)$dateStr);
  if ($dateStr === '' || $dateStr === '-') return null;
  try {
    $wDate  = new DateTime($dateStr);
    $today  = new DateTime('today');
    $days   = (int)$today->diff($wDate)->format('%r%a'); // سالب = منتهي
    return ['days' => $days, 'date' => $dateStr];
  } catch (Exception $e) {
    return null;
  }
}
function warrantyBadge($dateStr): array {
  $m = warrantyMeta($dateStr);
  if (!$m) return ['text' => '-', 'class' => 'badge bg-secondary'];

  $days = $m['days'];
  if ($days < 0)      return ['text' => 'منتهي',          'class' => 'badge bg-danger'];
  if ($days <= 30)    return ['text' => "قريب ($days يوم)", 'class' => 'badge bg-warning text-dark'];
  return ['text' => "سليم ($days يوم)", 'class' => 'badge bg-success'];
}

// فلتر الضمان (هنا عرض فقط - مثل اللي سويته عندك)
$wFilter = $_GET['warranty'] ?? '';
if ($wFilter === 'soon') {
  $assets = array_values(array_filter($assets, function($a){
    $m = warrantyMeta(getWarrantyDate($a));
    return $m && $m['days'] >= 0 && $m['days'] <= 30;
  }));
} elseif ($wFilter === 'expired') {
  $assets = array_values(array_filter($assets, function($a){
    $m = warrantyMeta(getWarrantyDate($a));
    return $m && $m['days'] < 0;
  }));
}

// عداد النتائج
$resultsCount = is_array($assets) ? count($assets) : 0;

// صلاحية إضافة
$canAddBtn = !empty($data['can_add_asset'] ?? false) || !empty($locations);
?>
<style>
  .assets-table { direction:ltr; }
  .assets-table th, .assets-table td { direction:rtl; vertical-align: middle; }
  .assets-table .ltr { direction:ltr; unicode-bidi:bidi-override; }

  .qr-img { width: 64px; height: 64px; }
  .col-qr { width: 86px; }
  .col-actions { width: 160px; white-space: nowrap; }
  .col-warranty { width: 170px; }

  .top-actions{
    display:flex;
    justify-content:flex-end;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom: 14px;
  }

  .filters-card{
    max-width: 720px;
    margin-left: auto;   /* يمين */
    margin-right: 0;
    border-radius: 14px;
  }
  .filters-card .card-body, .filters-card form { text-align: right; }

  /* طباعة: اخفِ الإجراءات + أي أزرار */
  @media print {
    .no-print, .top-actions, .filters-card, .navbar, footer { display:none !important; }

    /* اخفِ عمود الإجراءات */
    .col-actions,
    th.col-actions,
    td.col-actions { display:none !important; }

    /* احتياط: اخفِ أي زر داخل الجدول */
    .assets-table .btn,
    .assets-table button,
    .assets-table a.btn { display:none !important; }

    body { background:#fff !important; }
    .card { border: none !important; box-shadow: none !important; }
    .table { margin: 0 !important; }
  }
  @media print{
  .no-print{ display:none !important; }
  th.no-print, td.no-print{ display:none !important; }
}

</style>

<div class="container py-4">

  <!-- أزرار أعلى الصفحة -->
  <div class="top-actions">
    <?php if ($canAddBtn): ?>
      <a class="btn btn-success" href="index.php?page=assets/add">إضافة جهاز</a>
    <?php endif; ?>

    <button class="btn btn-outline-secondary" type="button" onclick="printList()">طباعة القائمة</button>

    <!-- تصدير Excel (CSV) -->
    <?php
  // رابط التصدير مع نفس الفلاتر الحالية
  $q = $_GET ?? [];
  unset($q['page']); // لا نكرّر page
  $exportHref = 'index.php?page=assets/exportcsv';
  $qs = http_build_query($q);
  if ($qs) $exportHref .= '&' . $qs;
?>
<a class="btn btn-outline-success" href="<?= $exportHref ?>">
  تصدير Excel
</a>








    <a class="btn btn-outline-warning" href="<?= e(buildUrlWith(['warranty' => 'soon'], [])) ?>">قرب انتهاء الضمان</a>
    <a class="btn btn-outline-danger" href="<?= e(buildUrlWith(['warranty' => 'expired'], [])) ?>">منتهي الضمان</a>
    <a class="btn btn-outline-dark" href="<?= e(buildUrlWith(['warranty' => null], ['warranty'])) ?>">عرض الكل</a>
  </div>

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div class="text-end ms-auto">
      <h2 class="mb-1">الأجهزة</h2>
      <div class="text-muted">النتائج: <b><?= (int)$resultsCount ?></b> جهاز</div>
    </div>
  </div>

  <!-- الفلاتر يمين -->
  <div class="card filters-card mb-3">
    <div class="card-body">
      <form method="get" action="index.php" class="row g-3 align-items-end">
        <input type="hidden" name="page" value="assets/index">

        <div class="col-md-4">
          <label class="form-label">الموقع</label>
          <select class="form-select" name="location_id">
            <option value="0">— كل المواقع —</option>
            <?php
              $selectedLoc = (int)($_GET['location_id'] ?? 0);
              foreach ($locations as $loc):
                $id = (int)($loc->id ?? 0);
                $label = $loc->name_ar ?? ($loc->name ?? ('موقع #'.$id));
                $sel = ($selectedLoc === $id) ? 'selected' : '';
            ?>
              <option value="<?= $id ?>" <?= $sel ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-5">
          <label class="form-label">بحث</label>
          <input class="form-control" name="q" placeholder="Tag / Serial / Brand / Model" value="<?= e($_GET['q'] ?? '') ?>">
        </div>

        <div class="col-md-3">
          <label class="form-label">خيارات</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="include_children" value="1" <?= !empty($_GET['include_children']) ? 'checked' : '' ?>>
            <label class="form-check-label">يشمل التوابع</label>
          </div>

          <div class="d-flex gap-2 mt-2">
            <button class="btn btn-primary" type="submit">تطبيق</button>
            <a class="btn btn-outline-secondary" href="index.php?page=assets/index">مسح الفلاتر</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- منطقة الطباعة -->
  <div class="card" id="print-area">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle assets-table">
          <thead>
            <tr>
              <th class="col-qr text-center">QR</th>
              <th>Tag</th>
              <th>النوع</th>
              <th class="d-none d-md-table-cell">الماركة / الموديل</th>
              <th class="d-none d-lg-table-cell">Serial</th>
              <th class="col-warranty d-none d-md-table-cell">الضمان</th>
              <th>الموقع</th>
              <th>الحالة</th>
              <th class="col-actions text-center no-print"></th> إجراءات</tr>
          </thead>
          <tbody>
          <?php if (empty($assets)): ?>
            <tr>
              <td colspan="9" class="text-center py-4 text-muted">لا توجد أجهزة مطابقة للفلاتر الحالية.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($assets as $a):
              $id    = (int)($a->id ?? 0);
              $locId = (int)($a->location_id ?? 0);

              $tag   = trim((string)($a->asset_tag ?? ''));
              $type  = trim((string)($a->type ?? ''));
              $brand = trim((string)($a->brand ?? ''));
              $model = trim((string)($a->model ?? ''));
              $brandModel = trim(($brand . ' - ' . $model), " -");

              $serial = trim((string)($a->serial_no ?? ($a->serial ?? '')));

              // اسم الموقع بدل الرقم
              $locationName = trim((string)($a->location_name ?? ''));
              if ($locationName === '' || ctype_digit($locationName)) {
                $locationName = $locNameById[$locId] ?? ($locationName ?: ('موقع #'.$locId));
              }

              $status = trim((string)($a->status ?? 'Active'));
              $statusLower = strtolower($status);
              $statusClass = 'bg-success';
              if (in_array($statusLower, ['inactive','retired','غير نشط','مستبعد'], true)) $statusClass = 'bg-secondary';
              if (in_array($statusLower, ['maintenance','repair','صيانة','تصليح'], true)) $statusClass = 'bg-warning text-dark';

              // QR -> صفحة الجهاز
              $qrUrl = $baseUrl . '/index.php?page=assets/show&id=' . $id;
              $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&margin=0&data=' . urlencode($qrUrl);

              $warrantyExpiry = getWarrantyDate($a);
              $wb = warrantyBadge($warrantyExpiry);

              // صلاحيات
              $canEdit   = function_exists('canManageLocation') ? (canManageLocation($locId, 'edit') || canManageLocation($locId, 'manage')) : true;
              $canDelete = function_exists('canManageLocation') ? (canManageLocation($locId, 'delete') || canManageLocation($locId, 'manage')) : true;
            ?>
            <tr>
              <td class="text-center">
                <img class="qr-img" src="<?= e($qrImg) ?>" alt="QR">
              </td>

              <td>
                <a href="index.php?page=assets/show&id=<?= $id ?>"><?= e($tag ?: ('#'.$id)) ?></a>
              </td>

              <td><?= e($type ?: '-') ?></td>

              <td class="d-none d-md-table-cell"><?= e($brandModel ?: '-') ?></td>

              <td class="d-none d-lg-table-cell">
                <span class="ltr"><?= e($serial ?: '-') ?></span>
              </td>

              <td class="d-none d-md-table-cell">
                <?php if ($warrantyExpiry): ?>
                  <span class="<?= e($wb['class']) ?>"><?= e($wb['text']) ?></span>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>

              <td><?= e($locationName ?: '-') ?></td>

              <td><span class="badge <?= e($statusClass) ?>"><?= e($status) ?></span></td>

<?php $id = is_object($asset) ? (int)($asset->id ?? 0) : (int)($asset['id'] ?? 0); ?>

<td class="col-actions text-center">
  <a class="btn btn-sm btn-outline-primary"
     href="index.php?page=assets/edit&id=<?= $id ?>">
    تعديل
  </a>

  <form class="d-inline-block"
        method="post"
        action="index.php?page=assets/delete"
        onsubmit="return confirm('متأكد تبغى حذف هذا الجهاز؟');">
    <input type="hidden" name="id" value="<?= $id ?>">
    <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
  </form>
</td>



            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="p-2 text-muted small text-end">
        ملاحظة: “طباعة القائمة” تطبع النتائج الحالية حسب الفلاتر.
      </div>
    </div>
  </div>
</div>

<script>
function printList(){
  // طباعة نفس الصفحة مع تطبيق @media print (أخف وأضمن)
  window.print();
}
</script>
<?php require APPROOT . '/views/inc/footer.php'; ?>
