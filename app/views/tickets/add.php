<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">

        <a href="<?php echo URLROOT; ?>/index.php?page=tickets/index" class="btn btn-light mb-3">
            <i class="fa fa-arrow-right"></i> عودة لقائمة التذاكر
        </a>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fa fa-ticket-alt"></i> فتح تذكرة دعم فني جديدة</h5>
            </div>

            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/index.php?page=tickets/add" method="post">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">تاريخ الطلب</label>
                            <input type="text" class="form-control bg-light" value="<?php echo date('Y-m-d H:i'); ?>" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">معلومات الاتصال <span class="text-danger">*</span></label>
                            <input type="text" name="contact_info" class="form-control"
                                   value="<?php echo htmlspecialchars($data['contact_info'] ?? ''); ?>"
                                   placeholder="رقم الجوال أو التحويلة" required>
                            <div class="form-text">لسرعة التواصل معك في حال عدم تواجدك بالمكتب.</div>
                        </div>
                    </div>

                    <?php if(function_exists('isSuperAdmin') && (isSuperAdmin() || isManager())): ?>
                        <!-- (للأدمن/المدير) فتح التذكرة لموظف -->
                        <div class="mb-3">
                            <label class="form-label">فتح التذكرة لـ (اختياري)</label>
                            <select name="requested_for_user_id" class="form-select">
                                <option value="">-- لنفسي (افتراضي) --</option>
                                <?php if(!empty($data['team_users'])): ?>
                                    <?php foreach($data['team_users'] as $u): ?>
                                        <option value="<?php echo (int)$u->id; ?>">
                                            <?php echo htmlspecialchars(isset($u->username) ? $u->username : $u->email); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">يستخدمها المدير لفتح تذكرة لموظف من فريقه.</div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">القسم / التصنيف</label>
                            <select name="team" class="form-select">
                                <option value="field_it" selected>الدعم الميداني (IT)</option>
                                <option value="network">الشبكات</option>
                                <option value="security">الأمن السيبراني</option>
                                <option value="electricity">الكهرباء</option>
                            </select>
                            <div class="form-text">اختر القسم الأقرب للمشكلة (يمكن التصعيد لاحقًا).</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">الأولوية / الأهمية</label>
                            <select name="priority" class="form-select">
                                <option value="Low">منخفضة (استفسار عام)</option>
                                <option value="Medium" selected>متوسطة (عطل غير معطل للعمل)</option>
                                <option value="High">عالية (عطل يؤثر على العمل)</option>
                                <option value="Critical">حرجة (توقف تام عن العمل)</option>
                            </select>
                        </div>
                    </div>

                    <!-- العنوان (كان ناقص) -->
                    <div class="mb-3">
                        <label class="form-label">عنوان المشكلة <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control"
                               value="<?php echo htmlspecialchars($data['subject'] ?? ''); ?>"
                               placeholder="مثال: جهاز معمل 12 لا يعمل / انقطاع شبكة / تعطل بروجكتر" required>
                        <?php if(!empty($data['subject_err'])): ?>
                            <small class="text-danger"><?php echo htmlspecialchars($data['subject_err']); ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الجهاز المرتبط (اختياري)</label>
                            <select name="asset_id" class="form-select">
                                <option value="">-- لا يوجد / مشكلة عامة --</option>

                                <?php if(!empty($data['assets'])): ?>
                                    <?php foreach($data['assets'] as $asset): ?>
                                        <option value="<?php echo (int)$asset->id; ?>">
                                            <?php echo htmlspecialchars($asset->asset_tag . ' - ' . $asset->brand . ' ' . $asset->model); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">اتركه فارغاً إذا كانت المشكلة بالإنترنت أو الكهرباء مثلاً.</div>
                        </div>

                        <?php if(function_exists('isSuperAdmin') && (isSuperAdmin() || isManager())): ?>
                            <!-- (اختياري) تعيين مباشر -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">تعيين التذكرة لموظف (اختياري)</label>
                                <select name="assigned_to" class="form-select">
                                    <option value="">-- بدون تعيين الآن --</option>
                                    <?php if(!empty($data['team_users'])): ?>
                                        <?php foreach($data['team_users'] as $u): ?>
                                            <option value="<?php echo (int)$u->id; ?>">
                                                <?php echo htmlspecialchars(isset($u->username) ? $u->username : $u->email); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">لو تبغى مباشرة تحدد المسؤول عن التذكرة.</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">وصف المشكلة <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="5" placeholder="اشرح المشكلة بالتفصيل..." required><?php echo htmlspecialchars($data['description'] ?? ''); ?></textarea>
                        <?php if(!empty($data['description_err'])): ?>
                            <small class="text-danger"><?php echo htmlspecialchars($data['description_err']); ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-paper-plane"></i> إرسال التذكرة
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
