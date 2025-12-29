<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        
        <a href="<?php echo URLROOT; ?>/index.php?page=tickets" class="btn btn-light mb-3">
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
                            <input type="text" name="contact_info" class="form-control" placeholder="رقم الجوال أو التحويلة" required>
                            <div class="form-text">لسرعة التواصل معك في حال عدم تواجدك بالمكتب.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الجهاز المرتبط (اختياري)</label>
                            <select name="asset_id" class="form-select">
                                <option value="">-- لا يوجد / مشكلة عامة --</option>
                                
                                <?php if(isset($data['assets']) && !empty($data['assets'])): ?>
                                    <?php foreach($data['assets'] as $asset): ?>
                                        <option value="<?php echo $asset->id; ?>">
                                            <?php echo $asset->asset_tag . ' - ' . $asset->brand . ' ' . $asset->model; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">اتركه فارغاً إذا كانت المشكلة بالإنترنت أو الكهرباء مثلاً.</div>
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

                    <div class="mb-3">
                        <label class="form-label">وصف المشكلة <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="5" placeholder="اشرح المشكلة بالتفصيل..." required></textarea>
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