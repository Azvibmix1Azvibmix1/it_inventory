<?php require APPROOT . '/views/includes/header.php'; ?>
<?php require APPROOT . '/views/includes/navbar.php'; ?>

<div class="container fade-in">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg my-5">
                <div class="card-header bg-warning text-dark text-center py-3">
                    <h4 class="m-0 fw-bold"><i class="fas fa-edit"></i> تعديل بيانات المستخدم</h4>
                </div>
                <div class="card-body p-4">
                    
                    <form action="<?php echo URLROOT; ?>/index.php?page=users/edit&id=<?php echo $data['id']; ?>" method="post">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">الاسم الكامل:</label>
                            <input type="text" name="name" class="form-control <?php echo (!empty($data['name_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['name']; ?>">
                            <span class="invalid-feedback"><?php echo $data['name_err']; ?></span>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">البريد الإلكتروني:</label>
                            <input type="email" name="email" class="form-control <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['email']; ?>">
                            <span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">كلمة المرور الجديدة (اتركها فارغة إذا لا تريد التغيير):</label>
                            <input type="password" name="password" class="form-control" placeholder="********">
                        </div>

                        <?php if(isSuperAdmin()): ?>
                        <div class="mb-4">
                            <label for="role" class="form-label fw-bold">الرتبة:</label>
                            <select name="role" class="form-select">
                                <option value="user" <?php echo ($data['role'] == 'user') ? 'selected' : ''; ?>>موظف عادي (User)</option>
                                <option value="manager" <?php echo ($data['role'] == 'manager') ? 'selected' : ''; ?>>مدير قسم (Manager)</option>
                                <option value="super_admin" <?php echo ($data['role'] == 'super_admin') ? 'selected' : ''; ?>>مدير عام (Super Admin)</option>
                            </select>
                        </div>
                        <?php else: ?>
                            <input type="hidden" name="role" value="<?php echo $data['role']; ?>">
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-save"></i> تحديث البيانات
                            </button>
                            <a href="<?php echo URLROOT; ?>/index.php?page=users/index" class="btn btn-light border">إلغاء</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/includes/footer.php'; ?>