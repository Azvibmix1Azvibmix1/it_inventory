<?php
// تأكد من بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعدادات افتراضية للغة والوضع
$lang_dir = 'rtl'; 
$body_class = '';  
$bootstrap_css = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css'; 

// فحص تفضيلات المستخدم من السيشن
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'] == 1) {
        $body_class = 'dark-mode';
    }
    if (isset($_SESSION['lang']) && $_SESSION['lang'] == 'en') {
        $lang_dir = 'ltr';
        $bootstrap_css = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ar' ?>" dir="<?= $lang_dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Inventory System</title>
    
    <link rel="stylesheet" href="<?= $bootstrap_css ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f4f6f9;
            transition: background-color 0.3s, color 0.3s;
        }
        
        /* تنسيقات الوضع الليلي */
        body.dark-mode {
            background-color: #1a1a2e;
            color: #e0e0e0;
        }
        body.dark-mode .card {
            background-color: #16213e;
            border-color: #30475e;
            color: #fff;
        }
        body.dark-mode .form-control, 
        body.dark-mode .form-select {
            background-color: #0f3460;
            border: 1px solid #30475e;
            color: #fff;
        }
        body.dark-mode .text-muted {
            color: #aab8c5 !important;
        }
        body.dark-mode .navbar {
            background-color: #16213e !important;
            border-bottom: 1px solid #30475e;
        }
        .navbar-nav .nav-link {
            font-weight: 600;
            margin-left: 10px;
        }
    </style>
</head>
<body class="<?= $body_class ?>">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-boxes me-2"></i>نظام إدارة العهد</a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
         <li class="nav-item">
             <a class="nav-link active" href="index.php">الرئيسية</a>
         </li>
         <li class="nav-item">
             <a class="nav-link" href="assets.php">الأصول</a>
         </li>
         <li class="nav-item">
             <a class="nav-link" href="parts.php">قطع الغيار</a>
         </li>
         <li class="nav-item">
             <a class="nav-link" href="users.php">المستخدمين</a>
         </li>
      </ul>
      
      <div class="d-flex align-items-center">
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php 
                        $avatarPath = "../public/uploads/avatars/" . ($_SESSION['user_avatar'] ?? '');
                        $headerAvatar = (!empty($_SESSION['user_avatar']) && file_exists($avatarPath)) 
                                        ? $avatarPath 
                                        : "https://via.placeholder.com/32";
                    ?>
                    <img src="<?= $headerAvatar ?>" alt="" width="35" height="35" class="rounded-circle me-2 border border-2 border-white">
                    <strong><?= $_SESSION['user_name'] ?? 'المستخدم' ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>الملف الشخصي</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>تسجيل خروج</a></li>
                </ul>
            </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="container"></div>