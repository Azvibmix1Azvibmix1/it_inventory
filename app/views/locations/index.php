<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
// تجهيز البيانات القادمة من الكنترولر
$locations   = $data['locations'] ?? [];
$name_ar     = $data['name_ar'] ?? '';
$name_en     = $data['name_en'] ?? '';
$type        = $data['type'] ?? 'College';
$parent_id   = $data['parent_id'] ?? '';
$name_err    = $data['name_err'] ?? '';

// نبني هيكل parent -> children
$byParent = [];
foreach ($locations as $loc) {
    $pid = $loc->parent_id ?? 0; // NULL نعتبره مستوى أعلى
    if (!isset($byParent[$pid])) {
        $byParent[$pid] = [];
    }
    $byParent[$pid][] = $loc;
}

// دالة عرض شجرة المواقع داخل الكلية/المبنى
if (!function_exists('renderLocationTree')) {
    function renderLocationTree($parentId, $byParent, $level = 0) {
        if (!isset($byParent[$parentId])) {
            return;
        }

        echo '<ul class="list-unstyled ms-' . ($level > 0 ? 4 : 0) . ' mt-2">';

        foreach ($byParent[$parentId] as $loc) {
            echo '<li class="mb-2">';
            echo '<div class="d-flex align-items-center justify-content-between bg-white border rounded p-2">';

            // معلومات الموقع
            echo '<div>';
            echo '<span class="fw-bold">' . htmlspecialchars($loc->name_ar) . '</span>';
            echo ' <span class="badge bg-secondary ms-2">' . htmlspecialchars($loc->type) . '</span>';
            if (!empty($loc->name_en)) {
                echo '<small class="text-muted ms-2">' . htmlspecialchars($loc->name_en) . '</small>';
            }
            echo '</div>';

            // أزرار الإجراءات
            echo '<div class="ms-2">';
            echo '<a href="' . URLROOT . '/index.php?page=locations/edit&id=' . $loc->id . '" ';
            echo 'class="btn btn-sm btn-outline-primary me-1"><i class="fa fa-edit"></i></a>';

            echo '<a href="' . URLROOT . '/index.php?page=locations/delete&id=' . $loc->id . '" ';
            echo 'class="btn btn-sm btn-outline-danger" ';
            echo 'onclick="return confirm(\'هل أنت متأكد من حذف هذا الموقع؟ قد يتم حذف المواقع التابعة له أيضاً.\');">';
            echo '<i class="fa fa-trash"></i></a>';

            echo '</div>'; // /actions
            echo '</div>'; // /card row

            // لو عنده أبناء، نعرضهم بشكل متدرج تحته
            renderLocationTree($loc->id, $byParent, $level + 1);

            echo '</li>';
        }

        echo '</ul>';
    }
}
?>

