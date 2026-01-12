<?php
// app/views/locations/index.php  ✅ Soft UI + Tree List + Toolbar (RTL)

// ===== Include layout (حتى لو الكونترولر ما يضمّن الهيدر) =====
if (defined('APPROOT')) {
  require APPROOT . '/views/layouts/header.php';
} else {
  require __DIR__ . '/../layouts/header.php';
}

// ===== Helpers (safe) =====
if (!function_exists('h')) {
  function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('intv')) {
  function intv($v): int { return (int)$v; }
}
if (!function_exists('buildUrl')) {
  // نظامك يستخدم: public/index.php?page=controller/method&...
  function buildUrl(string $page, array $params = []): string {
    $q = array_merge(['page' => $page], $params);
    return 'index.php?' . http_build_query($q);
  }
}

// ===== Data =====
$locations = $data['locations'] ?? [];
if (!is_array($locations) && !is_object($locations)) $locations = [];
$flash  = $data['flash'] ?? null;
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

// ===== Type labels/icons =====
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

// ===== Render node (row style like your screenshot) =====
$renderNode = function(int $id, int $depth = 0) use (
  &$renderNode, &$byId, &$kids, $typeLabels, $typeIcons
) {
  if (!isset($byId[$id])) return;

  $loc = $byId[$id];
  $childIds = $kids[$id] ?? [];
  $hasKids  = !empty($childIds);

  $name = trim((string)($loc->name_ar ?? ''));
  if ($name === '') $name = 'موقع #' . $id;

  $type = trim((string)($loc->type ?? 'Other'));
  if ($type === '') $type = 'Other';

  $typeLabel = $typeLabels[$type] ?? $type;
  $icon      = $typeIcons[$type] ?? 'bi-diagram-3';

  $collapseId = 'locCollapse_' . $id;

  $canAdd = function_exists('canManageLocation') ? (bool)canManageLocation($id, 'add') : true;
  $canEdit = function_exists('canManageLocation') ? (bool)canManageLocation($id, 'edit') : true;
  $canDelete = function_exists('canManageLocation') ? (bool)canManageLocation($id, 'delete') : true;

  $pad = min(56, $depth * 18); // indentation
  ?>
  <div class="loc-row soft-card" style="padding:12px; margin-bottom:10px;">
    <div class="d-flex align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3 flex-grow-1" style="min-width:0;">
        <div style="width:<?php echo (int)$pad; ?>px;"></div>

        <button
          class="soft-icon-btn <?php echo $hasKids ? '' : 'opacity-0'; ?>"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#<?php echo h($collapseId); ?>"
          aria-expanded="false"
          aria-controls="<?php echo h($collapseId); ?>"
          title="<?php echo $hasKids ? 'عرض/إخفاء التابع' : ''; ?>"
          <?php echo $hasKids ? '' : 'tabindex="-1" aria-hidden="true"'; ?>
        >
          <i class="bi bi-chevron-down"></i>
        </button>

        <div class="loc-badge">
          <i class="bi <?php echo h($icon); ?>"></i>
        </div>

        <div style="min-width:0;">
          <div class="fw-bold text-truncate" style="max-width:520px;">
            <?php echo h($name); ?>
          </div>
          <div class="text-muted" style="font-size:12px;">
            <span class="me-2">ID: <?php echo (int)$id; ?></span>
            <span class="soft-pill"><?php echo h($typeLabel); ?></span>
          </div>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <?php if ($canAdd): ?>
          <button
            class="soft-icon-btn"
            type="button"
            title="إضافة موقع تابع"
            data-set-parent="<?php echo (int)$id; ?>"
          >
            <i class="bi bi-plus-lg"></i>
          </button>
        <?php endif; ?>

        <?php if ($canEdit): ?>
          <a class="soft-icon-btn" title="تعديل" href="<?php echo h(buildUrl('locations/edit', ['id' => $id])); ?>">
            <i class="bi bi-pencil"></i>
          </a>
        <?php endif; ?>

        <?php if ($canDelete): ?>
          <form method="post" action="<?php echo h(buildUrl('locations/delete', ['id' => $id])); ?>" class="m-0"
                onsubmit="return confirm('تأكيد حذف الموقع؟');">
            <button class="soft-icon-btn" type="submit" title="حذف">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($hasKids): ?>
      <div id="<?php echo h($collapseId); ?>" class="collapse mt-2">
        <div class="pt-2" style="border-top:1px solid var(--stroke);">
          <?php foreach ($childIds as $cid) $renderNode((int)$cid, $depth + 1); ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <?php
};
?>

