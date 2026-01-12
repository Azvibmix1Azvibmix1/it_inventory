<?php
require APPROOT . '/views/inc/header.php';

$locations  = $data['locations'] ?? [];
$users_list = $data['users_list'] ?? [];
$asset_err  = $data['asset_err'] ?? '';

if (!is_array($locations) && !is_object($locations)) {
  $locations = [];
}

$locById = [];
foreach ($locations as $loc) {
  $id = (int)($loc->id ?? 0);
  if ($id > 0) $locById[$id] = $loc;
}

if (!function_exists('buildLocationPath')) {
  function buildLocationPath($loc, array $locById): string
  {
    if (!$loc || empty($loc->id)) return '';

    $parts = [ (string)($loc->name_ar ?? $loc->name ?? ('موقع#'.$loc->id)) ];
    $cur = $loc;
    $guard = 0;

    while ($guard < 30) {
      $guard++;
      $pid = (int)($cur->parent_id ?? $cur->parentId ?? 0);
      if ($pid <= 0 || !isset($locById[$pid])) break;

      $cur = $locById[$pid];
      array_unshift($parts, (string)($cur->name_ar ?? $cur->name ?? ('موقع#'.$cur->id)));
    }

    return implode(' › ', $parts);
  }
}

$allowedTypes = ['Laptop','Desktop','Printer','Monitor','Server','Network','Other'];

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

  .page-shell{ max-width: 1180px; margin: 0 auto; padding: 18px; }
  .page-title{ font-weight:800; font-size:28px; margin: 10px 0 18px; display:flex; align-items:center; gap:10px; }
  .subtitle{ color:#6b7280; font-size:13px; margin-top:-10px; margin-bottom:14px; }

  .cardish{ background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow: 0 6px 20px rgba(0,0,0,.04); }
  .card-pad{ padding: 18px; }

  .grid-2{ display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
  @media(max-width: 900px){ .grid-2{ grid-template-columns: 1fr; } }

  .form-label{ font-weight:700; margin-bottom:6px; }
  .help{ font-size:12px; color:#6b7280; margin-top:6px; }

  /* Type Cards */
  .type-wrap{ margin-top:8px; }
  .type-grid{ display:grid; grid-template-columns: repeat(7, 1fr); gap:10px; }
  @media(max-width: 1100px){ .type-grid{ grid-template-columns: repeat(4, 1fr);} }
  @media(max-width: 600px){ .type-grid{ grid-template-columns: repeat(2, 1fr);} }

  .type-card{
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:10px 12px;
    display:flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
    background: #fff;
    transition: .15s ease;
    user-select:none;
  }
  .type-card:hover{ transform: translateY(-1px); box-shadow: 0 8px 18px rgba(0,0,0,.06); }
  .type-card.active{ border-color:#111827; box-shadow: 0 10px 24px rgba(17,24,39,.12); }

  .type-ico{
    width:40px; height:40px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    background:#f3f4f6; color:#111827; font-size:18px;
  }
  .type-name{ font-weight:800; }
  .type-sub{ font-size:12px; color:#6b7280; margin-top:-2px; }

  /* Location Picker */
  .loc-field{
    display:flex; gap:10px; align-items:center;
  }
  .loc-field input[readonly]{ background:#f9fafb; cursor:pointer; }
  .loc-btn{
    border-radius:12px; padding:10px 14px; border:1px solid #e5e7eb;
    background:#111827; color:#fff; font-weight:700;
  }
  .loc-btn:hover{ opacity:.95; }

  .req-badge{
    display:none; margin-right:8px;
    background:#fee2e2; color:#991b1b; border:1px solid #fecaca;
    padding:4px 8px; border-radius:999px; font-size:12px; font-weight:800;
  }

  /* Modal */
  .loc-modal{
    position:fixed; inset:0; background:rgba(0,0,0,.25);
    display:none; align-items:center; justify-content:center;
    padding:18px;
    z-index:9999;
  }
  .loc-modal.open{ display:flex; }
  .loc-card{
    width:min(920px, 100%);
    background:#fff;
    border-radius:18px;
    border:1px solid #e5e7eb;
    box-shadow: 0 30px 80px rgba(0,0,0,.25);
    overflow:hidden;
  }
  .loc-head{
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 16px;
    border-bottom:1px solid #e5e7eb;
    background:#f9fafb;
  }
  .loc-head h5{ margin:0; font-weight:900; }
  .loc-close{
    border:none; background:#fff; border:1px solid #e5e7eb;
    width:36px; height:36px; border-radius:12px; cursor:pointer;
  }

  .loc-body{ display:grid; grid-template-columns: 1.1fr .9fr; gap:0; }
  @media(max-width: 850px){ .loc-body{ grid-template-columns: 1fr; } }

  .loc-panel{ padding:12px 14px; }
  .loc-panel + .loc-panel{ border-right:1px solid #e5e7eb; }

  .loc-searchbar{ display:flex; gap:10px; align-items:center; margin-bottom:10px; }
  .loc-searchbar input{ border-radius:12px; }
  .mini-btn{
    border-radius:12px; padding:10px 12px; border:1px solid #e5e7eb;
    background:#fff; font-weight:800;
  }
  .mini-note{ font-size:12px; color:#6b7280; }

  .loc-list{
    border:1px solid #e5e7eb;
    border-radius:14px;
    max-height: 360px;
    overflow:auto;
    background:#fff;
  }
  .loc-item{
    display:flex; justify-content:space-between; gap:10px;
    padding:10px 12px;
    border-bottom:1px solid #f1f5f9;
    cursor:pointer;
  }
  .loc-item:last-child{ border-bottom:none; }
  .loc-item:hover{ background:#f9fafb; }
  .loc-main{ font-weight:900; }
  .loc-path{ font-size:12px; color:#6b7280; margin-top:2px; }

  .loc-actions{ display:flex; align-items:center; gap:8px; }
  .star-btn{
    border:none; background:#fff;
    width:34px; height:34px; border-radius:12px;
    border:1px solid #e5e7eb;
    font-size:16px;
    cursor:pointer;
  }
  .star-btn.active{ background:#111827; color:#fff; border-color:#111827; }

  .section-title{
    font-weight:900; margin: 0 0 8px; display:flex; align-items:center; justify-content:space-between;
  }

  .actions-row{ display:flex; justify-content:flex-end; gap:10px; margin-top:14px; }
  .btn-soft{
    border-radius:12px;
    padding:10px 14px;
    border:1px solid #e5e7eb;
    background:#fff;
    font-weight:800;
  }
  .btn-primary-soft{
    border-radius:12px;
    padding:10px 16px;
    border:1px solid #111827;
    background:#111827;
    color:#fff;
    font-weight:900;
  }
</style>

<div class="page-shell">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <div class="page-title">إضافة جهاز</div>
      <div class="subtitle">املأ البيانات الأساسية للجهاز ثم اختر الموقع بسهولة عبر البحث.</div>
    </div>
    <a href="<?= URLROOT; ?>/assets/index" class="btn-soft">رجوع</a>
  </div>

  <div class="cardish">
    <div class="card-pad">

      <form id="assetAddForm" action="<?= URLROOT; ?>/assets/add" method="post" autocomplete="off">
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Tag (رقم الجهاز) <span class="text-danger">*</span></label>
            <input type="text" name="tag" class="form-control <?= (!empty($data['tag_err'])) ? 'is-invalid' : ''; ?>"
                   value="<?= htmlspecialchars($data['tag'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <span class="invalid-feedback"><?= $data['tag_err'] ?? ''; ?></span>
            <div class="help">يتولد تلقائيًا بعد الحفظ (مثال: AST-000001). يتم توليد التالي تلقائيًا لتفادي التكرار.</div>
          </div>

          <div class="form-group">
            <label class="form-label">Physical address (MAC)</label>
            <input id="macInput" type="text" name="mac" class="form-control"
                   placeholder="AA:BB:CC:DD:EE:FF"
                   value="<?= htmlspecialchars($data['mac'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <div class="help">ينسّق تلقائيًا بصيغة MAC عند الكتابة.</div>
          </div>
        </div>

        <!-- TYPE (cards) -->
        <div class="form-group mt-3">
          <div class="d-flex align-items-center gap-2">
            <label class="form-label mb-0">النوع <span class="text-danger">*</span></label>
            <span id="typeReqBadge" class="req-badge">اختر نوع</span>
          </div>

          <input type="hidden" name="type" id="typeHidden" value="<?= htmlspecialchars($currentType, ENT_QUOTES, 'UTF-8'); ?>">

          <div class="type-wrap">
            <div id="typeGrid" class="type-grid">
              <?php
                $typeIcons = [
                  'Laptop'  => '💻',
                  'Desktop' => '🖥️',
                  'Printer' => '🖨️',
                  'Monitor' => '🖵',
                  'Server'  => '🗄️',
                  'Network' => '🌐',
                  'Other'   => '📦',
                ];
                $typeSubs = [
                  'Laptop'  => 'Laptop',
                  'Desktop' => 'Desktop',
                  'Printer' => 'Printer',
                  'Monitor' => 'Monitor',
                  'Server'  => 'Server',
                  'Network' => 'Network',
                  'Other'   => 'Other',
                ];
                foreach ($allowedTypes as $t):
                  $isActive = ($currentType === $t);
              ?>
              <div class="type-card <?= $isActive ? 'active' : '' ?>" data-type="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="type-ico"><?= $typeIcons[$t] ?? '📦' ?></div>
                <div>
                  <div class="type-name"><?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="type-sub"><?= htmlspecialchars($typeSubs[$t] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <span class="invalid-feedback d-block"><?= $data['type_err'] ?? ''; ?></span>
        </div>

        <div class="grid-2 mt-3">
          <div class="form-group">
            <label class="form-label">الماركة (اختياري)</label>
            <input type="text" name="brand" class="form-control"
                   value="<?= htmlspecialchars($data['brand'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">الموديل (اختياري)</label>
            <input type="text" name="model" class="form-control"
                   value="<?= htmlspecialchars($data['model'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>
        </div>

        <div class="grid-2 mt-3">
          <div class="form-group">
            <label class="form-label">تاريخ الشراء (اختياري)</label>
            <input type="text" name="purchase_date" class="form-control js-date"
                   placeholder="YYYY-MM-DD"
                   value="<?= htmlspecialchars($data['purchase_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">انتهاء الضمان (اختياري)</label>
            <input type="text" name="warranty_end" class="form-control js-date"
                   placeholder="YYYY-MM-DD"
                   value="<?= htmlspecialchars($data['warranty_end'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>
        </div>

        <div class="grid-2 mt-3">
          <div class="form-group">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-control">
              <?php
                $status = $data['status'] ?? 'Active';
                $opts = ['Active'=>'Active', 'Inactive'=>'Inactive', 'Maintenance'=>'Maintenance'];
                foreach($opts as $k=>$v):
              ?>
                <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8'); ?>" <?= ($status===$k?'selected':''); ?>>
                  <?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- LOCATION (modal picker) -->
          <div class="form-group">
            <div class="d-flex align-items-center gap-2">
              <label class="form-label mb-0">الموقع <span class="text-danger">*</span></label>
              <span id="locReqBadge" class="req-badge">اختر موقع</span>
            </div>

            <input type="hidden" name="location_id" id="locationHidden" value="<?= (int)$currentLoc; ?>">

            <div class="loc-field mt-2">
              <input id="locationDisplay" type="text" class="form-control" readonly
       placeholder="اختر موقع الجهاز..."
       value="<?= htmlspecialchars($currentLocLabel, ENT_QUOTES, 'UTF-8'); ?>">
              <button id="openLocPicker" type="button" class="loc-btn">اختيار</button>
            </div>

            <div class="help">بحث سريع + آخر استخدام + مفضلة ⭐ (بدون قوائم طويلة).</div>
            <span class="invalid-feedback d-block"><?= $data['location_err'] ?? ''; ?></span>
          </div>
        </div>

        <div class="form-group mt-3">
          <label class="form-label">ملاحظات (اختياري)</label>
          <textarea name="notes" class="form-control" rows="4"><?= htmlspecialchars($data['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="actions-row">
          <a href="<?= URLROOT; ?>/assets/index" class="btn-soft">إلغاء</a>
          <button type="submit" class="btn-primary-soft">حفظ الجهاز</button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- Location Picker Modal -->
<div id="locModal" class="loc-modal" aria-hidden="true">
  <div class="loc-card">
    <div class="loc-head">
      <h5>اختيار موقع الجهاز</h5>
      <button id="closeLocPicker" class="loc-close" type="button">×</button>
    </div>

    <div class="loc-body">
      <!-- Left: Search + Results/Recent -->
      <div class="loc-panel">
        <div class="loc-searchbar">
          <button type="button" id="clearLocSearch" class="mini-btn">مسح</button>
          <input id="locSearchInput" type="text" class="form-control"
                 placeholder="ابحث عن موقع... (مثال: مبنى 8، معمل 1)" autocomplete="off">
        </div>

        <div class="section-title">
          <span>النتائج</span>
          <span class="mini-note">تظهر أولاً</span>
        </div>

        <div id="allLocList" class="loc-list">
          <div class="p-3 mini-note">اكتب حرفين أو أكثر للبحث، أو اختر من "آخر استخدام".</div>
        </div>
      </div>

      <!-- Right: Favorites -->
      <div class="loc-panel">
        <div class="section-title">
          <span>المفضلة ⭐</span>
        </div>

        <div id="favLocList" class="loc-list">
          <div class="p-3 mini-note">لا يوجد مفضلة بعد. اضغط ★ على أي موقع.</div>
        </div>

        <div class="mini-note mt-2">تلميح: اضغط ⭐ لإضافة الموقع للمفضلة. يتم حفظها على جهازك.</div>
      </div>
    </div>
  </div>
</div>

<!-- Flatpickr JS + Arabic locale -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>

<script>
(function(){
  const API_LOC_SEARCH = 'index.php?page=api/locations';
  const API_LIMIT = 50;

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
      v = v.slice(0, 12);
      const parts = v.match(/.{1,2}/g) || [];
      macInput.value = parts.join(':');
    });
  }

  // ===== Type cards =====
  const typeHidden = document.getElementById('typeHidden');
  const typeGrid = document.getElementById('typeGrid');
  const typeReqBadge = document.getElementById('typeReqBadge');

  if (typeGrid) {
    typeGrid.addEventListener('click', (e)=>{
      const card = e.target.closest('.type-card');
      if (!card) return;
      const t = card.getAttribute('data-type') || '';
      if (!t) return;

      typeHidden.value = t;
      typeReqBadge.style.display = 'none';

      typeGrid.querySelectorAll('.type-card').forEach(c=>c.classList.remove('active'));
      card.classList.add('active');
    });
  }

  // ===== Location picker (API + Favorites/Recents) =====
 // ===== Location picker (API + Favorites/Recents + keyboard) =====
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

const LS_RECENT = 'itinv_recent_locations_v2';
const LS_FAV    = 'itinv_fav_locations_v2';

function getLS(key){
  try { const v = JSON.parse(localStorage.getItem(key) || '[]'); return Array.isArray(v) ? v : []; }
  catch(e){ return []; }
}
function setLS(key, val){ try { localStorage.setItem(key, JSON.stringify(val)); } catch(e){} }

function normalizeItem(x){
  const id = Number(x?.id || 0);
  const name = String(x?.name ?? x?.name_ar ?? ('موقع#'+id));
  const path = String(x?.path ?? x?.full_path ?? x?.label ?? name);
  const ts = Number(x?.ts || Date.now());
  return { id, name, path, ts };
}

function escapeHtml(str){
  return String(str||'')
    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'","&#039;");
}

function uniqById(list){
  const seen = new Set(); const out = [];
  for (const it of list){
    const id = Number(it?.id||0);
    if (!id || seen.has(id)) continue;
    seen.add(id); out.push(it);
  }
  return out;
}

function getFavList(){
  return uniqById(getLS(LS_FAV).map(normalizeItem)).slice(0, 50);
}
function getRecentList(){
  const r = getLS(LS_RECENT).map(normalizeItem);
  r.sort((a,b)=>(b.ts||0)-(a.ts||0));
  return uniqById(r).slice(0, 15);
}

function toggleFav(item){
  const id = Number(item?.id||0);
  if (!id) return;
  let fav = getFavList();
  const i = fav.findIndex(x=>x.id===id);
  if (i>=0) fav.splice(i,1);
  else fav.unshift({id, name:item.name, path:item.path, ts:Date.now()});
  setLS(LS_FAV, uniqById(fav).slice(0,50));
  renderFav();
  renderResults(lastQuery, lastResults);
}

function pushRecent(item){
  const id = Number(item?.id||0);
  if (!id) return;
  let r = getRecentList().filter(x=>x.id!==id);
  r.unshift({id, name:item.name, path:item.path, ts:Date.now()});
  setLS(LS_RECENT, uniqById(r).slice(0,15));
}

function selectLocation(item){
  locationHidden.value = String(item.id);
  locationDisplay.value = item.path || item.name || '';
  locReqBadge && (locReqBadge.style.display='none');
  pushRecent(item);
  closeModal();
}

function itemRow(item){
  const favIds = new Set(getFavList().map(x=>x.id));
  const isFavActive = favIds.has(item.id);

  const row = document.createElement('div');
  row.className = 'loc-item';
  row.setAttribute('data-id', String(item.id));
  row.innerHTML = `
    <div>
      <div class="loc-main">${escapeHtml(item.name)}</div>
      <div class="loc-path">${escapeHtml(item.path)}</div>
    </div>
    <div class="loc-actions">
      <button type="button" class="star-btn ${isFavActive?'active':''}" title="مفضلة">★</button>
    </div>
  `;

  row.addEventListener('click', (e)=>{
    const star = e.target.closest('.star-btn');
    if (star){ e.stopPropagation(); toggleFav(item); return; }
    selectLocation(item);
  });

  return row;
}

function renderFav(){
  const fav = getFavList();
  favLocList.innerHTML = '';
  if (!fav.length){
    favLocList.innerHTML = `<div class="p-3 mini-note">لا يوجد مفضلة بعد. اضغط ★ على أي موقع.</div>`;
    return;
  }
  fav.forEach(it=> favLocList.appendChild(itemRow(it)));
}

// نتائج + كيبورد selection
let lastQuery = '';
let lastResults = [];
let activeIndex = -1;
let abortCtrl = null;
let timer = null;

function highlightActive(){
  const rows = allLocList.querySelectorAll('.loc-item');
  rows.forEach((r,i)=> {
    r.style.background = (i===activeIndex) ? 'rgba(0,0,0,.04)' : '';
  });
}

function renderResults(q, results){
  allLocList.innerHTML = '';

  // بدون بحث: عرض recent
  if (!q || q.length < 2){
    const rec = getRecentList();
    if (!rec.length){
      allLocList.innerHTML = `<div class="p-3 mini-note">اكتب حرفين أو أكثر للبحث، أو اختر من آخر استخدام.</div>`;
      return;
    }
    rec.forEach(it=> allLocList.appendChild(itemRow(it)));
    activeIndex = -1;
    return;
  }

  if (!results.length){
    allLocList.innerHTML = `<div class="p-3 mini-note">لا توجد نتائج مطابقة.</div>`;
    return;
  }

  results.forEach(it=> allLocList.appendChild(itemRow(it)));
  activeIndex = -1;
}

function openModal(){
  locModal.classList.add('open');
  locModal.setAttribute('aria-hidden','false');
  renderFav();
  renderResults('', []);
  setTimeout(()=> locSearchInput && locSearchInput.focus(), 50);
}

function closeModal(){
  locModal.classList.remove('open');
  locModal.setAttribute('aria-hidden','true');
}

async function doSearch(q){
  lastQuery = q;
  if (abortCtrl) abortCtrl.abort();
  abortCtrl = new AbortController();

  // Loading UI
  allLocList.innerHTML = `
    <div class="p-3 mini-note d-flex align-items-center gap-2">
      <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
      جاري البحث...
    </div>
  `;

  const url = `index.php?page=api/locations&q=${encodeURIComponent(q)}&limit=30`;

  try{
    const res = await fetch(url, { signal: abortCtrl.signal, headers:{'Accept':'application/json'} });
    const data = await res.json();

    const items = (data?.items || []).map(normalizeItem).filter(x=>x.id);
    lastResults = items;

    // عرض النتائج مع meta
    allLocList.innerHTML = '';
    if (!items.length){
      allLocList.innerHTML = `<div class="p-3 mini-note">لا توجد نتائج مطابقة.</div>`;
      activeIndex = -1;
      return;
    }

    items.forEach(it=>{
      const row = document.createElement('div');
      row.className = 'loc-item';
      row.setAttribute('data-id', String(it.id));
      row.innerHTML = `
        <div>
          <div class="loc-main">${escapeHtml(it.name)}</div>
          <div class="loc-path">${escapeHtml(it.path)}</div>
          <div class="mini-note" style="margin-top:4px;">ID: ${it.id}${it.type ? ' • ' + escapeHtml(it.type) : ''}</div>
        </div>
        <div class="loc-actions">
          <button type="button" class="star-btn ${new Set(getFavList().map(x=>x.id)).has(it.id) ? 'active':''}" title="مفضلة">★</button>
        </div>
      `;

      row.addEventListener('click', (e)=>{
        const star = e.target.closest('.star-btn');
        if (star){ e.stopPropagation(); toggleFav(it); return; }
        selectLocation(it);
      });

      allLocList.appendChild(row);
    });

    activeIndex = -1;

  } catch(e){
    if (e?.name === 'AbortError') return;
    allLocList.innerHTML = `<div class="p-3 mini-note">تعذر جلب النتائج. تأكد من api/locations</div>`;
  }
}


function scheduleSearch(q){
  if (timer) clearTimeout(timer);
  timer = setTimeout(()=> doSearch(q), 220);
}

if (openLocPicker) openLocPicker.addEventListener('click', openModal);
if (closeLocPicker) closeLocPicker.addEventListener('click', closeModal);

if (locModal){
  locModal.addEventListener('click', (e)=>{ if (e.target === locModal) closeModal(); });
}

if (clearLocSearch){
  clearLocSearch.addEventListener('click', ()=>{
    locSearchInput.value = '';
    lastQuery = ''; lastResults = []; activeIndex = -1;
    renderFav();
    renderResults('', []);
    locSearchInput.focus();
  });
}

if (locSearchInput){
  locSearchInput.addEventListener('input', ()=>{
    const q = (locSearchInput.value || '').trim();
    renderFav();
    if (q.length < 2){
      lastQuery = ''; lastResults = []; activeIndex = -1;
      renderResults('', []);
      return;
    }
    scheduleSearch(q);
  });

  locSearchInput.addEventListener('keydown', (e)=>{
  const rows = allLocList.querySelectorAll('.loc-item');
  if (!rows.length) return;

  if (e.key === 'ArrowDown'){
    e.preventDefault();
    activeIndex = Math.min(activeIndex + 1, rows.length - 1);
    highlightActive();
    rows[activeIndex].scrollIntoView({block:'nearest'});
  }
  if (e.key === 'ArrowUp'){
    e.preventDefault();
    activeIndex = Math.max(activeIndex - 1, 0);
    highlightActive();
    rows[activeIndex].scrollIntoView({block:'nearest'});
  }
  if (e.key === 'Enter'){
    e.preventDefault();
    const pickIndex = (activeIndex >= 0) ? activeIndex : 0;
    const id = Number(rows[pickIndex].getAttribute('data-id')||0);
    const chosen = lastResults.find(x=>x.id===id) || getRecentList().find(x=>x.id===id);
    if (chosen) selectLocation(chosen);
  }
});

}

// ESC
document.addEventListener('keydown', (e)=>{
  if (e.key === 'Escape' && locModal?.classList.contains('open')) closeModal();
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

function extractAndNormalizeMac(raw) {
  if (!raw) return '';

  const text = String(raw).toUpperCase();

  // 1) التقط MAC جاهز مثل: 24-FB-E3-46-68-08 أو 24:FB:...
  const m = text.match(/([0-9A-F]{2}(?:[:-][0-9A-F]{2}){5})/);
  if (m && m[1]) {
    return m[1].replace(/:/g, '-'); // نخليه Dash
  }

  // 2) fallback: خذ آخر 12 HEX (مو أول 12) عشان ما يلقط من 101-P33...
  let hex = text.replace(/[^0-9A-F]/g, '');
  if (hex.length < 12) return raw.trim();
  hex = hex.slice(-12); // ✅ الأهم
  return hex.match(/.{2}/g).join('-');
}


// اربطها بحقل الـ MAC (عدّل السلكتور حسب اسم الحقل عندك)
const macInput =
  document.querySelector('input[name="mac"]')
  || document.querySelector('input[name="mac_address"]')
  || document.querySelector('#mac')
  || null;

if (macInput) {
  const apply = () => { macInput.value = extractAndNormalizeMac(macInput.value); };
  macInput.addEventListener('blur', apply);
  macInput.addEventListener('change', apply);
  macInput.addEventListener('paste', () => setTimeout(apply, 0));
}

function normalizeMacSafe(raw) {
  if (!raw) return '';

  const text = String(raw).toUpperCase();

  // 1) التقط MAC جاهز بأي مكان داخل النص (dash أو colon)
  const m = text.match(/([0-9A-F]{2}(?:[:-][0-9A-F]{2}){5})/);
  if (m && m[1]) {
    return m[1].replace(/:/g, '-'); // نخليه Dash
  }

  // 2) fallback: خذ آخر 12 hex فقط (عشان ما يلقط من 101-P33...)
  let hex = text.replace(/[^0-9A-F]/g, '');
  if (hex.length < 12) return String(raw).trim();
  hex = hex.slice(-12);
  return hex.match(/.{2}/g).join('-');
}

(function bindMacFix(){
  const macInput =
    document.querySelector('input[name="mac"]')
    || document.querySelector('input[name="mac_address"]')
    || document.querySelector('#mac')
    || null;

  if (!macInput) return;

  // ✅ أهم جزء: منع اللصق الافتراضي وقراءة النص من الكليببورد مباشرة
  macInput.addEventListener('paste', (e) => {
    const clip = (e.clipboardData || window.clipboardData)?.getData('text') || '';
    if (!clip) return;
    e.preventDefault();
    macInput.value = normalizeMacSafe(clip);
  });

  const apply = () => { macInput.value = normalizeMacSafe(macInput.value); };
  macInput.addEventListener('blur', apply);
  macInput.addEventListener('change', apply);
})();


</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
