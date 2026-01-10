<?php
// app/views/locations/index.php  ✅ نسخة نظيفة + UI جديد + تضمين الهيدر/الفوتر

// ===== Include layout (حتى لو الكونترولر ما يضمّن الهيدر) =====
if (defined('APPROOT')) {
  require APPROOT . '/views/layouts/header.php';
} else {
  require __DIR__ . '/../layouts/header.php';
}

// ===== Helpers =====
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function intv($v): int { return (int)$v; }

function buildUrl(string $page, array $params = []): string {
  $q = array_merge(['page' => $page], $params);
  return 'index.php?' . http_build_query($q);
}

// ===== Data =====
$locations = $data['locations'] ?? [];
if (!is_array($locations)) $locations = [];

$flash  = $data['flash']  ?? null;
$errors = $data['errors'] ?? [];

// ===== Build tree: parent_id => children =====
$byId = [];
$kids = []; // parent => [childIds...]

foreach ($locations as $l) {
  $id = intv($l->id ?? 0);
  if (!$id) continue;

  $byId[$id] = $l;

  $pid = $l->parent_id ?? 0;
  $pid = ($pid === '' || $pid === null) ? 0 : intv($pid);

  $kids[$pid][] = $id;
}

// ===== Type labels/icons (عدّلها إذا عندك أنواع ثانية) =====
$typeLabels = [
  'College'     => 'كلية / فرع رئيسي',
  'Building'    => 'مبنى',
  'Department'  => 'قسم',
  'Lab'         => 'معمل',
  'Office'      => 'مكتب',
  'Other'       => 'أخرى',
];

$typeIcons = [
  'College'     => 'bi-bank',
  'Building'    => 'bi-buildings',
  'Department'  => 'bi-diagram-3',
  'Lab'         => 'bi-pc-display',
  'Office'      => 'bi-door-open',
  'Other'       => 'bi-geo-alt',
];

// ===== Flatten tree for parent select =====
$flat = [];
$walk = function($parent, $depth) use (&$walk, &$kids, &$flat) {
  foreach (($kids[$parent] ?? []) as $id) {
    $flat[] = [$id, $depth];
    $walk($id, $depth + 1);
  }
};
$walk(0, 0);

// ===== Root add permission =====
$role = function_exists('currentRole') ? (string)currentRole() : 'user';
$canAddRoot = in_array($role, ['superadmin','manager'], true);

