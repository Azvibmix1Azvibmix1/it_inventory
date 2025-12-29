<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="text-primary"><i class="fa fa-plus-circle"></i> إضافة قطعة غيار جديدة</h2>
            <a href="<?php echo URLROOT; ?>/index.php?page=spareParts/index" class="btn btn-secondary">
                <i class="fa fa-arrow-right"></i> رجوع للقائمة
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/index.php?page=spareParts/add" method="post">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">اسم القطعة <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="مثال: RAM 8GB DDR4" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">رقم القطعة (Part Number)</label>
                            <input type="text" name="part_number" class="form-control" placeholder="اختياري">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الكمية المتوفرة <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" value="1" min="0" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">الحد الأدنى للتنبيه</label>
                            <input type="number" name="min_quantity" class="form-control" value="5" min="1">
                            <div class="form-text">سيتم تنبيهك إذا قلت الكمية عن هذا الرقم.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الوصف / ملاحظات</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
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