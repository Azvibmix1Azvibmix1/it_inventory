<?php require APPROOT . '/views/inc/header.php'; ?>

<style>
  .login-wrap{ direction: rtl; text-align:right; min-height: 70vh; display:flex; align-items:center; justify-content:center; }
  .login-card{ max-width: 420px; width: 100%; border-radius: 14px; }
  .btn-wide{ width: 100%; font-weight: 800; border-radius: 10px; padding: .6rem 1rem; }
</style>

<div class="container login-wrap">
  <div class="card shadow-sm login-card">
    <div class="card-body p-4">
      <h4 class="fw-bold mb-3">تسجيل الدخول</h4>

      <?php if (function_exists('flash')) { flash('auth_error'); flash('access_denied'); } ?>

      <form action="index.php?page=login" method="post" autocomplete="on">
        <div class="mb-3">
          <label class="form-label">البريد الإلكتروني</label>
          <input type="email"
                 name="email"
                 class="form-control <?= !empty($data['email_err'] ?? '') ? 'is-invalid' : '' ?>"
                 value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                 required>
          <?php if (!empty($data['email_err'] ?? '')): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($data['email_err']) ?></div>
          <?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label">كلمة المرور</label>
          <input type="password"
                 name="password"
                 class="form-control <?= !empty($data['password_err'] ?? '') ? 'is-invalid' : '' ?>"
                 required>
          <?php if (!empty($data['password_err'] ?? '')): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($data['password_err']) ?></div>
          <?php endif; ?>
        </div>

        <button class="btn btn-primary btn-wide" type="submit">دخول</button>
      </form>

    </div>
  </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
