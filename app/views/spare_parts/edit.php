<?php require APPROOT . '/views/layouts/header.php'; ?>

<div class="container py-3" dir="rtl">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h4 class="m-0">تعديل قطعة غيار</h4>
    <a class="btn btn-outline-secondary" href="index.php?page=SpareParts/index">رجوع</a>
  </div>

  <?php flash('part_message'); ?>

  <form class="card shadow-sm" method="post" action="index.php?page=spare_parts/edit&id=<?= (int)($data['id'] ?? 0) ?>">
    <div class="card-body">
      <div class="row g-2">

        <div class="col-12 col-md-6">
          <label class="form-label">اسم القطعة *</label>
          <input class="form-control <?= !empty($data['name_err']) ? 'is-invalid' : '' ?>"
                 name="name"
                 value="<?= htmlspecialchars($data['name'] ?? '') ?>"
                 required>
          <?php if(!empty($data['name_err'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($data['name_err']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">رقم القطعة (Part Number)</label>
          <input class="form-control" name="part_number" dir="ltr"
                 value="<?= htmlspecialchars($data['part_number'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">الموقع</label>
          <select class="form-select" name="location_id">
            <option value="">— غير محدد —</option>
            <?php foreach(($data['locations'] ?? []) as $loc): ?>
              <option value="<?= (int)$loc->id ?>"
                <?= ((int)($data['location_id'] ?? 0) === (int)$loc->id) ? 'selected' : '' ?>>
                <?= htmlspecialchars($loc->name_ar ?? ('موقع #' . $loc->id)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label">الكمية</label>
          <input class="form-control" name="quantity" type="number" min="0" dir="ltr"
                 value="<?= (int)($data['quantity'] ?? 0) ?>">
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label">الحد الأدنى للتنبيه</label>
          <input class="form-control" name="min_quantity" type="number" min="0" dir="ltr"
                 value="<?= (int)($data['min_quantity'] ?? 0) ?>">
        </div>

        <div class="col-12">
          <label class="form-label">الوصف / ملاحظات</label>
          <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
        </div>

      </div>
    </div>

    <div class="card-footer d-flex justify-content-end">
      <button class="btn btn-primary">حفظ التعديل</button>
    </div>
  </form>
</div>

<?php require APPROOT . '/views/layouts/footer.php'; ?>
