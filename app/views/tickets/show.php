<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
$ticket = $data['ticket'];
$status = $ticket->status ?? '';
$badgeClass = 'bg-secondary';
if ($status === 'Open') $badgeClass = 'bg-success';
if ($status === 'In Progress') $badgeClass = 'bg-warning text-dark';
if ($status === 'Resolved') $badgeClass = 'bg-info text-dark';
if ($status === 'Closed') $badgeClass = 'bg-dark';
?>

<div class="row mb-4 align-items-center">
  <div class="col-md-8">
    <h3 class="mb-0">
      <i class="fa fa-ticket-alt text-primary"></i>
      تفاصيل التذكرة #<?php echo (int)$ticket->id; ?>
    </h3>
  </div>
  <div class="col-md-4 text-md-end">
    <a href="<?php echo URLROOT; ?>/index.php?page=tickets/index" class="btn btn-secondary">
      <i class="fa fa-arrow-right"></i> عودة للقائمة
    </a>
  </div>
</div>

<div class="row">
  <div class="col-md-8">

    <div class="card shadow-sm mb-3">
      <div class="card-header bg-dark text-white">
        <strong>وصف المشكلة</strong>
      </div>
      <div class="card-body">

        <div class="mb-3">
          <label class="text-muted">العنوان:</label>
          <div class="fw-bold fs-5">
            <?php echo htmlspecialchars($ticket->subject ?? '-', ENT_QUOTES, 'UTF-8'); ?>
          </div>
        </div>

        <div class="mb-3">
          <label class="text-muted">الوصف:</label>
          <div class="p-3 bg-light rounded border">
            <?php echo nl2br(htmlspecialchars($ticket->description ?? '-', ENT_QUOTES, 'UTF-8')); ?>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <label class="text-muted">معلومات التواصل:</label>
            <div class="fw-bold"><?php echo htmlspecialchars($ticket->contact_info ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
          <div class="col-md-6">
            <label class="text-muted">تاريخ الإنشاء:</label>
            <div class="fw-bold"><?php echo htmlspecialchars($ticket->created_at ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        </div>

        <?php if (!empty($ticket->asset_id)): ?>
          <hr>
          <div class="alert alert-info mb-0">
            <i class="fa fa-laptop me-2"></i>
            <strong>جهاز مرتبط:</strong>
            <?php echo htmlspecialchars($ticket->asset_tag ?? ('ID: ' . (int)$ticket->asset_id), ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <?php if (!empty($data['updates'])): ?>
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <strong><i class="fa fa-history"></i> سجل التحديثات</strong>
        </div>
        <div class="card-body">
          <ul class="list-group">
            <?php foreach ($data['updates'] as $u): ?>
              <li class="list-group-item">
                <div class="d-flex justify-content-between">
                  <div>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($u->status ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if (!empty($u->comment)): ?>
                      <div class="mt-2"><?php echo nl2br(htmlspecialchars($u->comment, ENT_QUOTES, 'UTF-8')); ?></div>
                    <?php endif; ?>
                  </div>
                  <small class="text-muted"><?php echo htmlspecialchars($u->created_at ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($data['attachments'])): ?>
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <strong><i class="fa fa-paperclip"></i> المرفقات</strong>
        </div>
        <div class="card-body">
          <div class="row">
            <?php foreach ($data['attachments'] as $a): ?>
              <div class="col-md-4 mb-3">
                <a class="d-block" target="_blank" href="<?php echo URLROOT . '/' . $a->file_path; ?>">
                  <img class="img-fluid rounded border" src="<?php echo URLROOT . '/' . $a->file_path; ?>" alt="attachment">
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>

  <div class="col-md-4">

    <div class="card shadow-sm mb-3 text-center">
      <div class="card-body">
        <div class="text-muted">الحالة الحالية</div>
        <div class="mt-2">
          <span class="badge <?php echo $badgeClass; ?> fs-6 rounded-pill px-4">
            <?php echo htmlspecialchars($status ?: '-', ENT_QUOTES, 'UTF-8'); ?>
          </span>
        </div>

        <div class="text-muted mt-3">الأولوية</div>
        <div class="mt-2">
          <span class="badge bg-danger"><?php echo htmlspecialchars($ticket->priority ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3 border-primary">
      <div class="card-header bg-primary text-white">
        <strong><i class="fa fa-edit"></i> تحديث التذكرة</strong>
      </div>
      <div class="card-body">
        <form action="<?php echo URLROOT; ?>/index.php?page=tickets/update_status" method="post">
          <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket->id; ?>">

          <?php if (!empty($data['users'])): ?>
            <div class="mb-3">
              <label class="form-label">تعيين إلى موظف (اختياري)</label>
              <select name="assigned_to" class="form-select" disabled>
                <option value="">-- لاحقاً --</option>
              </select>
              <div class="form-text">التعيين نفعّله بعد ما نضيفه بالموديل/الداتابيس بشكل كامل.</div>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">تغيير الحالة</label>
            <select name="status" class="form-select" required>
              <option value="Open" <?php echo ($status==='Open')?'selected':''; ?>>Open</option>
              <option value="In Progress" <?php echo ($status==='In Progress')?'selected':''; ?>>In Progress</option>
              <option value="Resolved" <?php echo ($status==='Resolved')?'selected':''; ?>>Resolved</option>
              <option value="Closed" <?php echo ($status==='Closed')?'selected':''; ?>>Closed</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">تعليق (اختياري)</label>
            <textarea name="comment" class="form-control" rows="3"></textarea>
          </div>

          <div class="d-grid">
            <button class="btn btn-success" type="submit">
              <i class="fa fa-save"></i> حفظ التغييرات
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <strong><i class="fa fa-level-up-alt"></i> تصعيد التذكرة</strong>
      </div>
      <div class="card-body">
        <form action="<?php echo URLROOT; ?>/index.php?page=tickets/escalate" method="post">
          <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket->id; ?>">

          <div class="mb-3">
            <label class="form-label">التصعيد إلى</label>
            <select name="team" class="form-select" required>
              <option value="">-- اختر القسم --</option>
              <option value="network">الشبكات</option>
              <option value="security">الأمن السيبراني</option>
              <option value="electricity">الكهرباء</option>
              <option value="field_it">الدعم الميداني (IT)</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">سبب/ملاحظة (اختياري)</label>
            <textarea name="comment" class="form-control" rows="2"></textarea>
          </div>

          <div class="d-grid">
            <button class="btn btn-warning" type="submit">تصعيد</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header">
        <strong><i class="fa fa-image"></i> رفع صور</strong>
      </div>
      <div class="card-body">
        <form action="<?php echo URLROOT; ?>/index.php?page=tickets/upload" method="post" enctype="multipart/form-data">
          <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket->id; ?>">
          <div class="mb-2">
            <input type="file" name="images[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">مسموح: jpg, png, webp</div>
          </div>
          <div class="d-grid">
            <button class="btn btn-outline-primary" type="submit">رفع</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
