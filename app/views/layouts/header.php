<?php
$canLocations = function_exists('canAccessLocationsModule') ? canAccessLocationsModule() : false;
$logged = function_exists('isLoggedIn') ? isLoggedIn() : false;

function navActive(string $prefix): string {
  $p = $_GET['page'] ?? '';
  return (strpos($p, $prefix) === 0) ? ' active' : '';
}
?>

<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?php echo defined('SITENAME') ? SITENAME : 'إدارة الوسائل'; ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- Font Awesome (للصفحات القديمة) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- خط رسمي عربي -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">

  <style>
    /* ====== University of Jeddah Theme (Navbar black as requested) ====== */
    :root{
      --uj-primary:#004F6E;     /* أزرق رسمي */
      --uj-accent:#83CCEA;      /* سماوي Accent */
      --uj-bg:#F5F7F9;
      --uj-text:#1F2937;
      --uj-border:#E5E7EB;
      --uj-ring: rgba(0,79,110,.25);
      --nav-black:#0b0b0b;
    }

    html, body { height: 100%; }
    body{
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: var(--uj-bg);
      color: var(--uj-text);
      font-family: "Cairo", sans-serif;
    }
    main.site-content { flex: 1; }

    /* Navbar */
    .navbar{
      background: var(--nav-black) !important;
      box-shadow: 0 8px 18px rgba(0,0,0,.10);
    }
    .navbar .navbar-brand{
      font-weight: 800;
      color: #fff !important;
    }
    .navbar .nav-link{
      color: rgba(255,255,255,.82) !important;
      font-weight: 700;
      position: relative;
    }
    .navbar .nav-link:hover{ color:#fff !important; }

    /* Active nav underline (accent) */
    .navbar .nav-link.active{
      color:#fff !important;
    }
    .navbar .nav-link.active::after{
      content:"";
      position:absolute;
      right: .7rem;
      left: .7rem;
      bottom: .35rem;
      height: 2px;
      background: var(--uj-accent);
      border-radius: 2px;
    }

    /* Cards */
    .card{
      border-color: var(--uj-border) !important;
      border-radius: 14px;
    }

    /* Buttons: dynamic */
    .btn{
      transition: transform .08s ease, filter .15s ease, box-shadow .15s ease;
    }
    .btn:hover{ filter: brightness(.98); }
    .btn:active{ transform: translateY(1px); }

    .btn-primary{
      background: var(--uj-primary) !important;
      border-color: var(--uj-primary) !important;
    }
    .btn-primary:hover{
      filter: brightness(.95);
    }
    .btn-primary:focus{
      box-shadow: 0 0 0 .2rem var(--uj-ring) !important;
    }

    .btn-outline-primary{
      color: var(--uj-primary) !important;
      border-color: var(--uj-primary) !important;
    }
    .btn-outline-primary:hover{
      background: var(--uj-primary) !important;
      border-color: var(--uj-primary) !important;
      color:#fff !important;
    }

    /* Inputs focus */
    .form-control:focus, .form-select:focus{
      border-color: var(--uj-primary) !important;
      box-shadow: 0 0 0 .2rem var(--uj-ring) !important;
    }

    /* ===== Fix RTL date inputs (Chrome) ===== */
    input[type="date"],
    input[type="datetime-local"],
    input[type="month"],
    input[type="time"] {
      direction: ltr;
      unicode-bidi: plaintext;
      text-align: right;
    }
  </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">

    <a class="navbar-brand" href="index.php?page=dashboard/index">
      <?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNav">

      <!-- القائمة الأساسية -->
      <ul class="navbar-nav mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?= navActive('dashboard') ?>" href="index.php?page=dashboard/index">الرئيسية</a>
        </li>

        <li class="nav-item">
          <a class="nav-link<?= navActive('assets') ?>" href="index.php?page=assets/index">الأجهزة</a>
        </li>

        <li class="nav-item">
          <a class="nav-link<?= navActive('spareparts') ?>" href="index.php?page=spareparts/index">قطع الغيار</a>
        </li>

        <li class="nav-item">
          <a class="nav-link<?= navActive('tickets') ?>" href="index.php?page=tickets/index">التذاكر</a>
        </li>

        <?php if ($canLocations): ?>
          <li class="nav-item">
            <a class="nav-link<?= navActive('locations') ?>" href="index.php?page=locations/index">المواقع</a>
          </li>
        <?php endif; ?>

        <?php if (function_exists('currentRole') && in_array(currentRole(), ['superadmin','manager'], true)): ?>
          <li class="nav-item">
            <a class="nav-link<?= navActive('users') ?>" href="index.php?page=users/index">الموظفين</a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- الحساب -->
      <ul class="navbar-nav ms-lg-auto">
        <?php if ($logged): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle"></i>
              <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'حسابي'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="index.php?page=users/profile">
                  <i class="bi bi-person"></i> ملفي الشخصي
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item" href="index.php?page=logout">
                  <i class="bi bi-box-arrow-right"></i> تسجيل خروج
                </a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="index.php?page=register">تسجيل جديد</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php?page=login">دخول</a></li>
        <?php endif; ?>
      </ul>

    </div>
  </div>
</nav>

<!-- المحتوى الرئيسي -->
<main class="site-content container-fluid pt-3">
