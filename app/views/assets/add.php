<?php require APPROOT . '/views/inc/header.php'; ?>


<?php
$locations = $data['locations'] ?? [];

// خريطة سريعة: id => location
$locById = [];
foreach ($locations as $l) {
  $id = (int)($l->id ?? 0);
  if ($id > 0) $locById[$id] = $l;
}

/**
 * يبني المسار الكامل للموقع: كلية › مبنى › معمل
 */
function buildLocPath(int $id, array $locById): string
{
  if ($id <= 0 || !isset($locById[$id])) return '';

  $parts = [];
  $cur = $locById[$id];

  $parts[] = (string)($cur->name_ar ?? $cur->name ?? ('موقع#'.$id));

  $guard = 0;
  while (true) {
    $guard++;
    if ($guard > 30) break; // حماية من loop

    // أحياناً يكون الحقل parent_id أو parentId
    $pid = (int)($cur->parent_id ?? $cur->parentId ?? 0);
    if ($pid <= 0 || !isset($locById[$pid])) break;

    $cur = $locById[$pid];
    array_unshift($parts, (string)($cur->name_ar ?? $cur->name ?? ('موقع#'.$pid)));
  }

  return implode(' › ', $parts);
}
?>



<?php
$locations   = $data['locations']   ?? [];
$users_list  = $data['users_list']  ?? [];
$asset_err   = $data['asset_err']   ?? '';

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

$locById = [];
foreach ($locations as $loc) { $locById[$loc->id] = $loc; }

$allowedTypes = ['Laptop','Desktop','Printer','Monitor','Server','Network','Other'];

// تجهيز بيانات المواقع للـ JS (id + path)
$locItems = [];
foreach ($locations as $loc) {
  $locItems[] = [
    'id'   => (int)$loc->id,
    'name' => (string)($loc->name_ar ?? ('موقع#'.$loc->id)),
    'path' => (string)buildLocationPath($loc, $locById),
  ];
}

