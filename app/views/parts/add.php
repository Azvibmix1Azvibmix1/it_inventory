<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="fa fa-boxes"></i> إضافة قطعة غيار جديدة
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/index.php?page=parts/add" method="post">
                    
                    <div class="mb-3">
                        <label>اسم القطعة (Item Name)</label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: كابل HDMI" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>التصنيف (Category)</label>
                            <select name="category" class="form-select">
                                <option value="Cables">كابلات وتوصيلات</option>
                                <option value="Peripherals">ملحقات (فأرة، لوحة مفاتيح)</option>
                                <option value="Components">قطع داخلية (RAM, HDD)</option>
                                <option value="Consumables">استهلاكي (أحبار، ورق)</option>
                                <option value="Other">أخرى</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>مكان التخزين</label>
                            <select name="location_id" class="form-select">
                                <option value="">-- مخزن عام --</option>
                                <?php if(isset($data['locations'])): ?>
                                    <?php foreach($data['locations'] as $loc): ?>
                                        <option value="<?php echo $loc['id']; ?>"><?php echo $loc['name_en']; ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>الكمية الحالية</label>
                            <input type="number" name="quantity" class="form-control" value="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>حد التنبيه (Low Stock Alert)</label>
                            <input type="number" name="min_stock" class="form-control" value="5">
                            <small class="text-muted">سينبهك النظام إذا قلت الكمية عن هذا الرقم</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>وصف إضافي</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">حفظ القطعة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>