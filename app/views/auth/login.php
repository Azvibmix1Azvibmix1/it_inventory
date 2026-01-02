<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container mt-5" style="max-width: 520px;">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fa fa-sign-in-alt"></i> تسجيل الدخول</h5>
        </div>

        <div class="card-body">

            <?php flash('register_success'); ?>
            <?php flash('access_denied'); ?>

            <form action="<?php echo URLROOT; ?>/index.php?page=login" method="post">

                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email"
                           name="email"
                           class="form-control <?php echo (!empty($data['email_err'] ?? '')) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>"
                           required>
                    <div class="invalid-feedback">
                        <?php echo $data['email_err'] ?? ''; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password"
                           name="password"
                           class="form-control <?php echo (!empty($data['password_err'] ?? '')) ? 'is-invalid' : ''; ?>"
                           required>
                    <div class="invalid-feedback">
                        <?php echo $data['password_err'] ?? ''; ?>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-arrow-right"></i> دخول
                    </button>
                </div>

            </form>

            <hr>
            <div class="text-center">
                <a href="<?php echo URLROOT; ?>/index.php?page=register">لا تملك حساباً؟ سجل الآن</a>
            </div>

        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
