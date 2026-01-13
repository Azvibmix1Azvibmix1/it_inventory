<?php
require APPROOT . '/views/inc/header.php';

/**
 * توقعات الداتا القادمة من الكنترولر:
 * $data['assets']          => array of objects
 * $data['locations']       => array of locations (للـ dropdown)
 * $data['q']               => نص البحث
 * $data['location_id']     => رقم الموقع المختار
 * $data['include_children']=> 0/1
 * $data['counts']          => ['total'=>..,'expiring'=>..,'expired'=>..] (اختياري)
 */

$assets           = $data['assets'] ?? [];
$locations        = $data['locations'] ?? [];
$q                = (string)($data['q'] ?? '');
$location_id      = (int)($data['location_id'] ?? 0);
$include_children = !empty($data['include_children']);

$counts = $data['counts'] ?? [];
$totalCount   = (int)($counts['total'] ?? count($assets));
$expiringCnt  = (int)($counts['expiring'] ?? 0);
$expiredCnt   = (int)($counts['expired'] ?? 0);

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

?>
<style>
  .ltr{
    direction:ltr;
    unicode-bidi: plaintext;
    text-align:left;
    white-space: nowrap;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
  }
  .kpi-card{
    background: linear-gradient(135deg, #0b1220 0%, #111827 50%, #0b1220 100%);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 16px;
    color:#fff;
    min-height: 92px;
  }
  .kpi-card .kpi-num{ font-size: 26px; font-weight: 800; line-height: 1; }
  .kpi-card .kpi-label{ opacity:.9; font-weight:700; }
  .table thead th{ white-space: nowrap; }
  .tag-pill{
    display:inline-block;
    padding:.25rem .55rem;
    border-radius: 999px;
    background:#f3f4f6;
    border:1px solid #e5e7eb;
    font-weight:800;
  }
  .qr-cell img{ width:30px; height:30px; border-radius:6px; border:1px solid #e5e7eb; }
</style>

<div class="container-fluid py-3">

  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
    <div class="d-flex gap-2">
      <a class="btn btn-outline-success"
   href="<?= URLROOT; ?>/index.php?page=assets/export">
  تصدير Excel
</a>


      <a class="btn btn-outline-secondary"
         href="<?= URLROOT; ?>/index.php?page=assets/labels"
         target="_blank">
        طباعة
      </a>

      <a class="btn btn-dark"
         href="<?= URLROOT; ?>/index.php?page=assets/add">
        + إضافة جهاز
      </a>
    </div>

    <div class="text-end">
      <h3 class="mb-0 fw-bold">الأصول / الأجهزة</h3>
      <div class="text-muted small">إدارة الأجهزة وتتبعها حسب الموقع والضمان.</div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-lg-4">
      <div class="kpi-card p-3 d-flex align-items-center justify-content-between">
        <div>
          <div class="kpi-num"><?= (int)$totalCount; ?></div>
          <div class="kpi-label">عدد الأجهزة (حسب التصفية)</div>
        </div>
        <div class="fs-4 fw-bold opacity-75">📦</div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="kpi-card p-3 d-flex align-items-center justify-content-between">
        <div>
          <div class="kpi-num"><?= (int)$expiringCnt; ?></div>
          <div class="kpi-label">قريب انتهاء الضمان (أقل من 30 يوم)</div>
        </div>
        <div class="fs-4 fw-bold opacity-75">⏳</div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="kpi-card p-3 d-flex align-items-center justify-content-between">
        <div>
          <div class="kpi-num"><?= (int)$expiredCnt; ?></div>
          <div class="kpi-label">منتهي الضمان</div>
        </div>
        <div class="fs-4 fw-bold opacity-75">⚠️</div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
      <div class="fw-bold">بحث وفلاتر</div>
      <div class="text-muted small">الفلاتر تحفظ أيضاً بتوب الضمان.</div>
    </div>
    <div class="card-body">
      <form method="get" action="<?= URLROOT; ?>/index.php" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="assets/index">

        <div class="col-12 col-lg-3">
          <label class="form-label fw-bold">الموقع</label>
          <select name="location_id" class="form-select">
            <option value="0">— كل المواقع —</option>
            <?php foreach ($locations as $loc): ?>
              <?php
                $lid = (int)($loc->id ?? 0);
                $lname = $loc->name_ar ?? $loc->name ?? ('موقع#'.$lid);
              ?>
              <option value="<?= $lid; ?>" <?= ($lid === $location_id ? 'selected' : ''); ?>>
                <?= h($lname); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="include_children" value="1" id="incChildren"
                   <?= $include_children ? 'checked' : ''; ?>>
            <label class="form-check-label" for="incChildren">يشمل التوابع</label>
          </div>
        </div>

        <div class="col-12 col-lg-9">
          <label class="form-label fw-bold">بحث (Tag / Serial / Brand / Model)</label>
          <input type="text"
                 class="form-control"
                 name="q"
                 value="<?= h($q); ?>"
                 placeholder="ابحث...">
        </div>

        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary">تطبيق</button>
          <a class="btn btn-outline-secondary"
             href="<?= URLROOT; ?>/index.php?page=assets/index">مسح الفلاتر</a>

          <div class="ms-auto d-flex gap-2">
            <a class="btn btn-outline-primary"
               href="<?= URLROOT; ?>/index.php?page=assets/index&filter=expiring<?= $location_id ? '&location_id='.$location_id : '' ?><?= $include_children ? '&include_children=1' : '' ?>">
              قريب انتهاء الضمان
            </a>
            <a class="btn btn-outline-primary"
               href="<?= URLROOT; ?>/index.php?page=assets/index&filter=expired<?= $location_id ? '&location_id='.$location_id : '' ?><?= $include_children ? '&include_children=1' : '' ?>">
              منتهي الضمان
            </a>
            <a class="btn btn-dark"
               href="<?= URLROOT; ?>/index.php?page=assets/index">عرض الكل</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 d-flex justify-content-between">
      <div class="fw-bold">النتائج</div>
      <div class="text-muted small">عدد النتائج: <?= (int)count($assets); ?></div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-center" style="width:120px;">إجراءات</th>
            <th>الحالة</th>
            <th>الموقع</th>

            <th class="ltr">Host Name</th>
            <th class="ltr">MAC</th>

            <th>الضمان</th>
            <th class="ltr">Serial</th>
            <th>الماركة / الموديل</th>
            <th>النوع</th>

            <th class="ltr">Tag</th>
            <th class="text-center" style="width:70px;">QR</th>
          </tr>
        </thead>

        <tbody>
        <?php if (empty($assets)): ?>
          <tr>
            <td colspan="11" class="text-center text-muted py-5">لا توجد بيانات مطابقة.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($assets as $a): ?>
            <?php
              $id     = (int)($a->id ?? 0);
              $tag    = $a->asset_tag ?? '-';
              $serial = $a->serial_no ?? '-';
              $mac    = $a->mac_address ?? '-';
              $host   = $a->host_name ?? '-';

              $brand  = $a->brand ?? '';
              $model  = $a->model ?? '';
              $type   = $a->type ?? '-';
              $status = $a->status ?? 'Active';

              // اسم الموقع (حسب ما يرجع من الاستعلام)
              $locName = $a->location_path ?? ($a->location_name ?? ($a->location_ar ?? '—'));

              // الضمان
              $warranty = $a->warranty_expiry ?? '';
              $wText = ($warranty ? h($warranty) : '—');

              // روابط
              $showUrl = URLROOT . '/index.php?page=assets/show&id=' . $id;
              $editUrl = URLROOT . '/index.php?page=assets/edit&id=' . $id;

              // QR (لو عندك مسار جاهز في الداتا، وإلا نخلي أيقونة)
              $qrPath = $a->qr_path ?? '';
            ?>
            <tr>
              <td class="text-center">
                <a class="btn btn-sm btn-outline-primary" href="<?= h($editUrl); ?>" title="تعديل">
                  ✏️
                </a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= h($showUrl); ?>" title="عرض">
                  🔍
                </a>
              </td>

              <td><?= h($status); ?></td>
              <td><?= h($locName); ?></td>

              <td class="ltr"><?= h($host); ?></td>
              <td class="ltr"><?= h($mac); ?></td>

              <td><?= $wText; ?></td>
              <td class="ltr"><?= h($serial); ?></td>

              <td>
                <?= h($brand); ?>
                <?php if ($brand && $model): ?> / <?php endif; ?>
                <?= h($model); ?>
              </td>

              <td><?= h($type); ?></td>

              <td class="ltr">
                <span class="tag-pill"><?= h($tag); ?></span>
              </td>

              <td class="text-center qr-cell">
                <?php if (!empty($qrPath)): ?>
                  <img src="<?= h($qrPath); ?>" alt="QR">
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
