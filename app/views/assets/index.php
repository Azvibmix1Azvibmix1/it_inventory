<?php require APPROOT . '/views/inc/header.php'; ?>
<?php
// app/views/assets/index.php
// يفترض أن الكنترولر يمرر: $assets, $locations
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
    $wDate = new DateTime($dateStr);
    $today = new DateTime('today');
    $days = (int)$today->diff($wDate)->format('%r%a'); // سالب = منتهي
    return ['days' => $days, 'date' => $dateStr];
  } catch (Exception $e) {
    return null;
  }
}
function warrantyBadge($dateStr): array {
  $m = warrantyMeta($dateStr);
  if (!$m) return ['text' => '-', 'cls' => 'badgex'];
  $days = (int)$m['days'];
  if ($days < 0)  return ['text' => 'منتهي', 'cls' => 'badgex closed'];
  if ($days <= 30) return ['text' => "قريب ($days يوم)", 'cls' => 'badgex pending'];
  return ['text' => "سليم ($days يوم)", 'cls' => 'badgex open'];
}

// فلتر الضمان (عرض فقط)
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

// رابط التصدير (يحافظ على الفلاتر)
$q = $_GET ?? [];
unset($q['page']);
$exportHref = 'index.php?page=assets/exportcsv';
if (!empty($q)) $exportHref .= '&' . http_build_query($q);
?>

<style>
  /* جدول الأصول: خلي أرقام/Serial/Tag LTR */
  .assets-table { direction:ltr; }
  .assets-table th, .assets-table td { direction:rtl; vertical-align: middle; }
  .assets-table .ltr { direction:ltr; unicode-bidi:bidi-override; }

  .qr-img { width: 64px; height: 64px; }
  .col-qr { width: 86px; }
  .col-actions { width: 130px; white-space: nowrap; }
  .col-warranty { width: 190px; }

  /* Segmented pills (قرب الضمان/منتهي/الكل) */
  .seg{
    display:inline-flex;
    gap:8px;
    padding:6px;
    border-radius:999px;
    border:1px solid var(--border);
    background: rgba(240,241,245,.65);
  }
  body.theme-dark .seg{ background: rgba(255,255,255,.06); }

  .seg a{
    display:inline-flex;
    align-items:center;
    gap:8px;
    height:44px;
    padding:0 16px;
    border-radius:999px;
    text-decoration:none;
    color: var(--text);
    font-weight: 900;
  }
  .seg a:hover{ background: var(--hover-bg); }

  .seg a.active{
    background: var(--black-100);
    color: var(--white-100);
    box-shadow: var(--shadow2);
  }
  body.theme-dark .seg a.active{
    background: var(--white-100);
    color: var(--black-100);
  }

  /* Print */
  @media print{
    .no-print{ display:none !important; }
    th.no-print, td.no-print{ display:none !important; }
    .assets-table .btn, .assets-table button, .assets-table a.btn { display:none !important; }
    body { background:#fff !important; }
    .cardx { border:none !important; box-shadow:none !important; }
  }

  @media print{
  /* اطبع فقط محتوى #print-area */
  body * { visibility: hidden !important; }
  #print-area, #print-area * { visibility: visible !important; }

  /* حط منطقة الطباعة فوق */
  #print-area{
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
  }

  /* عناصر تظهر فقط في الطباعة */
  .print-only{ display:block !important; }
  .no-print{ display:none !important; }

  /* إزالة ظلال/كروت */
  .cardx{ box-shadow:none !important; border:0 !important; }

  /* اخفاء عمود الموقع في الطباعة (بدون تفاصيل الموقع) */
  .print-hide{ display:none !important; }
}
.print-only{ display:none; }

</style>

