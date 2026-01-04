<?php
require APPROOT . '/views/inc/header.php';

$assets    = $data['assets'] ?? [];
$locations = $data['locations'] ?? [];

$filters = $data['filters'] ?? [
  'location_id' => ($_GET['location_id'] ?? 0),
  'q' => ($_GET['q'] ?? ''),
  'include_children' => ($_GET['include_children'] ?? 0)
];

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/** يبني رابط ويحافظ على الموجود في GET */
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

/** خريطة أسماء المواقع */
$locNameById = [];
foreach ($locations as $loc) {
  $id = (int)($loc->id ?? 0);
  $name = $loc->name_ar ?? ($loc->name ?? ('موقع #'.$id));
  $locNameById[$id] = $name;
}

/** baseUrl للـ QR */
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$baseUrl  = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath;

/** ضمان */
function getWarrantyDate($a): string {
  return (string)($a->warranty_expiry
    ?? ($a->warranty_expiry_date
    ?? ($a->warranty_end ?? '')));
}

function warrantyMeta($dateStr): ?array {
  $dateStr = trim((string)$dateStr);
  if ($dateStr === '' || $dateStr === '-') return null;

  try{
    $wDate = new DateTime($dateStr);
    $today = new DateTime('today');
    $days  = (int)$today->diff($wDate)->format('%r%a'); // سالب = منتهي
    return ['days' => $days, 'date' => $dateStr];
  }catch(Exception $e){
    return null;
  }
}

function warrantyBadgeHtml($dateStr): string {
  $m = warrantyMeta($dateStr);
  if (!$m) return '<span class="text-muted">-</span>';

  $days = $m['days'];
  $safeDate = htmlspecialchars($m['date'], ENT_QUOTES, 'UTF-8');

  if ($days < 0) {
    return '<span class="badge bg-danger" title="انتهى: '.$safeDate.'">منتهي</span>';
  } elseif ($days <= 30) {
    return '<span class="badge bg-warning text-dark" title="ينتهي: '.$safeDate.'">قريب ('.$days.' يوم)</span>';
  } else {
    return '<span class="badge bg-success" title="ينتهي: '.$safeDate.'">سليم ('.$days.' يوم)</span>';
  }
}

/** فلتر الضمان (عرض فقط) */
$wFilter = $_GET['warranty'] ?? '';
if ($wFilter === 'soon') {
  $assets = array_values(array_filter($assets, function($a){
    $m = warrantyMeta(getWarrantyDate($a));
    if (!$m) return false;
    return ($m['days'] >= 0 && $m['days'] <= 30);
  }));
} elseif ($wFilter === 'expired') {
  $assets = array_values(array_filter($assets, function($a){
    $m = warrantyMeta(getWarrantyDate($a));
    if (!$m) return false;
    return ($m['days'] < 0);
  }));
}
?>

<style>
  .filters-card{
  width: 720px;
  max-width: 100%;
  margin-left: auto !important;   /* ✅ يخليها يمين */
  margin-right: 0 !important;
  border-radius: 14px;
  display: block;
}

.filters-card .card-body,
.filters-card form{
  text-align: right;
}

  .qr-img{ width:70px; height:70px; }
  .badge{ font-size: 11px; padding: 4px 8px; }

  .col-qr{ width: 90px; }
  .col-actions{ width: 160px; }
  .col-serial{ width: 170px; }
  .col-warranty{ width: 160px; }

  /* ✅ ترتيب الأعمدة طبيعي مع بقاء النص RTL */
  .assets-table { direction: ltr; }
  .assets-table th, .assets-table td { direction: rtl; }
  .assets-table .ltr { direction: ltr; unicode-bidi: bidi-override; }

  /* ✅ الأزرار يمين */
  .top-actions{
    display:flex;
    justify-content:flex-end;
    gap:8px;
    flex-wrap:wrap;
  }

  /* تحسين بسيط للعنوان */
  .page-title{ text-align:right; font-weight:800; }