<style>
  /* ===== Soft UI (page-local) ===== */
  :root{
    --bg: #f3f4f6;
    --surface: #f6f7f9;
    --text: #111827;
    --muted: #6b7280;
    --stroke: rgba(17,24,39,.10);

    --radius-xl: 18px;
    --radius-lg: 14px;

    --shadow-out: 10px 10px 22px rgba(17,24,39,.12), -10px -10px 22px rgba(255,255,255,.85);
    --shadow-in: inset 6px 6px 14px rgba(17,24,39,.10), inset -6px -6px 14px rgba(255,255,255,.90);
    --shadow-soft: 0 10px 25px rgba(17,24,39,.10);
    --focus: 0 0 0 3px rgba(17,24,39,.12);
  }

  .soft-card{
    background: var(--surface);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-out);
    border: 1px solid var(--stroke);
  }
  .soft-toolbar{
    display:flex; align-items:center; gap:10px; flex-wrap:wrap;
    padding:12px; border-radius: var(--radius-xl);
    background: var(--surface);
    box-shadow: var(--shadow-out);
    border: 1px solid var(--stroke);
  }
  .soft-input, .soft-select{
    height: 42px;
    border-radius: 14px;
    border: 1px solid var(--stroke);
    background: var(--surface);
    box-shadow: var(--shadow-in);
    padding: 0 12px;
    color: var(--text);
    outline: none;
  }
  .soft-input:focus, .soft-select:focus{
    box-shadow: var(--shadow-in), var(--focus);
  }

  .soft-btn{
    height: 42px;
    border-radius: 14px;
    border: 1px solid var(--stroke);
    background: var(--surface);
    box-shadow: var(--shadow-out);
    padding: 0 14px;
    display:inline-flex; align-items:center; gap:8px;
    cursor:pointer;
    text-decoration:none;
    color: var(--text);
  }
  .soft-btn:active{ box-shadow: var(--shadow-in); }
  .soft-btn-primary{
    background: #0b0f14;
    color: #fff;
    border-color: rgba(255,255,255,.08);
    box-shadow: 0 10px 26px rgba(0,0,0,.25);
  }

  .soft-icon-btn{
    width: 42px; height: 42px;
    border-radius: 14px;
    border: 1px solid var(--stroke);
    background: var(--surface);
    box-shadow: var(--shadow-out);
    display:grid; place-items:center;
    cursor:pointer;
    color: var(--text);
    text-decoration:none;
  }
  .soft-icon-btn:active{ box-shadow: var(--shadow-in); }

  .soft-pill{
    display:inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    border: 1px solid var(--stroke);
    background: rgba(255,255,255,.55);
    font-size: 12px;
    color: var(--muted);
  }
  .loc-badge{
    width: 42px; height: 42px;
    border-radius: 14px;
    border: 1px solid var(--stroke);
    background: rgba(255,255,255,.55);
    box-shadow: var(--shadow-in);
    display:grid; place-items:center;
    color: var(--text);
    flex: 0 0 auto;
  }
</style>

