<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
// تجهيز المواقع بشكل هرمي لعرضها في القائمة
$locations   = $data['locations'] ?? [];
$users       = $data['users_list'] ?? [];

// نبني مصفوفة id => object لاستخدامها في تكوين المسار الكامل
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
?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
            <i class="fa fa-desktop text-primary"></i>
            إضافة أصل جديد (جهاز / شاشة / طابعة ...)
        </h3>
        <a href="<?php echo URLROOT; ?>/index.php?page=assets/index" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-right"></i> عودة لقائمة الأصول
        </a>
    </div>

    <?php flash('asset_msg'); ?>
    <?php if (!empty($data['asset_err'])): ?>
        <div class="alert alert-danger"><?php echo $data['asset_err']; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <i class="fa fa-plus-circle"></i> بيانات الأصل
        </div>
        <div class="card-body">

            <form action="<?php echo URLROOT; ?>/index.php?page=assets/add" method="post">

                <div class="row">
                    <!-- رقم الأصل / العهدة -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">رقم الأصل / العهدة <span class="text-danger">*</span></label>
                        <input type="text"
                               name="asset_tag"
                               class="form-control"
                               value="<?php echo htmlspecialchars($data['asset_tag'] ?? ''); ?>"
                               placeholder="مثال: ASSET-2025-001">
                    </div>

                    <!-- الرقم التسلسلي -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الرقم التسلسلي (Serial)</label>
                        <input type="text"
                               name="serial_no"
                               class="form-control"
                               value="<?php echo htmlspecialchars($data['serial_no'] ?? ''); ?>"
                               placeholder="SN / Service Tag">
                    </div>

                    <!-- نوع الجهاز -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">نوع الأصل / الجهاز <span class="text-danger">*</span></label>
                        <input type="text"
                               name="type"
                               class="form-control"
                               value="<?php echo htmlspecialchars($data['type'] ?? ''); ?>"
                               placeholder="كمبيوتر مكتبي، لابتوب، شاشة، طابعة ...">
                    </div>
                </div>

                <div class="row">
                    <!-- الماركة -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الماركة (Brand)</label>
                        <input type="text"
                               name="brand"
                               class="form-control"
                               value="<?php echo htmlspecialchars($data['brand'] ?? ''); ?>"
                               placeholder="HP, Dell, Lenovo ...">
                    </div>

                    <!-- الموديل -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الموديل (Model)</label>
                        <input type="text"
                               name="model"
                               class="form-control"
                               value="<?php echo htmlspecialchars($data['model'] ?? ''); ?>"
                               placeholder="مثال: ProDesk 600 G3">
                    </div>

                    <!-- الموقع -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الموقع (كلية › مبنى › معمل)</label>
                        <select name="location_id" class="form-select">
                            <option value="">اختر موقع الجهاز</option>
                            <?php if (!empty($locations)): ?>
                                <?php foreach ($locations as $loc): ?>
                                    <?php
                                    // مثلاً: نسمح فقط بأنواع معينة كأماكن نهائية للجهاز
                                    $allowedTypes = ['Lab', 'Office', 'Store'];
                                    if (!in_array($loc->type, $allowedTypes)) continue;

                                    $selected = (!empty($data['location_id']) && $data['location_id'] == $loc->id)
                                        ? 'selected'
                                        : '';
                                    $label = buildLocationPath($loc, $locById) . ' (' . $loc->type . ')';
                                    ?>
                                    <option value="<?php echo $loc->id; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">
                            يتم جلب هذه المواقع من صفحة "إدارة الهيكل التنظيمي".
                        </div>
                    </div>
                </div>

                <hr>

                <!-- المستخدم / صاحب العهدة -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الموظف المستلم للعهدة</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">بدون تعيين / في المخزن</option>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $selected = (!empty($data['assigned_to']) && $data['assigned_to'] == $user->id)
                                        ? 'selected'
                                        : '';
                                    $userName = $user->name ?? ($user->username ?? $user->email);
                                    ?>
                                    <option value="<?php echo $user->id; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($userName) . ' (' . htmlspecialchars($user->role) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">
                            في حال كانت العهدة مرتبطة بموظف معين (مشغّل معمل مثلاً).
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">الحالة الحالية للأصل</label>
                        <select name="status" class="form-select" disabled>
                            <option selected>Active (نشط) - يتم تحديدها آلياً حالياً</option>
                        </select>
                        <div class="form-text">
                            لاحقاً نضيف حالات مثل (عطلان، تحت الصيانة، رجيع، مفقود...).
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fa fa-save"></i> حفظ الأصل
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