</style>

<div class="container-fluid py-3">

  <!-- ✅ الأزرار يمين -->
  <div class="top-actions mb-2">
    <a class="btn btn-warning btn-sm" href="<?= e(buildUrlWith(['warranty'=>'soon'])) ?>">قرب انتهاء الضمان</a>
    <a class="btn btn-outline-danger btn-sm" href="<?= e(buildUrlWith(['warranty'=>'expired'])) ?>">منتهي الضمان</a>

    <?php if (!empty($wFilter)): ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?= e(buildUrlWith(['warranty'=>null])) ?>">عرض الكل</a>
    <?php endif; ?>

    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="printTable()">طباعة القائمة</button>
    <a class="btn btn-success btn-sm" href="index.php?page=assets/add">إضافة جهاز</a>
  </div>

  <!-- العنوان -->
  <h3 class="page-title mb-3">الأجهزة</h3>

  <!-- ✅ عدّاد نتائج -->
  <div class="text-muted small mb-2 text-end">
    النتائج: <b><?= count($assets) ?></b> جهاز
    <?php if ($wFilter === 'soon'): ?>
      — <span class="badge bg-light text-dark border">قرب انتهاء الضمان</span>
    <?php elseif ($wFilter === 'expired'): ?>
      — <span class="badge bg-light text-dark border">منتهي الضمان</span>
    <?php endif; ?>
  </div>

  <!-- الفلاتر -->
  <div class="card filters-card mb-3">
    <div class="card-body">
      <form method="get" action="index.php" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="assets/index">

        <!-- ✅ حافظ على فلتر الضمان لو مفعّل -->
        <?php if (!empty($_GET['warranty'])): ?>
          <input type="hidden" name="warranty" value="<?= e($_GET['warranty']) ?>">
        <?php endif; ?>

        <div class="col-md-4">
          <label class="form-label">الموقع</label>
          <select class="form-select" name="location_id">
            <option value="0">— كل المواقع —</option>
            <?php foreach ($locations as $loc):
              $id = (int)($loc->id ?? 0);
              $label = $loc->name_ar ?? ($loc->name ?? ('موقع #'.$id));
              $sel = ((int)($filters['location_id'] ?? 0) === $id) ? 'selected' : '';
            ?>
              <option value="<?= $id ?>" <?= $sel ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-5">
          <label class="form-label">بحث</label>
          <input class="form-control" type="text" name="q"
                 placeholder="Tag / Serial / Brand / Model"
                 value="<?= e($filters['q'] ?? '') ?>">
        </div>

        <div class="col-md-3">
          <label class="form-label">خيارات</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="include_children" value="1"
              <?= !empty($filters['include_children']) ? 'checked' : '' ?>>
            <label class="form-check-label">يشمل التوابع</label>
          </div>

          <div class="d-flex gap-2 mt-2">
            <button class="btn btn-primary btn-sm" type="submit">تطبيق</button>

            <!-- ✅ يمسح البحث/الموقع ويُبقي فلتر الضمان لو مفعّل -->
            <a class="btn btn-outline-secondary btn-sm"
               href="<?= e(buildUrlWith(['location_id'=>null,'q'=>null,'include_children'=>null], [])) ?>">
              مسح الفلاتر
            </a>
          </div>
        </div>

      </form>
    </div>
  </div>

  <!-- الجدول -->
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
              <th class="col-serial d-none d-lg-table-cell">Serial</th>
              <th class="col-warranty d-none d-lg-table-cell">الضمان</th>
              <th>الموقع</th>
              <th>الحالة</th>
              <th class="col-actions text-center">إجراءات</th>
            </tr>
          </thead>

          <tbody>
          <?php if (empty($assets)): ?>
            <tr>
              <td colspan="9" class="text-center text-muted py-4">
                لا توجد أجهزة مطابقة للفلاتر الحالية.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($assets as $a):
              $id = (int)($a->id ?? 0);
              $locId = (int)($a->location_id ?? 0);

              $tag = trim((string)($a->asset_tag ?? ''));
              $type = trim((string)($a->type ?? ''));
              $brand = trim((string)($a->brand ?? ''));
              $model = trim((string)($a->model ?? ''));
              $brandModel = trim($brand . ' - ' . $model, " -");
              $serial = trim((string)($a->serial_no ?? ($a->serial ?? '')));

              // ✅ اسم الموقع بدل الرقم
              $locationName = trim((string)($a->location_name ?? ''));
              if ($locationName === '' || ctype_digit($locationName)) {
                $locationName = $locNameById[$locId] ?? ($locationName ?: ('موقع #'.$locId));
              }

              $status = trim((string)($a->status ?? 'Active'));
              $statusLower = strtolower($status);
              $statusClass = 'bg-success';
              if (in_array($statusLower, ['inactive','retired','غير نشط','مستبعد'], true)) $statusClass = 'bg-secondary';
              if (in_array($statusLower, ['maintenance','repair','صيانة','تصليح'], true)) $statusClass = 'bg-warning text-dark';

              $qrUrl = $baseUrl . '/index.php?page=assets/show&id=' . $id;
              $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&margin=0&data=' . urlencode($qrUrl);

              $warrantyExpiry = getWarrantyDate($a);
            ?>
              <tr>
                <td class="text-center">
                  <img class="qr-img" src="<?= e($qrImg) ?>" alt="QR">
                </td>

                <td>
                  <a class="fw-bold text-decoration-none" href="index.php?page=assets/show&id=<?= $id ?>">
                    <?= e($tag ?: ('#'.$id)) ?>
                  </a>
                </td>

                <td><?= e($type ?: '-') ?></td>

                <td class="d-none d-md-table-cell"><?= e($brandModel ?: '-') ?></td>

                <td class="d-none d-lg-table-cell">
                  <span class="ltr"><?= e($serial ?: '-') ?></span>
                </td>

                <td class="d-none d-lg-table-cell">
                  <?= warrantyBadgeHtml($warrantyExpiry) ?>
                </td>

                <td><?= e($locationName ?: '-') ?></td>

                <td>
                  <span class="badge <?= e($statusClass) ?>"><?= e($status ?: '-') ?></span>
                </td>

                <td class="text-center">
                  <a class="btn btn-sm btn-outline-primary" href="index.php?page=assets/edit&id=<?= $id ?>">تعديل</a>
                  <a class="btn btn-sm btn-outline-danger"
                     href="index.php?page=assets/delete&id=<?= $id ?>"
                     onclick="return confirm('متأكد تبغى تحذف الجهاز؟');">
                    حذف
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="p-3 small text-muted text-end">
        ملاحظة: “طباعة القائمة” تطبع النتائج الحالية حسب الفلاتر.
      </div>
    </div>
  </div>

</div>

<script>
function printTable(){
  const area = document.getElementById('print-area');
  if(!area){ window.print(); return; }

  const w = window.open('', '_blank', 'width=1000,height=700');
  w.document.open();
  w.document.write(`
    <!doctype html>
    <html lang="ar" dir="rtl">
    <head>
      <meta charset="utf-8">
      <title>Print</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        body{ margin: 12mm; font-family: Cairo, Arial, sans-serif; }
        .qr-img{ width:55px; height:55px; }
        .badge{ font-size: 11px; padding: 4px 8px; }
        .assets-table{ direction:ltr; }
        .assets-table th, .assets-table td{ direction:rtl; }
        .assets-table .ltr{ direction:ltr; unicode-bidi:bidi-override; }
      </style>
    </head>
    <body>
      ${area.outerHTML}
      <script>
        window.onload = function(){ window.focus(); window.print(); window.close(); }
      <\/script>
    </body>
    </html>
  `);
  w.document.close();
}
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
