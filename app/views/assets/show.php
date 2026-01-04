<?php require APPROOT . '/views/inc/header.php'; ?>

<?php
// نحاول نجيب الـ asset بأي شكل (حسب مشروعك)
$asset = $data['asset'] ?? ($asset ?? null);

// لو ما فيه بيانات
if (!$asset) {
  echo '<div class="container py-4"><div class="alert alert-danger">لا توجد بيانات للجهاز.</div></div>';
  require APPROOT . '/views/inc/footer.php';
  exit;
}

// رابط صفحة الجهاز (هذا اللي بنحطه داخل الـ QR)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

// مسار مجلد public (لأن عندك index.php داخل public)
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetId  = (int)($asset->id ?? 0);

// رابط ثابت للعرض
$assetUrl = $scheme . '://' . $host . $basePath . '/index.php?page=assets/show&id=' . $assetId;

// QR كصورة (يشتغل طالما عندك انترنت)
$qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($assetUrl);

// مساعدات عرض
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<style>
  @media print {
    .no-print, .no-print * { display: none !important; }
    body { background: #fff !important; }
    .print-area { box-shadow: none !important; border: none !important; }
  }

  .qr-box {
    width: 180px;
    text-align: center;
  }
  .qr-box img {
    width: 160px;
    height: 160px;
    display: block;
    margin: 0 auto;
    border: 1px solid rgba(0,0,0,.1);
    padding: 6px;
    border-radius: 8px;
    background: #fff;
  }

  .tag-big {
    font-weight: 800;
    letter-spacing: .5px;
  }

  /* ملصق للطباعة */
  .sticker {
    width: 320px;
    border: 1px dashed rgba(0,0,0,.25);
    border-radius: 12px;
    padding: 12px;
    background: #fff;
  }
  .sticker .rowx {
    display: flex;
    gap: 12px;
    align-items: center;
  }
  .sticker .qr-sm img {
    width: 110px;
    height: 110px;
    border: 1px solid rgba(0,0,0,.12);
    padding: 4px;
    border-radius: 8px;
    background: #fff;
  }
  .sticker .meta {
    flex: 1;
    min-width: 0;
  }
  .sticker .meta .tag {
    font-weight: 900;
    font-size: 14px;
    line-height: 1.2;
    word-break: break-word;
  }
  .sticker .meta .sub {
    font-size: 12px;
    color: #555;
    margin-top: 6px;
  }
</style>

<div class="container py-3" dir="rtl">

  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h4 class="mb-0">تفاصيل الجهاز</h4>

    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="index.php?page=assets/index">رجوع</a>

      <a class="btn btn-outline-primary" href="index.php?page=assets/edit&id=<?= (int)$asset->id ?>">
        تعديل
      </a>

      <button class="btn btn-success" onclick="window.print()">طباعة الملصق</button>
    </div>
  </div>

  <div class="card mb-3 print-area">
    <div class="card-body">
      <div class="d-flex flex-wrap gap-4 justify-content-between align-items-start">

        <div class="flex-grow-1">
          <div class="mb-2 tag-big">Tag: <?= e($asset->asset_tag ?? '-') ?></div>

          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
              <tbody>
                <tr>
                  <th style="width:180px;">الحالة</th>
                  <td><?= e($asset->status ?? '-') ?></td>
                </tr>
                <tr>
                  <th>الموقع</th>
                  <td><?= e($asset->location_name ?? $asset->location ?? $asset->name_ar ?? '-') ?></td>
                </tr>
                <tr>
                  <th>النوع</th>
                  <td><?= e($asset->type ?? '-') ?></td>
                </tr>
                <tr>
                  <th>الماركة</th>
                  <td><?= e($asset->brand ?? '-') ?></td>
                </tr>
                <tr>
                  <th>الموديل</th>
                  <td><?= e($asset->model ?? '-') ?></td>
                </tr>
                <tr>
                  <th>Serial</th>
                  <td><?= e($asset->serial_no ?? '-') ?></td>
                </tr>
                <tr>
                  <th>تاريخ الشراء</th>
                  <td><?= e($asset->purchase_date ?? '-') ?></td>
                </tr>
                <tr>
                  <th>انتهاء الضمان</th>
                  <td><?= e($asset->warranty_expiry ?? '-') ?></td>
                </tr>
                <tr>
                  <th>ملاحظات</th>
                  <td><?= e($asset->notes ?? '-') ?></td>
                </tr>
                <tr class="no-print">
                  <th>رابط QR</th>
                  <td>
                    <a href="<?= e($assetUrl) ?>" target="_blank"><?= e($assetUrl) ?></a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="qr-box">
          <div class="fw-bold mb-2">QR (رابط الجهاز)</div>
          <img src="<?= e($qrImg) ?>" alt="QR">
          <div class="small text-muted mt-2">امسحه لفتح صفحة الجهاز</div>
        </div>

      </div>
    </div>
  </div>

  <!-- ملصق بسيط للطباعة -->
  <div class="sticker print-area">
    <div class="rowx">
      <div class="qr-sm">
        <img src="<?= e($qrImg) ?>" alt="QR">
      </div>

      <div class="meta">
        <div class="tag"><?= e($asset->asset_tag ?? '-') ?></div>
        <div class="sub">
          <div>النوع: <?= e($asset->type ?? '-') ?></div>
          <div>الموقع: <?= e($asset->location_name ?? $asset->location ?? '-') ?></div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
