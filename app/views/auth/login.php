<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container mt-5" style="max-width: 500px;">

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white text-center">
            <h4 class="mb-0">
                <i class="fa fa-sign-in-alt"></i> تسجيل الدخول
            </h4>
        </div>

        <div class="card-body">

            <?php flash('register_success'); ?>

            <form action="<?php echo URLROOT; ?>/index.php?page=login" method="post">

                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control <?php echo !empty($data['email_err']) ? 'is-invalid' : ''; ?>"
                        value="<?php echo $data['email']; ?>"
                        required
                    >
                    <span class="invalid-feedback">
                        <?php echo $data['email_err']; ?>
                    </span>
                </div>

                <div class="mb-3">
                    <label class="form-label">كلمة المرور</label>
                    <input 
                        type="password" 
                        name="password" 
                        class="form-control <?php echo !empty($data['password_err']) ? 'is-invalid' : ''; ?>"
                        required
                    >
                    <span class="invalid-feedback">
                        <?php echo $data['password_err']; ?>
                    </span>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-lock"></i> دخول
                </button>

            </form>

        </div>

    </div>

</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
