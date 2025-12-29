<?php require APPROOT . '/views/includes/header.php'; ?>
<?php require APPROOT . '/views/includes/navbar.php'; ?>

<div class="container fade-in">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg my-5 border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="m-0 fw-bold"><i class="fas fa-laptop-medical"></i> إضافة أصل/عهدة جديدة</h4>
                </div>
                <div class="card-body p-4">
                    <form action="<?php echo URLROOT; ?>/index.php?page=assets/add" method="post">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">رقم الأصل (Asset Tag) <span class="text-danger">*</span></label>
                                <input type="text" name="asset_tag" class="form-control" value="<?php echo $data['asset_tag']; ?>" placeholder="مثال: IT-2025-001">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">الرقم التسلسلي (Serial No)</label>
                                <input type="text" name="serial_no" class="form-control" value="<?php echo $data['serial_no']; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">الماركة (Brand)</label>
                                <input type="text" name="brand" class="form-control" value="<?php echo $data['brand']; ?>" placeholder="Dell, HP, Apple...">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">الموديل (Model)</label>
                                <input type="text" name="model" class="form-control" value="<?php echo $data['model']; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">نوع الجهاز <span class="text-danger">*</span></label>
                            <select name="type" class="form-select">
                                <option value="Laptop">لابتوب (Laptop)</option>
                                <option value="Desktop">مكتبي (Desktop)</option>
                                <option value="Monitor">شاشة (Monitor)</option>
                                <option value="Printer">طابعة (Printer)</option>
                                <option value="Accessory">ملحقات (Accessory)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">الموقع (Location) <span class="text-danger">*</span></label>
                            <select name="location_id" class="form-select" required>
                                <option value="">اختر الموقع...</option>
                                <?php foreach($data['locations'] as $loc): ?>
                                    <option value="<?php echo $loc->id; ?>">
                                        <?php echo $loc->name_ar; ?> (<?php echo $loc->type; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-success"><i class="fas fa-user-check"></i> تخصيص للموظف (Assign User):</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- بدون تخصيص (في المخزن) --</option>
                                
                                <?php foreach($data['users_list'] as $user): ?>
                                    <option value="<?php echo $user->id; ?>">
                                        <?php echo $user->name; ?> - <?php echo $user->email; ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                            <?php if(isManager()): ?>
                                <small class="text-muted">تظهر لك فقط قائمة الموظفين التابعين لك.</small>
                            <?php endif; ?>
                        </div>

                        <?php if(!empty($data['asset_err'])): ?>
                            <div class="alert alert-danger"><?php echo $data['asset_err']; ?></div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">حفظ البيانات</button>
                            <a href="<?php echo URLROOT; ?>/index.php?page=assets/index" class="btn btn-light border">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/includes/footer.php'; ?>