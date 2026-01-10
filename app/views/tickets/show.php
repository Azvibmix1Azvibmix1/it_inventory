<?php
// app/views/tickets/show.php

// Include layout
if (defined('APPROOT')) {
  require APPROOT . '/views/layouts/header.php';
} else {
  require __DIR__ . '/../layouts/header.php';
}

// Helpers
if (!function_exists('h')) {
  function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
function urlroot_prefix(): string {
  return defined('URLROOT') ? rtrim(URLROOT, '/') : '';
}
function fmt_dt($v): string {
  if (!$v) return '-';
  $ts = strtotime((string)$v);
  if (!$ts) return h((string)$v);
  return date('Y-m-d H:i', $ts);
}
function statusUi(string $st): array {
  $st = trim($st);
  if ($st === 'Open')        return ['open',    'مفتوحة',      'bi-circle-fill'];
  if ($st === 'In Progress') return ['pending', 'قيد المعالجة', 'bi-arrow-repeat'];
  if ($st === 'Resolved')    return ['pending', 'تم الحل',      'bi-check2-circle'];
  if ($st === 'Closed')      return ['closed',  'مغلقة',        'bi-lock-fill'];
  if ($st === 'Escalated')   return ['pending', 'تم التصعيد',   'bi-exclamation-diamond-fill'];
  return ['open', ($st ?: '—'), 'bi-circle-fill'];
}
function priorityUi(string $pr): array {
  $pr = trim($pr);
  if ($pr === 'High')   return ['background:rgba(10,14,21,.14);', 'عالية',   'bi-exclamation-triangle-fill'];
  if ($pr === 'Medium') return ['background:rgba(10,14,21,.10);', 'متوسطة',  'bi-dash-circle'];
  if ($pr === 'Low')    return ['background:rgba(10,14,21,.06);', 'منخفضة',  'bi-arrow-down-circle'];
  return ['', ($pr ?: '—'), 'bi-dash-circle'];
}

// Data
$ticket      = $data['ticket'] ?? null;
$updates     = $data['updates'] ?? [];
$attachments = $data['attachments'] ?? [];
$users       = $data['users'] ?? [];

$prefix = urlroot_prefix();

$ticketId = (int)($ticket->id ?? 0);
$ticketNo = (string)($ticket->ticket_no ?? ($ticket->ticket_number ?? ('TCK-' . str_pad((string)$ticketId, 6, '0', STR_PAD_LEFT))));

$status   = (string)($ticket->status ?? '');
$priority = (string)($ticket->priority ?? '');

[$stCls, $stLbl, $stIcon] = statusUi($status);
[$prStyle, $prLbl, $prIcon] = priorityUi($priority);

$backUrl   = ($prefix !== '' ? $prefix.'/index.php?page=tickets/index' : 'index.php?page=tickets/index');
$updateUrl = ($prefix !== '' ? $prefix.'/index.php?page=tickets/update_status' : 'index.php?page=tickets/update_status');
$escUrl    = ($prefix !== '' ? $prefix.'/index.php?page=tickets/escalate' : 'index.php?page=tickets/escalate');
$uploadUrl = ($prefix !== '' ? $prefix.'/index.php?page=tickets/upload' : 'index.php?page=tickets/upload');

$subject = (string)($ticket->subject ?? '-');
$desc    = (string)($ticket->description ?? '-');
$contact = (string)($ticket->contact_info ?? '-');
$team    = (string)($ticket->team ?? '-');

$requester = (string)($ticket->user_name ?? $ticket->requester_name ?? '-');
$forName   = (string)($ticket->requested_for_name ?? '-');
$assignee  = (string)($ticket->assigned_to_name ?? 'غير مسند');

$createdAt = $ticket->created_at ?? null;
$closedAt  = $ticket->closed_at ?? null;

$assetTag  = (string)($ticket->asset_tag ?? (!empty($ticket->asset_id) ? ('ID: ' . (int)$ticket->asset_id) : '-'));
?>

<style>
/* تحسينات بسيطة للعرض (خفيفة وما تكسر CSS العام) */
.ticket-grid{
  display:grid;
  grid-template-columns: 1fr 360px;
  gap: 16px;
}
@media (max-width: 1100px){
  .ticket-grid{ grid-template-columns: 1fr; }
}
.kv{
  display:grid;
  grid-template-columns: 140px 1fr;
  gap: 10px;
  align-items:center;
  padding: 8px 0;
  border-bottom: 1px solid rgba(10,14,21,.06);
}
.kv:last-child{ border-bottom:0; }
.kv .k{ color: rgba(10,14,21,.60); font-weight: 900; }
.kv .v{ font-weight: 900; }
.muted{ color: rgba(10,14,21,.60); }
.timeline-item{
  border: 1px solid rgba(10,14,21,.08);
  border-radius: 14px;
  padding: 12px 12px;
  background: rgba(255,255,255,.7);
}
.attach-grid{
  display:grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}
@media (max-width: 900px){
  .attach-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 520px){
  .attach-grid{ grid-template-columns: 1fr; }
}
.attach-card{
  border: 1px solid rgba(10,14,21,.08);
  border-radius: 14px;
  overflow: hidden;
  background: #fff;
}
.attach-thumb{
  width: 100%;
  height: 140px;
  display:flex;
  align-items:center;
  justify-content:center;
  background: rgba(10,14,21,.03);
}
.attach-meta{
  padding: 10px 10px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 10px;
}
.attach-name{
  font-weight: 900;
  font-size: 13px;
  overflow:hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>

<div class="page-wrap">

  <div class="page-head">
    <div>
      <h1 class="page-title">
        تفاصيل التذكرة #<?= (int)$ticketId ?> <span class="muted" style="font-size:14px;">(<?= h($ticketNo) ?>)</span>
      </h1>
      <div class="page-sub">عرض كامل التفاصيل + التحديثات + المرفقات وإجراءات المعالجة.</div>
    </div>

    <div class="page-actions">
      <a class="btn btn-light border btn-soft" href="<?= h($backUrl) ?>">
        <i class="bi bi-arrow-right ms-1"></i> عودة للقائمة
      </a>
    </div>
  </div>

  <div class="ticket-grid">

    <!-- Main -->
    <div>

      <!-- Ticket info -->
      <div class="cardx mb-3">
        <div class="cardx-body">
          <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
            <div>
              <div class="cardx-title">بيانات التذكرة</div>
              <div style="font-size:22px; font-weight: 1000; margin-top: 6px;"><?= h($subject) ?></div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <span class="badgex <?= h($stCls) ?>">
                <i class="bi <?= h($stIcon) ?>"></i> <?= h($stLbl) ?>
              </span>
              <span class="badgex" style="<?= h($prStyle) ?>">
                <i class="bi <?= h($prIcon) ?>"></i> <?= h($prLbl) ?>
              </span>
            </div>
          </div>

          <div class="mt-3">
            <div class="kv">
              <div class="k">صاحب الطلب</div>
              <div class="v"><?= h($requester) ?></div>
            </div>
            <div class="kv">
              <div class="k">المطلوبة لـ</div>
              <div class="v"><?= h($forName) ?></div>
            </div>
            <div class="kv">
              <div class="k">المسؤول</div>
              <div class="v"><?= h($assignee) ?></div>
            </div>
            <div class="kv">
              <div class="k">القسم</div>
              <div class="v"><?= h($team) ?></div>
            </div>
            <div class="kv">
              <div class="k">تاريخ الإنشاء</div>
              <div class="v"><?= h(fmt_dt($createdAt)) ?></div>
            </div>
            <div class="kv">
              <div class="k">إغلاق (إن وجد)</div>
              <div class="v"><?= h($closedAt ? fmt_dt($closedAt) : '-') ?></div>
            </div>
            <div class="kv">
              <div class="k">معلومات التواصل</div>
              <div class="v"><?= h($contact) ?></div>
            </div>
            <div class="kv">
              <div class="k">الأصل</div>
              <div class="v"><?= h($assetTag) ?></div>
            </div>
          </div>

          <div class="mt-3">
            <div class="muted" style="font-weight:900; margin-bottom:6px;">الوصف</div>
            <div style="white-space:pre-wrap; line-height:1.8; font-weight:800;">
              <?= h($desc) ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Updates / Timeline -->
      <div class="cardx mb-3">
        <div class="cardx-body">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div class="cardx-title">سجل التحديثات</div>
            <div class="muted" style="font-weight:900;">
              <?= is_array($updates) ? count($updates) : 0 ?> تحديث
            </div>
          </div>

          <div class="mt-3 d-flex flex-column gap-2">
            <?php if (empty($updates)): ?>
              <div class="td-muted">لا يوجد تحديثات حتى الآن.</div>
            <?php else: ?>
              <?php foreach ($updates as $u): ?>
                <?php
                  $us = (string)($u->status ?? ($u->new_status ?? ''));
                  [$usCls, $usLbl, $usIcon] = statusUi($us);
                  $who = (string)($u->user_name ?? '-');
                  $cm  = (string)($u->comment ?? '');
                  $dt  = $u->created_at ?? null;
                ?>
                <div class="timeline-item">
                  <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <span class="badgex <?= h($usCls) ?>">
                        <i class="bi <?= h($usIcon) ?>"></i> <?= h($usLbl) ?>
                      </span>
                      <div class="muted" style="font-weight:900;">بواسطة: <?= h($who) ?></div>
                    </div>
                    <div class="muted" style="font-weight:900;"><?= h(fmt_dt($dt)) ?></div>
                  </div>

                  <?php if (trim($cm) !== ''): ?>
                    <div class="mt-2" style="white-space:pre-wrap; font-weight:800;">
                      <?= h($cm) ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Attachments -->
      <div class="cardx">
        <div class="cardx-body">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div class="cardx-title">المرفقات</div>
            <div class="muted" style="font-weight:900;">
              <?= is_array($attachments) ? count($attachments) : 0 ?> ملف
            </div>
          </div>

          <div class="mt-3">
            <?php if (empty($attachments)): ?>
              <div class="td-muted">لا يوجد مرفقات.</div>
            <?php else: ?>
              <div class="attach-grid">
                <?php foreach ($attachments as $a): ?>
                  <?php
                    $path = (string)($a->file_path ?? '');
                    if ($path === '') continue;
                    $name = (string)($a->original_name ?? basename($path));
                    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $isImg = in_array($ext, ['jpg','jpeg','png','webp'], true);
                    $url = ($prefix !== '' ? $prefix . '/' . ltrim($path, '/') : $path);
                  ?>
                  <div class="attach-card">
                    <div class="attach-thumb">
                      <?php if ($isImg): ?>
                        <img src="<?= h($url) ?>" alt="<?= h($name) ?>" style="max-width:100%; max-height:100%; object-fit:contain;">
                      <?php else: ?>
                        <div class="d-flex flex-column align-items-center justify-content-center">
                          <i class="bi bi-file-earmark-text" style="font-size:34px;"></i>
                          <div class="muted" style="font-weight:900; margin-top:6px;"><?= h(strtoupper($ext ?: 'FILE')) ?></div>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="attach-meta">
                      <div style="min-width:0;">
                        <div class="attach-name" title="<?= h($name) ?>"><?= h($name) ?></div>
                        <div class="muted" style="font-size:12px; font-weight:900;"><?= h($path) ?></div>
                      </div>
                      <a class="btn btn-light border btn-soft" href="<?= h($url) ?>" target="_blank" rel="noopener">
                        فتح
                      </a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>

    </div>

    <!-- Side actions -->
    <div>

      <!-- Quick status -->
      <div class="cardx mb-3">
        <div class="cardx-body">
          <div class="cardx-title">ملخص الحالة</div>

          <div class="mt-3 d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between">
              <div class="muted" style="font-weight:900;">الحالة الحالية</div>
              <span class="badgex <?= h($stCls) ?>">
                <i class="bi <?= h($stIcon) ?>"></i> <?= h($stLbl) ?>
              </span>
            </div>

            <div class="d-flex align-items-center justify-content-between">
              <div class="muted" style="font-weight:900;">الأولوية</div>
              <span class="badgex" style="<?= h($prStyle) ?>">
                <i class="bi <?= h($prIcon) ?>"></i> <?= h($prLbl) ?>
              </span>
            </div>

            <div class="d-flex align-items-center justify-content-between">
              <div class="muted" style="font-weight:900;">المسؤول</div>
              <div style="font-weight:1000;"><?= h($assignee) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Update ticket -->
      <div class="cardx mb-3">
        <div class="cardx-body">
          <div class="cardx-title">تحديث التذكرة</div>

          <form method="post" action="<?= h($updateUrl) ?>" class="mt-3">
            <input type="hidden" name="ticket_id" value="<?= (int)$ticketId ?>">

            <label class="form-label muted" style="font-weight:900;">تعيين إلى موظف (اختياري)</label>
            <select class="form-select select-soft mb-2" name="assigned_to">
              <option value="">غير مسند</option>
              <?php foreach ($users as $u): ?>
                <?php
                  $uid = (int)($u->id ?? 0);
                  $nm  = (string)($u->name ?? ('ID ' . $uid));
                  $sel = ((int)($ticket->assigned_to ?? 0) === $uid) ? 'selected' : '';
                ?>
                <option value="<?= $uid ?>" <?= $sel ?>><?= h($nm) ?></option>
              <?php endforeach; ?>
            </select>

            <label class="form-label muted" style="font-weight:900;">تغيير الحالة</label>
            <select class="form-select select-soft mb-2" name="status" required>
              <?php
                $opts = ['Open'=>'Open','In Progress'=>'In Progress','Resolved'=>'Resolved','Closed'=>'Closed'];
                foreach ($opts as $val => $lbl):
              ?>
                <option value="<?= h($val) ?>" <?= $status === $val ? 'selected' : '' ?>><?= h($lbl) ?></option>
              <?php endforeach; ?>
            </select>

            <label class="form-label muted" style="font-weight:900;">تعليق (اختياري)</label>
            <textarea class="form-control input-soft mb-3" name="comment" rows="3" placeholder="اكتب ملاحظة أو تفاصيل التحديث..."></textarea>

            <button class="btn btn-dark btn-soft w-100" type="submit">
              <i class="bi bi-save ms-1"></i> حفظ التغييرات
            </button>
          </form>
        </div>
      </div>

      <!-- Escalate -->
      <div class="cardx mb-3">
        <div class="cardx-body">
          <div class="cardx-title">تصعيد التذكرة</div>

          <form method="post" action="<?= h($escUrl) ?>" class="mt-3">
            <input type="hidden" name="ticket_id" value="<?= (int)$ticketId ?>">

            <label class="form-label muted" style="font-weight:900;">التصعيد إلى</label>
            <select class="form-select select-soft mb-2" name="team" required>
              <option value="">-- اختر القسم --</option>
              <option value="network">الشبكات</option>
              <option value="security">الأمن السيبراني</option>
              <option value="electric">الكهرباء</option>
              <option value="field_it">الدعم الميداني (IT)</option>
            </select>

            <label class="form-label muted" style="font-weight:900;">سبب/ملاحظة (اختياري)</label>
            <textarea class="form-control input-soft mb-3" name="comment" rows="3" placeholder="سبب التصعيد أو ملاحظات..."></textarea>

            <button class="btn btn-light border btn-soft w-100" type="submit">
              <i class="bi bi-arrow-up-right-circle ms-1"></i> تصعيد
            </button>
          </form>
        </div>
      </div>

      <!-- Upload -->
      <div class="cardx">
        <div class="cardx-body">
          <div class="cardx-title">رفع مرفقات</div>

          <form method="post" action="<?= h($uploadUrl) ?>" enctype="multipart/form-data" class="mt-3">
            <input type="hidden" name="ticket_id" value="<?= (int)$ticketId ?>">

            <label class="form-label muted" style="font-weight:900;">اختر الملفات</label>
            <input class="form-control input-soft mb-2" type="file" name="files[]" multiple>

            <div class="muted" style="font-size:12px; font-weight:900; margin-bottom:10px;">
              مسموح: صور + pdf/doc/docx/xls/xlsx/txt/zip
            </div>

            <button class="btn btn-light border btn-soft w-100" type="submit">
              <i class="bi bi-upload ms-1"></i> رفع
            </button>
          </form>
        </div>
      </div>

    </div>

  </div>
</div>

<?php
// Footer
if (defined('APPROOT')) {
  require APPROOT . '/views/layouts/footer.php';
} else {
  require __DIR__ . '/../layouts/footer.php';
}
