<?php require APPROOT . '/views/inc/header.php'; ?>
<?php require APPROOT . '/views/inc/navbar.php'; ?>

<style>
  /* RTL + شكل قريب من اللي بالصورة */
  .org-wrap { direction: rtl; text-align: right; }
  .page-title { font-weight: 700; }
  .page-sub { color:#6c757d; }
  .card-headline {
    display:flex; align-items:center; justify-content:space-between;
    gap:12px;
  }
  .card-headline .title {
    font-size: 1.25rem; font-weight: 700; margin:0;
    color:#0d6efd;
    display:flex; align-items:center; gap:10px;
  }
  .form-hint { font-size:.9rem; color:#0d6efd; cursor:default; }
  .loc-actions .btn { padding: .35rem .6rem; }
  .tree-item { border:1px solid rgba(0,0,0,.08); border-radius: .75rem; overflow:hidden; margin-bottom: .75rem; }
  .tree-item .tree-head { background:#f8f9fa; }
  .tree-title { display:flex; align-items:center; gap:10px; font-weight:700; }
  .tree-meta { display:flex; align-items:center; gap:8px; }
  .badge-soft { background: rgba(13,110,253,.08); color:#0d6efd; border:1px solid rgba(13,110,253,.18); }
  .indent { padding-right: 22px; border-right: 2px solid rgba(13,110,253,.15); margin-right: 10px; }
  .floating-add {
    position: fixed; bottom: 18px; left: 18px;
    z-index: 1040;
  }
</style>

<?php
  // رسائل فلاش (حسب اللي تستخدمه عندك)
  if (function_exists('flash')) {
    flash('location_msg');
    flash('access_denied');
  }

  // --- بناء شجرة من القائمة المسطحة ---
  $locations = $data['locations'] ?? [];

  $byId = [];
  $children = [];
  foreach ($locations as $l) {
    $byId[$l->id] = $l;
    $pid = $l->parent_id ?? 0;
    $children[$pid][] = $l->id;
  }

  $typeLabels = [
    'College'     => 'كلية / فرع رئيسي',
    'Building'    => 'مبنى',
    'Department'  => 'قسم',
    'Lab'         => 'معمل',
    'Office'      => 'مكتب',
    'Other'       => 'أخرى',
  ];

  // استخراج خيارات "الموقع الأب" بشكل مرتب (مع مسافات حسب العمق)
  $orderedIds = [];
  $walk = function($parentId, $depth) use (&$walk, &$children, &$orderedIds) {
    $kids = $children[$parentId] ?? [];
    foreach ($kids as $id) {
      $orderedIds[] = [$id, $depth];
      $walk($id, $depth + 1);
    }
  };
  $walk(0, 0);

  // رندر عقدة شجرة (أكورديون/توسيع)
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
    $headingId  = 'locHeading_' . $loc->id;

    // عدادات اختيارية (لو عندك أعمدة إضافية)
    $assetsCount = isset($loc->assets_count) ? (int)$loc->assets_count : null;
    $kidsCount   = count($kidIds);
    ?>
      <div class="<?= $depth > 0 ? 'indent' : '' ?>">
        <div class="tree-item">
          <div class="tree-head p-3" id="<?= $headingId ?>">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div class="tree-title">
                <span class="text-primary">
                  <i class="bi bi-building"></i>
                </span>
                <span><?= htmlspecialchars($name) ?></span>
                <span class="badge badge-soft"><?= htmlspecialchars($typeLabel) ?></span>
                <?php if ($en !== ''): ?>
                  <span class="text-muted" style="font-weight:500;">(<?= htmlspecialchars($en) ?>)</span>
                <?php endif; ?>
              </div>

              <div class="tree-meta">
                <span class="badge text-bg-light" title="عدد المواقع التابعة">
                  <i class="bi bi-diagram-3"></i> <?= $kidsCount ?>
                </span>

                <?php if ($assetsCount !== null): ?>
                  <span class="badge text-bg-light" title="عدد العهد">
                    <i class="bi bi-pc-display"></i> <?= $assetsCount ?>
                  </span>
                <?php endif; ?>

                <div class="loc-actions btn-group">
                  <!-- إضافة ابن: يعبّي parent_id بالأعلى -->
                  <button type="button"
                          class="btn btn-primary"
                          onclick="prefillParent(<?= (int)$loc->id ?>)"
                          title="إضافة موقع تابع">
                    <i class="bi bi-plus-lg"></i>
                  </button>

                  <a class="btn btn-warning"
                     href="index.php?page=locations/edit&id=<?= (int)$loc->id ?>"
                     title="تعديل">
                    <i class="bi bi-pencil"></i>
                  </a>

                  <form class="d-inline"
                        method="post"
                        action="index.php?page=locations/delete"
                        onsubmit="return confirm('متأكد من حذف هذا الموقع؟');">
                    <input type="hidden" name="id" value="<?= (int)$loc->id ?>">
                    <button class="btn btn-danger" type="submit" title="حذف">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>

                  <?php if ($hasKids): ?>
                    <button class="btn btn-light"
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
              <div class="p-3">
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
  <div class="mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h2 class="page-title m-0">
        <i class="bi bi-diagram-3 text-primary"></i>
        إدارة الهيكل التنظيمي
      </h2>
    </div>
    <div class="page-sub mt-1">
      قم ببناء الهيكل: أضف الكليات ثم المباني التابعة لها، ثم المعامل والمكاتب.
    </div>
  </div>

  <!-- فورم إضافة موقع جديد -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
      <div class="card-headline">
        <h3 class="title">
          <i class="bi bi-plus-circle"></i>
          إضافة موقع جديد
        </h3>
      </div>
    </div>

    <div class="card-body">
      <form method="post" action="index.php?page=locations/add" id="locationForm">
        <div class="row g-3 align-items-end">
          <!-- الاسم العربي (يمين) -->
          <div class="col-12 col-lg-3 order-1 order-lg-4">
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

          <!-- الاسم الإنجليزي (اختياري) -->
          <div class="col-12 col-lg-3 order-2 order-lg-3">
            <label class="form-label">الاسم (إنجليزي)</label>
            <input type="text"
                   class="form-control"
                   name="name_en"
                   value="<?= htmlspecialchars($data['name_en'] ?? '') ?>"
                   placeholder="Optional">
          </div>

          <!-- نوع المكان -->
          <div class="col-12 col-lg-3 order-3 order-lg-2">
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

          <!-- يتبع للموقع الأب -->
          <div class="col-12 col-lg-3 order-4 order-lg-1">
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
              <i class="bi bi-info-circle"></i>
              الكلية هي أعلى مستوى.
            </div>
          </div>
        </div>

        <div class="mt-3">
          <button type="submit" class="btn btn-success px-4">
            <i class="bi bi-floppy"></i>
            حفظ الموقع
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- شجرة المواقع -->
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

<!-- زر عائم "إضافة موقع جديد" -->
<div class="floating-add">
  <button class="btn btn-primary" type="button" onclick="scrollToForm()">
    <i class="bi bi-plus-lg"></i>
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

  // لو النوع "كلية/فرع رئيسي" نخلي parent_id فاضي (أعلى مستوى)
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
