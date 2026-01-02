<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require APPROOT . '/views/auth/login.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-lg border-0 mt-5">
            <div class="card-header bg-primary text-white text-center py-3">
                <h3 class="mb-0"><i class="fa fa-sign-in-alt"></i> تسجيل الدخول</h3>
            </div>
            <div class="card-body p-4">
                
                <?php flash('register_success'); ?>

                <form action="<?php echo URLROOT; ?>/index.php?page=users/login" method="post">
                    
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['email']; ?>">
                        <span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">كلمة المرور</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($data['password_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['password']; ?>">
                        <span class="invalid-feedback"><?php echo $data['password_err']; ?></span>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">دخول</button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="<?php echo URLROOT; ?>/index.php?page=users/register" class="text-decoration-none">لا تملك حساباً؟ سجل الآن</a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>