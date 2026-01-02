<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container-fluid mt-4">

    <!-- عنوان الصفحة + رجوع -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">
                <i class="fa fa-sitemap text-primary"></i>
                تعديل الموقع / الهيكل
            </h3>
            <small class="text-muted">
                عدّل بيانات الكلية / المبنى / المعمل، وحدّد من يملك صلاحية إدارته.
            </small>
        </div>

        <div class="text-end">
            <a href="<?php echo URLROOT; ?>/index.php?page=locations/index" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-right"></i> رجوع للهيكل
            </a>
        </div>
    </div>

    <!-- تنبيه: اسم الموقع -->
    <div class="alert alert-info d-flex align-items-center">
        <i class="fa fa-map-marker-alt fa-lg ms-2"></i>
        <div>
            أنت تعدّل: 
            <strong>
                <?php echo isset($data['location']->name_ar) ? htmlspecialchars($data['location']->name_ar) : 'موقع غير معروف'; ?>
            </strong>
            <span class="badge bg-light text-dark border ms-2">
                نوع: <?php echo isset($data['location']->type) ? htmlspecialchars($data['location']->type) : '--'; ?>
            </span>
        </div>
    </div>

    <form action="<?php echo URLROOT; ?>/index.php?page=locations/edit&id=<?php echo $data['location']->id ?? 0; ?>" method="post">

        <div class="row">
            <!-- 🟦 الكرت 1: بيانات الموقع الأساسية -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span><i class="fa fa-info-circle"></i> بيانات الموقع</span>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">الاسم (عربي) <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="name_ar"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($data['location']->name_ar ?? ''); ?>"
                                   placeholder="مثال: مبنى الحاسب، معمل 101">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الاسم (إنجليزي) <span class="text-muted">(اختياري)</span></label>
                            <input type="text"
                                   name="name_en"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($data['location']->name_en ?? ''); ?>"
                                   placeholder="Ex: IT Building A">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">نوع المكان</label>
                                <select name="type" class="form-select">
                                    <?php
                                    $currentType = $data['location']->type ?? 'College';
                                    $types = [
                                        'College' => 'كلية / فرع رئيسي',
                                        'Building' => 'مبنى',
                                        'Lab' => 'معمل',
                                        'Office' => 'مكتب',
                                        'Store' => 'مخزن',
                                    ];
                                    foreach($types as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($currentType == $key) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">يتبع لـ (الموقع الأب)</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">الكلية هي أعلى مستوى</option>
                                    <?php if (!empty($data['parents'])): ?>
                                        <?php foreach($data['parents'] as $parent): ?>
                                            <?php if (isset($data['location']->id) && $parent->id == $data['location']->id) continue; ?>
                                            <option value="<?php echo $parent->id; ?>"
                                                <?php echo (!empty($data['location']->parent_id) && $data['location']->parent_id == $parent->id) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($parent->name_ar); ?> 
                                                (<?php echo htmlspecialchars($parent->type); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">
                                    مثال: المبنى يتبع الكلية، المعمل يتبع المبنى.
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-2">
                            <button type="submit" name="save_basic" class="btn btn-success">
                                <i class="fa fa-save"></i> حفظ بيانات الموقع
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🟦 الكرت 2: صلاحيات الموقع (للسوبر أدمن) -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <span><i class="fa fa-user-shield"></i> صلاحيات إدارة هذا الموقع</span>
                        <span class="badge bg-warning text-dark">
                            تحكم السوبر أدمن فقط (شكل مبدئي)
                        </span>
                    </div>
                    <div class="card-body">

                        <p class="text-muted small mb-3">
                            هنا تختار من يحق له إضافة / تعديل المواقع داخل هذه الكلية أو المبنى.
                            (هذا الجزء واجهة فقط الآن، سنربطه بقاعدة البيانات لاحقاً).
                        </p>

                        <div class="mb-3">
                            <label class="form-label fw-bold mb-2">صلاحيات حسب الدور (Role)</label>

                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="perm_super_admin"
                                       id="perm_super_admin" checked>
                                <label class="form-check-label" for="perm_super_admin">
                                    السماح للسوبر أدمن بالتحكم الكامل في هذا الموقع
                                </label>
                            </div>

                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="perm_manager"
                                       id="perm_manager" checked>
                                <label class="form-check-label" for="perm_manager">
                                    السماح لمدراء الأقسام بإضافة/تعديل المباني والمعامل التابعة
                                </label>
                            </div>

                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="perm_user"
                                       id="perm_user">
                                <label class="form-check-label" for="perm_user">
                                    السماح للموظف العادي بإضافة معامل / مكاتب في هذا الموقع
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label fw-bold">إضافة صلاحية لمستخدم معيّن</label>
                            <select name="special_user_id" class="form-select mb-2">
                                <option value="">-- اختر مستخدم (اختياري) --</option>
                                <?php if (!empty($data['users'])): ?>
                                    <?php foreach($data['users'] as $user): ?>
                                        <option value="<?php echo $user->id; ?>">
                                            <?php echo htmlspecialchars($user->name ?? $user->email); ?>
                                            (<?php echo htmlspecialchars($user->role); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary btn-sm">
                                <i class="fa fa-plus"></i> إضافة صلاحية مخصصة (واجهة فقط الآن)
                            </button>
                            <div class="form-text">
                                لاحقاً سنحفظ هذه الصلاحيات في جدول خاص (locations_permissions مثلاً).
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" name="save_permissions" class="btn btn-primary">
                                <i class="fa fa-lock"></i> حفظ إعدادات الصلاحيات
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🟦 الكرت 3: الأبناء (المواقع التابعة) -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span>
                    <i class="fa fa-layer-group text-primary"></i>
                    المواقع التابعة لهذا الموقع
                </span>
                <button type="button" class="btn btn-sm btn-outline-success" disabled>
                    <i class="fa fa-plus"></i> إضافة فرع / معمل (واجهة فقط الآن)
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($data['children'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>الاسم العربي</th>
                                    <th>النوع</th>
                                    <th>يتبع لـ</th>
                                    <th class="text-center">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['children'] as $child): ?>
                                    <tr>
                                        <td><?php echo $child->id; ?></td>
                                        <td><?php echo htmlspecialchars($child->name_ar); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($child->type); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($data['location']->name_ar ?? '—'); ?></td>
                                        <td class="text-center">
                                            <a href="<?php echo URLROOT; ?>/index.php?page=locations/edit&id=<?php echo $child->id; ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" disabled>
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        لا توجد مواقع تابعة لهذا الموقع حالياً.
                        <br>
                        <small>لاحقاً نفعّل زر "إضافة فرع" لربطه بقاعدة البيانات.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 🟦 الكرت 4: سجل التعديلات (Placeholder) -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <i class="fa fa-history text-primary"></i> سجل آخر التعديلات (شكلي فقط الآن)
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">
                    هنا لاحقاً نعرض:
                    <ul class="text-muted small">
                        <li>من عدّل هذا الموقع؟</li>
                        <li>ما التغييرات (اسم / نوع / صلاحيات)؟</li>
                        <li>وقت و تاريخ التعديل.</li>
                    </ul>
                    حالياً هذا مجرد تصميم لتوضيح الفكرة.
                </p>
            </div>
        </div>

        <!-- زر حفظ شامل تحت الصفحة -->
        <div class="text-end mb-5">
            <button type="submit" name="save_all" class="btn btn-success btn-lg">
                <i class="fa fa-save"></i> حفظ كل التغييرات
            </button>
        </div>

    </form>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
