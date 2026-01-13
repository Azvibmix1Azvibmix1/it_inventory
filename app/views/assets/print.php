<?php
// app/views/assets/print.php
$assets = $data['assets'] ?? [];

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>طباعة الأصول</title>
  <style>
    body{ font-family: Arial, sans-serif; margin: 18px; color:#111; }
    .topbar{ display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
    h2{ margin:0; font-size:18px; }
    .no-print button{ padding:8px 12px; cursor:pointer; }
    table{ width:100%; border-collapse: collapse; }
    th, td{ border:1px solid #e5e7eb; padding:8px; font-size:12px; vertical-align:middle; }
    th{ background:#f3f4f6; font-weight:800; }
    .ltr{ direction:ltr; unicode-bidi: plaintext; text-align:left; font-family: Consolas, monospace; white-space:nowrap; }
    .muted{ color:#6b7280; }
    @media print{
      .no-print{ display:none !important; }
      body{ margin: 0; }
      th{ -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>

  <div class="topbar">
    <div>
      <h2>قائمة الأصول / الأجهزة</h2>
      <div class="muted">عدد الأجهزة: <?= (int)count($assets); ?></div>
    </div>
    <div class="no-print">
      <button onclick="window.print()">طباعة</button>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:40px;">#</th>
        <th class="ltr">Tag</th>
        <th class="ltr">Host Name</th>
        <th class="ltr">MAC</th>
        <th class="ltr">Serial</th>
        <th>النوع</th>
        <th>الماركة / الموديل</th>
        <th>الحالة</th>
        <th>الموقع</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($assets)): ?>
        <tr><td colspan="9" style="text-align:center;" class="muted">لا توجد بيانات للطباعة</td></tr>
      <?php else: ?>
        <?php $i=1; foreach($assets as $a): ?>
          <?php
            $loc = $a->location_path ?? ($a->location_name ?? ($a->location_ar ?? '—'));
            $brand = trim((string)($a->brand ?? ''));
            $model = trim((string)($a->model ?? ''));
            $bm = trim($brand . ($brand && $model ? ' / ' : '') . $model);
          ?>
          <tr>
            <td><?= $i++; ?></td>
            <td class="ltr"><?= h($a->asset_tag ?? '-'); ?></td>
            <td class="ltr"><?= h($a->host_name ?? '-'); ?></td>
            <td class="ltr"><?= h($a->mac_address ?? '-'); ?></td>
            <td class="ltr"><?= h($a->serial_no ?? '-'); ?></td>
            <td><?= h($a->type ?? '-'); ?></td>
            <td><?= h($bm ?: '-'); ?></td>
            <td><?= h($a->status ?? '-'); ?></td>
            <td><?= h($loc); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

</body>
</html>
