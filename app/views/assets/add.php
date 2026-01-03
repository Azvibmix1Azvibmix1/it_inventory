<?php require APPROOT . '/views/inc/header.php'; ?>

<style>
  .wrap{ direction: rtl; text-align: right; }
  .card{ border-radius:12px; }
  .btn-round{ border-radius:10px !important; }
  .barcode-box{ border:1px dashed #ccc; border-radius:12px; padding:10px; background:#fafafa; text-align:center; }
  .barcode-box svg{ max-width:100%; height:auto; }
</style>

<div class="container-fluid wrap py-3">
  <?php if (function_exists('flash')) { flash('asset_msg'); flash('access_denied'); } ?>

  <?php
    $locations = $data['locations'] ?? [];
    $users = $data['users_list'] ?? [];
    $role = function_exists('currentRole') ? currentRole() : ($_SESSION['user_role'] ?? 'user');

    // تجهيز مسار الموقع (كلية › مبنى › معمل)
    $locById = [];
    foreach ($locations as $loc) { $locById[$loc->id] = $loc; }

    if (!function_exists('buildLocationPath')) {
      function buildLocationPath($loc, $locById) {
        $parts = [ $loc->name_ar ?? ('موقع#'.$loc->id) ];
        $current = $loc;
        while (!empty($current->parent_id) && isset($locById[$current->parent_id])) {
          $current = $locById[$current->parent_id];
          array_unshift($parts, $current->name_ar ?? ('موقع#'.$current->id));
        }
        return implode(' › ', $parts);
      }
    }

    $allowedTypes = ['Laptop','Desktop','Printer','Monitor','Server','Network','Other'];
  ?>

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h4 class="m-0 fw-bold"><i class="bi bi-plus-circle"></i> إضافة جهاز</h4>
    <a class="btn btn-outline-secondary btn-round" href="index.php?page=assets/index">
      <i class="bi bi-arrow-right"></i> رجوع
    </a>
  </div>

  <?php if (empty($locations) && $role === 'user'): ?>
    <div class="alert alert-warning mt-3">
      لا توجد لديك مواقع مسموح لك الإضافة عليها. اطلب من السوبر أدمن منحك صلاحية على موقع.
    </div>
  <?php endif; ?>

  <div class="card shadow-sm mt-3">
    <div class="card-body">
      <form method="post" action="index.php?page=assets/add">
        <?php if (!empty($data['asset_err'] ?? '')): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($data['asset_err']) ?></div>
        <?php endif; ?>

        <div class="row g-3">

          <!-- TAG + BARCODE -->
          <div class="col-12 col-lg-4">
            <label class="form-label">Tag (رقم الجهاز) <span class="text-danger">*</span></label>

            <div class="input-group">
              <input
                class="form-control"
                id="asset_tag"
                name="asset_tag"
                required
                readonly
                value="<?= htmlspecialchars($data['asset_tag'] ?? '') ?>"
              >
              <button class="btn btn-outline-secondary" type="button" id="regen_tag">
                توليد جديد
              </button>
            </div>

            <div class="barcode-box mt-2">
              <svg id="tag_barcode"></svg>
              <div class="small text-muted mt-1">Barcode (CODE128)</div>

              <button class="btn btn-sm btn-outline-primary mt-2" type="button" id="print_barcode">
                طباعة الباركود
              </button>
            </div>

            <div class="text-muted small mt-1">
              يتم توليد التاق تلقائيًا لتفادي التكرار.
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">Serial (اختياري)</label>
            <input class="form-control" name="serial_no"
                   value="<?= htmlspecialchars($data['serial_no'] ?? '') ?>">
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">النوع <span class="text-danger">*</span></label>
            <select class="form-select" name="type" required>
              <option value="">— اختر النوع —</option>
              <?php foreach ($allowedTypes as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= (($data['type'] ?? '') === $t) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">الماركة (اختياري)</label>
            <input class="form-control" name="brand"
                   value="<?= htmlspecialchars($data['brand'] ?? '') ?>">
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">الموديل (اختياري)</label>
            <input class="form-control" name="model"
                   value="<?= htmlspecialchars($data['model'] ?? '') ?>">
          </div>

          <div class="col-12">
            <label class="form-label">الموقع <span class="text-danger">*</span></label>
            <select class="form-select" name="location_id" required <?= (empty($locations) && $role==='user') ? 'disabled' : '' ?>>
              <option value="">— اختر موقع الجهاز —</option>
              <?php foreach ($locations as $loc): ?>
                <?php
                  $label = buildLocationPath($loc, $locById);
                  $selected = (!empty($data['location_id']) && (int)$data['location_id'] === (int)$loc->id) ? 'selected' : '';
                ?>
                <option value="<?= (int)$loc->id ?>" <?= $selected ?>>
                  <?= htmlspecialchars($label) ?><?php if (!empty($loc->type)): ?> (<?= htmlspecialchars($loc->type) ?>)<?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="text-muted small mt-1">يتم جلب المواقع من صفحة “المواقع والمباني”.</div>
          </div>

          <?php if (in_array($role, ['superadmin','manager'], true)): ?>
            <div class="col-12">
              <label class="form-label">الموظف المستلم (اختياري)</label>
              <select class="form-select" name="assigned_to">
                <option value="">— بدون تعيين / في المخزن —</option>
                <?php foreach ($users as $u): ?>
                  <?php
                    $name = $u->name ?? $u->username ?? $u->email ?? ('User#'.$u->id);
                    $selected = (!empty($data['assigned_to']) && (int)$data['assigned_to'] === (int)$u->id) ? 'selected' : '';
                  ?>
                  <option value="<?= (int)$u->id ?>" <?= $selected ?>>
                    <?= htmlspecialchars($name) ?><?php if (!empty($u->role)): ?> (<?= htmlspecialchars($u->role) ?>)<?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="text-muted small mt-1">للسوبر أدمن/المدير فقط.</div>
            </div>
          <?php endif; ?>

        </div>

        <div class="mt-3">
          <button class="btn btn-success btn-round" type="submit" <?= (empty($locations) && $role==='user') ? 'disabled' : '' ?>>
            <i class="bi bi-floppy"></i> حفظ الجهاز
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<!-- JsBarcode (بدون مكتبات PHP) -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
  const tagInput = document.getElementById('asset_tag');
  const regenBtn = document.getElementById('regen_tag');
  const printBtn = document.getElementById('print_barcode');

  function renderBarcode(tag) {
    if (!tag) return;
    try {
      JsBarcode("#tag_barcode", tag, {
        format: "CODE128",
        displayValue: true
      });
    } catch (e) {
      console.error(e);
    }
  }

  async function fetchNewTag() {
    const res = await fetch('index.php?page=assets/generate_tag', { cache: 'no-store' });
    if (!res.ok) throw new Error('Failed to generate tag');
    const json = await res.json();
    return (json && json.tag) ? json.tag : '';
  }

  async function ensureTagAndBarcode() {
    let tag = (tagInput.value || '').trim();
    if (!tag) {
      try {
        tag = await fetchNewTag();
        tagInput.value = tag;
      } catch (e) {
        console.error(e);
      }
    }
    renderBarcode(tag);
  }

  regenBtn.addEventListener('click', async () => {
    regenBtn.disabled = true;
    try {
      const tag = await fetchNewTag();
      tagInput.value = tag;
      renderBarcode(tag);
    } catch (e) {
      alert('تعذر توليد Tag جديد. تأكد أن route assets/generate_tag يعمل.');
    } finally {
      regenBtn.disabled = false;
    }
  });

  printBtn.addEventListener('click', () => {
    const tag = (tagInput.value || '').trim();
    if (!tag) return;

    // اطبع ملصق بسيط
    const svg = document.getElementById('tag_barcode').outerHTML;
    const w = window.open('', '_blank', 'width=520,height=420');
    w.document.open();
    w.document.write(`
      <html>
        <head>
          <meta charset="utf-8">
          <title>Barcode</title>
          <style>
            body{ font-family: Arial, sans-serif; margin: 20px; text-align:center; }
            .box{ border:1px dashed #999; padding:14px; border-radius:12px; display:inline-block; }
          </style>
        </head>
        <body>
          <div class="box">
            ${svg}
          </div>
          <script>
            window.onload = function(){ window.print(); };
          <\/script>
        </body>
      </html>
    `);
    w.document.close();
  });

  // on load
  ensureTagAndBarcode();
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
