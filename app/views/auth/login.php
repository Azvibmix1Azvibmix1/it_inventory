<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg border-0 rounded-lg">
            <div class="card-header bg-primary text-white text-center p-4">
                <h3 class="font-weight-bold my-2">نظام إدارة العهد</h3>
                <small>تسجيل الدخول للنظام الداخلي</small>
            </div>
            <div class="card-body p-5">
                
                <?php flash('register_success'); ?>

                <form action="<?php echo URLROOT; ?>/index.php?page=login" method="post">
                    
                    <div class="form-floating mb-3">
                        <input type="text" name="username" class="form-control <?php echo (!empty($data['username_err'])) ? 'is-invalid' : ''; ?>" id="inputUser" placeholder="اسم المستخدم" value="<?php echo $data['username']; ?>">
                        <label for="inputUser">اسم المستخدم</label>
                        <span class="invalid-feedback"><?php echo $data['username_err']; ?></span>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" name="password" class="form-control <?php echo (!empty($data['password_err'])) ? 'is-invalid' : ''; ?>" id="inputPass" placeholder="كلمة المرور">
                        <label for="inputPass">كلمة المرور</label>
                        <span class="invalid-feedback"><?php echo $data['password_err']; ?></span>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">دخول</button>
                    </div>

                </form>
            </div>
            <div class="card-footer text-center py-3">
                <div class="small text-muted">قسم تقنية المعلومات - النظام الداخلي</div>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>