<?php
require APPROOT . '/views/inc/header.php';

$asset = $data['asset'] ?? null;
$logs  = $data['logs']  ?? [];
$qrUrl = $data['qrUrl'] ?? '';


function action_ar($a){
  switch($a){
    case 'create': return 'إضافة';
    case 'update': return 'تعديل';
    case 'delete': return 'حذف';
    case 'transfer': return 'نقل موقع';
    case 'status': return 'تغيير حالة';
    default: return $a ?: '-';
  }
}

$qrUrl = $data['qrUrl'] ?? '';

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function is_blank($v){
  $v = trim((string)$v);
  if ($v === '' || $v === '-' ) return true;
  $lv = strtolower($v);
  return ($lv === 'null' || $lv === 'none');
}
function field_val($v){
  return is_blank($v) ? null : (string)$v;
}

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
// ===== Warranty indicator (days left) =====
$warrantyBadge = null;
$warrantyText  = null;

if ($warranty && $warranty !== '-') {
  try {
    $wDate = new DateTime($warranty);
    $today = new DateTime('today');
    $diffDays = (int)$today->diff($wDate)->format('%r%a'); // سالب = منتهي

    if ($diffDays < 0) {
      $warrantyBadge = 'badge-soft-secondary';
      $warrantyText  = "الضمان منتهي (منذ " . abs($diffDays) . " يوم)";
    } elseif ($diffDays <= 30) {
      $warrantyBadge = 'badge-soft-warning';
      $warrantyText  = "الضمان قرب ينتهي (باقي {$diffDays} يوم)";
    } else {
      $warrantyBadge = 'badge-soft-success';
      $warrantyText  = "الضمان سليم (باقي {$diffDays} يوم)";
    }
  } catch (Exception $e) {
    // تجاهل لو التاريخ مو مضبوط
  }
}

// fallback لو ما وصل qrUrl من الكنترولر
if (empty($qrUrl)) {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
  $qrUrl = $scheme . '://' . $host . $basePath . '/index.php?page=assets/show&id=' . $assetId;
}

// QR بجودة عالية (نطبعها صغيرة)
$qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=0&data=' . urlencode($qrUrl);

