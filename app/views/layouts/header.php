<?php

$canLocations = function_exists('canAccessLocationsModule') ? canAccessLocationsModule() : false;
$logged       = function_exists('isLoggedIn') ? isLoggedIn() : false;

if (!function_exists('pageKey')) {
  function pageKey(): string {
    $p = strtolower(trim((string)($_GET['page'] ?? 'dashboard/index')));
    return $p ?: 'dashboard/index';
  }
}

if (!function_exists('isActive')) {
  function isActive(string $prefix): bool {
    return strpos(pageKey(), strtolower($prefix)) === 0;
  }
}

if (!function_exists('activeClass')) {
  function activeClass(string $prefix): string {
    return isActive($prefix) ? ' active' : '';
  }
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
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <style>
<?php
// NOTE: تم تحديث الـ CSS هنا ليطابق الصور (Pill Active + Black/White)
// مع إضافة segmented pills جاهزة لاستخدامها كتبات داخل الصفحات.
?>
<?php /* CSS Start */ ?>
<?php echo preg_replace('/^\h*\v+/m', '', '
    :root{
      /* ===== Palette (inspired by your reference) ===== */
      --black-100: #0A0E15;
      --black-90:  #212631;
      --black-80:  #373F4E;
      --black-70:  #4E576A;
      --black-60:  #667085;

      --white-100: #FFFFFF;
      --white-90:  #F0F1F5;
      --white-80:  #E0E4EB;
      --white-70:  #D1D6E0;
      --white-60:  #BFC6D4;

      /* Tokens (Light) */
      --bg:        #F3F5F8;
      --card:      #FFFFFF;
      --rail:      rgba(255,255,255,.90);
      --panel:     rgba(255,255,255,.82);
      --topbar:    rgba(255,255,255,.72);

      --text:      var(--black-100);
      --muted:     var(--black-60);
      --icon:      var(--black-70);
      --border:    rgba(209,214,224,.65);

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

      --shadow:  0 22px 44px rgba(0,0,0,.42);
      --shadow2: 0 12px 26px rgba(0,0,0,.35);
    }

    html, body{ height:100%; }
    body{
      margin:0;
      font-family: "Cairo", sans-serif;
      background: var(--bg);
      color: var(--text);
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
      backdrop-filter: blur(14px);
      border-left: 1px solid var(--border);
      display:flex;
      flex-direction:column;
      align-items:center;
      padding: 14px 10px;
      gap: 10px;
    }
    body.theme-dark .app-rail{
      border-left: 1px solid rgba(224,228,235,.16);
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
      user-select:none;
    }
    body.theme-dark .rail-logo{
      background: linear-gradient(135deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
      border: 1px solid rgba(255,255,255,.10);
    }

    .rail-btn{
      width: 44px; height:44px;
      border-radius: 14px;
      border: 1px solid transparent;
      background: transparent;
      display:flex; align-items:center; justify-content:center;
      color: var(--icon);
      transition: background .15s ease, transform .08s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
      cursor:pointer;
    }
    .rail-btn:hover{
      background: var(--hover-bg);
      border-color: rgba(10,14,21,.06);
      box-shadow: var(--shadow2);
    }
    body.theme-dark .rail-btn:hover{
      border-color: rgba(255,255,255,.12);
    }
    .rail-btn:active{ transform: translateY(1px); }

    /* ✅ Active = Pill (Black/White) مثل الصور */
    .rail-btn.active{
      background: var(--black-100);
      color: var(--white-100);
      border-color: transparent;
      box-shadow: 0 12px 26px rgba(10,14,21,.18);
    }
    body.theme-dark .rail-btn.active{
      background: var(--white-100);
      color: var(--black-100);
      border-color: transparent;
      box-shadow: 0 12px 26px rgba(0,0,0,.35);
    }

    .rail-spacer{ flex:1; }

    /* ====== Panel ====== */
    .app-panel{
      right: var(--rail-w);
      width: var(--panel-w);
      background: var(--panel);
      backdrop-filter: blur(16px);
      border-left: 1px solid var(--border);
      box-shadow: var(--shadow);
      display:flex;
      flex-direction:column;
      padding: 14px;
      gap: 12px;
      border-top-left-radius: 18px;
      border-bottom-left-radius: 18px;
    }
    body.theme-dark .app-panel{
      border-left: 1px solid rgba(224,228,235,.16);
    }

    .panel-head{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
    }

    .brand{
      display:flex;
      align-items:center;
      gap:10px;
      min-width:0;
    }
    .brand .dot{
      width: 40px; height:40px;
      border-radius: 14px;
      display:flex;
      align-items:center;
      justify-content:center;
      background: var(--card);
      border: 1px solid var(--border);
      box-shadow: var(--shadow2);
      color: var(--text);
      flex:0 0 auto;
    }
    .brand .txt{ min-width:0; }
    .brand .t1{
      font-weight: 900;
      font-size: 14px;
      line-height: 1.2;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .brand .t2{
      font-weight: 800;
      font-size: 12px;
      color: var(--muted);
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }

    .panel-close{
      border: 1px solid var(--border);
      background: var(--card);
      color: var(--text);
      border-radius: 14px;
      width: 40px; height:40px;
      display:flex; align-items:center; justify-content:center;
      box-shadow: var(--shadow2);
    }

    .panel-search{
      border: 1px solid var(--border);
      background: var(--card);
      border-radius: 999px;
      padding: 10px 12px;
      display:flex;
      align-items:center;
      gap:10px;
      box-shadow: var(--shadow2);
    }
    .panel-search i{ color: var(--muted); }
    .panel-search input{
      border:0;
      outline:none;
      background: transparent;
      width: 100%;
      color: var(--text);
      font-weight: 800;
      font-size: 13px;
    }
    .panel-search input::placeholder{ color: var(--muted); font-weight:800; }

    .panel-sec-title{
      font-size: 12px;
      color: var(--muted);
      font-weight: 900;
      padding: 6px 6px 0;
    }

    .menu{
      display:flex;
      flex-direction:column;
      gap:8px;
    }
    .menu a{
      text-decoration:none;
      color: var(--text);
      border: 1px solid transparent;
      border-radius: 16px;
      padding: 10px 12px;
      display:flex;
      align-items:center;
      gap:10px;
      font-weight: 900;
      background: transparent;
      transition: background .15s ease, border-color .15s ease, transform .08s ease, box-shadow .15s ease;
    }
    .menu a .l{
      display:flex; align-items:center; gap:10px; min-width:0;
    }
    .menu a i{ color: var(--icon); }
    .menu a span{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

    .menu a:hover{
      background: var(--hover-bg);
      border-color: rgba(10,14,21,.08);
      box-shadow: var(--shadow2);
    }
    body.theme-dark .menu a:hover{
      border-color: rgba(255,255,255,.10);
    }
    .menu a:active{ transform: translateY(1px); }

    /* ✅ Active = Pill (Black/White) مثل الصور */
    .menu a.active{
      background: var(--black-100);
      color: var(--white-100);
      border-color: transparent;
      box-shadow: 0 14px 30px rgba(10,14,21,.14);
    }
    .menu a.active i{ color: inherit; }
    body.theme-dark .menu a.active{
      background: var(--white-100);
      color: var(--black-100);
      border-color: transparent;
      box-shadow: 0 14px 30px rgba(0,0,0,.35);
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
      border: 1px solid var(--border);
      background: var(--card);
      border-radius: 18px;
      padding: 12px;
      box-shadow: var(--shadow2);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
    }
    .user-mini{
      display:flex;
      align-items:center;
      gap:10px;
      min-width:0;
    }
    .user-avatar{
      width: 40px; height:40px;
      border-radius: 16px;
      background: linear-gradient(135deg, rgba(10,14,21,.06), rgba(10,14,21,.02));
      border: 1px solid rgba(10,14,21,.10);
      display:flex; align-items:center; justify-content:center;
      font-weight: 900;
      color: var(--text);
      flex:0 0 auto;
      user-select:none;
    }
    body.theme-dark .user-avatar{
      background: linear-gradient(135deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
      border: 1px solid rgba(255,255,255,.12);
    }
    .user-info{ min-width:0; }
    .user-info .n{
      font-weight: 900;
      font-size: 13px;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .user-info .e{
      font-weight: 800;
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
      backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--border);
    }

    .topbar-inner{
      padding: 12px 14px;
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
      display:flex;
      align-items:center;
      justify-content:center;
      box-shadow: var(--shadow2);
      color: var(--text);
    }

    .topbar-title{
      min-width:0;
      display:flex;
      flex-direction:column;
      gap:2px;
    }
    .topbar-title .h{
      font-size: 14px;
      font-weight: 900;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .topbar-title .s{
      font-size: 12px;
      color: var(--muted);
      font-weight: 800;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }

    /* Backdrop for mobile */
    .backdrop{
      position: fixed;
      inset:0;
      background: rgba(10,14,21,.20);
      backdrop-filter: blur(2px);
      opacity:0;
      pointer-events:none;
      transition: opacity .15s ease;
      z-index: 1039;
    }
    body.sb-open .backdrop{
      opacity:1;
      pointer-events:auto;
    }
    
    /* ===== Desktop collapse support ===== */
.app-panel{
  transition: transform .18s ease;
}

/* لما نقفل الـ Panel على الديسكتوب */
body.sb-collapsed{
  --sb-offset: var(--rail-w);
}
body.sb-collapsed .app-panel{
  transform: translateX(110%);
}
body.sb-collapsed .backdrop{
  opacity: 0;
  pointer-events: none;
}


    /* Mobile / small screens */
    @media (max-width: 991px){
      :root{ --panel-w: 300px; --sb-offset: var(--rail-w); }

      .app-panel{
        right: var(--rail-w);
        transform: translateX(110%);
        transition: transform .18s ease;
      }
      body.sb-open .app-panel{
        transform: translateX(0);
      }
      .panel-close{ display:flex; }
      .app-content{ margin-right: var(--rail-w); }
    }

    @media (min-width: 992px){
      .panel-close{ display:none; }
      body.sb-collapsed .app-panel{
        transform: translateX(110%);
      }
      body.sb-collapsed .app-content{
        margin-right: var(--rail-w);
      }
    }

    /* ====== Segmented pills (للتبويبات داخل الصفحات) ====== */
    .segmented{
      display:inline-flex;
      gap: 8px;
      padding: 8px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,.65);
      box-shadow: var(--shadow2);
      backdrop-filter: blur(10px);
    }
    body.theme-dark .segmented{
      background: rgba(33,38,49,.55);
    }
    .segmented .seg-item{
      border: 0;
      background: transparent;
      color: var(--muted);
      font-weight: 900;
      padding: 10px 14px;
      border-radius: 999px;
      display:inline-flex;
      align-items:center;
      gap:10px;
      transition: background .15s ease, color .15s ease, transform .08s ease;
      cursor:pointer;
      text-decoration:none;
    }
    .segmented .seg-item:hover{
      background: var(--hover-bg);
      color: var(--text);
    }
    .segmented .seg-item.is-active,
    .segmented .seg-item.active{
      background: var(--black-100);
      color: var(--white-100);
      box-shadow: 0 14px 30px rgba(10,14,21,.14);
    }
    body.theme-dark .segmented .seg-item.is-active,
    body.theme-dark .segmented .seg-item.active{
      background: var(--white-100);
      color: var(--black-100);
      box-shadow: 0 14px 30px rgba(0,0,0,.35);
    }

    /* ===== Soft UI / Gray System (RTL friendly) ===== */
:root{
  --bg: #f3f4f6;
  --surface: #f6f7f9;
  --surface-2: #eef0f3;
  --text: #111827;
  --muted: #6b7280;
  --stroke: rgba(17,24,39,.10);

  --dark: #0b0f14;
  --dark-2:#111827;

  --radius-xl: 18px;
  --radius-lg: 14px;
  --radius-md: 12px;

  --shadow-out: 10px 10px 22px rgba(17,24,39,.12), -10px -10px 22px rgba(255,255,255,.85);
  --shadow-in: inset 6px 6px 14px rgba(17,24,39,.10), inset -6px -6px 14px rgba(255,255,255,.90);
  --shadow-soft: 0 10px 25px rgba(17,24,39,.10);

  --focus: 0 0 0 3px rgba(17,24,39,.12);
}

/* اختياري: وضع داكن للنفس المكونات */
[data-theme="dark"]{
  --bg:#0f141a;
  --surface:#131a22;
  --surface-2:#0f141a;
  --text:#e5e7eb;
  --muted:#94a3b8;
  --stroke: rgba(255,255,255,.10);

  --shadow-out: 10px 10px 22px rgba(0,0,0,.55), -10px -10px 22px rgba(255,255,255,.04);
  --shadow-in: inset 6px 6px 14px rgba(0,0,0,.45), inset -6px -6px 14px rgba(255,255,255,.03);
  --shadow-soft: 0 12px 28px rgba(0,0,0,.45);
}

body{
  background: var(--bg);
  color: var(--text);
}

/* Surface */
.soft-card{
  background: var(--surface);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-out);
  border: 1px solid var(--stroke);
}

/* “غاطس” */
.soft-inset{
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-in);
  border: 1px solid var(--stroke);
}

/* Toolbar */
.soft-toolbar{
  display:flex;
  align-items:center;
  gap:10px;
  padding:12px;
  border-radius: var(--radius-xl);
  background: var(--surface);
  box-shadow: var(--shadow-out);
  border: 1px solid var(--stroke);
}

/* ===== Neumorphism Pills / Segments (مثل الصور) ===== */
.soft-segment{
  display:inline-flex;
  gap:8px;
  padding:8px;
  border-radius: 999px;
  background: var(--surface);
  box-shadow: var(--shadow-in);
  border: 1px solid var(--stroke);
}

.soft-seg-btn{
  height: 40px;
  padding: 0 16px;
  border-radius: 999px;
  border: 1px solid transparent;
  background: transparent;
  color: var(--muted);
  font-weight: 900;
  display:inline-flex;
  align-items:center;
  gap:10px;
  cursor:pointer;
  user-select:none;
  transition: transform .08s ease, filter .15s ease, box-shadow .15s ease, background .15s ease, color .15s ease;
}
.soft-seg-btn:hover{ filter: brightness(.98); }
.soft-seg-btn:active{ transform: translateY(1px); }

.soft-seg-btn.is-active{
  background: var(--dark);
  color: #fff;
  box-shadow: 0 10px 26px rgba(0,0,0,.25);
  border-color: rgba(255,255,255,.08);
}

/* Checkbox pill like الصورة */
.soft-check{
  display:flex;
  align-items:center;
  gap:10px;
  padding: 10px 12px;
  border-radius: 18px;
  background: var(--surface);
  box-shadow: var(--shadow-out);
  border: 1px solid var(--stroke);
}
.soft-check .box{
  width:42px;
  height:42px;
  border-radius: 16px;
  display:grid;
  place-items:center;
  background: var(--surface);
  box-shadow: var(--shadow-in);
  border: 1px solid var(--stroke);
  color: var(--muted);
}
.soft-check.is-on .box{
  background: var(--dark);
  color:#fff;
  box-shadow: 0 10px 26px rgba(0,0,0,.25);
  border-color: rgba(255,255,255,.08);
}
.soft-check .label{
  font-weight: 900;
}

/* Titles داخل الكارد */
.soft-kicker{
  font-weight: 900;
  color: var(--muted);
  font-size: 12px;
  margin: 0;
}
.soft-h1{
  font-weight: 900;
  font-size: 18px;
  margin: 0;
}
.soft-divider{
  height:1px;
  background: var(--stroke);
  margin: 10px 0;
  border-radius: 99px;
}

/* Bootstrap alerts align with theme */
.alert{
  border-radius: 16px !important;
}


/* Controls */
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

/* Buttons */
.soft-btn{
  height: 42px;
  border-radius: 14px;
  border: 1px solid var(--stroke);
  background: var(--surface);
  box-shadow: var(--shadow-out);
  padding: 0 14px;
  color: var(--text);
  display:inline-flex;
  align-items:center;
  gap:8px;
  cursor:pointer;
}
.soft-btn:hover{ filter: brightness(.98); }
.soft-btn:active{ box-shadow: var(--shadow-in); }

.soft-btn-primary{
  background: var(--dark);
  color: #fff;
  border-color: rgba(255,255,255,.08);
  box-shadow: 0 10px 26px rgba(0,0,0,.25);
}

/* Icon buttons (list/grid) */
.soft-icon-btn{
  width: 42px;
  height: 42px;
  border-radius: 14px;
  border: 1px solid var(--stroke);
  background: var(--surface);
  box-shadow: var(--shadow-out);
  display:grid;
  place-items:center;
  cursor:pointer;
}
.soft-icon-btn.is-active{
  background: var(--dark);
  color:#fff;
  border-color: rgba(255,255,255,.08);
  box-shadow: 0 10px 26px rgba(0,0,0,.25);
}

/* Utility */
.soft-title{
  font-weight: 800;
  margin: 0 0 8px 0;
}
.text-muted{ color: var(--muted); }
.flex-1{ flex:1; }
.wrap{ flex-wrap: wrap; }

/* RTL safety */
[dir="rtl"] .soft-toolbar{ direction: rtl; }


'); ?>
<?php /* CSS End */ ?>


  </style>
 


</head>

<body>
  <div class="app-shell" id="appShell">
  <?php require APPROOT . '/views/layouts/header.php'; ?>
  <div class="app-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="btn btn-outline-secondary btn-sm" type="button" id="collapseBtn" title="طي القائمة">
          <i class="bi bi-layout-sidebar-inset-reverse"></i>
        </button>
        <span class="topbar-title"><?= htmlspecialchars(defined('SITENAME') ? SITENAME : 'IT Inventory'); ?></span>
      </div>

      <div class="topbar-right">
        <a class="btn btn-outline-secondary btn-sm" href="<?= URLROOT; ?>/index.php?page=users/profile" title="ملفي">
          <i class="bi bi-person"></i>
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= URLROOT; ?>/index.php?page=users/logout" title="خروج">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      </div>
    </div>

    <main class="main-area">


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

      <button class="rail-btn<?= activeClass('spareparts') ?>" title="قطع الغيار" onclick="location.href='index.php?page=spareParts/index'">
        <i class="bi bi-tools"></i>
      </button>

      <button class="rail-btn<?= activeClass('tickets') ?>" title="التذاكر" onclick="location.href='index.php?page=tickets/index'">
        <i class="bi bi-ticket-perforated"></i>
      </button>

      <?php if ($logged && $canLocations): ?>
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

      <?php if ($logged): ?>
        <button class="rail-btn" id="railThemeToggle" title="الوضع الليلي">
          <i class="bi bi-moon-stars"></i>
        </button>
        <button class="rail-btn" title="تسجيل خروج" onclick="location.href='index.php?page=users/logout'">
          <i class="bi bi-box-arrow-right"></i>
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

        <button class="panel-close" id="panelCloseBtn" type="button" title="إغلاق" style="border-radius:12px;">
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

        <a class="<?= activeClass('assets') ?>" href="index.php?page=assets/index" data-label="الأصول الأجهزة">
          <div class="l"><i class="bi bi-pc-display"></i><span>الأصول / الأجهزة</span></div>
        </a>

        <a class="<?= activeClass('spareparts') ?>" href="index.php?page=spareParts/index" data-label="قطع الغيار">
          <div class="l"><i class="bi bi-tools"></i><span>قطع الغيار</span></div>
        </a>

        <a class="<?= activeClass('tickets') ?>" href="index.php?page=tickets/index" data-label="التذاكر">
          <div class="l"><i class="bi bi-ticket-perforated"></i><span>التذاكر</span></div>
        </a>

        <?php if ($logged && $canLocations): ?>
          <a class="<?= activeClass('locations') ?>" href="index.php?page=locations/index" data-label="المواقع">
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
          <div class="l"><i class="bi bi-person"></i><span>ملفي الشخصي</span></div>
        </a>
        <a href="index.php?page=settings/index" data-label="الإعدادات">
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
        <div class="user-card">
          <div class="user-mini">
            <div class="user-avatar"><?php echo mb_substr(trim((string)$userName), 0, 1); ?></div>
            <div class="user-info">
              <div class="n"><?php echo htmlspecialchars((string)$userName); ?></div>
              <div class="e"><?php echo htmlspecialchars((string)$userEmail); ?></div>
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
                <a class="dropdown-item text-danger" href="index.php?page=users/logout">
                  <i class="bi bi-box-arrow-right me-2"></i> تسجيل خروج
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </aside>

    <!-- Content -->
    <div class="app-content">
      <div class="app-topbar">
        <div class="topbar-inner">
          <div class="topbar-left">
            <button class="toggle-panel" id="panelToggleBtn" type="button" title="القائمة">
              <i class="bi bi-list"></i>
            </button>

            <div class="topbar-title">
              <div class="h"><?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?></div>
              <div class="s">واجهة موحّدة + Sidebar جديد</div>
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
