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
        body{
            font-family: "Cairo", sans-serif;
            background:#f8f9fa;
        }
        .navbar-brand{
            font-weight:700;
            letter-spacing:.3px;
        }
        .nav-link{
            font-weight:600;
        }
        .container-main{
            padding-top: 22px;
            padding-bottom: 22px;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo URLROOT; ?>/index.php?page=dashboard">
            <img src="<?php echo URLROOT; ?>/img/logo.png" alt="Logo" style="height:32px; width:auto;">
            <span><?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <?php if(function_exists('isLoggedIn') && isLoggedIn()): ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=dashboard">
                            <i class="fa fa-home"></i> الرئيسية
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=assets/index">
                            <i class="fa fa-desktop"></i> الأجهزة والعهد
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=locations/index">
                            <i class="fa fa-map-marker-alt"></i> المواقع
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=spare_parts/index">
                            <i class="fa fa-toolbox"></i> قطع الغيار
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=tickets/index">
                            <i class="fa fa-ticket"></i> التذاكر
                        </a>
                    </li>

                    <?php if(function_exists('isSuperAdmin') && isSuperAdmin() || (function_exists('isManager') && isManager())): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=users/index">
                                <i class="fa fa-users-cog"></i> المستخدمين
                            </a>
                        </li>
                    <?php endif; ?>

                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto">

                <?php if(function_exists('isLoggedIn') && isLoggedIn()): ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fa fa-user-circle"></i>
                            <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'حسابي'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <!-- لو ما عندك route users/profile حاليًا، نضيفه بعدين -->
                            <li>
                                <a class="dropdown-item" href="<?php echo URLROOT; ?>/index.php?page=users/profile">
                                    <i class="fa fa-id-card"></i> ملفي الشخصي
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo URLROOT; ?>/index.php?page=logout">
                                    <i class="fa fa-sign-out-alt"></i> تسجيل خروج
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=auth/register">
                            <i class="fa fa-user-plus"></i> تسجيل جديد
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=login">
                            <i class="fa fa-sign-in-alt"></i> دخول
                        </a>
                    </li>

                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
    <div class="container container-main">
