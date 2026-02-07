<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm" dir="rtl">
  <div class="container-fluid">
    
    <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo URLROOT; ?>/index.php?page=dashboard">
      <img src="<?php echo URLROOT; ?>/img/logo.png" alt="شعار النظام" height="45" class="d-inline-block align-text-top me-2 bg-white rounded p-1">
      <span class="ms-2">نظام إدارة العهد</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 p-0">
        
        <li class="nav-item">
          <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=dashboard">
            <i class="fas fa-home"></i> الرئيسية
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=assets/index">
            <i class="fas fa-laptop"></i> الأجهزة والعهد
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=locations/index">
            <i class="fas fa-sitemap"></i> المواقع
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=spare_parts/index">
            <i class="fas fa-microchip"></i> قطع الغيار
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=tickets/index">
            <i class="fas fa-ticket-alt"></i> التذاكر
          </a>
        </li>

        <?php if(isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 'super_admin' || $_SESSION['user_role'] == 'manager')): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo URLROOT; ?>/index.php?page=users/index">
            <i class="fas fa-users-cog"></i> المستخدمين
          </a>
        </li>
        <?php endif; ?>

      </ul>

      <ul class="navbar-nav ms-auto">
        <?php if(isset($_SESSION['user_id'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle active" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle fa-lg"></i> <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'المستخدم'; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end text-end" aria-labelledby="userDropdown">
              <li>
                  <a class="dropdown-item" href="<?php echo URLROOT; ?>/index.php?page=users/profile">
                      <i class="fas fa-id-card me-2 text-primary"></i> الملف الشخصي
                  </a>
              </li>
              <li>
                  <a class="dropdown-item" href="<?php echo URLROOT; ?>/index.php?page=assets/my">
                      <i class="fas fa-box-open me-2 text-success"></i> عهدي الشخصية
                  </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                  <a class="dropdown-item text-danger fw-bold" href="<?php echo URLROOT; ?>/index.php?page=logout">
                      <i class="fas fa-sign-out-alt me-2"></i> تسجيل خروج
                  </a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link btn btn-outline-light px-3 ms-2" href="<?php echo URLROOT; ?>index.php?page=users/login">
                <i class="fas fa-sign-in-alt"></i> دخول
            </a>
          </li>
        <?php endif; ?>
      </ul>

    </div>
  </div>
</nav>