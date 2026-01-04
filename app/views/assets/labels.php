<?php require APPROOT . '/views/inc/header.php'; ?>
<style>
  @media print {
    .no-print, .no-print * { display:none !important; }
    body { background:#fff !important; }
  }

  .sheet {
    padding: 10px;
  }

  /* شبكة ملصقات A4 تقريبًا: 3 أعمدة */
  .labels {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
  }

  .label {
    border: 1px dashed #999;
    border-radius: 10px;
    padding: 10px;
    min-height: 120px;
    display: grid;
    grid-template-columns: 90px 1fr;
    gap: 10px;
    align-items: center;
  }

  .qr { width: 90px; height: 90px; }
  .qr img, .qr canvas { width: 90px !important; height: 90px !important; display:block; }

  .tag { font-weight: 800; font-size: 14px; word-break: break-word; }
  .meta { font-size: 12px; color: #555; line-height: 1.3; }

</style>

<?php
  $assets = $data['assets'] ?? [];
  $baseUrl = $data['baseUrl'] ?? '';
?>

<div class="container py-3 no-print" dir="rtl">
  <div class="d-flex justify-content-between align-items-center">
    <a class="btn btn-outline-secondary" href="index.php?page=assets/index">رجوع</a>
    <button class="btn btn-primary" onclick="window.print()">طباعة الملصقات</button>
  </div>
</div>

<div class="sheet" dir="rtl">
  <div class="labels">
    <?php foreach ($assets as $a): ?>
      <?php
        $id = (int)($a->id ?? 0);
        $tag = (string)($a->asset_tag ?? '');
        $loc = (string)($a->location_name ?? ('موقع #' . (int)($a->location_id ?? 0)));

        // ✅ رابط صفحة التفاصيل (QR يفتحها)
        $url = $baseUrl . '/index.php?page=assets/show&id=' . $id;
      ?>
      <div class="label">
        <div class="qr" data-text="<?= htmlspecialchars($url) ?>"></div>
        <div>
          <div class="tag"><?= htmlspecialchars($tag) ?></div>
          <div class="meta">
            <?= htmlspecialchars($a->type ?? '-') ?> • <?= htmlspecialchars($loc) ?><br>
            SN: <?= htmlspecialchars($a->serial_no ?? '-') ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  window.addEventListener('load', function () {
    document.querySelectorAll('.qr').forEach(el => {
      const text = (el.getAttribute('data-text') || '').trim();
      if (!text) return;
      el.innerHTML = '';
      new QRCode(el, { text, width: 90, height: 90, correctLevel: QRCode.CorrectLevel.M });
    });
  });
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
