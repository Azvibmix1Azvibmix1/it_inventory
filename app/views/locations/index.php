<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container mt-4">

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

    <?php if (flash('location_msg')): ?>
        <!-- الرسالة تظهر من session_helper إن كنت مسويها ترجع القيمة -->
    <?php endif; ?>

    <!-- إضافة موقع جديد -->
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
                           placeholder="مثال: كلية الحاسب"
                           required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">الاسم (إنجليزي)</label>
                    <input type="text"
                           name="name_en"
                           class="form-control"
                           placeholder="Optional">
                </div>

                <div class="col-md-2">
                    <label class="form-label">نوع المكان</label>
                    <select name="type" class="form-select">
                        <option value="College">كلية</option>
                        <option value="Branch">فرع رئيسي</option>
                        <option value="Building">مبنى</option>
                        <option value="Floor">طابق</option>
                        <option value="Lab">معمل</option>
                        <option value="Office">مكتب</option>
                        <option value="Store">مستودع</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">يتبع لـ (الموقع الأب)</label>
                    <select name="parent_id" class="form-select">
                        <option value="">-- الكيان هو أعلى مستوى --</option>
                        <?php if (!empty($data['locations'])): ?>
                            <?php foreach ($data['locations'] as $loc): ?>
                                <option value="<?php echo $loc->id; ?>">
                                    <?php echo $loc->name_ar . ' - ' . $loc->type; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-text">
                        مثال: المبنى يتبع للكلية، المعمل يتبع للمبنى... إلخ.
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> حفظ الموقع
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- جدول المواقع -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <span class="fw-bold">
                <i class="fa fa-list-ul text-primary"></i>
                قائمة المواقع الحالية
            </span>
        </div>
        <div class="card-body">
            <?php if (!empty($data['locations'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
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
                            <?php foreach ($data['locations'] as $loc): ?>
                                <tr>
                                    <td><?php echo $loc->id; ?></td>
                                    <td><?php echo htmlspecialchars($loc->name_ar); ?></td>
                                    <td><?php echo htmlspecialchars($loc->name_en); ?></td>
                                    <td>
                                        <?php
                                            switch ($loc->type) {
                                                case 'College':  echo 'كلية'; break;
                                                case 'Branch':   echo 'فرع رئيسي'; break;
                                                case 'Building': echo 'مبنى'; break;
                                                case 'Floor':    echo 'طابق'; break;
                                                case 'Lab':      echo 'معمل'; break;
                                                case 'Office':   echo 'مكتب'; break;
                                                case 'Store':    echo 'مستودع'; break;
                                                default:         echo $loc->type;
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            // نطالع اسم الأب من نفس المصفوفة
                                            $parentName = '—';
                                            if (!empty($loc->parent_id)) {
                                                foreach ($data['locations'] as $parent) {
                                                    if ($parent->id == $loc->parent_id) {
                                                        $parentName = $parent->name_ar;
                                                        break;
                                                    }
                                                }
                                            }
                                            echo $parentName;
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
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">
                    لا توجد أي مواقع مضافة حالياً. ابدأ بإضافة الكلية الأولى من النموذج أعلاه.
                </p>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
