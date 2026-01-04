<?php
require APPROOT . '/views/inc/header.php';

/**
 * Helpers
 */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function is_blank($v): bool {
  $v = trim((string)$v);
  if ($v === '' || $v === '-') return true;
  $lv = strtolower($v);
  return ($lv === 'null' || $lv === 'none');
}

function field_val($v): ?string {
  return is_blank($v) ? null : (string)$v;
}

function action_ar($a): string {
  $a = (string)$a;
  return match ($a) {
    'create'   => 'إضافة',
    'update'   => 'تعديل',
    'delete'   => 'حذف',
    'transfer' => 'نقل موقع',
    'status'   => 'تغيير حالة',
    'assign'   => 'تسليم',
    'unassign' => 'إلغاء تسليم',
    default    => $a ?: '-',
  };
}

function user_name_by_id($id, array $users): string {
  $id = (int)$id;
  if ($id <= 0) return '-';
  foreach ($users as $u) {
    if ((int)($u->id ?? 0) === $id) {
      return (string)($u->name ?? $u->username ?? $u->email ?? ('مستخدم #' . $id));
    }
  }
  return 'مستخدم #' . $id;
}

/**
 * Data
 */
$asset = $data['asset'] ?? null;
$logs  = $data['logs'] ?? [];
$users = $data['users'] ?? [];
$qrUrl = $data['qrUrl'] ?? '';

if (!$asset) {
  echo '<div class="container py-4"><div class="alert alert-warning">لا توجد بيانات للجهاز.</div></div>';
  require APPROOT . '/views/inc/footer.php';
  exit;
}

$assetId  = (int)($asset->id ?? ($_GET['id'] ?? 0));
$tag      = field_val($asset->asset_tag ?? null) ?? '-';
$status   = field_val($asset->status ?? null) ?? '-';
$type     = field_val($asset->type ?? null) ?? '-';
$location = field_val($asset->location_name ?? ($asset->location ?? null)) ?? '-';
$brand    = field_val($asset->brand ?? null);
$model    = field_val($asset->model ?? null);
$serial   = field_val($asset->serial_no ?? ($asset->serial ?? null));
$purchase = field_val($asset->purchase_date ?? null);
$warranty = field_val($asset->warranty_expiry ?? null);
$notes    = field_val($asset->notes ?? null);

$assignedTo = (int)($asset->assigned_to ?? 0);

// Status badge
$statusLower = strtolower((string)$status);
$statusBadge = 'badge-soft-secondary';
if (in_array($statusLower, ['active','available','تم التفعيل','نشط'], true)) $statusBadge = 'badge-soft-success';
if (in_array($statusLower, ['maintenance','repair','صيانة','تصليح'], true)) $statusBadge = 'badge-soft-warning';
if (in_array($statusLower, ['inactive','retired','غير نشط','مستبعد'], true)) $statusBadge = 'badge-soft-secondary';

// Warranty indicator
$warrantyBadge = null;
$warrantyText  = null;
$warrantyDays  = null;

if ($warranty) {
  try {
    $wDate = new DateTime($warranty);
    $today = new DateTime('today');
    $diffDays = (int)$today->diff($wDate)->format('%r%a'); // سالب = منتهي
    $warrantyDays = $diffDays;

    if ($diffDays < 0) {
      $warrantyBadge = 'badge-soft-danger';
      $warrantyText  = "منتهي (منذ " . abs($diffDays) . " يوم)";
    } elseif ($diffDays <= 30) {
      $warrantyBadge = 'badge-soft-warning';
      $warrantyText  = "قريب الانتهاء (باقي {$diffDays} يوم)";
    } else {
      $warrantyBadge = 'badge-soft-success';
      $warrantyText  = "سليم (باقي {$diffDays} يوم)";
    }
  } catch (Throwable $e) {
    // ignore invalid date
  }
}

// Fallback qrUrl لو ما جاء من الكنترولر
if (empty($qrUrl)) {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

  // مثال عندك: /it_inventory/public/index.php
  $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');

  // نحذف /index.php من النهاية ونبني الرابط الصحيح
  $base = preg_replace('#/index\.php$#', '', $script);

  $qrUrl = $scheme . '://' . $host . $base . '/index.php?page=assets/show&id=' . (int)$assetId;
}



// High-quality QR (we will render it small)
$qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&margin=0&data=' . urlencode($qrUrl);