// ===== Render node =====
$renderNode = function(int $id) use (&$renderNode, &$byId, &$kids, $typeLabels, $typeIcons) {
  if (!isset($byId[$id])) return;

  $loc = $byId[$id];
  $childIds = $kids[$id] ?? [];
  $hasKids  = !empty($childIds);

  $name = trim((string)($loc->name_ar ?? ''));
  if ($name === '') $name = 'موقع #' . $id;

  $en = trim((string)($loc->name_en ?? ''));
  $type = trim((string)($loc->type ?? 'Other'));
  if ($type === '') $type = 'Other';

  $typeLabel = $typeLabels[$type] ?? $type;
  $icon = $typeIcons[$type] ?? 'bi-diagram-3';

  $collapseId = 'locCollapse_' . $id;

  $canAdd    = function_exists('canManageLocation') ? (bool)canManageLocation($id, 'add') : true;
  $canEdit   = function_exists('canManageLocation') ? (bool)canManageLocation($id, 'edit') : true;
  $canDelete = function_exists('canManageLocation') ? (bool)canManageLocation($id, 'delete') : true;
  ?>
  <div class="cardx mb-2" style="overflow:hidden;">
    <div class="cardx-body py-2" style="padding-right:12px;padding-left:12px;">
      <div class="d-flex align-items-center justify-content-between gap-2">

        <div class="d-flex align-items-center gap-2" style="min-width:0;">
          <span class="badgex">
            <i class="bi <?= h($icon) ?>"></i>
            <?= h($typeLabel) ?>
          </span>

          <div style="min-width:0;">
            <div style="font-weight:900; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
              <?= h($name) ?>
            </div>
            <div class="td-muted" style="font-size:12px;">
              <?php if ($en !== ''): ?>
                <?= h($en) ?> <span class="mx-2">•</span>
              <?php endif; ?>
              <?= $hasKids ? ('يتبع له: ' . count($childIds)) : 'لا يوجد تفرعات' ?>
              <span class="mx-2">•</span>ID: <?= (int)$id ?>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">

          <?php if ($canAdd): ?>
            <a class="icon-btn" href="#addForm" title="إضافة تابع"
               onclick="document.getElementById('parent_id') && (document.getElementById('parent_id').value='<?= (int)$id ?>');">
              <i class="bi bi-plus-lg"></i>
            </a>
          <?php else: ?>
            <span class="icon-btn" style="opacity:.35; pointer-events:none;" title="لا تملك صلاحية الإضافة">
              <i class="bi bi-plus-lg"></i>
            </span>
          <?php endif; ?>

          <?php if ($canEdit): ?>
            <a class="icon-btn" href="<?= h(buildUrl('locations/edit', ['id'=>$id])) ?>" title="تعديل">
              <i class="bi bi-pencil-square"></i>
            </a>
          <?php else: ?>
            <span class="icon-btn" style="opacity:.35; pointer-events:none;" title="لا تملك صلاحية التعديل">
              <i class="bi bi-pencil-square"></i>
            </span>
          <?php endif; ?>

          <?php if ($canDelete): ?>
            <form method="post" action="<?= h(buildUrl('locations/delete', ['id'=>$id])) ?>" class="d-inline"
                  onsubmit="return confirm('متأكد تبغى تحذف هذا الموقع؟');">
              <button class="icon-btn" type="submit" title="حذف">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          <?php else: ?>
            <span class="icon-btn" style="opacity:.35; pointer-events:none;" title="لا تملك صلاحية الحذف">
              <i class="bi bi-trash"></i>
            </span>
          <?php endif; ?>

          <?php if ($hasKids): ?>
            <button class="icon-btn" type="button"
              data-bs-toggle="collapse"
              data-bs-target="#<?= h($collapseId) ?>"
              aria-expanded="false"
              aria-controls="<?= h($collapseId) ?>"
              title="عرض/إخفاء التفرعات">
              <i class="bi bi-chevron-down"></i>
            </button>
          <?php else: ?>
            <span class="icon-btn" style="opacity:.35; pointer-events:none;">
              <i class="bi bi-chevron-down"></i>
            </span>
          <?php endif; ?>

        </div>

      </div>
    </div>

    <?php if ($hasKids): ?>
      <div id="<?= h($collapseId) ?>" class="collapse">
        <div class="cardx-body pt-2" style="padding-right:12px; padding-left:12px;">
          <div style="padding-right: 10px; border-right: 2px dashed rgba(102,112,133,.25);">
            <?php foreach ($childIds as $cid): ?>
              <?php $renderNode((int)$cid); ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <?php
};
?>

