<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
  // Dashboard counts
  $parts = $data['parts'] ?? [];
  $totalCount = is_array($parts) ? count($parts) : 0;
  $outCount = 0;
  $lowCount = 0;

  if (is_array($parts)) {
    foreach ($parts as $p) {
      $q = (int)($p->quantity ?? 0);
      $min = (int)($p->min_quantity ?? 0);
      if ($q <= 0) {
        $outCount++;
      } elseif ($q <= $min) {
        $lowCount++;
      }
    }
  }
?>

<style>
  /* ✅ KPI cards: unified navy color */
  .bg-navy { background-color: #0F2A43 !important; }
  .text-navy { color: #0F2A43 !important; }

  .kpi-card {
    border: 0;
    border-radius: 14px;
  }
  .kpi-card .card-text { opacity: .9; }
</style>

<div class="container mt-4">

    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h1><i class="fa fa-microchip text-navy"></i> إدارة قطع الغيار</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?php echo URLROOT; ?>/index.php?page=spareParts/add" class="btn btn-primary">
                <i class="fa fa-plus"></i> إضافة قطعة جديدة
            </a>
        </div>
    </div>

    <div class="row mb-4 text-center">
        <div class="col-md-4">
            <div class="card text-white bg-navy mb-3 shadow-sm kpi-card">
                <div class="card-body">
                    <h1 class="display-4 fw-bold" dir="ltr"><?php echo $outCount; ?></h1>
                    <p class="card-text">قطع نفذت كميتها</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-navy mb-3 shadow-sm kpi-card">
                <div class="card-body">
                    <h1 class="display-4 fw-bold" dir="ltr"><?php echo $lowCount; ?></h1>
                    <p class="card-text">قطع منخفضة العدد</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-navy mb-3 shadow-sm kpi-card">
                <div class="card-body">
                    <h1 class="display-4 fw-bold" dir="ltr">
                        <?php echo $totalCount; ?>
                    </h1>
                    <p class="card-text">إجمالي القطع المسجلة</p>
                </div>
            </div>
        </div>
    </div>
    <?php
  $f = $data['filters'] ?? [];
  $q = htmlspecialchars($f['q'] ?? '');
  $selectedLoc = (int)($f['location_id'] ?? 0);
  $status = $f['status'] ?? '';
?>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form method="get" action="index.php" class="row g-2 align-items-end">
      <input type="hidden" name="page" value="spareparts/index">

      <div class="col-12 col-md-5">
        <label class="form-label">بحث (الاسم أو PN)</label>
        <input type="text" name="q" class="form-control" value="<?= $q ?>" placeholder="مثال: RAM أو 4502">
      </div>

      <div class="col-12 col-md-4">
        <label class="form-label">الموقع</label>
        <select name="location_id" class="form-select">
          <option value="0">الكل</option>
          <?php foreach (($data['locations'] ?? []) as $loc): ?>
            <?php $lid = (int)($loc->id ?? 0); ?>
            <option value="<?= $lid ?>" <?= $lid === $selectedLoc ? 'selected' : '' ?>>
              <?= htmlspecialchars((string)($loc->name_ar ?? $loc->name_en ?? ('موقع#'.$lid))) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select">
          <option value=""   <?= $status === '' ? 'selected' : '' ?>>الكل</option>
          <option value="ok" <?= $status === 'ok' ? 'selected' : '' ?>>متوفر</option>
          <option value="low" <?= $status === 'low' ? 'selected' : '' ?>>منخفض</option>
          <option value="out" <?= $status === 'out' ? 'selected' : '' ?>>منتهية</option>
        </select>
      </div>

      <div class="col-12 d-flex gap-2 mt-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-search"></i> تطبيق
        </button>

        <a class="btn btn-light" href="index.php?page=spareparts/index">
          مسح الفلاتر
        </a>
      </div>
    </form>
  </div>
</div>


    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-secondary">سجل المخزون</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <?php
  $f = $data['filters'] ?? [];
  $currSort = $f['sort'] ?? 'name';
  $currDir  = $f['dir'] ?? 'asc';

  function sortUrl($key, $f) {
    $sort = $f['sort'] ?? 'name';
    $dir  = $f['dir'] ?? 'asc';

    $nextDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';

    $params = [
      'page' => 'spareparts/index',
      'q' => $f['q'] ?? '',
      'location_id' => $f['location_id'] ?? 0,
      'status' => $f['status'] ?? '',
      'sort' => $key,
      'dir' => $nextDir,
    ];

    return 'index.php?' . http_build_query($params);
  }
?>

                    <thead class="table-light">
  <tr>
    <th>
      <a href="<?= sortUrl(key: 'name', f: $f) ?>" class="text-decoration-none text-dark">
        اسم القطعة
      </a>
    </th>

    <th>رقم القطعة (PN)</th>

    <th>
      <a href="<?= sortUrl(key: 'qty', f: $f) ?>" class="text-decoration-none text-dark">
        الكمية
      </a>
    </th>

    <th>
      <a href="<?= sortUrl(key: 'location', f: $f) ?>" class="text-decoration-none text-dark">
        الموقع
      </a>
    </th>

    <th>
      <a href="<?= sortUrl(key: 'status', f: $f) ?>" class="text-decoration-none text-dark">
        الحالة
      </a>
    </th>

    <th>إجراءات سريعة</th>

    
  </tr>
</thead>

                    <tbody>
                        <?php if(!empty($data['parts'])): ?>
                            <?php foreach($data['parts'] as $part): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $part->name; ?></td>
                                <td class="text-muted"><?php echo $part->part_number ?? '-'; ?></td>

                                <td>
                                    <?php if($part->quantity == 0): ?>
                                        <span class="text-danger fw-bold">0</span>
                                    <?php elseif($part->quantity <= $part->min_quantity): ?>
                                        <span class="text-warning fw-bold"><?php echo $part->quantity; ?></span>
                                    <?php else: ?>
                                        <span class="text-success fw-bold"><?php echo $part->quantity; ?></span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                  <?php
                                    $locName = $part->location_name_ar ?? '';
                                    if (empty($locName) && !empty($part->location_name_en)) $locName = $part->location_name_en;
                                    echo !empty($locName) ? htmlspecialchars($locName) : 'غير محدد';
                                  ?>
                                 </td>


                                <td>
                                    <?php if($part->quantity > $part->min_quantity): ?>
                                        <span class="badge bg-success rounded-pill">متوفر</span>
                                    <?php elseif($part->quantity > 0): ?>
                                        <span class="badge bg-warning text-dark rounded-pill">منخفض</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill">نافذ</span>
                                    <?php endif; ?>
                                </td>

<td class="text-nowrap">
  <div class="d-inline-flex gap-1 align-items-center">
    <?php $returnTo = $_SERVER['REQUEST_URI']; ?>
    <?php $locId = (int)($part->location_id ?? 0); ?>

    <!-- توريد +1 -->
    <form method="post" action="index.php?page=spareparts/adjust" class="d-inline">
      <input type="hidden" name="id" value="<?= (int)$part->id ?>">
      <input type="hidden" name="delta" value="1">
      <input type="hidden" name="location_id" value="<?= $locId ?>">
      <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
      <button type="submit" class="btn btn-sm btn-success" title="توريد +1">
        <i class="bi bi-plus-circle"></i>
      </button>
    </form>

    <!-- صرف -1 -->
    <!-- صرف -1 -->
<form method="post" action="index.php?page=spareparts/adjust" class="d-inline">
  <input type="hidden" name="id" value="<?= (int)$part->id ?>">
  <input type="hidden" name="delta" value="-1">
  <input type="hidden" name="location_id" value="<?= $locId ?>">
  <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
  <button type="submit" class="btn btn-sm btn-warning" title="-1 صرف">
    <i class="bi bi-dash-circle"></i>
  </button>
</form>


    <!-- تعديل -->
    <a href="index.php?page=spareparts/edit&id=<?= (int)$part->id ?>"
       class="btn btn-sm btn-outline-primary" title="تعديل">
      <i class="bi bi-pencil"></i>
    </a>

    <!-- حذف -->
    <form method="post" action="index.php?page=spareparts/delete" class="d-inline"
          onsubmit="return confirm('متأكد تبغى تحذف القطعة؟');">
      <input type="hidden" name="id" value="<?= (int)$part->id ?>">
      <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
      <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
        <i class="bi bi-trash"></i>
      </button>
    </form>

    
<button type="button"
  class="btn btn-sm btn-outline-secondary btn-moves"
  data-id="<?= (int)$part->id ?>"
  data-name="<?= htmlspecialchars((string)($part->name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
  data-pn="<?= htmlspecialchars((string)($part->part_number ?? ''), ENT_QUOTES, 'UTF-8') ?>"
  title="سجل الحركة">
  <i class="bi bi-clock-history"></i>
</button>


  </div>
</td>




                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد قطع غيار مسجلة حتى الآن.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
            </div>
            <?php
  $pg = $data['pagination'] ?? null;
  $f  = $data['filter'] ?? [];

  function pageUrl($p, $f) {
    $params = [
      'page' => 'spareparts/index',
      'q' => $f['q'] ?? '',
      'location_id' => $f['location_id'] ?? 0,
      'status' => $f['status'] ?? '',
      'sort' => $f['sort'] ?? 'name',
      'dir'  => $f['dir'] ?? 'asc',
      'p'    => $p,
    ];
    return 'index.php?' . http_build_query($params);
  }
?>

<?php if ($pg && ($pg['total_pages'] ?? 1) > 1): ?>
  <nav class="mt-3">
    <ul class="pagination justify-content-center flex-wrap">
      <?php $current = (int)$pg['page']; $total = (int)$pg['total_pages']; ?>

      <li class="page-item <?= $current <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= $current <= 1 ? '#' : pageUrl($current - 1, $f) ?>">السابق</a>
      </li>

      <?php
        // عرض 7 أزرار حول الصفحة الحالية
        $start = max(1, $current - 3);
        $end   = min($total, $current + 3);
      ?>

      <?php if ($start > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= pageUrl(1, $f) ?>">1</a></li>
        <?php if ($start > 2): ?>
          <li class="page-item disabled"><span class="page-link">…</span></li>
        <?php endif; ?>
      <?php endif; ?>

      <?php for ($i = $start; $i <= $end; $i++): ?>
        <li class="page-item <?= $i === $current ? 'active' : '' ?>">
          <a class="page-link" href="<?= pageUrl($i, $f) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>

      <?php if ($end < $total): ?>
        <?php if ($end < $total - 1): ?>
          <li class="page-item disabled"><span class="page-link">…</span></li>
        <?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= pageUrl($total, $f) ?>"><?= $total ?></a></li>
      <?php endif; ?>

      <li class="page-item <?= $current >= $total ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= $current >= $total ? '#' : pageUrl($current + 1, $f) ?>">التالي</a>
      </li>
    </ul>
  </nav>

  <div class="text-center text-muted small">
    عرض <?= (int)$pg['per_page'] ?> لكل صفحة — إجمالي النتائج: <?= (int)$pg['total_rows'] ?>
  </div>
<?php endif; ?>

        </div>
    </div>

</div>


<!-- Movements Modal -->
<div class="modal fade" id="movesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="movesModalTitle">سجل حركة القطعة</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div id="movesAlert" class="alert alert-danger d-none"></div>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th class="text-nowrap">الوقت</th>
                <th class="text-nowrap">الحركة</th>
                <th class="text-nowrap">الموقع</th>
                <th class="text-nowrap">المستخدم</th>
                <th>ملاحظة</th>
              </tr>
            </thead>
            <tbody id="movesTbody">
              <tr><td colspan="5" class="text-center text-muted py-4">...</td></tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
(function () {
  function esc(s) {
    return String(s ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  const modalEl = document.getElementById('movesModal');
  const titleEl = document.getElementById('movesModalTitle');
  const tbodyEl = document.getElementById('movesTbody');
  const alertEl = document.getElementById('movesAlert');

  if (!modalEl || !titleEl || !tbodyEl || !alertEl) return;

  const bsModal = (window.bootstrap && bootstrap.Modal)
    ? new bootstrap.Modal(modalEl)
    : null;

  function showError(msg) {
    alertEl.textContent = msg;
    alertEl.classList.remove('d-none');
  }

  function hideError() {
    alertEl.classList.add('d-none');
    alertEl.textContent = '';
  }

  function setLoading() {
    tbodyEl.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">جارٍ التحميل...</td></tr>';
  }

  function renderRows(rows) {
    if (!rows || !rows.length) {
      tbodyEl.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">لا توجد حركات</td></tr>';
      return;
    }

    let html = '';
    for (const r of rows) {
      const delta = Number(r.delta || 0);
      const moveText = delta > 0 ? ('توريد +' + delta) : ('صرف ' + delta); // delta سالب
      html += `
        <tr>
          <td class="text-nowrap">${esc(r.time)}</td>
          <td class="text-nowrap">${esc(moveText)}</td>
          <td class="text-nowrap">${esc(r.location || 'غير محدد')}</td>
          <td class="text-nowrap">${esc(r.user || 'غير معروف')}</td>
          <td>${esc(r.note || '')}</td>
        </tr>
      `;
    }
    tbodyEl.innerHTML = html;
  }

  async function loadMoves(partId, partName, partPn) {
    hideError();
    setLoading();

    // مهم: نفس الراوت اللي في public/index.php
    const url = 'index.php?page=spareparts/movements&id=' + encodeURIComponent(partId) + '&_=' + Date.now();

    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

      const ct = (res.headers.get('content-type') || '').toLowerCase();

      // لو رجع HTML بدل JSON، اعرض أول جزء للتشخيص
      if (!ct.includes('application/json')) {
        const text = await res.text();
        showError('الرد ليس JSON (غالباً تحويل/تحذير). أول جزء من الرد:\n' + text.slice(0, 200));
        renderRows([]);
        return;
      }

      const data = await res.json();

      if (!data || data.ok !== true) {
        showError((data && data.message) ? data.message : 'فشل جلب السجل');
        renderRows([]);
        return;
      }

      const p = data.part || {};
      const title = `سجل حركة القطعة: ${p.name || partName || ''}` + (p.pn || partPn ? ` (PN: ${p.pn || partPn})` : '');
      titleEl.textContent = title;

      renderRows(data.rows || []);
    } catch (e) {
      showError('خطأ أثناء جلب السجل: ' + (e && e.message ? e.message : e));
      renderRows([]);
    }
  }

  // Event delegation عشان يشتغل مع أي زر داخل الجدول
  document.addEventListener('click', function (ev) {
    const btn = ev.target.closest('.btn-moves');
    if (!btn) return;

    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name') || '';
    const pn = btn.getAttribute('data-pn') || '';

    if (!id) return;

    titleEl.textContent = 'سجل حركة القطعة';
    hideError();
    setLoading();

    if (bsModal) bsModal.show();

    loadMoves(id, name, pn);
  });
})();
</script>

<script>
(function () {
  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
  }

  const modalEl = document.getElementById('movesModal');
  const titleEl = document.getElementById('movesModalTitle');
  const tbodyEl = document.getElementById('movesTbody');
  const alertEl = document.getElementById('movesAlert');

  function showError(msg) {
    if (!alertEl) return;
    alertEl.classList.remove('d-none');
    alertEl.textContent = msg;
  }
  function hideError() {
    if (!alertEl) return;
    alertEl.classList.add('d-none');
    alertEl.textContent = '';
  }
  function setLoading() {
    tbodyEl.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">جاري التحميل...</td></tr>';
  }
  function renderRows(rows) {
    if (!rows || !rows.length) {
      tbodyEl.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">لا توجد حركات</td></tr>';
      return;
    }
    tbodyEl.innerHTML = rows.map(r => `
      <tr>
        <td class="text-nowrap">${esc(r.time)}</td>
        <td class="text-nowrap">${esc(r.move)}</td>
        <td class="text-nowrap">${esc(r.location)}</td>
        <td class="text-nowrap">${esc(r.user)}</td>
        <td>${esc(r.note)}</td>
      </tr>
    `).join('');
  }

  // Bootstrap modal instance (لو موجود)
  let bsModal = null;
  if (modalEl && window.bootstrap && window.bootstrap.Modal) {
    bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
  }

  async function loadMoves(id, name, pn) {
    hideError();
    setLoading();

    const url = `index.php?page=spareparts/movements&id=${encodeURIComponent(id)}`;

    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

    // اقرأ كنص أولاً عشان لو رجع HTML نوريه كخطأ واضح
    const text = await res.text();

    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      throw new Error('الرد ليس JSON (غالباً الراوت رجّع صفحة HTML):\n' + text.slice(0, 200));
    }

    if (!res.ok || !data.ok) {
      throw new Error(data.message || 'فشل تحميل السجل');
    }

    const partName = data.part?.name || name || '';
    const partPn   = data.part?.pn   || pn   || '';

    titleEl.textContent = `سجل حركة القطعة: ${partName}${partPn ? ' (PN: ' + partPn + ')' : ''}`;
    renderRows(data.rows || []);
  }

  // event delegation
  document.addEventListener('click', async function (ev) {
    const btn = ev.target.closest('.btn-moves');
    if (!btn) return;

    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name') || '';
    const pn = btn.getAttribute('data-pn') || '';

    if (!id) return;

    titleEl.textContent = 'سجل حركة القطعة';
    hideError();
    setLoading();

    if (bsModal) bsModal.show();

    try {
      await loadMoves(id, name, pn);
    } catch (err) {
      showError(err && err.message ? err.message : String(err));
      renderRows([]);
    }
  });
})();
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
