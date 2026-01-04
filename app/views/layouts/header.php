<?php
$canLocations = function_exists('canAccessLocationsModule') ? canAccessLocationsModule() : false;
$logged = function_exists('isLoggedIn') ? isLoggedIn() : false;
?>

<!doctype html>
<html lang="ar" dir="rtl">
  
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?php echo defined('SITENAME') ? SITENAME : 'إدارة الوسائل'; ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- ✅ Bootstrap Icons (لصفحاتك الجديدة اللي تستخدم bi bi-...) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- ✅ Font Awesome (حل مؤقت عشان الصفحات القديمة اللي تستخدم fa/fa-solid ما تختفي أيقوناتها) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <style>
    /* ✅ Sticky footer layout */
    html, body { height: 100%; }
    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: #f6f7fb;
    }
    main.site-content { flex: 1; }

    /* Navbar tweaks */
  </style>
   <style>
/* ===== Fix RTL date inputs (Chrome) ===== */
input[type="date"],
input[type="datetime-local"],
input[type="month"],
input[type="time"] {
  direction: ltr;
  unicode-bidi: plaintext;
  text-align: right; /* لو تبغاه يسار غيّرها left */
}
</style>

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">

    <a class="navbar-brand" href="index.php?page=dashboard/index">
      <?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNav">

      <!-- ✅ القائمة الأساسية: تبقى يمين (RTL) -->
      <ul class="navbar-nav mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php?page=dashboard/index">الرئيسية</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=assets/index">الأجهزة</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=spareparts/index">قطع الغيار</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=tickets/index">التذاكر</a></li>

        <?php if ($canLocations): ?>
          <li class="nav-item"><a class="nav-link" href="index.php?page=locations/index">المواقع</a></li>
        <?php endif; ?>

        <?php if (function_exists('currentRole') && in_array(currentRole(), ['superadmin','manager'], true)): ?>
          <li class="nav-item"><a class="nav-link" href="index.php?page=users/index">الموظفين</a></li>
        <?php endif; ?>
      </ul>

      <!-- ✅ قائمة الحساب: تنزاح لليسار بدون ما تتوسّط القائمة -->
      <ul class="navbar-nav ms-lg-auto">
        <?php if ($logged): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle"></i>
              <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'حسابي'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="index.php?page=users/profile"><i class="bi bi-person"></i> ملفي الشخصي</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="index.php?page=logout"><i class="bi bi-box-arrow-right"></i> تسجيل خروج</a></li>
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

<!-- ✅ المحتوى الرئيسي -->
<main class="site-content container-fluid pt-3">
