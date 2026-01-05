<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
  $locations = $data['locations'] ?? [];
  $selectedLoc = (int)($data['location_id'] ?? ($_GET['location_id'] ?? 0));
?>

<div class="row justify-content-center" dir="rtl">
  <div class="col-md-8">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h2 class="text-primary m-0"><i class="fa fa-plus-circle"></i> إضافة قطعة غيار جديدة</h2>
      <a href="<?php echo URLROOT; ?>/index.php?page=spareParts/index" class="btn btn-secondary">
        <i class="fa fa-arrow-right"></i> رجوع للقائمة
      </a>
    </div>

    <?php if (function_exists('flash')) { flash('part_message'); } ?>

    <div class="card shadow-sm">
      <div class="card-body">
        <form action="<?php echo URLROOT; ?>/index.php?page=spareParts/add" method="post">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">اسم القطعة <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control"
                     placeholder="مثال: RAM 8GB DDR4"
                     value="<?= htmlspecialchars($data['name'] ?? '') ?>"
                     required>
              <?php if (!empty($data['name_err'])): ?>
                <div class="text-danger small mt-1"><?= htmlspecialchars($data['name_err']) ?></div>
              <?php endif; ?>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">رقم القطعة (Part Number)</label>
              <input type="text" name="part_number" class="form-control" dir="ltr"
                     placeholder="اختياري"
                     value="<?= htmlspecialchars($data['part_number'] ?? '') ?>">
            </div>
          </div>

          <!-- ✅ اختيار الموقع -->
          <div class="mb-3">
            <label class="form-label">الموقع (اختياري)</label>
            <select name="location_id" class="form-select">
              <option value="">— غير محدد —</option>
              <?php foreach ($locations as $loc): ?>
                <option value="<?= (int)$loc->id ?>" <?= ($selectedLoc === (int)$loc->id) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($loc->name_ar ?? ('موقع #' . $loc->id)) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">لو فتحت الإضافة من صفحة الموقع، بيكون محدد تلقائيًا.</div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">الكمية المتوفرة <span class="text-danger">*</span></label>
              <input type="number" name="quantity" class="form-control" dir="ltr"
                     value="<?= (int)($data['quantity'] ?? 1) ?>" min="0" required>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">الحد الأدنى للتنبيه</label>
              <input type="number" name="min_quantity" class="form-control" dir="ltr"
                     value="<?= (int)($data['min_quantity'] ?? 5) ?>" min="0">
              <div class="form-text">سيتم تنبيهك إذا قلت الكمية عن هذا الرقم.</div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">الوصف / ملاحظات</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-success btn-lg">
              <i class="fa fa-save"></i> حفظ القطعة
            </button>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