<div class="page-wrap">

  <div class="page-head">
    <div>
      <h1 class="page-title">إدارة الهيكل التنظيمي</h1>
      <div class="page-sub">قم ببناء الهيكل: أضف الكليات ثم المباني التابعة لها، ثم المعامل والمكاتب.</div>
    </div>

    <div class="page-actions">
      <a class="btn btn-dark btn-soft" href="#addForm">
        <i class="bi bi-plus-lg ms-1"></i> إضافة موقع جديد
      </a>

      <button class="btn btn-light border btn-soft" type="button" onclick="expandAll()">
        <i class="bi bi-arrows-expand ms-1"></i> فتح الكل
      </button>
      <button class="btn btn-light border btn-soft" type="button" onclick="collapseAll()">
        <i class="bi bi-arrows-collapse ms-1"></i> إغلاق الكل
      </button>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="cardx mb-3"><div class="cardx-body"><?= h($flash) ?></div></div>
  <?php endif; ?>

  <?php if (!empty($errors) && is_array($errors)): ?>
    <div class="alert alert-danger cardx-body cardx mb-3">
      <div style="font-weight:900;">يوجد أخطاء:</div>
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= h($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Add Form -->
  <div id="addForm" class="cardx mb-3">
    <div class="cardx-body">
      <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
        <div class="cardx-title mb-0">إضافة موقع جديد</div>
        <div class="cardx-muted">إضافة مستوى أعلى/أدنى حسب اختيار “يتبع لـ”.</div>
      </div>

      <?php if (!$canAddRoot): ?>
        <div class="alert alert-info mb-3" style="border-radius:14px;">
          إضافة مستوى أعلى (بدون أب) للمدير/السوبر أدمن فقط — اختر موقع أب.
        </div>
      <?php endif; ?>

      <form method="post" action="<?= h(buildUrl('locations/add')) ?>" class="row g-3">

        <div class="col-12 col-lg-4">
          <label class="form-label" style="font-weight:900;">يتبع لـ (الموقع الأب)</label>
          <select id="parent_id" name="parent_id" class="form-select select-soft" <?= $canAddRoot ? '' : 'required' ?>>
            <?php if ($canAddRoot): ?>
              <option value="0">— لا يوجد (مستوى أعلى) —</option>
            <?php else: ?>
              <option value="" selected>— اختر الموقع الأب —</option>
            <?php endif; ?>

            <?php foreach ($flat as [$pid, $depth]): ?>
              <?php
                $p = $byId[(int)$pid] ?? null;
                if (!$p) continue;
                $pName = trim((string)($p->name_ar ?? ''));
                if ($pName === '') $pName = 'موقع #' . (int)$pid;
                $pref = str_repeat('— ', (int)$depth);
              ?>
              <option value="<?= (int)$pid ?>"><?= h($pref . $pName) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="cardx-muted mt-1">الكلية هي أعلى مستوى.</div>
        </div>

        <div class="col-12 col-lg-3">
          <label class="form-label" style="font-weight:900;">نوع المكان</label>
          <select name="type" class="form-select select-soft" required>
            <?php foreach ($typeLabels as $val => $label): ?>
              <option value="<?= h($val) ?>"><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-lg-3">
          <label class="form-label" style="font-weight:900;">الاسم (عربي) *</label>
          <input name="name_ar" class="form-control input-soft" required placeholder="مثال: كلية الحاسب">
        </div>

        <div class="col-12 col-lg-2">
          <label class="form-label" style="font-weight:900;">الاسم (إنجليزي)</label>
          <input name="name_en" class="form-control input-soft" placeholder="Optional">
        </div>

        <div class="col-12 d-flex justify-content-end">
          <button class="btn btn-success btn-soft" type="submit">
            <i class="bi bi-save ms-1"></i> حفظ الموقع
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tree -->
  <div class="cardx">
    <div class="cardx-body">
      <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
        <div class="cardx-title mb-0">الهيكل</div>
        <div class="cardx-muted">اضغط السهم لعرض/إخفاء المواقع التابعة.</div>
      </div>

      <?php if (empty($kids[0])): ?>
        <div class="text-center td-muted py-4">لا توجد مواقع مضافة حتى الآن.</div>
      <?php else: ?>
        <?php foreach ($kids[0] as $rootId): ?>
          <?php $renderNode((int)$rootId); ?>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="mt-3">
        <a class="btn btn-dark btn-soft" href="#addForm">
          <i class="bi bi-plus-lg ms-1"></i> إضافة موقع جديد
        </a>
      </div>
    </div>
  </div>

</div>

<script>
  function expandAll(){
    document.querySelectorAll('.collapse').forEach(el=>{
      const c = bootstrap.Collapse.getOrCreateInstance(el, {toggle:false});
      c.show();
    });
  }
  function collapseAll(){
    document.querySelectorAll('.collapse.show').forEach(el=>{
      const c = bootstrap.Collapse.getOrCreateInstance(el, {toggle:false});
      c.hide();
    });
  }
</script>

<?php
// ===== Include footer =====
if (defined('APPROOT')) {
  require APPROOT . '/views/layouts/footer.php';
} else {
  require __DIR__ . '/../layouts/footer.php';
}
