<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
// تأكد أن المصفوفة موجودة
$locations = $data['locations'] ?? [];

// تجميع حسب الـ parent_id عشان نرسم شجرة
$byParent = [];
foreach ($locations as $loc) {
    $pid = $loc->parent_id;
    if ($pid === null) {
        $pid = 0; // الجذور
    }
    if (!isset($byParent[$pid])) {
        $byParent[$pid] = [];
    }
    $byParent[$pid][] = $loc;
}

// الجذور = مواقع بدون parent_id
$roots = $byParent[0] ?? [];
?>

<div class="container mt-4">

    <!-- عنوان -->
    <div class="row mb-3">
        <div class="col-md-8">
            <h1 class="h3">
                <i class="fa fa-sitemap text-primary"></i>
                إدارة الهيكل التنظيمي
            </h1>
            <p class="text-muted mb-0">
                قم ببناء الهيكل: أضف الكليات، ثم المباني التابعة لها، ثم المعامل والمكاتب.
            </p>
        </div>
    </div>

    <!-- نموذج إضافة موقع جديد -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span class="fw-bold">
                <i class="fa fa-plus-circle text-success"></i>
                إضافة موقع جديد
            </span>
        </div>
        <div class="card-body">
            <form action="<?php echo URLROOT; ?>/index.php?page=locations/add" method="post" class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">الاسم (عربي) <span class="text-danger">*</span></label>
                    <input type="text"
                           name="name_ar"
                           class="form-control"
                           value="<?php echo htmlspecialchars($data['name_ar'] ?? ''); ?>"
                           placeholder="مثال: كلية الحاسب"
                           required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">الاسم (إنجليزي)</label>
                    <input type="text"
                           name="name_en"
                           class="form-control"
                           value="<?php echo htmlspecialchars($data['name_en'] ?? ''); ?>"
                           placeholder="Optional">
                </div>

                <div class="col-md-2">
                    <label class="form-label">نوع المكان</label>
                    <select name="type" class="form-select">
                        <?php
                        $currentType = $data['type'] ?? 'College';
                        $types = [
                            'College'  => 'كلية / فرع رئيسي',
                            'Building' => 'مبنى',
                            'Floor'    => 'طابق',
                            'Lab'      => 'معمل',
                            'Office'   => 'مكتب',
                            'Store'    => 'مستودع',
                        ];
                        foreach ($types as $value => $label):
                        ?>
                            <option value="<?php echo $value; ?>" <?php echo ($currentType === $value ? 'selected' : ''); ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">يتبع لـ (الموقع الأب)</label>
                    <select name="parent_id" class="form-select">
                        <option value="">-- الكيان هو أعلى مستوى --</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo $loc->id; ?>"
                                <?php echo (!empty($data['parent_id']) && $data['parent_id'] == $loc->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc->name_ar) . ' - ' . $loc->type; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        مثال: المبنى يتبع للكلية، المعمل يتبع للمبنى... إلخ.
                    </div>
                </div>

                <?php if (!empty($data['name_err'])): ?>
                    <div class="col-12">
                        <div class="alert alert-danger mb-0">
                            <?php echo htmlspecialchars($data['name_err']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-12">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> حفظ الموقع
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- عرض الهيكل الحالي (هرمي) -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">
                <i class="fa fa-layer-group text-primary"></i>
                الهيكل الحالي
            </span>
        </div>
        <div class="card-body">

            <?php if (empty($roots)): ?>
                <p class="text-muted mb-0">
                    لا توجد أي مواقع مضافة حالياً. ابدأ بإضافة الكلية الأولى من النموذج أعلاه.
                </p>
            <?php else: ?>

                <?php foreach ($roots as $root): ?>
                    <?php $children = $byParent[$root->id] ?? []; ?>

                    <div class="mb-3 border rounded">
                        <!-- الكلية / الفرع -->
                        <div class="d-flex justify-content-between align-items-center p-3 bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-university text-primary"></i>
                                <strong><?php echo htmlspecialchars($root->name_ar); ?></strong>
                                <span class="badge bg-secondary">
                                    <?php
                                    switch ($root->type) {
                                        case 'College':  echo 'كلية'; break;
                                        case 'Branch':   echo 'فرع رئيسي'; break;
                                        default:         echo $root->type;
                                    }
                                    ?>
                                </span>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <a href="<?php echo URLROOT; ?>/index.php?page=locations/edit&id=<?php echo $root->id; ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="<?php echo URLROOT; ?>/index.php?page=locations/delete&id=<?php echo $root->id; ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('هل أنت متأكد من حذف هذا الموقع وكل المواقع التابعة له (إن وجدت)؟');">
                                    <i class="fa fa-trash"></i> حذف الكلية
                                </a>
                            </div>
                        </div>

                        <!-- الأبناء المباشرين (مباني / معامل / مكاتب...) -->
                        <div class="p-3">

                            <?php if (empty($children)): ?>
                                <p class="text-muted mb-0">لا توجد مواقع فرعية.</p>
                            <?php else: ?>

                                <?php foreach ($children as $child): ?>
                                    <?php $grandChildren = $byParent[$child->id] ?? []; ?>

                                    <div class="border rounded p-2 mb-2 ms-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($child->name_ar); ?></strong>
                                                <span class="badge bg-info text-dark ms-2">
                                                    <?php
                                                    switch ($child->type) {
                                                        case 'Building': echo 'مبنى'; break;
                                                        case 'Floor':    echo 'طابق'; break;
                                                        case 'Lab':      echo 'معمل'; break;
                                                        case 'Office':   echo 'مكتب'; break;
                                                        case 'Store':    echo 'مستودع'; break;
                                                        default:         echo $child->type;
                                                    }
                                                    ?>
                                                </span>
                                            </div>

                                            <div class="d-flex gap-2">
                                                <a href="<?php echo URLROOT; ?>/index.php?page=locations/edit&id=<?php echo $child->id; ?>"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="<?php echo URLROOT; ?>/index.php?page=locations/delete&id=<?php echo $child->id; ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('هل أنت متأكد من حذف هذا الموقع؟');">
                                                    <i class="fa fa-trash"></i> حذف
                                                </a>
                                            </div>
                                        </div>

                                        <?php if (!empty($grandChildren)): ?>
                                            <ul class="mt-2 mb-0">
                                                <?php foreach ($grandChildren as $g): ?>
                                                    <li>
                                                        <?php echo htmlspecialchars($g->name_ar); ?>
                                                        <span class="badge bg-light text-muted">
                                                            <?php echo $g->type; ?>
                                                        </span>
                                                        <a href="<?php echo URLROOT; ?>/index.php?page=locations/edit&id=<?php echo $g->id; ?>"
                                                           class="text-primary ms-2">
                                                            <i class="fa fa-pen"></i>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </div>

</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
