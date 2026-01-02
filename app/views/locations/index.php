<?php require APPROOT . '/views/inc/header.php'; ?>

<style>
  /* ===== RTL + Layout ===== */

.form-row-rtl { flex-direction: row-reverse; }

  .org-wrap { direction: rtl; text-align: right; }
  .page-sub { color:#6c757d; }
  .card { border-radius: 12px; }
  .card-header { border-top-left-radius:12px; border-top-right-radius:12px; }

  /* ===== Add link (top right) ===== */
  .link-add{
    color:#0d6efd;
    font-weight: 800;
    text-decoration: none;
    display:inline-flex;
    align-items:center;
    gap:8px;
  }
  .link-add:hover{ text-decoration: underline; }

  /* ===== Form styling ===== */
  .form-hint { font-size:.9rem; color:#0d6efd; }
  .btn-save{
    border-radius: 10px !important;
    padding: .55rem 1.25rem !important;
    font-weight: 800;
  }

  /* ===== Tree styling ===== */
  .tree-box{
    border:1px solid rgba(0,0,0,.08);
    border-radius: 12px;
    overflow:hidden;
    background:#fff;
  }
  .tree-head{
    background:#f8f9fa;
    padding: 14px 16px;
  }
  .tree-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
  }
  .tree-title{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight: 800;
  }
  .type-badge{
    background: rgba(13,110,253,.08);
    color:#0d6efd;
    border:1px solid rgba(13,110,253,.18);
    border-radius: 999px;
    padding:.15rem .55rem;
    font-weight:700;
    font-size:.85rem;
  }
  .meta-badge{
    background:#fff;
    border:1px solid rgba(0,0,0,.12);
    border-radius: 999px;
    padding:.15rem .55rem;
    font-size:.85rem;
    color:#212529;
    display:inline-flex;
    align-items:center;
    gap:6px;
  }
  .tree-body{ padding: 14px 16px; background:#fff; }

  .indent{
    padding-right: 22px;
    border-right: 2px solid rgba(13,110,253,.15);
    margin-right: 10px;
  }

  /* ===== Buttons (soft) ===== */
  .btn-icon{
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 !important;
    border-radius: 10px !important;
  }
  .btn-soft-primary{
    background: rgba(13,110,253,.08) !important;
    border: 1px solid rgba(13,110,253,.18) !important;
    color:#0d6efd !important;
  }
  .btn-soft-primary:hover{ background: rgba(13,110,253,.14) !important; }

  .btn-soft-danger{
    background: rgba(220,53,69,.08) !important;
    border: 1px solid rgba(220,53,69,.18) !important;
    color:#dc3545 !important;
  }
  .btn-soft-danger:hover{ background: rgba(220,53,69,.14) !important; }

  .btn-soft-warning{
    background: rgba(255,193,7,.15) !important;
    border: 1px solid rgba(255,193,7,.35) !important;
    color:#b58100 !important;
  }
  .btn-soft-warning:hover{ background: rgba(255,193,7,.22) !important; }

  .btn-soft-dark{
    background: rgba(33,37,41,.06) !important;
    border: 1px solid rgba(33,37,41,.12) !important;
    color:#212529 !important;
  }
  .btn-soft-dark:hover{ background: rgba(33,37,41,.10) !important; }

  /* ===== Floating add button ===== */
  .floating-add{
    position: fixed;
    bottom: 18px;
    left: 18px;
    z-index: 1040;
  }
  .floating-add .btn{
    border-radius: 12px !important;
    padding: .65rem 1.05rem !important;
    font-weight: 800;
    box-shadow: 0 6px 18px rgba(0,0,0,.12);
  }
</style>

<?php
  // فلاش رسائل (إذا موجودة)
  if (function_exists('flash')) {
    flash('location_msg');
    flash('access_denied');
  }

  $locations = $data['locations'] ?? [];

  // Build maps
  $byId = [];
  $children = [];
  foreach ($locations as $l) {
    $byId[$l->id] = $l;
    $pid = $l->parent_id ?? 0;
    $children[$pid][] = $l->id;
  }

  // Types labels
  $typeLabels = [
    'College'     => 'كلية / فرع رئيسي',
    'Building'    => 'مبنى',
    'Department'  => 'قسم',
    'Lab'         => 'معمل',
    'Office'      => 'مكتب',
    'Other'       => 'أخرى',
  ];

  // Ordered options for parent select
  $orderedIds = [];
  $walk = function($parentId, $depth) use (&$walk, &$children, &$orderedIds) {
    foreach (($children[$parentId] ?? []) as $id) {
      $orderedIds[] = [$id, $depth];
      $walk($id, $depth + 1);
    }
  };
  $walk(0, 0);

  // Node renderer
  $renderNode = function($id, $depth = 0) use (&$renderNode, &$byId, &$children, $typeLabels) {
    if (!isset($byId[$id])) return;

    $loc = $byId[$id];
    $kidIds = $children[$id] ?? [];
    $hasKids = count($kidIds) > 0;

    $name = trim(($loc->name_ar ?? '') ?: ('موقع #' . $loc->id));
    $en   = trim($loc->name_en ?? '');
    $type = trim($loc->type ?? 'Other');
    $typeLabel = $typeLabels[$type] ?? $type;

    $collapseId = 'locCollapse_' . $loc->id;
    $kidsCount  = count($kidIds);

    ?>
    <div class="<?= $depth > 0 ? 'indent' : '' ?>">
      <div class="tree-box mb-3">
        <div class="tree-head">
          <div class="tree-row">
            <div class="tree-title">
              <span class="text-primary"><i class="bi bi-building"></i></span>
              <span><?= htmlspecialchars($name) ?></span>
              <span class="type-badge"><?= htmlspecialchars($typeLabel) ?></span>
              <?php if ($en !== ''): ?>
                <span class="text-muted" style="font-weight:600;">(<?= htmlspecialchars($en) ?>)</span>
              <?php endif; ?>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="meta-badge" title="عدد المواقع التابعة">
                <i class="bi bi-diagram-3"></i> <?= (int)$kidsCount ?>
              </span>

              <div class="d-flex align-items-center gap-2">
                <!-- Add child -->
                <button type="button"
                        class="btn btn-soft-primary btn-icon"
                        onclick="prefillParent(<?= (int)$loc->id ?>)"
                        title="إضافة موقع تابع">
                  <i class="bi bi-plus-lg"></i>
                </button>

                <!-- Edit -->
                <a class="btn btn-soft-warning btn-icon"
                   href="index.php?page=locations/edit&id=<?= (int)$loc->id ?>"
                   title="تعديل">
                  <i class="bi bi-pencil"></i>
                </a>

                <!-- Delete -->
                <form class="d-inline"
                      method="post"
                      action="index.php?page=locations/delete"
                      onsubmit="return confirm('متأكد من حذف هذا الموقع؟');">
                  <input type="hidden" name="id" value="<?= (int)$loc->id ?>">
                  <button class="btn btn-soft-danger btn-icon" type="submit" title="حذف">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>

                <!-- Collapse -->
                <?php if ($hasKids): ?>
                  <button class="btn btn-soft-dark btn-icon"
                          type="button"
                          data-bs-toggle="collapse"
                          data-bs-target="#<?= $collapseId ?>"
                          aria-expanded="true"
                          aria-controls="<?= $collapseId ?>"
                          title="عرض/إخفاء التابع">
                    <i class="bi bi-chevron-down"></i>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <?php if ($hasKids): ?>
          <div id="<?= $collapseId ?>" class="collapse show">
            <div class="tree-body">
              <?php foreach ($kidIds as $kidId) { $renderNode($kidId, $depth + 1); } ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php
  };
?>

<div class="container-fluid org-wrap py-4">
  <div class="mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h4 class="m-0 fw-bold">
          <i class="bi bi-diagram-3 text-primary"></i>
          إدارة الهيكل التنظيمي
        </h4>
        <div class="page-sub mt-1">
          قم ببناء الهيكل: أضف الكليات ثم المباني التابعة لها، ثم المعامل والمكاتب.
        </div>
      </div>

      <!-- Link like screenshot -->
      <a href="javascript:void(0)" class="link-add" onclick="scrollToForm()">
        <i class="bi bi-plus-circle"></i> إضافة موقع جديد
      </a>
    </div>
  </div>

  <!-- Add form -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
      <div class="fw-bold">إضافة موقع جديد</div>
    </div>

    <div class="card-body">
      <form method="post" action="index.php?page=locations/add" id="locationForm">
        <div class="row g-3 align-items-end form-row-rtl">


          <!-- Parent -->
          <div class="col-12 col-lg-3">
            <label class="form-label">يتبع لـ (الموقع الأب)</label>
            <select class="form-select" name="parent_id" id="parentSelect">
              <option value="">— اختر الموقع الأب —</option>
              <?php
                $selectedParent = $data['parent_id'] ?? '';
                foreach ($orderedIds as [$id, $depth]):
                  $loc = $byId[$id] ?? null;
                  if (!$loc) continue;
                  $label = str_repeat('— ', (int)$depth) . ($loc->name_ar ?? ('موقع #' . $id));
              ?>
                <option value="<?= (int)$id ?>" <?= ((string)$selectedParent === (string)$id) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint mt-1">
              <i class="bi bi-info-circle"></i> الكلية هي أعلى مستوى.
            </div>
          </div>

          <!-- Type -->
          <div class="col-12 col-lg-3">
            <label class="form-label">نوع المكان</label>
            <select class="form-select" name="type" id="typeSelect">
              <?php
                $selectedType = $data['type'] ?? 'College';
                foreach ($typeLabels as $val => $label):
              ?>
                <option value="<?= htmlspecialchars($val) ?>" <?= ($selectedType === $val) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- English -->
          <div class="col-12 col-lg-3">
            <label class="form-label">الاسم (إنجليزي)</label>
            <input type="text"
                   class="form-control"
                   name="name_en"
                   value="<?= htmlspecialchars($data['name_en'] ?? '') ?>"
                   placeholder="Optional">
          </div>

          <!-- Arabic -->
          <div class="col-12 col-lg-3">
            <label class="form-label">الاسم (عربي) <span class="text-danger">*</span></label>
            <input type="text"
                   class="form-control <?= !empty($data['name_err'] ?? '') ? 'is-invalid' : '' ?>"
                   name="name_ar"
                   value="<?= htmlspecialchars($data['name_ar'] ?? '') ?>"
                   placeholder="مثال: كلية الحاسب">
            <?php if (!empty($data['name_err'] ?? '')): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($data['name_err']) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="mt-3">
          <button type="submit" class="btn btn-success btn-save">
            <i class="bi bi-floppy"></i> حفظ الموقع
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tree -->
  <div class="card shadow-sm">
    <div class="card-body">
      <?php if (empty($children[0] ?? [])): ?>
        <div class="text-muted">لا توجد مواقع مضافة حتى الآن.</div>
      <?php else: ?>
        <?php foreach (($children[0] ?? []) as $rootId) { $renderNode($rootId, 0); } ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Floating add -->
<div class="floating-add">
  <button class="btn btn-primary" type="button" onclick="scrollToForm()">
    إضافة موقع جديد
  </button>
</div>

<script>
  function scrollToForm() {
    const el = document.getElementById('locationForm');
    if (el) el.scrollIntoView({behavior:'smooth', block:'start'});
  }

  function prefillParent(parentId) {
    scrollToForm();
    const parentSelect = document.getElementById('parentSelect');
    if (parentSelect) parentSelect.value = String(parentId);
  }

  function syncParentByType() {
    const typeSel = document.getElementById('typeSelect');
    const parentSel = document.getElementById('parentSelect');
    if (!typeSel || !parentSel) return;

    const isTop = (typeSel.value === 'College');
    parentSel.disabled = isTop;
    if (isTop) parentSel.value = '';
  }

  document.addEventListener('DOMContentLoaded', function () {
    syncParentByType();
    const typeSel = document.getElementById('typeSelect');
    if (typeSel) typeSel.addEventListener('change', syncParentByType);
  });
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
