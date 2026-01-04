<?php require APPROOT . '/views/inc/header.php'; ?>
<style>
  .qr-box { width: 220px; }
  .qr { width: 180px; height: 180px; margin: 0 auto; }
  .qr img, .qr canvas { width: 180px !important; height: 180px !important; display:block; margin:0 auto; }
  @media print {
    .no-print, .no-print * { display:none !important; }
    .card { border: none !important; box-shadow: none !important; }
  }
</style>

<?php
  $a = $data['asset'] ?? null;
  if (!$a) { die('Asset not found'); }

  $qrUrl = $data['qrUrl'] ?? '';
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
          <div class="mb-2"><b>الموقع:</b> <?= htmlspecialchars($a->location_name ?? ('موقع #' . (int)($a->location_id ?? 0))) ?></div>
          <div class="mb-2"><b>الحالة:</b> <?= htmlspecialchars($a->status ?? '-') ?></div>

          <?php if (!empty($a->assigned_name) || !empty($a->assigned_email)): ?>
            <div class="mb-2"><b>مُسند إلى:</b>
              <?= htmlspecialchars(trim(($a->assigned_name ?? '') . ' ' . ($a->assigned_email ? '(' . $a->assigned_email . ')' : ''))) ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($a->purchase_date)): ?>
            <div class="mb-2"><b>تاريخ الشراء:</b> <?= htmlspecialchars($a->purchase_date) ?></div>
          <?php endif; ?>

          <?php if (!empty($a->warranty_expiry)): ?>
            <div class="mb-2"><b>انتهاء الضمان:</b> <?= htmlspecialchars($a->warranty_expiry) ?></div>
          <?php endif; ?>

          <?php if (!empty($a->notes)): ?>
            <div class="mt-3">
              <b>ملاحظات:</b>
              <div class="border rounded p-2 mt-1"><?= nl2br(htmlspecialchars($a->notes)) ?></div>
            </div>
          <?php endif; ?>
        </div>

        <div class="col-12 col-md-4">
          <div class="card qr-box">
            <div class="card-body text-center">
              <div class="fw-bold mb-2">QR</div>
              <div class="qr" id="qr" data-text="<?= htmlspecialchars($qrUrl) ?>"></div>
              <div class="small text-muted mt-2">يمسح ويعرض تفاصيل الجهاز</div>
              <div class="small text-muted mt-1" style="word-break: break-all;"><?= htmlspecialchars($qrUrl) ?></div>
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
    new QRCode(el, { text, width: 180, height: 180, correctLevel: QRCode.CorrectLevel.M });
  });
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
