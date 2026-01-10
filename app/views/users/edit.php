<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container py-4">

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <h3 class="mb-1">تعديل بيانات المستخدم</h3>
      <div class="text-muted small">حدّث البيانات والصلاحية. اترك كلمة المرور فارغة إذا لا تريد تغييرها.</div>
    </div>

    <div class="d-flex gap-2">
      <a href="<?php echo URLROOT; ?>/index.php?page=users/index" class="btn btn-outline-secondary">
        <i class="fa fa-arrow-right"></i> رجوع
      </a>
    </div>
  </div>

  <?php flash('user_message'); ?>
  <?php flash('access_denied'); ?>

  <div class="card shadow-sm border-0">
    <div class="card-body">

      <form action="<?php echo URLROOT; ?>/index.php?page=users/edit" method="POST" novalidate>
        <input type="hidden" name="id" value="<?php echo (int)($data['id'] ?? 0); ?>">

        <?php
          $isSelf = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)($data['id'] ?? 0);
          $roleVal = normalizeRole($data['role'] ?? 'user');
        ?>

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
              required
            >
            <?php if (!empty($data['username_err'])): ?>
              <div class="invalid-feedback"><?php echo $data['username_err']; ?></div>
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
              required
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
              required
            >
            <?php if (!empty($data['email_err'])): ?>
              <div class="invalid-feedback"><?php echo $data['email_err']; ?></div>
            <?php else: ?>
              <div class="form-text">تأكد أن البريد صحيح وغير مكرر.</div>
            <?php endif; ?>
          </div>

          <!-- كلمة المرور الجديدة -->
          <div class="col-md-6">
            <label class="form-label">كلمة المرور الجديدة</label>
            <div class="input-group">
              <input
                type="password"
                name="password"
                dir="ltr"
                class="form-control <?php echo (!empty($data['password_err'])) ? 'is-invalid' : ''; ?>"
                value=""
                placeholder="اتركها فارغة إذا لا تريد التغيير"
              >
              <button class="btn btn-outline-secondary" type="button" onclick="togglePass(this)">
                <i class="fa fa-eye"></i>
              </button>
            </div>
            <?php if (!empty($data['password_err'])): ?>
              <div class="invalid-feedback d-block"><?php echo $data['password_err']; ?></div>
            <?php else: ?>
              <div class="form-text">اختياري — إذا تركتها فارغة لن تتغير كلمة المرور.</div>
            <?php endif; ?>
          </div>

          <!-- الدور -->
          <div class="col-md-6">
            <label class="form-label">الدور (الصلاحية) <span class="text-danger">*</span></label>

            <select name="role" class="form-select" <?php echo $isSelf ? 'disabled' : ''; ?>>
              <option value="user" <?php echo ($roleVal === 'user') ? 'selected' : ''; ?>>موظف (User)</option>
              <option value="manager" <?php echo ($roleVal === 'manager') ? 'selected' : ''; ?>>مدير (Manager)</option>
              <option value="super_admin" <?php echo ($roleVal === 'super_admin') ? 'selected' : ''; ?>>سوبر أدمن (Super Admin)</option>
            </select>

            <?php if ($isSelf): ?>
              <div class="form-text text-muted">لا يمكنك تغيير دور حسابك من هذه الصفحة.</div>
              <input type="hidden" name="role" value="<?php echo htmlspecialchars($roleVal); ?>">
            <?php endif; ?>
          </div>

          <!-- أزرار -->
          <div class="col-12">
            <div class="d-flex flex-wrap gap-2 justify-content-end mt-2">
              <a href="<?php echo URLROOT; ?>/index.php?page=users/index" class="btn btn-outline-secondary">
                إلغاء
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> تحديث البيانات
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
