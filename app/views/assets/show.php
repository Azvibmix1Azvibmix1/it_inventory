<?php require APPROOT . '/views/inc/header.php'; ?>
<style>
  .qr-box { width: 180px; }
  .qr { width: 160px; height: 160px; margin: 0 auto; }
  .qr img, .qr canvas { width: 160px !important; height: 160px !important; display:block; margin:0 auto; }
  @media print {
    .no-print, .no-print * { display:none !important; }
  }
</style>

<?php
  $a = $data['asset'];
  $locName = $data['location_name'] ?? '—';

  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath;

  // رابط نفس الصفحة (يفيد للمسح)
  $url = $baseUrl . '/index.php?page=assets/show&id=' . (int)$a->id;
?>

<div class="container py-4" dir="rtl">
  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a class="btn btn-outline-secondary" href="index.php?page=assets/index">رجوع</a>
    <button class="btn btn-primary" onclick="window.print()">طباعة ملصق</button>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="row g-4 align-items-start">

        <div class="col-12 col-md-8">
          <h4 class="fw-bold mb-3">تفاصيل الجهاز</h4>

          <div class="mb-2"><b>Tag:</b> <?= htmlspecialchars($a->asset_tag ?? '') ?></div>
          <div class="mb-2"><b>النوع:</b> <?= htmlspecialchars($a->type ?? '') ?></div>
          <div class="mb-2"><b>الماركة:</b> <?= htmlspecialchars($a->brand ?? '-') ?></div>
          <div class="mb-2"><b>الموديل:</b> <?= htmlspecialchars($a->model ?? '-') ?></div>
          <div class="mb-2"><b>Serial:</b> <?= htmlspecialchars($a->serial_no ?? '-') ?></div>
          <div class="mb-2"><b>الموقع:</b> <?= htmlspecialchars($locName) ?></div>
          <div class="mb-2"><b>الحالة:</b> <?= htmlspecialchars($a->status ?? '-') ?></div>

          <?php if (!empty($a->purchase_date)): ?>
            <div class="mb-2"><b>تاريخ الشراء:</b> <?= htmlspecialchars($a->purchase_date) ?></div>
          <?php endif; ?>
          <?php if (!empty($a->warranty_expiry)): ?>
            <div class="mb-2"><b>انتهاء الضمان:</b> <?= htmlspecialchars($a->warranty_expiry) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-12 col-md-4">
          <div class="card qr-box">
            <div class="card-body text-center">
              <div class="fw-bold mb-2">QR</div>
              <div class="qr" id="qr" data-text="<?= htmlspecialchars($url) ?>"></div>
              <div class="small text-muted mt-2">يمسح ويعرض تفاصيل الجهاز</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  window.addEventListener('load', function () {
    const el = document.getElementById('qr');
    if (!el) return;
    const text = (el.getAttribute('data-text') || '').trim();
    if (!text) return;
    el.innerHTML = '';
    new QRCode(el, { text, width: 160, height: 160, correctLevel: QRCode.CorrectLevel.M });
  });
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
