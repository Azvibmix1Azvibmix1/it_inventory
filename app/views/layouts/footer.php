<?php
// footer.php (Layouts)
?>

<footer class="text-center text-muted py-3" style="font-weight:800;">
  <small>Version 1.0</small>
  <div>© جامعة جدة - نظام إدارة العهد. جميع الحقوق محفوظة.</div>
</footer>

<style>
  /* ===== FORCE Sidebar close/open (works even if header.php missing rules) ===== */

  /* panel selectors (حسب اختلاف هيكل مشروعك) */
  :root{
    --uj-rail-w: 72px;     /* عرض الشريط الصغير */
  }

  /* عند الإغلاق على الديسكتوب: اخفِ اللوحة بالكامل */
  body.sb-collapsed #ujSidePanel,
  body.sb-collapsed .app-panel,
  body.sb-collapsed .uj-panel,
  body.sb-collapsed .sidebar-panel{
    transform: translateX(120%) !important;
    opacity: 0 !important;
    pointer-events: none !important;
  }

  /* عند الفتح (خصوصًا للجوال): رجّع اللوحة */
  body.sb-open #ujSidePanel,
  body.sb-open .app-panel,
  body.sb-open .uj-panel,
  body.sb-open .sidebar-panel{
    transform: translateX(0) !important;
    opacity: 1 !important;
    pointer-events: auto !important;
  }

  /* backdrop */
  body.sb-collapsed #sbBackdrop,
  body.sb-collapsed .backdrop{
    opacity: 0 !important;
    pointer-events: none !important;
  }

  /* على الجوال: افتراضيًا مقفل إلا إذا sb-open */
  @media (max-width: 991px){
    body:not(.sb-open) #ujSidePanel,
    body:not(.sb-open) .app-panel,
    body:not(.sb-open) .uj-panel,
    body:not(.sb-open) .sidebar-panel{
      transform: translateX(120%) !important;
      opacity: 0 !important;
      pointer-events: none !important;
    }
  }

  /* انتقالات ناعمة */
  #ujSidePanel, .app-panel, .uj-panel, .sidebar-panel{
    transition: transform .18s ease, opacity .18s ease;
  }
  #sbBackdrop, .backdrop{
    transition: opacity .18s ease;
  }
</style>


<script>
(function () {
  const body = document.body;

  // عناصر محتملة بأكثر من اسم
  const panel =
    document.getElementById('ujSidePanel') ||
    document.querySelector('.app-panel') ||
    document.querySelector('.uj-panel') ||
    document.querySelector('.sidebar-panel') ||
    null;

  const backdrop =
    document.getElementById('sbBackdrop') ||
    document.querySelector('.backdrop') ||
    null;

  // زر القائمة (☰)
  const panelToggleBtn =
    document.getElementById('panelToggleBtn') ||
    document.getElementById('panelToggle') ||
    document.querySelector('[data-panel-toggle]') ||
    document.querySelector('.js-panel-toggle') ||
    null;

  // زر الإغلاق (X)
  const panelCloseBtn =
    document.getElementById('panelCloseBtn') ||
    document.getElementById('panelClose') ||
    document.querySelector('[data-panel-close]') ||
    document.querySelector('.js-panel-close') ||
    null;

  function isMobile() {
    return window.matchMedia('(max-width: 991px)').matches;
  }

  function openPanel() {
    // افتح للجوال (overlay) + على الديسكتوب فك الكولابس
    body.classList.add('sb-open');
    body.classList.remove('sb-collapsed');
    try { localStorage.setItem('uj_sb_collapsed', '0'); } catch(e) {}
  }

  function closePanel() {
    // ✅ دايمًا شيل sb-open (حتى لو ديسكتوب)
    body.classList.remove('sb-open');

    if (isMobile()) {
      // الجوال خلاص قفلناه
      return;
    }

    // الديسكتوب: كولابس كامل
    body.classList.add('sb-collapsed');
    try { localStorage.setItem('uj_sb_collapsed', '1'); } catch(e) {}
  }

  function toggleCollapsedDesktop() {
    body.classList.remove('sb-open');
    body.classList.toggle('sb-collapsed');
    try {
      localStorage.setItem('uj_sb_collapsed', body.classList.contains('sb-collapsed') ? '1' : '0');
    } catch (e) {}
  }

  function syncStateOnResize() {
    if (isMobile()) {
      // على الجوال لا نستخدم collapsed
      body.classList.remove('sb-collapsed');
      // اترك sb-open حسب المستخدم
    } else {
      // على الديسكتوب لا نستخدم overlay
      body.classList.remove('sb-open');
      // استرجاع collapsed من التخزين
      try {
        const saved = localStorage.getItem('uj_sb_collapsed');
        if (saved === '1') body.classList.add('sb-collapsed');
      } catch (e) {}
    }
  }

  // init
  syncStateOnResize();
  window.addEventListener('resize', syncStateOnResize);

  // ☰ toggle
  if (panelToggleBtn) {
    panelToggleBtn.addEventListener('click', function (e) {
      e.preventDefault();
      if (isMobile()) {
        body.classList.toggle('sb-open'); // فتح/إغلاق للجوال
      } else {
        toggleCollapsedDesktop();         // collapse للديسكتوب
      }
    });
  }

  // X close
  if (panelCloseBtn) {
    panelCloseBtn.addEventListener('click', function (e) {
      e.preventDefault();
      closePanel();
    });
  }

  // backdrop click
  if (backdrop) {
    backdrop.addEventListener('click', function () {
      closePanel();
    });
  }

  // ESC يغلق
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closePanel();
    }
  });

  // إذا ضغط رابط داخل الـ panel على الجوال → يقفل
  if (panel) {
    panel.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        if (isMobile()) closePanel();
      });
    });
  }
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
