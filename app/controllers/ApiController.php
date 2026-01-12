<?php
  $selectedLocationId = !empty($data['location_id']) ? (int)$data['location_id'] : 0;
  $selectedPath = '';
  if (!empty($selectedLocationId) && isset($locById[$selectedLocationId])) {
    $selectedPath = buildLocPath($selectedLocationId, $locById);
  }
?>

<div class="form-group mb-3">
  <label class="form-label">الموقع <span class="text-danger">*</span></label>

  <input type="hidden" name="location_id" id="location_id" value="<?= (int)$selectedLocationId ?>">

  <div class="position-relative">
    <input
      type="text"
      id="loc_search"
      class="form-control"
      placeholder="ابحث عن الموقع (مثال: مبنى 2، معمل 8...)"
      autocomplete="off"
      value="<?= htmlspecialchars($selectedPath) ?>"
    >

    <div id="loc_dropdown" class="loc-dd d-none"></div>
  </div>

  <small class="text-muted d-block mt-1" id="loc_help">
    اكتب حرفين أو أكثر، ثم اختر الموقع من القائمة.
  </small>

  <div class="mt-2 d-flex gap-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" id="loc_clear">
      مسح الاختيار
    </button>
    <span class="badge bg-light text-dark" id="loc_badge" style="<?= $selectedLocationId ? '' : 'display:none;' ?>">
      تم اختيار موقع ✓
    </span>
  </div>
</div>

<style>
  .loc-dd{
    position:absolute;
    top:100%;
    left:0;
    right:0;
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:12px;
    box-shadow:0 12px 30px rgba(0,0,0,.08);
    margin-top:8px;
    overflow:hidden;
    z-index:9999;
    max-height:320px;
    overflow-y:auto;
  }
  .loc-dd .item{
    padding:10px 12px;
    cursor:pointer;
    border-bottom:1px solid #f1f5f9;
  }
  .loc-dd .item:last-child{ border-bottom:0; }
  .loc-dd .item:hover{ background:#f8fafc; }
  .loc-dd .path{ font-weight:600; }
  .loc-dd .meta{ font-size:12px; color:#64748b; margin-top:2px; }
</style>

<script>
(function(){
  const input = document.getElementById('loc_search');
  const hidden = document.getElementById('location_id');
  const dd = document.getElementById('loc_dropdown');
  const clearBtn = document.getElementById('loc_clear');
  const badge = document.getElementById('loc_badge');

  let t = null;
  let lastQ = '';

  function showDropdown(){ dd.classList.remove('d-none'); }
  function hideDropdown(){ dd.classList.add('d-none'); dd.innerHTML = ''; }

  function setSelected(id, path){
    hidden.value = id;
    input.value = path || '';
    badge.style.display = id ? '' : 'none';
    hideDropdown();
  }

  clearBtn.addEventListener('click', () => setSelected('', ''));

  document.addEventListener('click', (e) => {
    if (!dd.contains(e.target) && e.target !== input) hideDropdown();
  });

  input.addEventListener('input', () => {
    const q = (input.value || '').trim();
    if (q.length < 2) { hideDropdown(); return; }

    if (t) clearTimeout(t);
    t = setTimeout(async () => {
      if (q === lastQ) return;
      lastQ = q;

      try{
        const url = `index.php?page=api/locations&q=${encodeURIComponent(q)}&limit=20`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        const items = (data && data.items) ? data.items : [];
        if (!items.length){ hideDropdown(); return; }

        dd.innerHTML = items.map(it => `
          <div class="item" data-id="${it.id}" data-path="${(it.path||'').replace(/"/g,'&quot;')}">
            <div class="path">${it.path || it.name_ar}</div>
            <div class="meta">ID: ${it.id}${it.type ? ' • ' + it.type : ''}</div>
          </div>
        `).join('');

        dd.querySelectorAll('.item').forEach(el => {
          el.addEventListener('click', () => {
            const id = el.getAttribute('data-id');
            const path = el.getAttribute('data-path');
            setSelected(id, path);
          });
        });

        showDropdown();
      }catch(e){
        hideDropdown();
      }
    }, 200);
  });

  // إذا عندنا ID محفوظ بدون مسار واضح
  if (hidden.value && !input.value){
    fetch(`index.php?page=api/location_path&id=${encodeURIComponent(hidden.value)}`)
      .then(r => r.json())
      .then(d => { if (d && d.ok && d.path) { input.value = d.path; badge.style.display=''; } })
      .catch(()=>{});
  }
})();
</script>