<div class="container-fluid" dir="rtl" style="max-width:1200px; padding:18px;">
  <!-- Header -->
  <div class="soft-card p-3 mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <div class="fw-bold" style="font-size:20px;">إدارة الهيكل التنظيمي</div>
        <div class="text-muted" style="font-size:13px;">
          قم ببناء الهيكل: أضف الكليات ثم المباني التابعة لها، ثم المعامل والمكاتب.
        </div>
      </div>

      <div class="d-flex gap-2">
        <button class="soft-btn" type="button" onclick="openAll()">
          <i class="bi bi-arrows-expand"></i> فتح الكل
        </button>
        <button class="soft-btn" type="button" onclick="closeAll()">
          <i class="bi bi-arrows-collapse"></i> إغلاق الكل
        </button>

        <?php if ($canAddRoot): ?>
          <a class="soft-btn soft-btn-primary" href="#addForm">
            <i class="bi bi-plus-lg"></i> إضافة موقع جديد
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (!empty($flash)): ?>
    <div class="alert alert-info"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <?php if (!empty($errors) && is_array($errors)): ?>
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">يوجد أخطاء:</div>
      <ul class="m-0">
        <?php foreach ($errors as $e): ?>
          <li><?php echo h($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Add form -->
  <div id="addForm" class="soft-card p-3 mb-3">
    <div class="fw-bold mb-2">إضافة موقع جديد</div>
    <div class="text-muted mb-3" style="font-size:13px;">
      إضافة مستوى أعلى/أدنى حسب اختيار “يتبع لـ”.
      <?php if (!$canAddRoot): ?>
        <span class="ms-2">إضافة مستوى أعلى (بدون أب) للمدير/السوبر أدمن فقط.</span>
      <?php endif; ?>
    </div>

    <form method="post" action="<?php echo h(buildUrl('locations/add')); ?>">
      <div class="row g-3 align-items-end">
        <div class="col-lg-4">
          <label class="form-label fw-bold">يتبع لـ (الموقع الأب)</label>
          <select id="parent_id" name="parent_id" class="soft-select w-100">
            <?php if ($canAddRoot): ?>
              <option value="0">— لا يوجد (مستوى أعلى) —</option>
            <?php else: ?>
              <option value="">— اختر الموقع الأب —</option>
            <?php endif; ?>

            <?php foreach ($flat as $row): ?>
              <?php [$pid, $depth] = $row;
                $p = $byId[$pid] ?? null;
                $pName = $p ? trim((string)($p->name_ar ?? '')) : '';
                if ($pName === '') $pName = 'موقع #' . (int)$pid;
                $pref = str_repeat('— ', (int)$depth);
              ?>
              <option value="<?php echo (int)$pid; ?>">
                <?php echo h($pref . $pName); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="text-muted mt-1" style="font-size:12px;">الكلية هي أعلى مستوى.</div>
        </div>

        <div class="col-lg-3">
          <label class="form-label fw-bold">نوع المكان</label>
          <select name="type" class="soft-select w-100">
            <?php foreach ($typeLabels as $k => $label): ?>
              <option value="<?php echo h($k); ?>"><?php echo h($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-lg-3">
          <label class="form-label fw-bold">الاسم (عربي) <span class="text-danger">*</span></label>
          <input name="name_ar" class="soft-input w-100" required placeholder="مثال: كلية الحاسب">
        </div>

        <div class="col-lg-2">
          <label class="form-label fw-bold">الاسم (إنجليزي)</label>
          <input name="name_en" class="soft-input w-100" placeholder="Optional">
        </div>

        <div class="col-12">
          <button class="soft-btn soft-btn-primary" type="submit">
            <i class="bi bi-check2"></i> حفظ الموقع
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- Tree -->
  <div class="soft-card p-3">
    <div class="fw-bold mb-2">الهيكل</div>
    <div class="text-muted mb-3" style="font-size:13px;">اضغط السهم لعرض/إخفاء المواقع التابعة.</div>

    <?php if (empty($kids[0])): ?>
      <div class="text-center text-muted py-4">لا توجد مواقع مضافة حتى الآن.</div>
      <?php if ($canAddRoot): ?>
        <div class="text-center">
          <a class="soft-btn soft-btn-primary" href="#addForm"><i class="bi bi-plus-lg"></i> إضافة موقع جديد</a>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <?php foreach ($kids[0] as $rootId): ?>
        <?php $renderNode((int)$rootId, 0); ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
  // فتح/إغلاق الكل (Bootstrap collapse)
  function openAll(){
    document.querySelectorAll('.collapse').forEach(el=>{
      const c = bootstrap.Collapse.getOrCreateInstance(el, {toggle:false});
      c.show();
    });
  }
  function closeAll(){
    document.querySelectorAll('.collapse.show').forEach(el=>{
      const c = bootstrap.Collapse.getOrCreateInstance(el, {toggle:false});
      c.hide();
    });
  }

  // زر (+) على كل موقع: يضبط parent_id ويروح للفورم
  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-set-parent]');
    if(!btn) return;
    const parentId = btn.getAttribute('data-set-parent');
    const sel = document.getElementById('parent_id');
    if(sel){
      sel.value = parentId;
      sel.focus();
    }
    const form = document.getElementById('addForm');
    if(form) form.scrollIntoView({behavior:'smooth', block:'start'});
  });
</script>

<?php
// ===== Include footer =====
if (defined('APPROOT')) {
  require APPROOT . '/views/layouts/footer.php';
} else {
  require __DIR__ . '/../layouts/footer.php';
}