// شارات الحالة
$statusLower = strtolower((string)$status);
$statusBadge = 'badge-soft-secondary';
if (in_array($statusLower, ['active','available','تم التفعيل','نشط'])) $statusBadge = 'badge-soft-success';
if (in_array($statusLower, ['inactive','retired','غير نشط','مستبعد'])) $statusBadge = 'badge-soft-secondary';
if (in_array($statusLower, ['maintenance','repair','صيانة','تصليح'])) $statusBadge = 'badge-soft-warning';
?>
<style>
  :root{
    --gov-primary:#0F5132;
    --gov-primary-dark:#0B3D26;
    --gov-accent:#1B7A57;
    --gov-bg:#F5F7F9;
    --gov-text:#1F2937;
    --gov-border:#E5E7EB;
  }

  body{ background:var(--gov-bg); color:var(--gov-text); }
  .asset-show-page{direction:rtl}

  /* تحسين عام للشكل */
  .card{ border-color:var(--gov-border) !important; border-radius:14px; }
  .card-shadow{ box-shadow: 0 6px 18px rgba(0,0,0,.06); }
  .btn-primary{ background:var(--gov-primary) !important; border-color:var(--gov-primary) !important; }
  .btn-primary:hover{ background:var(--gov-primary-dark) !important; border-color:var(--gov-primary-dark) !important; }
  .btn-success{ background:var(--gov-accent) !important; border-color:var(--gov-accent) !important; }

  .ltr{direction:ltr; unicode-bidi:bidi-override}
  .kpi{display:flex; flex-wrap:wrap; gap:10px}
  .kpi .chip{background:#fff; border:1px solid var(--gov-border); border-radius:999px; padding:6px 10px; font-size:13px}
  .kpi .chip b{font-weight:800}

  /* شارات ناعمة */
  .badge-soft-success{ background:#E7F6ED; color:#0F5132; border:1px solid #BFE6CE; }
  .badge-soft-warning{ background:#FFF4E5; color:#8A4B00; border:1px solid #FFD9A8; }
  .badge-soft-secondary{ background:#F1F5F9; color:#334155; border:1px solid #E2E8F0; }

  /* ====== الملصق (مقاس افتراضي صغير) ====== */
  /* اختر مقاس الملصق (فعل واحد فقط) */

  /* 50×30mm */
  @page { size: 50mm 30mm; margin: 0; }
  :root { --label-w: 50mm; --label-h: 30mm; --qr: 18mm; }

  /* 57×32mm */
  /* @page { size: 57mm 32mm; margin: 0; }
  :root { --label-w: 57mm; --label-h: 32mm; --qr: 20mm; } */

  /* 80×50mm */
  /* @page { size: 80mm 50mm; margin: 0; }
  :root { --label-w: 80mm; --label-h: 50mm; --qr: 26mm; } */

  .label-box{
    width: var(--label-w);
    height: var(--label-h);
    padding: 2mm;
    border: 1px solid var(--gov-border);
    border-radius: 10px;
    background: #fff;
    display:flex;
    flex-direction:column;
    justify-content:center;
    gap: 1.2mm;
  }
  .label-tag{font-weight:900; font-size:12px; line-height:1; text-align:center}
  .label-row{display:flex; align-items:center; justify-content:space-between; gap:2mm}
  .label-qr{width: var(--qr); height: var(--qr); display:block; flex:0 0 auto}
  .label-info{flex:1 1 auto; font-size:8.5px; line-height:1.15}
  .label-info b{font-weight:800}
  .label-note{font-size:6.5px; opacity:.75; line-height:1.1}
</style>

<div class="container py-4 asset-show-page">

  <!-- رأس الصفحة -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">تفاصيل الجهاز</h3>

    <div class="btn-group">
      <a class="btn btn-outline-secondary" href="index.php?page=assets/index">رجوع</a>
      <a class="btn btn-outline-primary" href="index.php?page=assets/edit&id=<?=$assetId?>">تعديل</a>
      <button class="btn btn-primary" type="button" onclick="printLabelOnly()">طباعة الملصق</button>
      <button class="btn btn-success" type="button" onclick="printSheetA4()">طباعة شيت A4</button>
    </div>
  </div>

  <!-- ملخص سريع -->
  <div class="card card-shadow mb-3">
    <div class="card-body">
      <div class="kpi">
        <div class="chip"><b>Tag:</b> <span class="badge bg-dark ltr"><?=e($tag)?></span></div>
        <div class="chip"><b>الحالة:</b> <span class="badge <?=$statusBadge?>"><?=e($status)?></span></div>
        <div class="chip"><b>الموقع:</b> <?=e($location)?></div>
        <div class="chip"><b>النوع:</b> <?=e($type)?></div>
        <?php if ($serial): ?>
          <div class="chip"><b>Serial:</b> <span class="ltr"><?=e($serial)?></span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php if ($warrantyText): ?>
  <div class="card card-shadow mb-3">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div style="font-weight:800;">
        <i class="bi bi-shield-check"></i> تنبيه الضمان
      </div>
      <div>
        <span class="badge <?= $warrantyBadge ?>"><?= e($warrantyText) ?></span>

        <span class="text-muted small ms-2 ltr">(<?= e($warranty) ?>)</span>
      </div>
    </div>
  </div>
<?php endif; ?>

  <div class="row g-3 align-items-start">

    <!-- ملصق QR + زر نسخ الرابط -->
    <div class="col-lg-5">
      <div class="card card-shadow">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div style="font-weight:800;">ملصق الجهاز</div>
            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyLink()">نسخ رابط الجهاز</button>
          </div>

          <div id="labelPreview" class="label-box">
            <div class="label-tag ltr"><?=e($tag)?></div>

            <div class="label-row">
              <img class="label-qr" src="<?=e($qrImg)?>" alt="QR">
              <div class="label-info">
                <div><b>النوع:</b> <?=e($type)?></div>
                <div><b>الموقع:</b> <?=e($location)?></div>
                <div class="label-note ltr">ID: <?=e($assetId)?></div>
              </div>
            </div>

            <div class="label-note">امسح QR لفتح صفحة الجهاز</div>
          </div>

          <div class="small text-muted mt-2" id="copyMsg" style="display:none;">✅ تم نسخ الرابط</div>
        </div>
      </div>
    </div>

    <!-- التفاصيل (بدون الفاضي) -->
    <div class="col-lg-7">
      <div class="card card-shadow">
        <div class="card-body">

          <?php
          $rows = [];

          // لا نعرض إلا اللي له قيمة
          $rows[] = ['Tag', '<span class="badge bg-dark ltr">'.e($tag).'</span>', false];
          $rows[] = ['الحالة', '<span class="badge '.$statusBadge.'">'.e($status).'</span>', false];
          $rows[] = ['الموقع', e($location), false];
          $rows[] = ['النوع', e($type), false];

          if ($brand)   $rows[] = ['الماركة', e($brand), false];
          if ($model)   $rows[] = ['الموديل', e($model), false];
          if ($serial)  $rows[] = ['Serial', '<span class="ltr">'.e($serial).'</span>', true];
          if ($purchase)$rows[] = ['تاريخ الشراء', '<span class="ltr">'.e($purchase).'</span>', true];
          if ($warranty)$rows[] = ['انتهاء الضمان', '<span class="ltr">'.e($warranty).'</span>', true];
          ?>

          <dl class="row mb-0">
            <?php foreach ($rows as $r): ?>
              <dt class="col-sm-4"><?=e($r[0])?></dt>
              <dd class="col-sm-8"><?= $r[1] ?></dd>
            <?php endforeach; ?>

            <?php if ($notes): ?>
              <dt class="col-sm-4">ملاحظات</dt>
              <dd class="col-sm-8"><?= nl2br(e($notes)) ?></dd>
            <?php endif; ?>
          </dl>

        </div>
      </div>
    </div>
  </div>
</div>
<div class="row g-3 mt-3">
  <div class="col-12">
    <div class="card card-shadow">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="mb-0">سجل الحركة</h6>
          <span class="text-muted small">آخر <?= count($logs) ?> عملية</span>
        </div>

        <?php if (empty($logs)): ?>
          <div class="text-muted">لا يوجد سجل حتى الآن.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th style="width:160px">التاريخ</th>
                  <th style="width:140px">العملية</th>
                  <th style="width:120px">المستخدم</th>
                  <th>التفاصيل</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($logs as $l): ?>
                  <tr>
                    <td class="ltr text-muted"><?= e($l->created_at ?? '-') ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e(action_ar($l->action ?? '')) ?></span></td>
                    <td class="text-muted">
                      <?= !empty($l->user_id) ? ('مستخدم #'.e($l->user_id)) : 'النظام' ?>
                    </td>
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
</div>

<script>
  function copyLink(){
    const url = <?= json_encode($qrUrl) ?>;
    const msg = document.getElementById('copyMsg');

    // Clipboard API
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(url).then(() => {
        msg.style.display = 'block';
        setTimeout(()=> msg.style.display='none', 1500);
      }).catch(()=> fallbackCopy(url));
    } else {
      fallbackCopy(url);
    }
  }

  function fallbackCopy(text){
    const ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    ta.remove();
    const msg = document.getElementById('copyMsg');
    msg.style.display = 'block';
    setTimeout(()=> msg.style.display='none', 1500);
  }

  // ===== طباعة ملصق واحد فقط =====
  function printLabelOnly(){
    const label = document.querySelector('#labelPreview');
    if(!label){ window.print(); return; }

    const w = window.open('', '_blank', 'width=600,height=600');
    w.document.open();
    w.document.write(`
      <!doctype html>
      <html lang="ar" dir="rtl">
      <head>
        <meta charset="utf-8">
        <title>Print Label</title>
        <style>
          @page { size: var(--label-w) var(--label-h); margin: 0; }
          :root { --label-w: ${getComputedStyle(document.documentElement).getPropertyValue('--label-w').trim() || '50mm'};
                  --label-h: ${getComputedStyle(document.documentElement).getPropertyValue('--label-h').trim() || '30mm'};
                  --qr: ${getComputedStyle(document.documentElement).getPropertyValue('--qr').trim() || '18mm'}; }
          html, body{ margin:0; padding:0; }
          .ltr{direction:ltr; unicode-bidi:bidi-override}
          .label-box{
            width: var(--label-w);
            height: var(--label-h);
            padding: 2mm;
            box-sizing:border-box;
            background:#fff;
            display:flex;
            flex-direction:column;
            justify-content:center;
            gap: 1.2mm;
          }
          .label-tag{font-weight:900; font-size:12px; line-height:1; text-align:center}
          .label-row{display:flex; align-items:center; justify-content:space-between; gap:2mm}
          .label-qr{width: var(--qr); height: var(--qr); display:block; flex:0 0 auto}
          .label-info{flex:1 1 auto; font-size:8.5px; line-height:1.15}
          .label-info b{font-weight:800}
          .label-note{font-size:6.5px; opacity:.75; line-height:1.1}
        </style>
      </head>
      <body>
        ${label.outerHTML}
        <script>
          window.onload = function(){ window.focus(); window.print(); window.close(); }
        <\/script>
      </body>
      </html>
    `);
    w.document.close();
  }
<?php $users = $data['users'] ?? []; ?>
<div class="card card-shadow mt-3">
  <div class="card-body">
    <h6 class="mb-3">تسليم الجهاز</h6>

    <?php if (!empty($asset->assigned_to)): ?>
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="text-muted">
          مُسلم لـ: <b><?= e($asset->assigned_to) ?></b>
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
                <?= e(($u->name ?? $u->username ?? ('مستخدم #'.($u->id ?? '')))) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <button class="btn btn-success w-100" type="submit">تسليم</button>
        </div>
      </form>
    <?php endif; ?>

  </div>
</div>

  function printSheetA4(){
    const label = document.querySelector('#labelPreview');
    if(!label){ alert('الملصق غير موجود'); return; }

    const qty = Math.max(1, parseInt(prompt('كم ملصق تبغى في الشيت؟', '12'), 10) || 12);

    const rootStyle = getComputedStyle(document.documentElement);
    const labelW = (rootStyle.getPropertyValue('--label-w') || '50mm').trim();
    const labelH = (rootStyle.getPropertyValue('--label-h') || '30mm').trim();
    const qrS    = (rootStyle.getPropertyValue('--qr')      || '18mm').trim();

    let labelsHtml = '';
    for (let i = 0; i < qty; i++) labelsHtml += label.outerHTML;

    const w = window.open('', '_blank', 'width=950,height=720');
    w.document.open();
    w.document.write(`


  <style>
    @page { size: A4; margin: 8mm; }
    html, body { margin:0; padding:0; }
    body { direction:rtl; font-family: Arial, sans-serif; }

    :root { --label-w:${labelW}; --label-h:${labelH}; --qr:${qrS}; }
    .ltr{direction:ltr; unicode-bidi:bidi-override}

    .sheet{
      display:grid;
      grid-template-columns: repeat(auto-fill, minmax(var(--label-w), var(--label-w)));
      gap: 4mm;
      align-content:start;
    }

    .label-box{
      width: var(--label-w);
      height: var(--label-h);
      padding: 2mm;
      box-sizing:border-box;
      border: 1px dashed #999;  /* خط قص */
      background:#fff;
      display:flex;
      flex-direction:column;
      justify-content:center;
      gap: 1.2mm;
      page-break-inside: avoid;
    }
    .label-tag{font-weight:900; font-size:12px; line-height:1; text-align:center}
    .label-row{display:flex; align-items:center; justify-content:space-between; gap:2mm}
    .label-qr{width: var(--qr); height: var(--qr); display:block; flex:0 0 auto}
    .label-info{flex:1 1 auto; font-size:8.5px; line-height:1.15}
    .label-info b{font-weight:800}
    .label-note{font-size:6.5px; opacity:.75; line-height:1.1}
  </style>
</head>
<body>
  <div class="sheet">
    ${labelsHtml}
  </div>
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
