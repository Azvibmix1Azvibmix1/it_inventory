<?php require APPROOT . '/views/inc/header.php'; ?>

<style>
  .wrap{ direction: rtl; text-align: right; }
  .card{ border-radius: 12px; }
  .card-header{ border-top-left-radius:12px; border-top-right-radius:12px; }
  .hint{ color:#6c757d; font-size:.9rem; }
  .btn-save{ border-radius:10px!important; font-weight:800; padding:.55rem 1.2rem!important; }
</style>

<?php
  if (function_exists('flash')) {
    flash('location_msg');
    flash('access_denied');
  }

  $typeLabels = [
    'College'     => 'كلية / فرع رئيسي',
    'Building'    => 'مبنى',
    'Department'  => 'قسم',
    'Lab'         => 'معمل',
    'Office'      => 'مكتب',
    'Other'       => 'أخرى',
  ];

  $locations  = $data['locations'] ?? [];

  $name_ar   = $data['name_ar'] ?? '';
  $name_en   = $data['name_en'] ?? '';
  $type      = $data['type'] ?? 'College';
  $parent_id = $data['parent_id'] ?? '';

  $name_err  = $data['name_err'] ?? '';
?>

<div class="container-fluid wrap py-4">

  <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <strong>إضافة موقع جديد</strong>
      <div class="hint mt-1">قم بإنشاء كلية/مبنى/قسم/معمل وربطه بموقع أب عند الحاجة.</div>
    </div>

    <a class="btn btn-outline-secondary" href="index.php?page=locations/index">
      <i class="bi bi-arrow-right"></i> رجوع
    </a>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
          <i class="bi bi-geo-alt"></i> بيانات الموقع
        </div>

        <div class="card-body">

          <form method="post" action="index.php?page=locations/add">

            <div class="row g-3">

              <div class="col-12 col-md-6">
                <label class="form-label">الاسم (عربي) <span class="text-danger">*</span></label>
                <input
                  type="text"
                  name="name_ar"
                  class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>"
                  value="<?php echo htmlspecialchars($name_ar); ?>"
                  placeholder="مثال: كلية الحاسب"
                  required
                >
                <?php if (!empty($name_err)): ?>
                  <div class="invalid-feedback"><?php echo htmlspecialchars($name_err); ?></div>
                <?php endif; ?>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">الاسم (إنجليزي) (اختياري)</label>
                <input
                  type="text"
                  name="name_en"
                  class="form-control"
                  value="<?php echo htmlspecialchars($name_en); ?>"
                  placeholder="Ex: Computer College"
                  style="direction:ltr;"
                >
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">نوع المكان</label>
                <select name="type" class="form-select">
                  <?php foreach ($typeLabels as $k => $label): ?>
                    <option value="<?php echo htmlspecialchars($k); ?>" <?php echo ($type === $k) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($label); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">يتبع لـ (الموقع الأب)</label>
                <select name="parent_id" class="form-select">
                  <option value="">— بدون (مستوى أعلى) —</option>
                  <?php foreach ($locations as $l): ?>
                    <?php
                      $id = (int)($l->id ?? 0);
                      if ($id <= 0) continue;
                      $label = $l->name_ar ?? ($l->name ?? ('موقع #'.$id));
                      $sel = ((string)$parent_id === (string)$id) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $id; ?>" <?php echo $sel; ?>>
                      <?php echo htmlspecialchars($label); ?>
                    </option>
                  <?php endforeach; ?>
                </select>

                <div class="hint mt-2">
                  اتركه “بدون” إذا كان هذا مستوى أعلى (مثل كلية/فرع رئيسي)، أو اختر موقعًا ليكون هذا الموقع تابعًا له.
                </div>
              </div>

            </div>

            <div class="d-flex gap-2 justify-content-end mt-4">
              <a class="btn btn-outline-dark" href="index.php?page=locations/index">إلغاء</a>
              <button class="btn btn-success btn-save" type="submit">
                <i class="bi bi-check2-circle"></i> حفظ الموقع
              </button>
            </div>

          </form>

        </div>
      </div>
    </div>
  </div>

</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
