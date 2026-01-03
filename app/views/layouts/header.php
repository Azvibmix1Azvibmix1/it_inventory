<?php
// تأكد أن session_helper متحمّل عندك من bootstrap
$canLocations = function_exists('canAccessLocationsModule') ? canAccessLocationsModule() : false;
$logged = function_exists('isLoggedIn') ? isLoggedIn() : false;
?>

<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?></title>

  <!-- Bootstrap CSS (إذا عندك ملفك الخاص خليه) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    body { background:#f6f7fb; }
    .navbar-brand{ font-weight:900; }
    .nav-link{ font-weight:700; }
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
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <li class="nav-item"><a class="nav-link" href="index.php?page=dashboard/index">الرئيسية</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=assets/index">الأجهزة والعهد</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=spareparts/index">قطع الغيار</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=tickets/index">التذاكر</a></li>

        <!-- ✅ المواقع والمباني تظهر فقط لمن عنده صلاحية -->
        <?php if ($canLocations): ?>
          <li class="nav-item">
            <a class="nav-link" href="index.php?page=locations/index">المواقع والمباني</a>
          </li>
        <?php endif; ?>

        <!-- المستخدمين (مثال: للمدير/السوبر أدمن) -->
        <?php if (function_exists('currentRole') && in_array(currentRole(), ['superadmin','manager'], true)): ?>
          <li class="nav-item"><a class="nav-link" href="index.php?page=users/index">المستخدمين</a></li>
        <?php endif; ?>

      </ul>

      <ul class="navbar-nav ms-auto">
        <?php if ($logged): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle"></i>
              <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'حسابي'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="index.php?page=users/profile">ملفي الشخصي</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="index.php?page=logout">تسجيل خروج</a></li>
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

<div class="container-fluid pt-3">
