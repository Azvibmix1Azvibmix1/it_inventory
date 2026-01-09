<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row justify-content-center">
  <div class="col-md-9">

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
            <div class="col-md-4 mb-3">
              <label class="form-label">تاريخ الطلب</label>
              <input type="text" class="form-control bg-light" value="<?php echo date('Y-m-d H:i'); ?>" readonly>
            </div>

            <div class="col-md-8 mb-3">
              <label class="form-label">معلومات الاتصال <span class="text-danger">*</span></label>
              <input
                type="text"
                name="contact_info"
                class="form-control <?php echo (!empty($data['contact_err'])) ? 'is-invalid' : ''; ?>"
                placeholder="رقم الجوال أو التحويلة"
                value="<?php echo htmlspecialchars($data['contact_info'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                required
              >
              <?php if(!empty($data['contact_err'])): ?>
                <div class="invalid-feedback"><?php echo $data['contact_err']; ?></div>
              <?php else: ?>
                <div class="form-text">لسرعة التواصل معك في حال عدم تواجدك بالمكتب.</div>
              <?php endif; ?>
            </div>
          </div>

          <?php
            $canAssign = (function_exists('isSuperAdmin') && isSuperAdmin()) || (function_exists('isManager') && isManager());
          ?>

          <?php if($canAssign && !empty($data['users'])): ?>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">فتح التذكرة لـ (اختياري)</label>
                <select name="requested_for_user_id" class="form-select">
                  <option value="">-- لنفسي (افتراضي) --</option>
                  <?php foreach($data['users'] as $u): ?>
                    <option value="<?php echo (int)$u->id; ?>">
                     <?php echo htmlspecialchars($u->name ?? ($u->username ?? $u->email ?? ''), ENT_QUOTES, 'UTF-8'); ?>

                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text">المدير يقدر يفتح تذكرة لموظف من فريقه.</div>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">تعيين التذكرة لموظف (اختياري)</label>
                <select name="assigned_to" class="form-select">
                  <option value="">-- بدون تعيين الآن --</option>
                  <?php foreach($data['users'] as $u): ?>
                    <option value="<?php echo (int)$u->id; ?>">
                      <?php echo htmlspecialchars($u->name ?? ($u->username ?? $u->email ?? ''), ENT_QUOTES, 'UTF-8'); ?>

                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text">لو تبغى تحدد المسؤول مباشرة.</div>
              </div>
            </div>
          <?php endif; ?>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">القسم / التصنيف</label>
              <select name="team" class="form-select">
                <option value="IT" selected>الدعم الميداني (IT)</option>
                <option value="network">الشبكات</option>
                <option value="security">الأمن السيبراني</option>
                <option value="electricity">الكهرباء</option>
              </select>
              <div class="form-text">اختر الأقرب للمشكلة (يمكن التصعيد لاحقًا).</div>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">الأولوية / الأهمية</label>
              <select name="priority" class="form-select">
                <option value="Low">منخفضة</option>
                <option value="Medium" selected>متوسطة</option>
                <option value="High">عالية</option>
                <option value="Critical">حرجة</option>
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">الجهاز المرتبط (اختياري)</label>
              <select name="asset_id" class="form-select">
                <option value="">-- لا يوجد / مشكلة عامة --</option>
                <?php if(!empty($data['assets'])): ?>
                  <?php foreach($data['assets'] as $asset): ?>
                    <option value="<?php echo (int)$asset->id; ?>">
                      <?php
                        echo htmlspecialchars(
                          ($asset->asset_tag ?? '') . ' - ' . ($asset->brand ?? '') . ' ' . ($asset->model ?? ''),
                          ENT_QUOTES, 'UTF-8'
                        );
                      ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">عنوان المشكلة <span class="text-danger">*</span></label>
            <input
              type="text"
              name="subject"
              class="form-control <?php echo (!empty($data['subject_err'])) ? 'is-invalid' : ''; ?>"
              value="<?php echo htmlspecialchars($data['subject'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              required
            >
            <?php if(!empty($data['subject_err'])): ?>
              <div class="invalid-feedback"><?php echo $data['subject_err']; ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label">وصف المشكلة <span class="text-danger">*</span></label>
            <textarea
              name="description"
              class="form-control <?php echo (!empty($data['description_err'])) ? 'is-invalid' : ''; ?>"
              rows="5"
              required >
           <?php echo htmlspecialchars($data['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

            <?php if(!empty($data['description_err'])): ?>
              <div class="invalid-feedback"><?php echo $data['description_err']; ?></div>
            <?php endif; ?>
          </div>

          <div class="d-grid">
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
