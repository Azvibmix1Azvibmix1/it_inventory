<?php require APPROOT . '/views/inc/header.php'; ?>

<?php
// ==============================
// Assets Index (UI clean / gray)
// ==============================

// data
$assets     = $data['assets'] ?? [];
$locations  = $data['locations'] ?? [];
$filters    = $data['filters'] ?? [];
$canAddBtn  = !empty($data['can_add_asset'] ?? false) || !empty($locations);

// current query
$q                = trim($_GET['q'] ?? ($filters['q'] ?? ''));
$selectedLoc      = (int)($_GET['location_id'] ?? ($filters['location_id'] ?? 0));
$includeChildren  = !empty($_GET['include_children'] ?? ($filters['include_children'] ?? 0));
$wFilter          = trim($_GET['warranty'] ?? ''); // all | soon | expired

// helper: build url keeping current query params
function buildUrl(array $merge = []): string {
  $q = $_GET ?? [];
  unset($q['page']);
  foreach ($merge as $k => $v) {
    if ($v === null || $v === '' || $v === false) unset($q[$k]);
    else $q[$k] = $v;
  }
  $q['page'] = 'assets/index';
  return 'index.php?' . http_build_query($q);
}

function buildExportUrl(string $page): string {
  $q = $_GET ?? [];
  unset($q['page']);
  $url = 'index.php?page=' . $page;
  if (!empty($q)) $url .= '&' . http_build_query($q);
  return $url;
}

// baseUrl for QR links
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
$baseUrl  = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath;

// location map (id -> name)
$locNameById = [];
foreach ($locations as $loc) {
  $id = (int)($loc->id ?? 0);
  $name = $loc->name_ar ?? ($loc->name ?? ('موقع #'.$id));
  $locNameById[$id] = $name;
}

// warranty helpers
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
  } catch (Exception $e) { return null; }
}
function warrantyBadge($dateStr): array {
  $m = warrantyMeta($dateStr);
  if (!$m) return ['text' => '-', 'cls' => 'badgex'];
  $days = (int)$m['days'];
  if ($days < 0) return ['text' => 'منتهي', 'cls' => 'badgex closed'];
  if ($days <= 30) return ['text' => "قريب ($days يوم)", 'cls' => 'badgex pending'];
  return ['text' => "سليم ($days يوم)", 'cls' => 'badgex open'];
}

// Apply warranty filter (UI-level)
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

// counts (based on current list)
$totalCount = is_array($assets) ? count($assets) : 0;
$soonCount = 0; $expiredCount = 0;
foreach ($assets as $a) {
  $m = warrantyMeta(getWarrantyDate($a));
  if (!$m) continue;
  if ($m['days'] < 0) $expiredCount++;
  elseif ($m['days'] <= 30) $soonCount++;
}

// urls
$exportHref = buildExportUrl('assets/exportcsv');
$printHref  = buildExportUrl('assets/print'); // special route in router
?>

