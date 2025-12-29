<?php require APPROOT . '/views/includes/header.php'; ?>
<?php require APPROOT . '/views/includes/navbar.php'; ?>

<div class="container fade-in">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg my-5">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="m-0 fw-bold"><i class="fas fa-user-plus"></i> إضافة مستخدم جديد</h4>
                </div>
                <div class="card-body p-4">
                    
                    <form action="<?php echo URLROOT; ?>/index.php?page=users/add" method="post">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">الاسم الكامل: <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-lg <?php echo (!empty($data['name_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['name']; ?>">
                            <span class="invalid-feedback"><?php echo $data['name_err']; ?></span>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">البريد الإلكتروني: <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control form-control-lg <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['email']; ?>">
                            <span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">كلمة المرور: <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control form-control-lg <?php echo (!empty($data['password_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['password']; ?>">
                            <span class="invalid-feedback"><?php echo $data['password_err']; ?></span>
                        </div>

                        <?php if(isSuperAdmin()): ?>
                        <div class="mb-4">
                            <label for="role" class="form-label fw-bold">الرتبة:</label>
                            <select name="role" class="form-select form-select-lg">
                                <option value="user" <?php echo ($data['role'] == 'user') ? 'selected' : ''; ?>>موظف عادي (User)</option>
                                <option value="manager" <?php echo ($data['role'] == 'manager') ? 'selected' : ''; ?>>مدير قسم (Manager)</option>
                            </select>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> "مدير قسم" يستطيع إضافة موظفين ورؤية عهدهم فقط.
                            </small>
                        </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> حفظ البيانات
                            </button>
                            <a href="<?php echo URLROOT; ?>/index.php?page=users/index" class="btn btn-light btn-lg border">
                                إلغاء
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/includes/footer.php'; ?>