$currentType = (string)($data['type'] ?? '');
$currentLoc  = (int)($data['location_id'] ?? 0);
$currentLocLabel = '';
if ($currentLoc && isset($locById[$currentLoc])) {
  $currentLocLabel = buildLocationPath($locById[$currentLoc], $locById);
}
?>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
  .flatpickr-calendar { direction: rtl; }

  /* ===== Type cards ===== */
  .type-grid{
    display:grid;
    grid-template-columns:repeat(7, minmax(0,1fr));
    gap:10px;
  }
  @media (max-width: 1200px){ .type-grid{ grid-template-columns:repeat(4, minmax(0,1fr)); } }
  @media (max-width: 768px){ .type-grid{ grid-template-columns:repeat(2, minmax(0,1fr)); } }

  .type-card{
    border:1px solid rgba(0,0,0,.08);
    border-radius:16px;
    background:#fff;
    padding:12px 10px;
    text-align:center;
    cursor:pointer;
    user-select:none;
    transition: transform .08s ease, box-shadow .12s ease, border-color .12s ease;
    box-shadow:0 10px 24px rgba(0,0,0,.03);
    font-weight:900;
  }
  .type-card:hover{ transform: translateY(-1px); box-shadow:0 12px 26px rgba(0,0,0,.06); }
  .type-card.active{
    border-color:#0b0f14;
    box-shadow:0 16px 30px rgba(0,0,0,.10);
  }
  .type-icon{
    width:38px; height:38px; border-radius:14px;
    display:inline-flex; align-items:center; justify-content:center;
    background:rgba(0,0,0,.04);
    margin-bottom:8px;
    font-size:18px;
  }
  .type-key{ font-size:12px; color:#6b7280; font-weight:800; margin-top:4px; }

  /* ===== Location picker ===== */
  .loc-input-wrap{
    display:flex; gap:8px; align-items:stretch;
  }
  .loc-input-wrap .form-control{ border-radius:12px; }
  .loc-btn{
    border-radius:12px;
    font-weight:900;
    white-space:nowrap;
  }
  .loc-hint{ color:#6b7280; font-weight:800; font-size:12px; margin-top:6px; }

  /* ===== Custom modal ===== */
  .ux-modal{
    position:fixed; inset:0;
    background:rgba(0,0,0,.35);
    display:none;
    align-items:center; justify-content:center;
    z-index:9999;
    padding:18px;
  }
  .ux-modal.open{ display:flex; }
  .ux-modal-card{
    width:min(820px, 100%);
    background:#fff;
    border-radius:18px;
    box-shadow:0 24px 70px rgba(0,0,0,.25);
    overflow:hidden;
    border:1px solid rgba(0,0,0,.08);
  }
  .ux-modal-hd{
    padding:12px 14px;
    background:rgba(0,0,0,.02);
    border-bottom:1px solid rgba(0,0,0,.06);
    display:flex; align-items:center; justify-content:space-between; gap:10px;
  }
  .ux-modal-title{ margin:0; font-weight:1000; }
  .ux-close{
    border:1px solid rgba(0,0,0,.10);
    background:#fff;
    border-radius:12px;
    width:40px; height:40px;
    display:inline-flex; align-items:center; justify-content:center;
    cursor:pointer; font-weight:900;
  }
  .ux-modal-bd{ padding:14px; }
  .loc-search{
    display:flex; gap:10px; align-items:center; margin-bottom:10px;
  }
  .loc-search input{ border-radius:14px; }
  .loc-sections{ display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
  @media (max-width: 768px){ .loc-sections{ grid-template-columns: 1fr; } }

  .loc-box{
    border:1px solid rgba(0,0,0,.08);
    border-radius:16px;
    overflow:hidden;
  }
  .loc-box-hd{
    padding:10px 12px;
    background:rgba(0,0,0,.02);
    border-bottom:1px solid rgba(0,0,0,.06);
    font-weight:1000;
    display:flex; align-items:center; justify-content:space-between;
  }
  .loc-list{
    max-height:360px;
    overflow:auto;
    background:#fff;
  }
  .loc-item{
    padding:10px 12px;
    display:flex; align-items:flex-start; gap:10px;
    cursor:pointer;
    border-bottom:1px solid rgba(0,0,0,.05);
  }
  .loc-item:hover{ background:rgba(0,0,0,.02); }
  .loc-main{ font-weight:1000; }
  .loc-path{ color:#6b7280; font-weight:800; font-size:12px; margin-top:2px; }
  .loc-actions{ margin-inline-start:auto; display:flex; gap:6px; align-items:center; }
  .star-btn{
    width:34px; height:34px; border-radius:12px;
    border:1px solid rgba(0,0,0,.10);
    background:#fff;
    display:inline-flex; align-items:center; justify-content:center;
    cursor:pointer; font-weight:1000;
  }
  .star-btn.active{ background:#0b0f14; color:#fff; border-color:#0b0f14; }

  .mini-note{
    font-size:12px; color:#6b7280; font-weight:800;
    margin-top:8px;
  }

  .req-badge{
    display:inline-block;
    background:#fee2e2;
    color:#991b1b;
    font-weight:900;
    font-size:12px;
    border-radius:999px;
    padding:2px 8px;
    margin-inline-start:6px;
  }
</style>

<div class="container-fluid py-3" dir="rtl">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">إضافة جهاز</h4>
    <a class="btn btn-outline-secondary" href="index.php?page=assets/index">رجوع</a>
  </div>

  <?php if (!empty($asset_err)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($asset_err) ?></div>
  <?php endif; ?>

  <?php if (empty($locations)): ?>
    <div class="alert alert-warning">
      لا توجد لديك مواقع مسموح لك الإضافة عليها. اطلب من السوبر أدمن منحك صلاحية على موقع.
    </div>
  <?php else: ?>

    <div class="card">
      <div class="card-body">

        <form id="assetAddForm" method="post" action="index.php?page=assets/add" autocomplete="off">

          <!-- التاق يتولد تلقائيًا بعد الحفظ -->
          <input type="hidden" name="asset_tag" value="">

          <div class="mb-3">
            <label class="form-label">Tag (رقم الجهاز) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" value="يتولد تلقائيًا بعد الحفظ (AST-000001)" readonly>
            <div class="form-text text-muted">يتم توليد التاق تلقائيًا لتفادي التكرار.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Physical address (MAC)</label>
            <!-- ملاحظة: عندك النظام يستخدم serial_no — بنخليه نفسه عشان ما نخرب الـ backend -->
            <input id="macInput" type="text" name="serial_no" class="form-control"
                   placeholder="AA:BB:CC:DD:EE:FF"
                   value="<?= htmlspecialchars($data['serial_no'] ?? '') ?>">
            <div class="mini-note">ينسّق تلقائيًا بصيغة MAC عند الكتابة.</div>
          </div>

          <!-- ===== TYPE (Cards) ===== -->
          <div class="mb-3">
            <label class="form-label">
              النوع <span class="text-danger">*</span>
              <span class="req-badge" id="typeReqBadge" style="display:none;">مطلوب</span>
            </label>

            <input type="hidden" name="type" id="typeHidden" value="<?= htmlspecialchars($currentType) ?>">

            <div class="type-grid" id="typeGrid">
              <?php
                $typeMeta = [
                  'Laptop'  => ['icon'=>'💻','label'=>'Laptop'],
                  'Desktop' => ['icon'=>'🖥️','label'=>'Desktop'],
                  'Printer' => ['icon'=>'🖨️','label'=>'Printer'],
                  'Monitor' => ['icon'=>'📺','label'=>'Monitor'],
                  'Server'  => ['icon'=>'🗄️','label'=>'Server'],
                  'Network' => ['icon'=>'🌐','label'=>'Network'],
                  'Other'   => ['icon'=>'📦','label'=>'Other'],
                ];
              ?>
              <?php foreach ($allowedTypes as $t): ?>
                <?php $active = ($currentType === $t) ? 'active' : ''; ?>
                <div class="type-card <?= $active ?>" data-type="<?= htmlspecialchars($t) ?>">
                  <div class="type-icon"><?= htmlspecialchars($typeMeta[$t]['icon'] ?? '🔧') ?></div>
                  <div><?= htmlspecialchars($typeMeta[$t]['label'] ?? $t) ?></div>
                  <div class="type-key"><?= htmlspecialchars($t) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">الماركة (اختياري)</label>
              <input type="text" name="brand" class="form-control"
                     value="<?= htmlspecialchars($data['brand'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">الموديل (اختياري)</label>
              <input type="text" name="model" class="form-control"
                     value="<?= htmlspecialchars($data['model'] ?? '') ?>">
            </div>
          </div>

          <!-- Dates -->
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">تاريخ الشراء (اختياري)</label>
              <input type="text" name="purchase_date" class="form-control js-date"
                     placeholder="YYYY-MM-DD"
                     value="<?= htmlspecialchars($data['purchase_date'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">انتهاء الضمان (اختياري)</label>
              <input type="text" name="warranty_expiry" class="form-control js-date"
                     placeholder="YYYY-MM-DD"
                     value="<?= htmlspecialchars($data['warranty_expiry'] ?? '') ?>">
            </div>
          </div>

          <!-- ===== LOCATION (Modal Search) ===== -->
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">
                الموقع <span class="text-danger">*</span>
                <span class="req-badge" id="locReqBadge" style="display:none;">مطلوب</span>
              </label>

              <input type="hidden" name="location_id" id="locationHidden" value="<?= (int)$currentLoc ?>">

              <div class="loc-input-wrap">
                <input id="locationDisplay" type="text" class="form-control"
                       value="<?= htmlspecialchars($currentLocLabel ?: '') ?>"
                       placeholder="اختر الموقع..."
                       readonly>
                <button type="button" class="btn btn-dark loc-btn" id="openLocPicker">اختيار</button>
              </div>

              <div class="loc-hint">بحث سريع + آخر استخدام + مفضلة ⭐ (بدون قوائم طويلة).</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">الحالة</label>
              <?php $st = $data['status'] ?? 'Active'; ?>
              <select name="status" class="form-select" style="border-radius:12px;">
                <option value="Active"  <?= ($st === 'Active') ? 'selected' : '' ?>>Active</option>
                <option value="Retired" <?= ($st === 'Retired') ? 'selected' : '' ?>>Retired</option>
                <option value="Repair"  <?= ($st === 'Repair') ? 'selected' : '' ?>>Repair</option>
              </select>
            </div>
          </div>

          <?php if (!empty($users_list)): ?>
            <div class="mt-3">
              <label class="form-label">الموظف المستلم (اختياري)</label>
              <select name="assigned_to" class="form-select" style="border-radius:12px;">
                <option value="">— بدون تعيين / في المخزن —</option>
                <?php foreach ($users_list as $u): ?>
                  <?php
                    $name = $u->name ?? $u->username ?? $u->email ?? ('User#'.$u->id);
                    $role = $u->role ?? '';
                    $selected = (!empty($data['assigned_to']) && (int)$data['assigned_to'] === (int)$u->id) ? 'selected' : '';
                  ?>
                  <option value="<?= (int)$u->id ?>" <?= $selected ?>>
                    <?= htmlspecialchars($name) ?><?= $role ? ' ('.$role.')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text text-muted">للسوبر أدمن/المدير فقط.</div>
            </div>
          <?php endif; ?>

          <div class="mt-3">
            <label class="form-label">ملاحظات (اختياري)</label>
            <textarea name="notes" class="form-control" rows="3" style="border-radius:12px;"><?= htmlspecialchars($data['notes'] ?? '') ?></textarea>
          </div>

          <div class="mt-4 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary" style="border-radius:12px; font-weight:900;">حفظ الجهاز</button>
            <a class="btn btn-outline-secondary" style="border-radius:12px; font-weight:900;" href="index.php?page=assets/index">إلغاء</a>
          </div>

        </form>
      </div>
    </div>

  <?php endif; ?>
</div>

<!-- ===== Location Picker Modal ===== -->
<div class="ux-modal" id="locModal" aria-hidden="true">
  <div class="ux-modal-card">
    <div class="ux-modal-hd">
      <h6 class="ux-modal-title mb-0">اختيار موقع الجهاز</h6>
      <button class="ux-close" type="button" id="closeLocPicker">✕</button>
    </div>
    <div class="ux-modal-bd">
      <div class="loc-search">
        <input id="locSearchInput" type="text" class="form-control" placeholder="ابحث عن موقع... (مثال: مبنى 8، معمل 1)">
        <button type="button" class="btn btn-outline-secondary" style="border-radius:12px; font-weight:900;" id="clearLocSearch">مسح</button>
      </div>

      <div class="loc-sections">
        <div class="loc-box">
          <div class="loc-box-hd">
            <span>المفضلة ⭐</span>
            <span class="mini-note" style="margin:0;">تظهر أولاً</span>
          </div>
          <div class="loc-list" id="favLocList"></div>
        </div>

        <div class="loc-box">
          <div class="loc-box-hd">
            <span>النتائج</span>
            <span class="mini-note" style="margin:0;">+ آخر استخدام</span>
          </div>
          <div class="loc-list" id="allLocList"></div>
        </div>
      </div>

      <div class="mini-note">تلميح: اضغط ⭐ لإضافة الموقع للمفضلة. يتم حفظها على جهازك.</div>
    </div>
  </div>
</div>

<!-- Flatpickr JS + Arabic locale -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>

<script>
(function(){
  const LOCATIONS = <?= json_encode($locItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  // ===== Flatpickr =====
  document.addEventListener('DOMContentLoaded', function () {
    if (window.flatpickr) {
      if (flatpickr.l10ns && flatpickr.l10ns.ar) flatpickr.localize(flatpickr.l10ns.ar);
      flatpickr('.js-date', { dateFormat:'Y-m-d', allowInput:true, disableMobile:true });
    }
  });

  // ===== MAC formatting =====
  const macInput = document.getElementById('macInput');
  if (macInput) {
    macInput.addEventListener('input', () => {
      let v = (macInput.value || '').toUpperCase().replace(/[^0-9A-F]/g,'');
      // حد أقصى 12
      v = v.slice(0, 12);
      // AA:BB:CC...
      const parts = v.match(/.{1,2}/g) || [];
      macInput.value = parts.join(':');
    });
  }

  // ===== Type cards =====
  const typeHidden = document.getElementById('typeHidden');
  const typeGrid = document.getElementById('typeGrid');
  const typeReqBadge = document.getElementById('typeReqBadge');

  function setType(t){
    typeHidden.value = t || '';
    typeReqBadge.style.display = (typeHidden.value ? 'none' : 'inline-block');
    [...typeGrid.querySelectorAll('.type-card')].forEach(el=>{
      el.classList.toggle('active', el.dataset.type === t);
    });
  }
  if (typeGrid) {
    typeGrid.addEventListener('click', (e)=>{
      const card = e.target.closest('.type-card');
      if (!card) return;
      setType(card.dataset.type || '');
    });
  }

  // ===== Location picker =====
  const locModal = document.getElementById('locModal');
  const openLocPicker = document.getElementById('openLocPicker');
  const closeLocPicker = document.getElementById('closeLocPicker');
  const locationHidden = document.getElementById('locationHidden');
  const locationDisplay = document.getElementById('locationDisplay');
  const locSearchInput = document.getElementById('locSearchInput');
  const clearLocSearch = document.getElementById('clearLocSearch');
  const allLocList = document.getElementById('allLocList');
  const favLocList = document.getElementById('favLocList');
  const locReqBadge = document.getElementById('locReqBadge');

  const LS_RECENT = 'itinv_recent_locations';
  const LS_FAV    = 'itinv_fav_locations';

  function getLS(key){
    try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch(e){ return []; }
  }
  function setLS(key, val){
    try { localStorage.setItem(key, JSON.stringify(val)); } catch(e){}
  }

  function openModal(){
    locModal.classList.add('open');
    locModal.setAttribute('aria-hidden','false');
    setTimeout(()=>{ locSearchInput && locSearchInput.focus(); }, 50);
    renderLists();
  }
  function closeModal(){
    locModal.classList.remove('open');
    locModal.setAttribute('aria-hidden','true');
  }

  function toggleFav(id){
    let fav = getLS(LS_FAV).map(Number).filter(Boolean);
    const i = fav.indexOf(id);
    if (i >= 0) fav.splice(i,1);
    else fav.unshift(id);
    fav = [...new Set(fav)].slice(0, 50);
    setLS(LS_FAV, fav);
    renderLists();
  }

  function pushRecent(id){
    let recent = getLS(LS_RECENT).map(Number).filter(Boolean);
    recent = [id, ...recent.filter(x=>x!==id)].slice(0, 15);
    setLS(LS_RECENT, recent);
  }

  function selectLocation(item){
    locationHidden.value = String(item.id);
    locationDisplay.value = item.path;
    locReqBadge.style.display = 'none';
    pushRecent(item.id);
    closeModal();
  }

  function itemRow(item, isFavActive){
    const row = document.createElement('div');
    row.className = 'loc-item';
    row.innerHTML = `
      <div>
        <div class="loc-main">${escapeHtml(item.name)}</div>
        <div class="loc-path">${escapeHtml(item.path)}</div>
      </div>
      <div class="loc-actions">
        <button type="button" class="star-btn ${isFavActive ? 'active':''}" title="مفضلة">★</button>
      </div>
    `;

    row.addEventListener('click', (e)=>{
      // لو ضغط على زر النجمة
      const star = e.target.closest('.star-btn');
      if (star) {
        e.stopPropagation();
        toggleFav(item.id);
        return;
      }
      selectLocation(item);
    });

    return row;
  }

  function renderLists(){
    const q = (locSearchInput?.value || '').trim().toLowerCase();
    const fav = new Set(getLS(LS_FAV).map(Number));
    const recentArr = getLS(LS_RECENT).map(Number);
    const recentSet = new Set(recentArr);

    // المفضلة
    favLocList.innerHTML = '';
    const favItems = LOCATIONS.filter(x => fav.has(x.id));
    if (!favItems.length) {
      favLocList.innerHTML = `<div class="p-3 mini-note">لا يوجد مفضلة بعد. اضغط ★ على أي موقع.</div>`;
    } else {
      favItems.slice(0, 200).forEach(item=>{
        favLocList.appendChild(itemRow(item, true));
      });
    }

    // النتائج + آخر استخدام (في الأعلى)
    allLocList.innerHTML = '';
    let list = LOCATIONS;

    if (q) {
      list = list.filter(x =>
        (x.name || '').toLowerCase().includes(q) ||
        (x.path || '').toLowerCase().includes(q)
      );
    }

    // رتب: recent أولاً ثم الباقي
    list.sort((a,b)=>{
      const ar = recentSet.has(a.id) ? 0 : 1;
      const br = recentSet.has(b.id) ? 0 : 1;
      if (ar !== br) return ar - br;
      return (a.path || '').localeCompare((b.path || ''), 'ar');
    });

    if (!list.length) {
      allLocList.innerHTML = `<div class="p-3 mini-note">لا توجد نتائج مطابقة.</div>`;
      return;
    }

    list.slice(0, 300).forEach(item=>{
      allLocList.appendChild(itemRow(item, fav.has(item.id)));
    });
  }

  function escapeHtml(str){
    return String(str || '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  if (openLocPicker) openLocPicker.addEventListener('click', openModal);
  if (closeLocPicker) closeLocPicker.addEventListener('click', closeModal);

  // اغلاق عند الضغط خارج الكارد
  if (locModal) {
    locModal.addEventListener('click', (e)=>{
      if (e.target === locModal) closeModal();
    });
  }

  if (locSearchInput) locSearchInput.addEventListener('input', renderLists);
  if (clearLocSearch) clearLocSearch.addEventListener('click', ()=>{
    locSearchInput.value = '';
    renderLists();
    locSearchInput.focus();
  });

  // ESC closes
  document.addEventListener('keydown', (e)=>{
    if (e.key === 'Escape' && locModal.classList.contains('open')) closeModal();
  });

  // ===== Form validation (required type + location) =====
  const form = document.getElementById('assetAddForm');
  form.addEventListener('submit', (e)=>{
    let ok = true;

    if (!typeHidden.value) {
      ok = false;
      typeReqBadge.style.display = 'inline-block';
      typeGrid.scrollIntoView({behavior:'smooth', block:'center'});
    }

    if (!locationHidden.value) {
      ok = false;
      locReqBadge.style.display = 'inline-block';
      locationDisplay.scrollIntoView({behavior:'smooth', block:'center'});
    }

    if (!ok) {
      e.preventDefault();
    }
  });

})();
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
