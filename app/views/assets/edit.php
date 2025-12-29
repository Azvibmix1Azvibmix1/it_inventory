<?php require APPROOT . '/views/includes/header.php'; ?>
<?php require APPROOT . '/views/includes/navbar.php'; ?>

<div class="container fade-in">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg my-5 border-0">
                <div class="card-header bg-warning text-dark text-center py-3">
                    <h4 class="m-0 fw-bold"><i class="fas fa-edit"></i> تعديل بيانات الأصل</h4>
                </div>
                <div class="card-body p-4">
                    <form action="<?php echo URLROOT; ?>/index.php?page=assets/edit&id=<?php echo $data['id']; ?>" method="post">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">رقم الأصل (Asset Tag)</label>
                                <input type="text" name="asset_tag" class="form-control" value="<?php echo $data['asset_tag']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">الرقم التسلسلي</label>
                                <input type="text" name="serial_no" class="form-control" value="<?php echo $data['serial_no']; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">الماركة</label>
                                <input type="text" name="brand" class="form-control" value="<?php echo $data['brand']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">الموديل</label>
                                <input type="text" name="model" class="form-control" value="<?php echo $data['model']; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">نوع الجهاز</label>
                            <select name="type" class="form-select">
                                <option value="Laptop" <?php echo ($data['type'] == 'Laptop') ? 'selected' : ''; ?>>لابتوب</option>
                                <option value="Desktop" <?php echo ($data['type'] == 'Desktop') ? 'selected' : ''; ?>>مكتبي</option>
                                <option value="Monitor" <?php echo ($data['type'] == 'Monitor') ? 'selected' : ''; ?>>شاشة</option>
                                <option value="Printer" <?php echo ($data['type'] == 'Printer') ? 'selected' : ''; ?>>طابعة</option>
                                <option value="Accessory" <?php echo ($data['type'] == 'Accessory') ? 'selected' : ''; ?>>ملحقات</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">الموقع</label>
                            <select name="location_id" class="form-select">
                                <?php foreach($data['locations'] as $loc): ?>
                                    <option value="<?php echo $loc->id; ?>" <?php echo ($data['location_id'] == $loc->id) ? 'selected' : ''; ?>>
                                        <?php echo $loc->name_ar; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-success">الموظف المسؤول:</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- في المخزن --</option>
                                <?php foreach($data['users_list'] as $user): ?>
                                    <option value="<?php echo $user->id; ?>" <?php echo ($data['assigned_to'] == $user->id) ? 'selected' : ''; ?>>
                                        <?php echo $user->name; ?> (<?php echo $user->email; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">الحالة</label>
                            <select name="status" class="form-select">
                                <option value="Active" <?php echo ($data['status'] == 'Active') ? 'selected' : ''; ?>>نشط (Active)</option>
                                <option value="In Repair" <?php echo ($data['status'] == 'In Repair') ? 'selected' : ''; ?>>في الصيانة (In Repair)</option>
                                <option value="Retired" <?php echo ($data['status'] == 'Retired') ? 'selected' : ''; ?>>تالف/مكهن (Retired)</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning btn-lg">تحديث البيانات</button>
                            <a href="<?php echo URLROOT; ?>/index.php?page=assets/index" class="btn btn-light border">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/includes/footer.php'; ?>