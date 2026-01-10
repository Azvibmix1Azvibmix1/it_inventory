<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container py-4">

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <h3 class="mb-1">إضافة مستخدم جديد</h3>
      <div class="text-muted small">إنشاء حساب جديد وتحديد صلاحياته داخل النظام</div>
    </div>

    <div class="d-flex gap-2">
      <a href="<?php echo URLROOT; ?>/index.php?page=users/index" class="btn btn-outline-secondary">
        <i class="fa fa-arrow-right"></i> رجوع
      </a>
    </div>
  </div>

  <?php flash('register_success'); ?>
  <?php flash('access_denied'); ?>
  <?php flash('user_message'); ?>

  <div class="card shadow-sm border-0">
    <div class="card-body">

      <form action="<?php echo URLROOT; ?>/index.php?page=users/add" method="POST" novalidate>
        <div class="row g-3">

          <!-- اسم المستخدم -->
          <div class="col-md-6">
            <label class="form-label">اسم المستخدم (Username) <span class="text-danger">*</span></label>
            <input
              type="text"
              name="username"
              dir="ltr"
              class="form-control <?php echo (!empty($data['username_err'])) ? 'is-invalid' : ''; ?>"
              value="<?php echo htmlspecialchars($data['username'] ?? ''); ?>"
              placeholder="مثال: aziz"
            >
            <?php if (!empty($data['username_err'])): ?>
              <div class="invalid-feedback"><?php echo $data['username_err']; ?></div>
            <?php else: ?>
              <div class="form-text">إذا تركته فارغًا سيتم توليده من البريد تلقائيًا.</div>
            <?php endif; ?>
          </div>

          <!-- الاسم -->
          <div class="col-md-6">
            <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
            <input
              type="text"
              name="name"
              class="form-control <?php echo (!empty($data['name_err'])) ? 'is-invalid' : ''; ?>"
              value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>"
              placeholder="مثال: محمد أحمد"
            >
            <?php if (!empty($data['name_err'])): ?>
              <div class="invalid-feedback"><?php echo $data['name_err']; ?></div>
            <?php endif; ?>
          </div>

          <!-- البريد -->
          <div class="col-md-6">
            <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
            <input
              type="email"
              name="email"
              dir="ltr"
              class="form-control <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>"
              value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>"
              placeholder="name@uj.edu.sa"
            >
            <?php if (!empty($data['email_err'])): ?>
              <div class="invalid-feedback"><?php echo $data['email_err']; ?></div>
            <?php else: ?>
              <div class="form-text">يفضّل استخدام البريد الرسمي إن وجد.</div>
            <?php endif; ?>
          </div>

          <!-- كلمة المرور -->
          <div class="col-md-6">
            <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
            <div class="input-group">
              <input
                type="password"
                name="password"
                dir="ltr"
                class="form-control <?php echo (!empty($data['password_err'])) ? 'is-invalid' : ''; ?>"
                value="<?php echo htmlspecialchars($data['password'] ?? ''); ?>"
                placeholder="********"
              >
              <button class="btn btn-outline-secondary" type="button" onclick="togglePass(this)">
                <i class="fa fa-eye"></i>
              </button>
            </div>

            <?php if (!empty($data['password_err'])): ?>
              <div class="invalid-feedback d-block"><?php echo $data['password_err']; ?></div>
            <?php else: ?>
              <div class="form-text">يفضّل 6 أحرف على الأقل.</div>
            <?php endif; ?>
          </div>

          <!-- الدور -->
          <div class="col-md-6">
            <label class="form-label">الدور (الصلاحية) <span class="text-danger">*</span></label>
            <?php $roleVal = normalizeRole($data['role'] ?? 'user'); ?>
            <select name="role" class="form-select">
              <option value="user" <?php echo ($roleVal === 'user') ? 'selected' : ''; ?>>موظف (User)</option>
              <option value="manager" <?php echo ($roleVal === 'manager') ? 'selected' : ''; ?>>مدير (Manager)</option>
              <option value="super_admin" <?php echo ($roleVal === 'super_admin') ? 'selected' : ''; ?>>سوبر أدمن (Super Admin)</option>
            </select>
            <div class="form-text">
              <div><strong>موظف:</strong> استخدام النظام فقط حسب الصلاحيات.</div>
              <div><strong>مدير:</strong> عرض المستخدمين التابعين له (حسب إعداداتك).</div>
              <div><strong>سوبر أدمن:</strong> إدارة المستخدمين بالكامل.</div>
            </div>
          </div>

          <!-- أزرار -->
          <div class="col-12">
            <div class="d-flex flex-wrap gap-2 justify-content-end mt-2">
              <a href="<?php echo URLROOT; ?>/index.php?page=users/index" class="btn btn-outline-secondary">
                إلغاء
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> حفظ البيانات
              </button>
            </div>
          </div>

        </div>
      </form>

    </div>
  </div>

</div>

<script>
function togglePass(btn) {
  const input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
  if (!input) return;
  if (input.type === 'password') {
    input.type = 'text';
    btn.innerHTML = '<i class="fa fa-eye-slash"></i>';
  } else {
    input.type = 'password';
    btn.innerHTML = '<i class="fa fa-eye"></i>';
  }
}
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
