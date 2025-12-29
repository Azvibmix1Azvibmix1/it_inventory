<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="fa fa-ticket-alt text-primary"></i> تفاصيل التذكرة #<?php echo $data['ticket']->id; ?></h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo URLROOT; ?>/index.php?page=tickets" class="btn btn-secondary">
            <i class="fa fa-arrow-right"></i> عودة للقائمة
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">وصف المشكلة</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted">الوصف:</label>
                    <p class="fs-5 p-3 bg-light rounded border">
                        <?php echo nl2br($data['ticket']->description); ?>
                    </p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="text-muted">معلومات التواصل:</label>
                        <p class="fw-bold"><?php echo $data['ticket']->contact_info; ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted">تاريخ الإنشاء:</label>
                        <p class="fw-bold"><?php echo $data['ticket']->created_at; ?></p>
                    </div>
                </div>

                <?php if(!empty($data['ticket']->asset_id)): ?>
                <hr>
                <div class="alert alert-info d-flex align-items-center">
                    <i class="fa fa-laptop me-2 fs-4"></i>
                    <div>
                        <strong>جهاز مرتبط:</strong> 
                        (ID: <?php echo $data['ticket']->asset_id; ?>)
                        </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm mb-3 text-center">
            <div class="card-body">
                <h6 class="text-muted">الحالة الحالية</h6>
                <?php 
                    $badgeClass = 'bg-secondary';
                    if($data['ticket']->status == 'Open') $badgeClass = 'bg-success';
                    if($data['ticket']->status == 'Closed') $badgeClass = 'bg-dark';
                    if($data['ticket']->status == 'In Progress') $badgeClass = 'bg-warning text-dark';
                ?>
                <span class="badge <?php echo $badgeClass; ?> fs-5 rounded-pill px-4">
                    <?php echo $data['ticket']->status; ?>
                </span>

                <h6 class="text-muted mt-3">الأولوية</h6>
                <span class="badge bg-danger"><?php echo $data['ticket']->priority; ?></span>
            </div>
        </div>

        <div class="card shadow-sm border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fa fa-edit"></i> تحديث التذكرة</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/index.php?page=tickets/update&id=<?php echo $data['ticket']->id; ?>" method="post">
                    
                    <div class="mb-3">
                        <label class="form-label">تعيين إلى موظف:</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">-- اختر موظف --</option>
                            <?php if(!empty($data['users'])): ?>
                                <?php foreach($data['users'] as $user): ?>
                                    <option value="<?php echo $user->id; ?>" <?php echo ($data['ticket']->assigned_to == $user->id) ? 'selected' : ''; ?>>
                                        <?php echo isset($user->username) ? $user->username : $user->email; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">تغيير الحالة:</label>
                        <select name="status" class="form-select">
                            <option value="Open" <?php echo ($data['ticket']->status == 'Open') ? 'selected' : ''; ?>>مفتوحة (Open)</option>
                            <option value="In Progress" <?php echo ($data['ticket']->status == 'In Progress') ? 'selected' : ''; ?>>جاري العمل (In Progress)</option>
                            <option value="Resolved" <?php echo ($data['ticket']->status == 'Resolved') ? 'selected' : ''; ?>>تم الحل (Resolved)</option>
                            <option value="Closed" <?php echo ($data['ticket']->status == 'Closed') ? 'selected' : ''; ?>>مغلقة (Closed)</option>
                        </select>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-save"></i> حفظ التغييرات
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>