?>
<style>
  .badge-soft-success{background:#d1e7dd;color:#0f5132;border:1px solid #badbcc}
  .badge-soft-warning{background:#fff3cd;color:#664d03;border:1px solid #ffecb5}
  .badge-soft-danger{background:#f8d7da;color:#842029;border:1px solid #f5c2c7}
  .badge-soft-secondary{background:#e2e3e5;color:#41464b;border:1px solid #d3d6d8}

  .card-shadow{box-shadow:0 10px 30px rgba(0,0,0,.06); border-radius:14px}
  .ltr{direction:ltr; unicode-bidi:bidi-override}

  /* label preview */
  .label-wrap{
    width: 210px;
    border: 1px solid #e6e6e6;
    border-radius: 12px;
    padding: 10px;
    background:#fff;
  }
  .label-grid{display:grid; grid-template-columns: 1fr 90px; gap:10px; align-items:center;}
  .label-qr img{width:90px;height:90px;display:block}
  .label-tag{font-weight:800; font-size:16px; letter-spacing:.5px}
  .label-mini{font-size:12px; color:#555; line-height:1.2}
  .label-foot{font-size:11px;color:#777;margin-top:6px}

  .btn-group-top{display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end}
</style>

<div class="container py-4">

  <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h3 class="mb-1">تفاصيل الجهاز</h3>
      <div class="text-muted small">ID: <span class="ltr"><?= (int)$assetId ?></span></div>
    </div>

    <div class="btn-group-top">
      <a class="btn btn-outline-secondary" href="index.php?page=assets/index">رجوع</a>
      <a class="btn btn-outline-primary" href="index.php?page=assets/edit&id=<?= (int)$assetId ?>">تعديل</a>
      <button class="btn btn-primary" type="button" onclick="printLabelOnly()">طباعة الملصق</button>
      <button class="btn btn-success" type="button" onclick="printSheetA4()">طباعة شيت A4</button>
    </div>
  </div>

  <div class="row g-3 align-items-start">

    <!-- تفاصيل -->
    <div class="col-lg-7">
      <div class="card card-shadow">
        <div class="card-body">

          <div class="row g-2 mb-3">
            <div class="col-12">
              <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="fw-bold">Tag:</div>
                <span class="badge bg-dark ltr"><?= e($tag) ?></span>
                <span class="badge <?= e($statusBadge) ?>"><?= e($status) ?></span>

                <?php if ($warrantyText): ?>
                  <span class="badge <?= e($warrantyBadge) ?>">الضمان: <?= e($warrantyText) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <tbody>
                <tr><th style="width:180px">الموقع</th><td><?= e($location) ?></td></tr>
                <tr><th>النوع</th><td><?= e($type) ?></td></tr>
                <?php if ($brand): ?><tr><th>الماركة</th><td><?= e($brand) ?></td></tr><?php endif; ?>
                <?php if ($model): ?><tr><th>الموديل</th><td><?= e($model) ?></td></tr><?php endif; ?>
                <?php if ($serial): ?><tr><th>Serial</th><td class="ltr"><?= e($serial) ?></td></tr><?php endif; ?>
                <?php if ($purchase): ?><tr><th>تاريخ الشراء</th><td class="ltr"><?= e($purchase) ?></td></tr><?php endif; ?>
                <?php if ($warranty): ?><tr><th>انتهاء الضمان</th><td class="ltr"><?= e($warranty) ?></td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>

          <?php if ($notes): ?>
            <hr>
            <div class="fw-bold mb-1">ملاحظات</div>
            <div class="text-muted"><?= nl2br(e($notes)) ?></div>
          <?php endif; ?>

        </div>
      </div>

      <!-- تسليم الجهاز -->
      <div class="card card-shadow mt-3">
        <div class="card-body">
          <h6 class="mb-3">تسليم الجهاز</h6>

          <?php if ($assignedTo > 0): ?>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="text-muted">
                مُسلم لـ: <b><?= e(user_name_by_id($assignedTo, $users)) ?></b>
                <span class="text-muted small ms-2">(ID: <span class="ltr"><?= (int)$assignedTo ?></span>)</span>
              </div>

              <form method="post" action="index.php?page=assets/unassign" class="m-0">
                <input type="hidden" name="asset_id" value="<?= (int)$assetId ?>">
                <button class="btn btn-outline-danger btn-sm" type="submit">إلغاء التسليم</button>
              </form>
            </div>
          <?php else: ?>
            <form method="post" action="index.php?page=assets/assign" class="row g-2 align-items-end">
              <input type="hidden" name="asset_id" value="<?= (int)$assetId ?>">

              <div class="col-md-8">
                <label class="form-label">اختر الموظف</label>
                <select name="user_id" class="form-select" required>
                  <option value="">— اختر —</option>
                  <?php foreach ($users as $u): ?>
                    <option value="<?= (int)($u->id ?? 0) ?>">
                      <?= e(($u->name ?? $u->username ?? $u->email ?? ('مستخدم #' . ($u->id ?? '')))) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (empty($users)): ?>
                  <div class="text-muted small mt-1">ملاحظة: لا توجد قائمة موظفين (تحقق من users في الكنترولر).</div>
                <?php endif; ?>
              </div>

              <div class="col-md-4">
                <button class="btn btn-success w-100" type="submit">تسليم</button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- سجل الحركة -->
      <div class="card card-shadow mt-3">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">سجل الحركة</h6>
            <span class="text-muted small">آخر <?= (int)count($logs) ?> عملية</span>
          </div>

          <?php if (empty($logs)): ?>
            <div class="text-muted">لا يوجد سجل حتى الآن.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width:170px">التاريخ</th>
                    <th style="width:140px">العملية</th>
                    <th style="width:140px">المستخدم</th>
                    <th>التفاصيل</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($logs as $l): ?>
                    <?php
                      $logUserId = (int)($l->user_id ?? 0);
                      $logUserName = $logUserId > 0 ? user_name_by_id($logUserId, $users) : 'النظام';
                    ?>
                    <tr>
                      <td class="ltr text-muted"><?= e($l->created_at ?? '-') ?></td>
                      <td><span class="badge bg-light text-dark border"><?= e(action_ar($l->action ?? '')) ?></span></td>
                      <td class="text-muted"><?= e($logUserName) ?></td>
                      <td><?= e($l->details ?? '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

        </div>
      </div>

    </div>

    <!-- ملصق + QR -->
    <div class="col-lg-5">
      <div class="card card-shadow">
        <div class="card-body">

          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">ملصق الجهاز</h6>
            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyLink()">نسخ رابط الجهاز</button>
          </div>

          <div class="d-flex justify-content-center">
            <div class="label-wrap" id="labelArea">
              <div class="label-grid">
                <div>
                  <div class="label-tag ltr"><?= e($tag) ?></div>
                  <div class="label-mini">النوع: <?= e($type) ?></div>
                  <div class="label-mini">الموقع: <?= e($location) ?></div>
                  <div class="label-foot text-muted ltr" style="display:none"></div>

                </div>
                <div class="label-qr text-center">
                  <img src="<?= e($qrImg) ?>" alt="QR">
                </div>
              </div>
              <div class="text-center text-muted mt-2" style="font-size:11px">امسح QR لفتح صفحة الجهاز</div>
            </div>
          </div>

          <div id="copyOk" class="text-success small mt-2" style="display:none">✅ تم نسخ الرابط</div>
          <div class="text-muted small mt-2">ملاحظة: “طباعة الملصق” تطبع الملصق فقط (بدون باقي الصفحة).</div>

        </div>
      </div>
    </div>

  </div>
</div>

<script>
  function copyLink(){
    const url = <?= json_encode($qrUrl) ?>;
    navigator.clipboard.writeText(url).then(() => {
      const el = document.getElementById('copyOk');
      el.style.display = 'block';
      setTimeout(()=> el.style.display = 'none', 1500);
    });
  }

  function openPrintWindow(html, pageCss){
    const w = window.open('', '_blank');
    w.document.open();
    w.document.write(`
      <html lang="ar" dir="rtl">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Print</title>
        <style>
          body{font-family: Arial, sans-serif; margin:0; padding:0;}
          ${pageCss}
        </style>
      </head>
      <body>${html}</body>
      </html>
    `);
    w.document.close();
    w.focus();
    setTimeout(() => {
      w.print();
      w.close();
    }, 350);
  }

  // ✅ طباعة الملصق فقط (حل مشكلة تعدد الصفحات)
  function printLabelOnly(){
    const label = document.getElementById('labelArea').outerHTML;

    const css = `
      @page { size: 58mm 38mm; margin: 0; }
      body{display:flex; align-items:center; justify-content:center;}
      .label-wrap{width: 58mm; border:0; padding:3mm;}
      .label-grid{grid-template-columns: 1fr 22mm; gap:3mm;}
      .label-qr img{width:22mm;height:22mm;}
      .label-tag{font-size:14px;}
      .label-mini{font-size:11px;}
      .label-foot{display:none;}
      .text-muted{color:#555;}
    `;

    openPrintWindow(label, css);
  }

  // ✅ طباعة A4 (يعرض الملصق بشكل أكبر + معلومات)
  function printSheetA4(){
    const tag = <?= json_encode($tag) ?>;
    const type = <?= json_encode($type) ?>;
    const location = <?= json_encode($location) ?>;
    const qrImg = <?= json_encode($qrImg) ?>;
    const url = <?= json_encode($qrUrl) ?>;

    const html = `
      <div style="padding:14mm">
        <h2 style="margin:0 0 10mm 0;">نظام إدارة العهد - ملصق جهاز</h2>
        <div style="border:1px solid #ddd; border-radius:12px; padding:10mm; display:flex; gap:10mm; align-items:center;">
          <div style="flex:1">
            <div style="font-size:22px; font-weight:800; direction:ltr">${tag}</div>
            <div style="margin-top:4mm; font-size:16px">النوع: ${type}</div>
            <div style="margin-top:2mm; font-size:16px">الموقع: ${location}</div>
            <div style="margin-top:6mm; font-size:12px; direction:ltr; color:#555">${url}</div>
          </div>
          <div>
            <img src="${qrImg}" style="width:50mm; height:50mm" />
          </div>
        </div>
      </div>
    `;

    const css = `
      @page { size: A4; margin: 10mm; }
    `;

    openPrintWindow(html, css);
  }
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
