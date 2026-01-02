<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
$locations = $data['locations'] ?? [];
$users     = $data['users_list'] ?? [];

// بناء مصفوفة id => object
$locById = [];
foreach ($locations as $loc) {
    $locById[$loc->id] = $loc;
}

if (!function_exists('buildLocationPath')) {
    function buildLocationPath($loc, $locById) {
        $parts   = [$loc->name_ar];
        $current = $loc;

        while (!empty($current->parent_id) && isset($locById[$current->parent_id])) {
            $current = $locById[$current->parent_id];
            array_unshift($parts, $current->name_ar);
        }

        return implode(' › ', $parts);
    }
}
$asset = $data['asset'] ?? null;
?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
            <i class="fa fa-edit text-primary"></i>
            تعديل بيانات الأصل
            <?php if ($asset): ?>
                <small class="text-muted">(#<?php echo $asset->id; ?>)</small>
            <?php endif; ?>
        </h3>
        <a href="<?php echo URLROOT; ?>/index.php?page=assets/index" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-right"></i> عودة لقائمة الأصول
        </a>
    </div>

    <?php flash('asset_msg'); ?>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <i class="fa fa-desktop"></i> معلومات الأصل
        </div>
        <div class="card-body">

            <form action="<?php echo URLROOT; ?>/index.php?page=assets/edit&id=<?php echo $data['id']; ?>" method="post">

                <div class="row">
                    <!-- رقم الأصل -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">رقم الأصل / العهدة</label>
                        <input type="text"
                               name="asset_tag"
                               class="form-control"
                               value="<?php echo htmlspecialchars($data['asset_tag']); ?>">
                    </div>

                    <!-- Serial -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الرقم التسلسلي</label>
                        <input type="text"
                               name="serial_no"
                               class="form-control"
                               value="<?php echo htmlspecialchars($data['serial_no']); ?>">
                    </div>

                    <!-- نوع الجهاز -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">نوع الجهاز</label>
                        <input type="text"
                               name="type"
                               class="form-control"
                               value="<?php echo htmlspecialchars($data['type']); ?>">
                    </div>
                </div>

                <div class="row">
                    <!-- الماركة -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الماركة (Brand)</label>
                        <input type="text"
                               name="brand"
                               class="form-control"
                               value="<?php echo htmlspecialchars($data['brand']); ?>">
                    </div>

                    <!-- الموديل -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الموديل (Model)</label>
                        <input type="text"
                               name="model"
                               class="form-control"
                               value="<?php echo htmlspecialchars($data['model']); ?>">
                    </div>

                    <!-- الموقع -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الموقع الحالي</label>
                        <select name="location_id" class="form-select">
                            <option value="">اختر موقع الجهاز</option>
                            <?php if (!empty($locations)): ?>
                                <?php foreach ($locations as $loc): ?>
                                    <?php
                                    $allowedTypes = ['Lab', 'Office', 'Store'];
                                    if (!in_array($loc->type, $allowedTypes)) continue;

                                    $selected = ((int)$data['location_id'] === (int)$loc->id) ? 'selected' : '';
                                    $label    = buildLocationPath($loc, $locById) . ' (' . $loc->type . ')';
                                    ?>
                                    <option value="<?php echo $loc->id; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <!-- الموظف المستلم -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الموظف المستلم للعهدة</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">بدون تعيين / في المخزن</option>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $userName = $user->name ?? ($user->username ?? $user->email);
                                    $selected = (!empty($data['assigned_to']) && (int)$data['assigned_to'] === (int)$user->id)
                                        ? 'selected'
                                        : '';
                                    ?>
                                    <option value="<?php echo $user->id; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($userName) . ' (' . htmlspecialchars($user->role) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- الحالة -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">حالة الأصل</label>
                        <select name="status" class="form-select">
                            <option value="Active"   <?php echo ($data['status'] == 'Active')   ? 'selected' : ''; ?>>نشط (Active)</option>
                            <option value="Broken"   <?php echo ($data['status'] == 'Broken')   ? 'selected' : ''; ?>>عطلان (Broken)</option>
                            <option value="Repair"   <?php echo ($data['status'] == 'Repair')   ? 'selected' : ''; ?>>تحت الإصلاح (Repair)</option>
                            <option value="Retired"  <?php echo ($data['status'] == 'Retired')  ? 'selected' : ''; ?>>رجيع / مستبعد (Retired)</option>
                            <option value="Lost"     <?php echo ($data['status'] == 'Lost')     ? 'selected' : ''; ?>>مفقود (Lost)</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> حفظ التعديلات
                    </button>

                    <a href="<?php echo URLROOT; ?>/index.php?page=assets/delete&id=<?php echo $data['id']; ?>"
                       class="btn btn-outline-danger"
                       onclick="return confirm('هل أنت متأكد من حذف هذا الأصل؟');">
                        <i class="fa fa-trash"></i> حذف الأصل
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
