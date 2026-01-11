      </main>

      <footer class="app-foot py-3 mt-auto" style="border-top:1px solid rgba(209,214,224,.65); background: var(--card);">
        <div class="container-fluid">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted small">Version 1.0</div>
            <div class="text-muted small">© جامعة جدة - نظام إدارة العهد. جميع الحقوق محفوظة.</div>
          </div>
        </div>
      </footer>
    </div><!-- /app-content -->

  </div><!-- /app-shell -->

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // ===== Sidebar toggle (Desktop collapse + Mobile overlay) =====
    (function(){
      const body = document.body;
      const btnToggle = document.getElementById('panelToggleBtn');
      const btnClose  = document.getElementById('panelCloseBtn');
      const backdrop  = document.getElementById('sbBackdrop');

      function isMobile(){
        return window.matchMedia('(max-width: 992px)').matches;
      }

      function openMobile(){
        body.classList.add('sb-open');
      }
      function closeMobile(){
        body.classList.remove('sb-open');
      }

      function toggleDesktop(){
          body.classList.remove('sb-open'); // مهم
        body.classList.toggle('sb-collapsed');
      }

      btnToggle?.addEventListener('click', function(){
        if(isMobile()) openMobile();
        else toggleDesktop();
      });

      btnClose?.addEventListener('click', function(){
  if(isMobile()){
    closeMobile();
  }else{
    body.classList.add('sb-collapsed');
  }
});


      backdrop?.addEventListener('click', closeMobile);

      // default behavior on load
      // default behavior on load
if(isMobile()){
  body.classList.remove('sb-collapsed');
}else{
  body.classList.remove('sb-collapsed'); // يبدأ مفتوح على الكمبيوتر
}


      window.addEventListener('resize', function(){
  if(isMobile()){
    body.classList.remove('sb-collapsed');
    closeMobile();
  }else{
    closeMobile();
    // على الديسكتوب نخليه مفتوح بشكل طبيعي
    body.classList.remove('sb-collapsed');
  }
});

    })();

    // ===== Quick menu search =====
    (function(){
      const input = document.getElementById('menuSearch');
      const list  = document.getElementById('menuList');
      if(!input || !list) return;

      input.addEventListener('input', function(){
        const q = (input.value || '').trim().toLowerCase();
        const items = list.querySelectorAll('a[data-label]');
        items.forEach(a=>{
          const label = (a.getAttribute('data-label') || '').toLowerCase();
          a.style.display = (!q || label.includes(q)) ? '' : 'none';
        });
      });
    })();

    // ===== Theme toggle (localStorage) =====
    (function(){
      const body = document.body;
      const key = 'uj_theme';
      const btn1 = document.getElementById('themeToggle');
      const btn2 = document.getElementById('railThemeBtn');

      function apply(v){
        if(v === 'dark') body.classList.add('theme-dark');
        else body.classList.remove('theme-dark');
      }
      function toggle(){
        const isDark = body.classList.contains('theme-dark');
        const next = isDark ? 'light' : 'dark';
        localStorage.setItem(key, next);
        apply(next);
      }
      apply(localStorage.getItem(key) || 'light');

      btn1?.addEventListener('click', function(e){ e.preventDefault(); toggle(); });
      btn2?.addEventListener('click', function(e){ e.preventDefault(); toggle(); });
    })();

    // ===== Submit Loading =====
    document.addEventListener('submit', function(e){
      const form = e.target;
      const btn = form.querySelector('button[type="submit"]');
      if(!btn) return;

      if(btn.dataset.loading === "1") return;
      btn.dataset.loading = "1";

      btn.disabled = true;
      btn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        جاري الحفظ...
      `;
    });
  </script>

</body>
</html>