<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">
            <i class="fa fa-sitemap text-primary"></i>
            إدارة الهيكل التنظيمي (كليات - مباني - معامل)
        </h2>
    </div>

    <?php flash('location_msg'); ?>
    <?php flash('access_denied'); ?>

    <div class="row">
        <!-- نموذج إضافة موقع جديد -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-plus-circle"></i>
                        إضافة موقع جديد للنظام
                    </h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo URLROOT; ?>/index.php?page=locations/add" method="post">

                        <!-- الاسم العربي -->
                        <div class="mb-3">
                            <label class="form-label">الاسم (عربي) <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="name_ar"
                                   class="form-control <?php echo !empty($name_err) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo htmlspecialchars($name_ar); ?>"
                                   placeholder="مثال: كلية الحاسب، أو مبنى الشبكات، أو معمل 101">
                            <?php if (!empty($name_err)): ?>
                                <div class="invalid-feedback">
                                    <?php echo $name_err; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- الاسم الإنجليزي (اختياري) -->
                        <div class="mb-3">
                            <label class="form-label">الاسم (إنجليزي) <small class="text-muted">(اختياري)</small></label>
                            <input type="text"
                                   name="name_en"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($name_en); ?>"
                                   placeholder="Ex: Asfan Campus, Network Lab">
                        </div>

                        <!-- نوع المكان -->
                        <div class="mb-3">
                            <label class="form-label">نوع المكان</label>
                            <select name="type" class="form-select">
                                <option value="College"  <?php echo $type === 'College'  ? 'selected' : ''; ?>>كلية / فرع رئيسي</option>
                                <option value="Building" <?php echo $type === 'Building' ? 'selected' : ''; ?>>مبنى</option>
                                <option value="Floor"    <?php echo $type === 'Floor'    ? 'selected' : ''; ?>>طابق</option>
                                <option value="Lab"      <?php echo $type === 'Lab'      ? 'selected' : ''; ?>>معمل</option>
                                <option value="Office"   <?php echo $type === 'Office'   ? 'selected' : ''; ?>>مكتب</option>
                                <option value="Store"    <?php echo $type === 'Store'    ? 'selected' : ''; ?>>مستودع / مخزن</option>
                            </select>
                            <div class="form-text">
                                ابدأ بإضافة الكلية أو الفرع أولاً، ثم أضف المباني التابعة لها، ثم المعامل/المكاتب تحت كل مبنى.
                            </div>
                        </div>

                        <!-- الموقع الأب -->
                        <div class="mb-3">
                            <label class="form-label">يتبع لـ (الموقع الأب)</label>
                            <select name="parent_id" class="form-select">
                                <option value="">هذا فرع رئيسي (لا يتبع أحد)</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo $loc->id; ?>"
                                        <?php echo ($parent_id == $loc->id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc->name_ar); ?>
                                        (<?php echo htmlspecialchars($loc->type); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                مثال: اختر الكلية كأب للمبنى، واختر المبنى كأب للمعمل.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> حفظ الموقع
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- عرض الهيكل الحالي -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-layer-group text-primary"></i>
                        الهيكل الحالي
                    </h5>
                </div>
                <div class="card-body">

                    <?php
                    // الجذور (كليات / فروع عليا)
                    $roots = $byParent[0] ?? [];

                    if (empty($roots)) {
                        echo '<div class="alert alert-info mb-0">';
                        echo 'لا توجد أي مواقع مضافة حالياً، ابدأ بإضافة الكلية الأولى من النموذج أعلاه.';
                        echo '</div>';
                    } else {
                        foreach ($roots as $root) {
                            $accordionId = 'locAccordion' . $root->id;
                            $collapseId  = 'collapse' . $root->id;
                            $headingId   = 'heading' . $root->id;

                            echo '<div class="accordion mb-3" id="' . $accordionId . '">';
                            echo '  <div class="accordion-item">';
                            echo '    <h2 class="accordion-header" id="' . $headingId . '">';
                            echo '      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"';
                            echo '              data-bs-target="#' . $collapseId . '" aria-expanded="false" aria-controls="' . $collapseId . '">';
                            echo            htmlspecialchars($root->name_ar);
                            echo '        <span class="badge bg-info ms-2">' . htmlspecialchars($root->type) . '</span>';
                            if (!empty($root->name_en)) {
                                echo '    <small class="text-muted ms-2">' . htmlspecialchars($root->name_en) . '</small>';
                            }
                            echo '      </button>';
                            echo '    </h2>';

                            echo '    <div id="' . $collapseId . '" class="accordion-collapse collapse" aria-labelledby="' . $headingId . '">';
                            echo '      <div class="accordion-body">';

                            // أبناء هذا الجذر (مباني، معامل، ... إلخ)
                            renderLocationTree($root->id, $byParent, 0);

                            echo '      </div>';
                            echo '    </div>';
                            echo '  </div>';
                            echo '</div>';
                        }
                    }
                    ?>

                </div>
            </div>

            <!-- جدول بسيط بكل المواقع (للبحث السريع) -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fa fa-list"></i> قائمة المواقع الحالية</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle text-center">
                            <thead class="table-secondary">
                                <tr>
                                    <th>#</th>
                                    <th>الاسم العربي</th>
                                    <th>الاسم الإنجليزي</th>
                                    <th>النوع</th>
                                    <th>الموقع الأب</th>
                                    <th>تاريخ الإضافة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($locations)): ?>
                                    <?php foreach ($locations as $loc): ?>
                                        <tr>
                                            <td><?php echo $loc->id; ?></td>
                                            <td><?php echo htmlspecialchars($loc->name_ar); ?></td>
                                            <td><?php echo htmlspecialchars($loc->name_en); ?></td>
                                            <td><?php echo htmlspecialchars($loc->type); ?></td>
                                            <td>
                                                <?php
                                                if ($loc->parent_id && isset($byParent[0])) {
                                                    // نحاول إيجاد اسم الأب من نفس المصفوفة
                                                    $parentName = '';
                                                    foreach ($locations as $p) {
                                                        if ($p->id == $loc->parent_id) {
                                                            $parentName = $p->name_ar;
                                                            break;
                                                        }
                                                    }
                                                    echo $parentName ?: '—';
                                                } else {
                                                    echo '—';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo $loc->created_at; ?></td>
                                            <td>
                                                <a href="<?php echo URLROOT; ?>/index.php?page=locations/edit&id=<?php echo $loc->id; ?>"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="<?php echo URLROOT; ?>/index.php?page=locations/delete&id=<?php echo $loc->id; ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('هل أنت متأكد من حذف هذا الموقع؟');">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-muted py-3">
                                            لا توجد مواقع مسجلة حتى الآن.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
