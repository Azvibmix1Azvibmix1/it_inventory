<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-lg border-0 mt-5">
            
            <div class="card-header <?php echo isset($_SESSION['user_id']) ? 'bg-primary' : 'bg-success'; ?> text-white text-center py-3">
                <h3 class="mb-0">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <i class="fa fa-user-plus"></i> إضافة موظف جديد
                    <?php else: ?>
                        <i class="fa fa-user-plus"></i> إنشاء حساب جديد
                    <?php endif; ?>
                </h3>
            </div>
            
            <div class="card-body p-4">
                
                <p class="text-center text-muted mb-4">الرجاء تعبئة البيانات المطلوبة</p>

                <form action="<?php echo URLROOT; ?>/index.php?page=users/register" method="post">
                    
                    <div class="mb-3">
                        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($data['name_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['username'] ?? ''; ?>">
                        <span class="invalid-feedback"><?php echo $data['name_err']; ?></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['email'] ?? ''; ?>">
                        <span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">نوع الحساب (الصلاحية) <span class="text-danger">*</span></label>
                        <select name="role" class="form-select">
                            <option value="user">موظف (User) - صلاحيات محدودة</option>
                            <option value="admin">مدير نظام (Admin) - تحكم كامل</option>
                        </select>
                        <div class="form-text text-muted">انتبه: المدير يمكنه حذف وتعديل جميع البيانات.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control <?php echo (!empty($data['password_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['password'] ?? ''; ?>">
                            <span class="invalid-feedback"><?php echo $data['password_err']; ?></span>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control <?php echo (!empty($data['confirm_password_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['confirm_password'] ?? ''; ?>">
                            <span class="invalid-feedback"><?php echo $data['confirm_password_err']; ?></span>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn <?php echo isset($_SESSION['user_id']) ? 'btn-primary' : 'btn-success'; ?> btn-lg">
                            <i class="fa fa-save"></i> حفظ البيانات
                        </button>
                        
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a href="<?php echo URLROOT; ?>/index.php?page=users/login" class="btn btn-outline-secondary">
                                لديك حساب بالفعل؟ دخول
                            </a>
                        <?php else: ?>
                            <a href="<?php echo URLROOT; ?>/index.php?page=users/index" class="btn btn-outline-secondary">
                                <i class="fa fa-times"></i> إلغاء / رجوع للقائمة
                            </a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>