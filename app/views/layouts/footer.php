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
    // ===== Sidebar open/close (mobile) + collapse (desktop) =====
    (function(){
      const body = document.body;
      const panelToggleBtn = document.getElementById('panelToggleBtn');
      const panelCloseBtn  = document.getElementById('panelCloseBtn');
      const backdrop       = document.getElementById('sbBackdrop');

      function isMobile(){
        return window.matchMedia('(max-width: 991px)').matches;
      }

      function openPanel(){
        body.classList.add('sb-open');
      }
      function closePanel(){
        body.classList.remove('sb-open');
      }

      if(panelToggleBtn){
        panelToggleBtn.addEventListener('click', function(e){
          e.preventDefault();
          if(isMobile()){
            openPanel();
          }else{
            body.classList.toggle('sb-collapsed');
            localStorage.setItem('uj_sb_collapsed', body.classList.contains('sb-collapsed') ? '1' : '0');
          }
        });
      }

      if(panelCloseBtn){
        panelCloseBtn.addEventListener('click', function(e){
          e.preventDefault();
          closePanel();
        });
      }

      if(backdrop){
        backdrop.addEventListener('click', function(){
          closePanel();
        });
      }

      // restore collapsed state (desktop)
      try{
        const saved = localStorage.getItem('uj_sb_collapsed');
        if(saved === '1' && !isMobile()){
          body.classList.add('sb-collapsed');
        }
      }catch(e){}

      // responsive reset
      window.addEventListener('resize', function(){
        if(isMobile()){
          body.classList.remove('sb-collapsed');
        }else{
          body.classList.remove('sb-open');
          const saved = localStorage.getItem('uj_sb_collapsed');
          if(saved === '1') body.classList.add('sb-collapsed');
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

      function setTheme(mode){
        if(mode === 'dark'){
          body.classList.add('theme-dark');
        }else{
          body.classList.remove('theme-dark');
        }
        try{ localStorage.setItem(key, mode); }catch(e){}
      }

      function getTheme(){
        try{ return localStorage.getItem(key); }catch(e){}
        return null;
      }

      const saved = getTheme();
      if(saved){
        setTheme(saved);
      }

      const btn  = document.getElementById('themeToggle');
      const rail = document.getElementById('railThemeToggle');

      function toggle(e){
        if(e) e.preventDefault();
        const isDark = body.classList.contains('theme-dark');
        setTheme(isDark ? 'light' : 'dark');
      }

      if(btn)  btn.addEventListener('click', toggle);
      if(rail) rail.addEventListener('click', toggle);
    })();

    // ===== Global UX: loading state on submit =====
    document.addEventListener('submit', function(e){
      const form = e.target;
      if(!form) return;

      const btn = form.querySelector('.js-loading-on-submit');
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
