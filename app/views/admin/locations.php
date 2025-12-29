<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row">
    
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="fa fa-map-marker-alt"></i> إضافة موقع جديد
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/index.php?page=locations/add" method="post">
                    
                    <div class="mb-3">
                        <label>الاسم (English)</label>
                        <input type="text" name="name_en" class="form-control" placeholder="e.g. College of Science" required>
                    </div>

                    <div class="mb-3">
                        <label>الاسم (عربي)</label>
                        <input type="text" name="name_ar" class="form-control text-end" placeholder="مثال: كلية العلوم" required>
                    </div>

                    <div class="mb-3">
                        <label>النوع</label>
                        <select name="type" class="form-select">
                            <option value="college">College (كلية)</option>
                            <option value="branch">Branch (فرع)</option>
                            <option value="building">Building (مبنى)</option>
                            <option value="lab">Lab (معمل)</option>
                            <option value="office">Office (مكتب)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>تابع لـ (الموقع الرئيسي)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- مستوى رئيسي --</option>
                            <?php if(!empty($data['locations'])): ?>
                                <?php foreach($data['locations'] as $loc): ?>
                                    <?php if($loc['type'] == 'college' || $loc['type'] == 'branch' || $loc['type'] == 'building'): ?>
                                        <option value="<?php echo $loc['id']; ?>">
                                            <?php echo $loc['name_en']; ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">حفظ الموقع</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        
        <?php flash('location_msg'); ?>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span><i class="fa fa-sitemap"></i> هيكلية المواقع</span>
                <span class="badge bg-light text-dark"><?php echo count($data['locations']); ?> مواقع</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>اسم الموقع</th>
                                <th>النوع</th>
                                <th>تابع لـ</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($data['locations'])): ?>
                                <?php foreach($data['locations'] as $loc): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $loc['name_en']; ?></strong><br>
                                        <small class="text-muted"><?php echo $loc['name_ar']; ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            // تلوين النص حسب النوع
                                            $badgeClass = 'bg-secondary';
                                            if($loc['type'] == 'college') $badgeClass = 'bg-primary';
                                            if($loc['type'] == 'lab') $badgeClass = 'bg-info text-dark';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($loc['type']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo !empty($loc['parent_name']) ? $loc['parent_name'] : '<span class="text-muted">-</span>'; ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo URLROOT; ?>/index.php?page=locations/edit&id=<?php echo $loc['id']; ?>" class="btn btn-sm btn-warning text-dark" title="تعديل">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            
                                            <a href="<?php echo URLROOT; ?>/index.php?page=locations/delete&id=<?php echo $loc['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('هل أنت متأكد من حذف هذا الموقع؟\nتحذير: لا يمكن حذف موقع يحتوي على أجهزة أو مواقع فرعية.');"
                                               title="حذف">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>

                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">لا توجد مواقع مضافة بعد.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>