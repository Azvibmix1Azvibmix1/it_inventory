<?php
$canLocations = function_exists('canAccessLocationsModule') ? canAccessLocationsModule() : false;
$logged       = function_exists('isLoggedIn') ? isLoggedIn() : false;

function pageKey(): string {
  $p = strtolower(trim((string)($_GET['page'] ?? 'dashboard/index')));
  return $p ?: 'dashboard/index';
}
function isActive(string $prefix): bool {
  return strpos(pageKey(), strtolower($prefix)) === 0;
}
function activeClass(string $prefix): string {
  return isActive($prefix) ? ' active' : '';
}

$userName  = $_SESSION['user_name']  ?? ($_SESSION['user_email'] ?? 'حسابي');
$userEmail = $_SESSION['user_email'] ?? '';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Font Awesome (للصفحات القديمة) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- خط عربي -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">

  <style>
    /* =========================
       Shades of Gray (مثل الصورة)
       ========================= */
    :root{
      --black-100:#0A0E15;
      --black-90:#212631;
      --black-80:#373F4E;
      --black-70:#4E576A;
      --black-60:#667085;

      --white-100:#FFFFFF;
      --white-90:#F0F1F5;
      --white-80:#E0E4EB;
      --white-70:#D1D6E0;
      --white-60:#BFC6D4;

      /* Tokens (Light) */
      --bg:        var(--white-90);
      --card:      var(--white-100);
      --rail:      var(--white-100);
      --panel:     rgba(255,255,255,.86);
      --topbar:    rgba(240,241,245,.82);

      --text:      var(--black-100);
      --muted:     var(--black-70);
      --icon:      var(--black-70);
      --border:    var(--white-80);

      --active-bg: rgba(10,14,21,.08);
      --hover-bg:  rgba(10,14,21,.04);

      --shadow:  0 18px 40px rgba(10,14,21,.10);
      --shadow2: 0 10px 22px rgba(10,14,21,.08);

      --radius: 16px;

      --rail-w: 68px;
      --panel-w: 292px;
      --sb-offset: calc(var(--rail-w) + var(--panel-w));

    }

    body.theme-dark{
      /* Tokens (Dark) */
      --bg:        var(--black-100);
      --card:      var(--black-90);
      --rail:      rgba(33,38,49,.92);
      --panel:     rgba(33,38,49,.78);
      --topbar:    rgba(10,14,21,.70);

      --text:      var(--white-100);
      --muted:     var(--white-80);
      --icon:      var(--white-80);
      --border:    rgba(224,228,235,.16);

      --active-bg: rgba(255,255,255,.10);
      --hover-bg:  rgba(255,255,255,.06);
    }

    html, body{ height:100%; }
    body{
      min-height:100vh;
      background: var(--bg);
      color: var(--text);
      font-family:"Cairo", sans-serif;
      overflow-x:hidden;
    }

    /* ====== Shell ====== */
    .app-shell{ min-height:100vh; }
    .app-rail, .app-panel{
      position: fixed;
      top:0; bottom:0;
      z-index: 1040;
    }

    /* ====== Rail (يمين) ====== */
    .app-rail{
      right:0;
      width: var(--rail-w);
      background: var(--rail);
      border-left: 1px solid var(--border);
      display:flex;
      flex-direction:column;
      align-items:center;
      padding: 14px 10px;
      gap: 12px;
      transition: transform .18s ease;

    }

    .rail-logo{
      width: 44px; height:44px;
      border-radius: 14px;
      display:flex; align-items:center; justify-content:center;
      background: linear-gradient(135deg, rgba(10,14,21,.06), rgba(10,14,21,.02));
      border: 1px solid rgba(10,14,21,.08);
      box-shadow: var(--shadow2);
      color: var(--text);
      font-weight: 900;
      letter-spacing:.5px;
    }
    body.theme-dark .rail-logo{
      background: linear-gradient(135deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
      border: 1px solid rgba(255,255,255,.14);
    }

    .rail-btn{
      width: 44px; height:44px;
      border-radius: 14px;
      border: 1px solid transparent;
      background: transparent;
      display:flex; align-items:center; justify-content:center;
      color: var(--icon);
      transition: background .15s ease, transform .08s ease, border-color .15s ease, color .15s ease;
      cursor:pointer;
    }
    .rail-btn:hover{
      background: var(--hover-bg);
      border-color: rgba(10,14,21,.06);
    }
    body.theme-dark .rail-btn:hover{
      border-color: rgba(255,255,255,.12);
    }
    .rail-btn:active{ transform: translateY(1px); }
    .rail-btn.active{
      background: var(--active-bg);
      border-color: rgba(10,14,21,.16);
      color: var(--text);
    }
    body.theme-dark .rail-btn.active{
      border-color: rgba(255,255,255,.18);
    }
    .rail-spacer{ flex:1; }

    /* ====== Panel ====== */
    .app-panel{
      right: var(--rail-w);
      width: var(--panel-w);
      background: var(--panel);
      backdrop-filter: blur(10px);
      border-left: 1px solid var(--border);
      padding: 14px 14px;
      display:flex;
      flex-direction:column;
      gap: 12px;
      transition: transform .18s ease;
    }

    .panel-head{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      padding: 4px 6px;
    }
    .brand{
      display:flex; align-items:center; gap:10px;
      min-width:0;
    }
    .brand .dot{
      width: 34px; height:34px;
      border-radius: 12px;
      background: linear-gradient(135deg, rgba(10,14,21,.08), rgba(10,14,21,.03));
      border: 1px solid rgba(10,14,21,.10);
      display:flex; align-items:center; justify-content:center;
      color: var(--text);
    }
    body.theme-dark .brand .dot{
      background: linear-gradient(135deg, rgba(255,255,255,.12), rgba(255,255,255,.05));
      border: 1px solid rgba(255,255,255,.14);
    }

    .brand .txt{ min-width:0; }
    .brand .txt .t1{ font-weight:900; line-height:1.1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .brand .txt .t2{ font-size:12px; color: var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

    .panel-search{
      border: 1px solid var(--border);
      background: var(--card);
      border-radius: 14px;
      padding: 10px 12px;
      display:flex;
      align-items:center;
      gap:10px;
      box-shadow: var(--shadow2);
    }
    .panel-search i{ color: var(--muted); }
    .panel-search input{
      border:0; outline:0;
      width:100%;
      background: transparent;
      font-weight:700;
      color: var(--text);
    }
    .panel-search input::placeholder{
      color: rgba(78,87,106,.85);
    }
    body.theme-dark .panel-search input::placeholder{
      color: rgba(224,228,235,.70);
    }

    .panel-sec-title{
      font-size: 12px;
      color: var(--muted);
      font-weight: 800;
      padding: 6px 6px 0;
    }

    .menu{
      display:flex;
      flex-direction:column;
      gap:6px;
    }
    .menu a{
      text-decoration:none;
      color: var(--text);
      border: 1px solid transparent;
      border-radius: 14px;
      padding: 10px 12px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      font-weight: 800;
      background: transparent;
      transition: background .15s ease, border-color .15s ease, transform .08s ease;
    }
    .menu a .l{
      display:flex; align-items:center; gap:10px; min-width:0;
    }
    .menu a .l i{ color: var(--icon); }
    .menu a .l span{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .menu a:hover{
      background: var(--hover-bg);
      border-color: rgba(10,14,21,.06);
    }
    body.theme-dark .menu a:hover{
      border-color: rgba(255,255,255,.12);
    }
    .menu a:active{ transform: translateY(1px); }

    .menu a.active{
      background: var(--active-bg);
      border-color: rgba(10,14,21,.16);
    }
    body.theme-dark .menu a.active{
      border-color: rgba(255,255,255,.18);
    }

    /* ====== Panel footer user ====== */
    .panel-footer{
      margin-top:auto;
      border-top: 1px solid rgba(209,214,224,.65);
      padding-top: 12px;
      display:flex;
      flex-direction:column;
      gap:10px;
    }
    body.theme-dark .panel-footer{
      border-top: 1px solid rgba(224,228,235,.16);
    }

    .user-card{
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 10px 10px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      box-shadow: var(--shadow2);
    }
    .user-card .u{
      display:flex; align-items:center; gap:10px; min-width:0;
    }
    .user-card .avatar{
      width: 40px; height:40px;
      border-radius: 14px;
      background: rgba(10,14,21,.06);
      border: 1px solid rgba(10,14,21,.10);
      display:flex; align-items:center; justify-content:center;
      color: var(--text);
      flex: 0 0 auto;
    }
    body.theme-dark .user-card .avatar{
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.14);
    }

    .user-card .meta{ min-width:0; }
    .user-card .meta .n{
      font-weight: 900;
      line-height: 1.1;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .user-card .meta .e{
      font-size: 12px;
      color: var(--muted);
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }

    /* ====== Content wrap ====== */
    .app-content{
  min-height:100vh;
  margin-right: var(--sb-offset);
  display:flex;
  flex-direction:column;
}

    .app-topbar{
      position: sticky;
      top:0;
      z-index: 1030;
      background: var(--topbar);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(209,214,224,.65);
      padding: 10px 14px;
    }
    body.theme-dark .app-topbar{
      border-bottom: 1px solid rgba(224,228,235,.16);
    }

    .topbar-inner{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
    }

    .topbar-left{
      display:flex;
      align-items:center;
      gap:10px;
      min-width:0;
    }

    .toggle-panel{
      border: 1px solid var(--border);
      background: var(--card);
      border-radius: 14px;
      width: 44px; height:44px;
      display:flex; align-items:center; justify-content:center;
      box-shadow: var(--shadow2);
      color: var(--text);
    }

    /* ====== Collapsed state (Desktop) ====== */
   /* ====== Collapsed state (Desktop) - إغلاق كامل ====== */
   /* ====== Collapsed state (Desktop) - إغلاق كامل بدون بقايا ====== */
body.sb-collapsed{
  --sb-offset: 0;
}
body.sb-collapsed .app-panel,
body.sb-collapsed .app-rail{
  transform: translateX(120%);
  opacity: 0;
  pointer-events: none;
}



    /* ====== Overlay (Mobile/Tablet) ====== */
    .backdrop{
      position: fixed;
      inset:0;
      background: rgba(10,14,21,.45);
      z-index: 1038;
      opacity:0;
      pointer-events:none;
      transition: opacity .15s ease;
    }
    body.sb-open .backdrop{
      opacity:1;
      pointer-events:auto;
    }

    @media (max-width: 992px){
        :root{ --sb-offset: 0; }
      .app-rail{ display:none; }
      .app-panel{
        right:0;
        width: min(92vw, 360px);
        transform: translateX(110%);
        box-shadow: var(--shadow);
      }
      body.sb-open .app-panel{
        transform: translateX(0);
      }
      .app-content{ margin-right: 0; }
      body.sb-collapsed .app-content{ margin-right: 0; }
      .app-topbar{ padding: 10px 12px; }
    }

    /* ===== Fix RTL date inputs ===== */
    input[type="date"], input[type="datetime-local"], input[type="month"], input[type="time"]{
      direction:ltr;
      unicode-bidi: plaintext;
      text-align:right;
    }

    /* =========================
   Page UI (Cards + Tables + Filters)
   ========================= */

.page-wrap{
  max-width: 1200px;
  margin: 0 auto;
}

.page-head{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  margin-bottom: 14px;
}
.page-title{
  font-weight: 900;
  margin:0;
  font-size: 22px;
  letter-spacing: .2px;
}
.page-sub{
  margin-top: 4px;
  color: var(--muted);
  font-weight: 700;
  font-size: 13px;
}
.page-actions{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
}

/* Cards */
.cardx{
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow2);
}
.cardx-body{ padding: 14px; }
.cardx-title{
  font-weight: 900;
  margin:0 0 10px 0;
  font-size: 14px;
}
.cardx-muted{ color: var(--muted); font-weight: 700; font-size: 12px; }

/* Inputs */
.input-soft, .select-soft{
  border-radius: 14px !important;
  border: 1px solid var(--border) !important;
  background: transparent !important;
  color: var(--text) !important;
  font-weight: 800 !important;
  height: 44px;
}
.input-soft::placeholder{ color: rgba(78,87,106,.75); font-weight: 700; }
body.theme-dark .input-soft::placeholder{ color: rgba(224,228,235,.55); }

.btn-soft{
  border-radius: 14px !important;
  height: 44px;
  font-weight: 900 !important;
}

/* Filters row */
.filters{
  display:grid;
  grid-template-columns: 1.2fr .8fr .8fr .8fr auto;
  gap:10px;
}
@media (max-width: 1200px){
  .filters{ grid-template-columns: 1fr 1fr 1fr; }
}
@media (max-width: 768px){
  .filters{ grid-template-columns: 1fr; }
}

/* Table */
.tablex{
  margin:0;
  color: var(--text);
}
.tablex thead th{
  font-size: 12px;
  color: var(--muted);
  font-weight: 900;
  border-bottom: 1px solid var(--border) !important;
  background: transparent;
  padding: 12px 12px;
  white-space: nowrap;
}
.tablex tbody td{
  border-top: 1px solid var(--border) !important;
  padding: 12px 12px;
  vertical-align: middle;
  font-weight: 800;
}
.tablex tbody tr:hover{
  background: var(--hover-bg);
}
.tablex .td-muted{ color: var(--muted); font-weight: 800; }

/* Badges */
.badgex{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid var(--border);
  font-size: 12px;
  font-weight: 900;
  background: transparent;
  color: var(--text);
  white-space: nowrap;
}
.badgex i{ font-size: 12px; opacity: .85; }

/* status colors (Gray-only but still distinct) */
.badgex.open     { background: rgba(10,14,21,.06); }
.badgex.pending  { background: rgba(10,14,21,.10); }
.badgex.closed   { background: rgba(10,14,21,.14); }

body.theme-dark .badgex.open    { background: rgba(255,255,255,.06); }
body.theme-dark .badgex.pending { background: rgba(255,255,255,.10); }
body.theme-dark .badgex.closed  { background: rgba(255,255,255,.14); }

/* Action buttons inside table */
.icon-btn{
  width: 40px;
  height: 40px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: transparent;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  color: var(--text);
  text-decoration:none;
}
.icon-btn:hover{ background: var(--hover-bg); }

/* ===== Page Layout (Unified) ===== */
.page-wrap{
  max-width: 1200px;
  margin: 0 auto;
}

.page-head{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  margin: 10px 0 14px;
}
.page-title{
  font-weight: 900;
  margin:0;
  font-size: 22px;
}
.page-sub{
  margin-top: 4px;
  color: var(--muted);
  font-weight: 800;
  font-size: 13px;
}
.page-actions{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
}

/* ===== Cards ===== */
.cardx{
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow2);
}
.cardx-body{ padding: 14px; }
.cardx-title{
  font-weight: 900;
  margin:0 0 10px 0;
  font-size: 14px;
  color: var(--text);
}
.cardx-muted{ color: var(--muted); font-weight: 800; font-size: 12px; }

/* ===== Buttons / Inputs ===== */
.btn-soft{
  border-radius: 14px !important;
  height: 44px;
  font-weight: 900 !important;
}
.input-soft, .select-soft{
  border-radius: 14px !important;
  border: 1px solid var(--border) !important;
  background: transparent !important;
  color: var(--text) !important;
  font-weight: 800 !important;
  height: 44px;
}
.input-soft::placeholder{ color: rgba(78,87,106,.75); font-weight: 700; }
body.theme-dark .input-soft::placeholder{ color: rgba(224,228,235,.55); }


  </style>
</head>

<body>

  <div class="backdrop" id="sbBackdrop"></div>

  <div class="app-shell">

    <!-- Rail (أيقونات) -->
    <aside class="app-rail" aria-label="Sidebar rail">
      <div class="rail-logo" title="IT Inventory">UJ</div>

      <button class="rail-btn<?= activeClass('dashboard') ?>" title="لوحة التحكم" onclick="location.href='index.php?page=dashboard/index'">
        <i class="bi bi-grid"></i>
      </button>

      <button class="rail-btn<?= activeClass('assets') ?>" title="الأصول/الأجهزة" onclick="location.href='index.php?page=assets/index'">
        <i class="bi bi-pc-display"></i>
      </button>

      <button class="rail-btn<?= activeClass('spareparts') ?>" title="قطع الغيار" onclick="location.href='index.php?page=spareparts/index'">
        <i class="bi bi-tools"></i>
      </button>

      <button class="rail-btn<?= activeClass('tickets') ?>" title="التذاكر" onclick="location.href='index.php?page=tickets/index'">
        <i class="bi bi-ticket-perforated"></i>
      </button>

      <?php if ($canLocations): ?>
        <button class="rail-btn<?= activeClass('locations') ?>" title="المواقع" onclick="location.href='index.php?page=locations/index'">
          <i class="bi bi-geo-alt"></i>
        </button>
      <?php endif; ?>

      <?php if ($logged && function_exists('isSuperAdmin') && function_exists('isManager') && (isSuperAdmin() || isManager())): ?>
        <button class="rail-btn<?= activeClass('users') ?>" title="الموظفين" onclick="location.href='index.php?page=users/index'">
          <i class="bi bi-people"></i>
        </button>
      <?php endif; ?>

      <div class="rail-spacer"></div>

      <button class="rail-btn" id="railThemeBtn" title="الوضع الليلي">
        <i class="bi bi-moon-stars"></i>
      </button>

      <?php if ($logged): ?>
        <button class="rail-btn" title="ملفي الشخصي" onclick="location.href='index.php?page=users/profile'">
          <i class="bi bi-person-circle"></i>
        </button>
      <?php else: ?>
        <button class="rail-btn" title="دخول" onclick="location.href='index.php?page=login'">
          <i class="bi bi-box-arrow-in-left"></i>
        </button>
      <?php endif; ?>
    </aside>

    <!-- Panel (القائمة) -->
    <aside class="app-panel" aria-label="Sidebar panel" id="sbPanel">
      <div class="panel-head">
        <div class="brand">
          <div class="dot"><i class="bi bi-snow"></i></div>
          <div class="txt">
            <div class="t1"><?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?></div>
            <div class="t2">جامعة جدة</div>
          </div>
        </div>

        <button class="btn btn-sm btn-light border" id="panelCloseBtn" title="إغلاق" style="border-radius:12px;">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="panel-search">
        <i class="bi bi-search"></i>
        <input id="menuSearch" type="text" placeholder="بحث سريع..." autocomplete="off">
      </div>

      <div class="panel-sec-title">القائمة</div>
      <div class="menu" id="menuList">
        <a class="<?= activeClass('dashboard') ?>" href="index.php?page=dashboard/index" data-label="لوحة التحكم داشبورد">
          <div class="l"><i class="bi bi-grid"></i><span>لوحة التحكم</span></div>
        </a>

        <a class="<?= activeClass('assets') ?>" href="index.php?page=assets/index" data-label="الأصول الأجهزة العهد">
          <div class="l"><i class="bi bi-pc-display"></i><span>الأصول / الأجهزة</span></div>
        </a>

        <a class="<?= activeClass('spareparts') ?>" href="index.php?page=spareparts/index" data-label="قطع الغيار مخزون">
          <div class="l"><i class="bi bi-tools"></i><span>قطع الغيار</span></div>
        </a>

        <a class="<?= activeClass('tickets') ?>" href="index.php?page=tickets/index" data-label="التذاكر الدعم">
          <div class="l"><i class="bi bi-ticket-perforated"></i><span>التذاكر</span></div>
        </a>

        <?php if ($canLocations): ?>
          <a class="<?= activeClass('locations') ?>" href="index.php?page=locations/index" data-label="المواقع المعامل القاعات">
            <div class="l"><i class="bi bi-geo-alt"></i><span>المواقع</span></div>
          </a>
        <?php endif; ?>

        <?php if ($logged && function_exists('isSuperAdmin') && function_exists('isManager') && (isSuperAdmin() || isManager())): ?>
          <a class="<?= activeClass('users') ?>" href="index.php?page=users/index" data-label="الموظفين المستخدمين">
            <div class="l"><i class="bi bi-people"></i><span>الموظفين</span></div>
          </a>
        <?php endif; ?>
      </div>

      <div class="panel-sec-title">التفضيلات</div>
      <div class="menu">
        <a href="index.php?page=users/profile" data-label="الملف الشخصي">
          <div class="l"><i class="bi bi-gear"></i><span>الإعدادات</span></div>
        </a>
        <a href="#" id="themeToggle" data-label="الوضع الليلي">
          <div class="l"><i class="bi bi-moon-stars"></i><span>الوضع الليلي</span></div>
        </a>
        <a href="#" onclick="alert('قريباً'); return false;" data-label="مساعدة">
          <div class="l"><i class="bi bi-question-circle"></i><span>مساعدة</span></div>
        </a>
      </div>

      <div class="panel-footer">
        <?php if ($logged): ?>
          <div class="user-card">
            <div class="u">
              <div class="avatar"><i class="bi bi-person"></i></div>
              <div class="meta">
                <div class="n"><?php echo htmlspecialchars($userName); ?></div>
                <div class="e"><?php echo htmlspecialchars($userEmail); ?></div>
              </div>
            </div>

            <div class="dropdown">
              <button class="btn btn-sm btn-light border" style="border-radius:12px;" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-chevron-down"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-start">
                <li>
                  <a class="dropdown-item" href="index.php?page=users/profile">
                    <i class="bi bi-person me-2"></i> ملفي الشخصي
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item text-danger" href="index.php?page=logout">
                    <i class="bi bi-box-arrow-right me-2"></i> تسجيل خروج
                  </a>
                </li>
              </ul>
            </div>
          </div>
        <?php else: ?>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-dark w-50" href="index.php?page=login">دخول</a>
            <a class="btn btn-dark w-50" href="index.php?page=register">تسجيل</a>
          </div>
        <?php endif; ?>
      </div>
    </aside>

    <!-- Content -->
    <div class="app-content">
      <div class="app-topbar">
        <div class="topbar-inner">
          <div class="topbar-left">
            <button class="toggle-panel" id="panelToggleBtn" type="button" title="القائمة">
              <i class="bi bi-layout-sidebar-inset"></i>
            </button>

            <div class="d-none d-md-block">
              <div style="font-weight:900; line-height:1.1;">
                <?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?>
              </div>
              <div style="font-size:12px; color: var(--muted);">واجهة موحّدة + Sidebar جديد</div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <?php if ($logged): ?>
              <a class="btn btn-light border" style="border-radius:14px;" href="index.php?page=dashboard/index" title="الرجوع للوحة التحكم">
                <i class="bi bi-house"></i>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- المحتوى الرئيسي -->
      <main class="site-content container-fluid py-3">