<div class="page-wrap">

  <div class="page-head">
    <div class="text-end">
      <h2 class="page-title">الأصول / الأجهزة</h2>
      <div class="page-sub">النتائج: <b><?= (int)$resultsCount ?></b> جهاز</div>
    </div>

    <div class="no-print d-flex gap-2 flex-wrap">
      <?php if ($canAddBtn): ?>
        <a class="btn btn-dark btn-soft" href="index.php?page=assets/add">إضافة جهاز</a>
      <?php endif; ?>

      <button class="btn btn-outline-dark btn-soft" type="button" onclick="printList()">طباعة</button>

      <a class="btn btn-outline-dark btn-soft" href="<?= e($exportHref) ?>">تصدير Excel</a>
    </div>
  </div>

  <!-- Warranty Segmented -->
  <div class="no-print d-flex justify-content-end mb-3">
    <div class="seg">
      <a class="<?= ($wFilter==='' ? 'active' : '') ?>" href="<?= e(buildUrlWith(['warranty'=>null], ['warranty'])) ?>">عرض الكل</a>
      <a class="<?= ($wFilter==='soon' ? 'active' : '') ?>" href="<?= e(buildUrlWith(['warranty'=>'soon'], [])) ?>">قرب انتهاء الضمان</a>
      <a class="<?= ($wFilter==='expired' ? 'active' : '') ?>" href="<?= e(buildUrlWith(['warranty'=>'expired'], [])) ?>">منتهي الضمان</a>
    </div>
  </div>

  <!-- Filters -->
  <div class="cardx no-print mb-3">
    <div class="cardx-body">
      <div class="cardx-title">بحث وفلترة</div>

      <form method="get" action="index.php">
        <input type="hidden" name="page" value="assets/index">
        <?php if (!empty($wFilter)): ?>
          <input type="hidden" name="warranty" value="<?= e($wFilter) ?>">
        <?php endif; ?>

        <div class="filters">
          <!-- Search -->
          <div>
            <label class="form-label mb-1">بحث</label>
            <input class="form-control input-soft"
                   name="q"
                   placeholder="Tag / Serial / Brand / Model"
                   value="<?= e($_GET['q'] ?? '') ?>">
          </div>

          <!-- Location -->
          <div>
            <label class="form-label mb-1">الموقع</label>
            <select class="form-select select-soft" name="location_id">
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

          <!-- Include children -->
          <div class="d-flex align-items-end">
            <div class="w-100">
              <label class="form-label mb-1">خيارات</label>
              <div class="d-flex align-items-center gap-2" style="height:44px;">
                <input class="form-check-input m-0"
                       type="checkbox"
                       name="include_children"
                       value="1"
                       <?= !empty($_GET['include_children']) ? 'checked' : '' ?>>
                <span style="font-weight:900;">يشمل التوابع</span>
              </div>
            </div>
          </div>

          <!-- Apply -->
          <div class="d-flex align-items-end">
            <button class="btn btn-dark btn-soft w-100" type="submit">تطبيق</button>
          </div>

          <!-- Reset -->
          <div class="d-flex align-items-end">
            <a class="btn btn-outline-dark btn-soft w-100"
               href="<?= e(buildUrlWith(['q'=>null,'location_id'=>0,'include_children'=>null], ['q','location_id','include_children'])) ?>">
              مسح الفلاتر
            </a>
          </div>
        </div>

      </form>
    </div>
  </div>

  <!-- Table / Print area -->
  <div class="cardx" id="print-area">
    <div class="cardx-body p-0">
      <div class="table-responsive">
        <div class="print-only" style="display:none; padding:16px 0; border-bottom:1px solid #ddd; margin-bottom:12px;">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div style="text-align:right;">
      <div style="font-weight:900; font-size:18px;">جامعة جدة</div>
      <div style="font-size:12px; color:#666;">تقرير الأصول / الأجهزة</div>
      <div style="font-size:12px; color:#666;">التاريخ: <?= date('Y/m/d') ?></div>
    </div>

    <div style="text-align:left;">
      <img src="img/uoj-footer.png" alt="University of Jeddah" style="height:46px;">
    </div>
  </div>
</div>

        <table class="tablex assets-table mb-0">
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
              <th class="col-actions text-center no-print">إجراءات</th>
            </tr>
          </thead>

          <tbody>
          <?php if (empty($assets)): ?>
            <tr>
              <td colspan="9" class="text-center py-4" style="color:var(--muted); font-weight:900;">
                لا توجد أجهزة مطابقة للفلاتر الحالية.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($assets as $a):
              $id    = is_object($a) ? (int)($a->id ?? 0) : (int)($a['id'] ?? 0);
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

              // status -> badge gray mapping
              $status = trim((string)($a->status ?? 'Active'));
              $statusLower = strtolower($status);
              $statusCls = 'badgex open';
              if (in_array($statusLower, ['inactive','retired','غير نشط','مستبعد'], true)) $statusCls = 'badgex closed';
              if (in_array($statusLower, ['maintenance','repair','صيانة','تصليح'], true)) $statusCls = 'badgex pending';

              // QR -> صفحة الجهاز
              $qrUrl = $baseUrl . '/index.php?page=assets/show&id=' . $id;
              $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&margin=0&data=' . urlencode($qrUrl);

              $warrantyExpiry = getWarrantyDate($a);
              $wb = warrantyBadge($warrantyExpiry);

              // صلاحيات (إن كانت موجودة بالمشروع)
              $canEdit = function_exists('canManageLocation') ? (canManageLocation($locId, 'edit') || canManageLocation($locId, 'manage')) : true;
              $canDelete = function_exists('canManageLocation') ? (canManageLocation($locId, 'delete') || canManageLocation($locId, 'manage')) : true;
            ?>
              <tr>
                <td class="text-center">
                  <img class="qr-img" loading="lazy" src="<?= e($qrImg) ?>" alt="QR">
                </td>

                <td class="ltr" style="font-weight:900;">
                  <a href="index.php?page=assets/show&id=<?= $id ?>" style="text-decoration:none;">
                    <?= e($tag ?: ('#'.$id)) ?>
                  </a>
                </td>

                <td><?= e($type ?: '-') ?></td>

                <td class="d-none d-md-table-cell"><?= e($brandModel ?: '-') ?></td>

                <td class="d-none d-lg-table-cell ltr"><?= e($serial ?: '-') ?></td>

                <td class="d-none d-md-table-cell">
                  <span class="<?= e($wb['cls']) ?>"><?= e($wb['text']) ?></span>
                </td>

                <td><?= e($locationName ?: '-') ?></td>

                <td><span class="<?= e($statusCls) ?>"><?= e($status) ?></span></td>

                <td class="text-center no-print">
                  <?php if ($canEdit): ?>
                    <a class="icon-btn" title="تعديل" href="index.php?page=assets/edit&id=<?= $id ?>">✏️</a>
                  <?php endif; ?>

                  <?php if ($canDelete): ?>
                    <form class="d-inline-block" method="post"
                          action="index.php?page=assets/delete"
                          onsubmit="return confirm('متأكد تبغى حذف هذا الجهاز؟');">
                      <input type="hidden" name="id" value="<?= $id ?>">
                      <button type="submit" class="icon-btn" title="حذف">🗑️</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="p-2 text-end" style="color:var(--muted); font-weight:800; font-size:12px;">
        ملاحظة: “طباعة” تطبع النتائج الحالية حسب الفلاتر.
      </div>
    </div>
  </div>

</div>

<script>
function printList(){
  window.print();
}
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
