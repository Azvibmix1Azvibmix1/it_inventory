<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <title><?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body { 
            background-color: #f8f9fa; 
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar-brand { font-weight: 700; font-size: 1.4rem; }
        .nav-link { font-weight: 600; margin-left: 5px; }
        /* إصلاح للصناديق */
        .card { border-radius: 10px; border: none; box-shadow: 0 0 15px rgba(0,0,0,0.05); }
        /* جعل المحتوى يتمدد لملء الشاشة */
        main { flex: 1; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 sticky-top">
        <div class="container">
            
            <a class="navbar-brand" href="<?php echo URLROOT; ?>/index.php?page=pages/index">
                <i class="fa fa-network-wired text-info"></i> <?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                
                <?php if(isset($_SESSION['user_id'])): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=pages/index">
                            <i class="fa fa-home"></i> الرئيسية
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=assets/index">
                            <i class="fa fa-laptop"></i> الأجهزة والعهد
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=locations/index">
                            <i class="fa fa-sitemap"></i> المواقع
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=SpareParts/index">
                            <i class="fa fa-microchip"></i> قطع الغيار
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=tickets/index">
                            <i class="fa fa-ticket-alt"></i> التذاكر
                        </a>
                    </li>

                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=users/index">
                            <i class="fa fa-users-cog"></i> المستخدمين
                        </a>
                    </li>
                    <?php endif; ?>

                </ul>
                <?php endif; ?>

                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fa fa-user-circle"></i> <?php echo $_SESSION['user_name'] ?? 'المستخدم'; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/index.php?page=users/profile"><i class="fa fa-id-card"></i> ملفي الشخصي</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo URLROOT; ?>/index.php?page=users/logout"><i class="fa fa-sign-out-alt"></i> تسجيل خروج</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=users/register"><i class="fa fa-user-plus"></i> تسجيل جديد</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=users/login"><i class="fa fa-sign-in-alt"></i> دخول</a>
                        </li>
                    <?php endif; ?>
                </ul>

            </div>
        </div>
    </nav>
    
    <main>