<style>
  .page-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:14px; }
  .page-title{ font-size:32px; font-weight:900; margin:0; }
  .page-sub{ margin:4px 0 0; color:#6b7280; font-weight:700; }
  .head-actions{ display:flex; gap:8px; flex-wrap:wrap; align-items:center; justify-content:flex-end; }
  .btn-pill{ border-radius:999px !important; font-weight:900; padding:.5rem .9rem; }
  .tabs-pills{ display:flex; gap:8px; flex-wrap:wrap; margin:6px 0 14px; }
  .tab-pill{
    display:inline-flex; align-items:center; gap:8px;
    border:1px solid rgba(0,0,0,.08); background:#fff;
    padding:.45rem .9rem; border-radius:999px; font-weight:900; text-decoration:none;
  }
  .tab-pill.active{ background:#0b0f14; color:#fff; border-color:#0b0f14; }
  .stat-row{ display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px; margin-bottom:14px; }
  @media (max-width: 992px){ .stat-row{ grid-template-columns:1fr; } }
  .stat-card{
    background:linear-gradient(180deg, #0e1725 0%, #0b1220 100%);
    color:#fff; border-radius:16px; padding:16px 18px;
    box-shadow:0 10px 26px rgba(0,0,0,.08);
  }
  .stat-num{ font-size:34px; font-weight:1000; line-height:1; }
  .stat-lbl{ margin-top:8px; opacity:.85; font-weight:800; }
  .card-soft{
    border-radius:16px; border:1px solid rgba(0,0,0,.08);
    box-shadow:0 10px 26px rgba(0,0,0,.05);
    overflow:hidden;
  }
  .card-soft .card-hd{
    padding:12px 14px; background:rgba(0,0,0,.02); border-bottom:1px solid rgba(0,0,0,.06);
    display:flex; align-items:center; justify-content:space-between; gap:10px;
  }
  .card-soft .card-hd .ttl{ font-weight:1000; margin:0; }
  .card-soft .card-bd{ padding:14px; background:#fff; }
  .filters-grid{ display:grid; grid-template-columns: 2fr 1fr; gap:12px; }
  @media (max-width: 992px){ .filters-grid{ grid-template-columns:1fr; } }
  .filters-actions{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
  .table thead th{ font-weight:1000; white-space:nowrap; }
  .td-actions{ display:flex; gap:6px; justify-content:flex-start; flex-wrap:wrap; }
  .icon-btn{
    width:34px; height:34px; border-radius:10px;
    display:inline-flex; align-items:center; justify-content:center;
    border:1px solid rgba(0,0,0,.10); background:#fff; text-decoration:none;
  }
  .empty{ padding:22px; text-align:center; color:#6b7280; font-weight:800; }
  .muted{ color:#6b7280; font-weight:800; }
</style>

<div class="page-head">
  <div>
    <h1 class="page-title">الأصول / الأجهزة</h1>
    <div class="page-sub">إدارة الأجهزة وتتبعها حسب الموقع والضمان.</div>
  </div>

  <div class="head-actions">
    <?php if ($canAddBtn): ?>
      <a class="btn btn-dark btn-pill" href="index.php?page=assets/add">+ إضافة جهاز</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary btn-pill" href="<?= htmlspecialchars($printHref) ?>">طباعة</a>
    <a class="btn btn-outline-success btn-pill" href="<?= htmlspecialchars($exportHref) ?>">تصدير Excel</a>
  </div>
</div>

<div class="tabs-pills">
  <a class="tab-pill <?= ($wFilter==='' ? 'active':'') ?>" href="<?= htmlspecialchars(buildUrl(['warranty'=>null])) ?>">عرض الكل</a>
  <a class="tab-pill <?= ($wFilter==='soon' ? 'active':'') ?>" href="<?= htmlspecialchars(buildUrl(['warranty'=>'soon'])) ?>">قرب انتهاء الضمان</a>
  <a class="tab-pill <?= ($wFilter==='expired' ? 'active':'') ?>" href="<?= htmlspecialchars(buildUrl(['warranty'=>'expired'])) ?>">منتهي الضمان</a>
</div>

<div class="stat-row">
  <div class="stat-card">
    <div class="stat-num"><?= (int)$totalCount ?></div>
    <div class="stat-lbl">عدد الأجهزة (حسب التصفية )</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= (int)$soonCount ?></div>
    <div class="stat-lbl">قريب انتهاء الضمان (اقل من 30 يوم)</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= (int)$expiredCount ?></div>
    <div class="stat-lbl">منتهي الضمان</div>
  </div>
</div>

<div class="card-soft mb-3">
  <div class="card-hd">
    <div class="ttl">بحث وفلترة</div>
    <div class="muted">الفلتر يحفظ أيضًا تبويب الضمان</div>
  </div>

  <div class="card-bd">
    <form method="get" action="index.php">
      <input type="hidden" name="page" value="assets/index">
      <?php if ($wFilter !== ''): ?>
        <input type="hidden" name="warranty" value="<?= htmlspecialchars($wFilter) ?>">
      <?php endif; ?>

      <div class="filters-grid">
        <div>
          <label class="form-label fw-bold">بحث (Tag / Serial / Brand / Model)</label>
          <input class="form-control" type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="مثال: Tag-001 أو Serial...">
        </div>

        <div>
          <label class="form-label fw-bold">الموقع</label>
          <select class="form-select" name="location_id">
            <option value="0">— كل المواقع —</option>
            <?php foreach ($locations as $loc): ?>
              <?php
                $id = (int)($loc->id ?? 0);
                $label = $loc->name_ar ?? ($loc->name ?? ('موقع #'.$id));
                $sel = ($selectedLoc === $id) ? 'selected' : '';
              ?>
              <option value="<?= (int)$id ?>" <?= $sel ?>><?= htmlspecialchars((string)$label) ?></option>
            <?php endforeach; ?>
          </select>

          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="incChildren" name="include_children" value="1" <?= $includeChildren ? 'checked' : '' ?>>
            <label class="form-check-label fw-bold" for="incChildren">يشمل التوابع</label>
          </div>
        </div>
      </div>

      <div class="filters-actions mt-3">
        <button class="btn btn-primary btn-pill" type="submit">تطبيق</button>
        <a class="btn btn-outline-secondary btn-pill" href="index.php?page=assets/index">مسح الفلاتر</a>
      </div>
    </form>
  </div>
</div>

<div class="card-soft">
  <div class="card-hd">
    <div class="ttl">النتائج</div>
    <div class="muted">عدد النتائج: <?= (int)$totalCount ?></div>
  </div>

  <div class="card-bd p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th style="width:60px;">QR</th>
            <th>Tag</th>
            <th>النوع</th>
            <th>الماركة / الموديل</th>
            <th>Serial</th>
            <th>الضمان</th>
            <th>الموقع</th>
            <th>الحالة</th>
            <th style="width:130px;">إجراءات</th>
          </tr>
        </thead>

        <tbody>
          <?php if (empty($assets)): ?>
            <tr>
              <td colspan="9" class="empty">لا توجد أجهزة مطابقة للفلاتر الحالية.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($assets as $a): ?>
              <?php
                $id = (int)($a->id ?? 0);
                $locId = (int)($a->location_id ?? 0);

                $tag   = trim((string)($a->asset_tag ?? ''));
                $type  = trim((string)($a->type ?? ''));
                $brand = trim((string)($a->brand ?? ''));
                $model = trim((string)($a->model ?? ''));
                $brandModel = trim(($brand . ' - ' . $model), " -");

                $serial = trim((string)($a->serial_no ?? ($a->serial ?? '')));

                $locationName = trim((string)($a->location_name ?? ''));
                if ($locationName === '' || ctype_digit($locationName)) {
                  $locationName = $locNameById[$locId] ?? ($locationName ?: ('موقع #'.$locId));
                }

                $status = trim((string)($a->status ?? 'Active'));
                $statusLower = strtolower($status);
                $statusCls = 'badgex open';
                if (in_array($statusLower, ['inactive','retired','غير نشط','مستبعد'], true)) $statusCls = 'badgex closed';
                if (in_array($statusLower, ['maintenance','repair','صيانة','تصليح'], true)) $statusCls = 'badgex pending';

                $warrantyExpiry = getWarrantyDate($a);
                $wb = warrantyBadge($warrantyExpiry);

                $qrUrl = $baseUrl . '/index.php?page=assets/show&id=' . $id;
                $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=80x80&margin=0&data=' . urlencode($qrUrl);

                $showHref = 'index.php?page=assets/show&id=' . $id;
                $editHref = 'index.php?page=assets/edit&id=' . $id;
              ?>
              <tr>
                <td>
                  <a class="icon-btn" href="<?= htmlspecialchars($showHref) ?>" title="فتح الجهاز">
                    <img src="<?= htmlspecialchars($qrImg) ?>" alt="QR" style="width:26px;height:26px;border-radius:6px;">
                  </a>
                </td>

                <td class="fw-bold"><?= htmlspecialchars($tag !== '' ? $tag : ('#'.$id)) ?></td>
                <td><?= htmlspecialchars($type) ?></td>
                <td><?= htmlspecialchars($brandModel) ?></td>
                <td><?= htmlspecialchars($serial) ?></td>
                <td><span class="<?= htmlspecialchars($wb['cls']) ?>"><?= htmlspecialchars($wb['text']) ?></span></td>
                <td><?= htmlspecialchars($locationName) ?></td>
                <td><span class="<?= htmlspecialchars($statusCls) ?>"><?= htmlspecialchars($status) ?></span></td>

                <td>
                  <div class="td-actions">
                    <a class="icon-btn" href="<?= htmlspecialchars($showHref) ?>" title="تفاصيل">🔎</a>
                    <a class="icon-btn" href="<?= htmlspecialchars($editHref) ?>" title="تعديل">✏️</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="p-3 muted">
      ملاحظة: “طباعة” تطبع النتائج الحالية حسب الفلاتر.
    </div>
  </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
