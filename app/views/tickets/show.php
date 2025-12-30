<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="fa fa-ticket-alt text-primary"></i> تفاصيل التذكرة #<?php echo (int)$data['ticket']->id; ?></h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo URLROOT; ?>/index.php?page=tickets/index" class="btn btn-secondary">
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
                    <label class="text-muted">العنوان:</label>
                    <p class="fw-bold fs-5 mb-0"><?php echo htmlspecialchars($data['ticket']->subject ?? ''); ?></p>
                </div>

                <div class="mb-3">
                    <label class="text-muted">الوصف:</label>
                    <p class="fs-6 p-3 bg-light rounded border mb-0">
                        <?php echo nl2br(htmlspecialchars($data['ticket']->description ?? '')); ?>
                    </p>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="text-muted">معلومات التواصل:</label>
                        <p class="fw-bold mb-0">
                            <?php echo htmlspecialchars($data['ticket']->contact_info ?? '-'); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted">تاريخ الإنشاء:</label>
                        <p class="fw-bold mb-0">
                            <?php echo htmlspecialchars($data['ticket']->created_at ?? '-'); ?>
                        </p>
                    </div>
                </div>

                <?php if(!empty($data['ticket']->asset_id)): ?>
                    <hr>
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fa fa-laptop me-2 fs-4"></i>
                        <div>
                            <strong>جهاز مرتبط:</strong>
                            (ID: <?php echo (int)$data['ticket']->asset_id; ?>)
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(!empty($data['updates'])): ?>
                    <hr>
                    <h5 class="mb-3"><i class="fa fa-history"></i> سجل التحديثات</h5>
                    <ul class="list-group">
                        <?php foreach($data['updates'] as $u): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold"><?php echo htmlspecialchars($u->status ?? ''); ?></span>
                                    <small class="text-muted"><?php echo htmlspecialchars($u->created_at ?? ''); ?></small>
                                </div>
                                <?php if(!empty($u->comment)): ?>
                                    <div class="mt-2"><?php echo nl2br(htmlspecialchars($u->comment)); ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if(!empty($data['attachments'])): ?>
                    <hr>
                    <h5 class="mb-3"><i class="fa fa-images"></i> المرفقات</h5>
                    <div class="row g-2">
                        <?php foreach($data['attachments'] as $a): ?>
                            <div class="col-6 col-md-4">
                                <a href="<?php echo URLROOT . '/public/' . htmlspecialchars($a->file_path); ?>" target="_blank">
                                    <img src="<?php echo URLROOT . '/public/' . htmlspecialchars($a->file_path); ?>" class="img-fluid rounded border" alt="attachment">
                                </a>
                            </div>
                        <?php endforeach; ?>
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
                    $status = $data['ticket']->status ?? '';
                    $badgeClass = 'bg-secondary';
                    if($status == 'Open') $badgeClass = 'bg-success';
                    if($status == 'Closed') $badgeClass = 'bg-dark';
                    if($status == 'In Progress') $badgeClass = 'bg-warning text-dark';
                    if($status == 'Resolved') $badgeClass = 'bg-info text-dark';
                ?>
                <span class="badge <?php echo $badgeClass; ?> fs-5 rounded-pill px-4">
                    <?php echo htmlspecialchars($status); ?>
                </span>

                <h6 class="text-muted mt-3">الأولوية</h6>
                <span class="badge bg-danger"><?php echo htmlspecialchars($data['ticket']->priority ?? ''); ?></span>
            </div>
        </div>

        <!-- تحديث الحالة + (اختياري) تعليق -->
        <div class="card shadow-sm border-primary mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fa fa-edit"></i> تحديث التذكرة</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/index.php?page=tickets/update_status" method="post">
                    <input type="hidden" name="ticket_id" value="<?php echo (int)$data['ticket']->id; ?>">

                    <?php if(!empty($data['users'])): ?>
                        <div class="mb-3">
                            <label class="form-label">تعيين إلى موظف:</label>
                            <select name="assigned_to" class="form-select" disabled>
                                <option value="">-- (لاحقاً) --</option>
                            </select>
                            <small class="text-muted">التعيين سنفعّله بعد ما نضيفه في الموديل/الداتابيس بشكل كامل.</small>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">تغيير الحالة:</label>
                        <select name="status" class="form-select">
                            <option value="Open" <?php echo ($status == 'Open') ? 'selected' : ''; ?>>مفتوحة (Open)</option>
                            <option value="In Progress" <?php echo ($status == 'In Progress') ? 'selected' : ''; ?>>جاري العمل (In Progress)</option>
                            <option value="Resolved" <?php echo ($status == 'Resolved') ? 'selected' : ''; ?>>تم الحل (Resolved)</option>
                            <option value="Closed" <?php echo ($status == 'Closed') ? 'selected' : ''; ?>>مغلقة (Closed)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">تعليق (اختياري):</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="مثال: بدأت العمل، تم استبدال كابل، تم اختبار الجهاز..."></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-save"></i> حفظ التغييرات
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- التصعيد -->
        <div class="card shadow-sm border-warning mb-3">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fa fa-level-up-alt"></i> تصعيد التذكرة</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/index.php?page=tickets/escalate" method="post">
                    <input type="hidden" name="ticket_id" value="<?php echo (int)$data['ticket']->id; ?>">

                    <div class="mb-3">
                        <label class="form-label">التصعيد إلى:</label>
                        <select name="team" class="form-select" required>
                            <option value="">-- اختر القسم --</option>
                            <option value="network">الشبكات</option>
                            <option value="security">الأمن السيبراني</option>
                            <option value="electricity">الكهرباء</option>
                            <option value="field_it">الدعم الميداني (IT)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">سبب/ملاحظة (اختياري):</label>
                        <textarea name="comment" class="form-control" rows="2" placeholder="مثال: المشكلة من السويتش، تحتاج فريق الشبكات..."></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-paper-plane"></i> تصعيد
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- رفع صور -->
        <div class="card shadow-sm border-secondary">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fa fa-upload"></i> رفع صور</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/index.php?page=tickets/upload" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="ticket_id" value="<?php echo (int)$data['ticket']->id; ?>">

                    <div class="mb-3">
                        <input type="file" name="images[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
                        <small class="text-muted">مسموح: jpg, png, webp</small>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fa fa-cloud-upload-alt"></i> رفع
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
