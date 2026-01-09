<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row mb-3 align-items-center">
  <div class="col-md-6">
    <h3 class="mb-0"><i class="fa fa-headset"></i> التذاكر والدعم الفني</h3>
  </div>
  <div class="col-md-6 text-md-end">
    <a href="<?php echo URLROOT; ?>/index.php?page=tickets/add" class="btn btn-primary">
      <i class="fa fa-plus"></i> فتح تذكرة جديدة
    </a>
  </div>
</div>

<?php flash('ticket_msg'); ?>

<?php
$filters = $data['filters'] ?? [];
$teams   = $data['teams'] ?? [];
$users   = $data['users'] ?? [];
$pg      = $data['pagination'] ?? ['page'=>1,'perPage'=>15,'total'=>0,'pages'=>1];

function buildTicketsUrl(array $overrides = []): string {
  $base = $_GET ?? [];
  $base['page'] = 'tickets/index';
  foreach ($overrides as $k => $v) $base[$k] = $v;

  // حذف قيم فاضية للتنظيف
  foreach ($base as $k => $v) {
    if ($v === '' || $v === null) unset($base[$k]);
  }
  return URLROOT . '/index.php?' . http_build_query($base);
}
?>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form method="get" action="<?php echo URLROOT; ?>/index.php" class="row g-2">
      <input type="hidden" name="page" value="tickets/index">

      <div class="col-md-4">
        <input type="text" name="q" value="<?php echo htmlspecialchars($filters['q'] ?? ''); ?>" class="form-control" placeholder="بحث (رقم/عنوان/وصف/أصل/اسم)">
      </div>

      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">كل الحالات</option>
          <?php foreach (['Open','In Progress','Resolved','Closed'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo (($filters['status'] ?? '') === $s) ? 'selected' : ''; ?>>
              <?php echo $s; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <select name="priority" class="form-select">
          <option value="">كل الأولويات</option>
          <?php foreach (['High','Medium','Low'] as $p): ?>
            <option value="<?php echo $p; ?>" <?php echo (($filters['priority'] ?? '') === $p) ? 'selected' : ''; ?>>
              <?php echo $p; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <select name="team" class="form-select">
          <option value="">كل الأقسام</option>
          <?php foreach ($teams as $t): ?>
            <option value="<?php echo htmlspecialchars((string)$t); ?>" <?php echo (($filters['team'] ?? '') === (string)$t) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars((string)$t); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <select name="assigned_to" class="form-select">
          <option value="0">كل المسؤولين</option>
          <?php foreach ($users as $u): ?>
            <option value="<?php echo (int)$u->id; ?>" <?php echo ((int)($filters['assigned_to'] ?? 0) === (int)$u->id) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($u->name ?? ('ID ' . (int)$u->id)); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="fa fa-filter"></i> تطبيق</button>
        <a class="btn btn-outline-secondary" href="<?php echo URLROOT; ?>/index.php?page=tickets/index"><i class="fa fa-undo"></i> مسح</a>
      </div>
    </form>
  </div>
</div>

<div class="card card-body bg-light shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover table-striped bg-white align-middle">
      <thead class="table-dark">


      <td style="white-space:nowrap;">
  <span class="badge bg-light text-dark border">
    💬 <?php echo (int)($ticket->updates_count ?? 0); ?>
  </span>
</td>

<td style="white-space:nowrap;">
  <span class="badge bg-light text-dark border">
    📎 <?php echo (int)($ticket->attachments_count ?? 0); ?>
  </span>
</td>

        <tr>
          <th style="white-space:nowrap;">رقم</th>
          <th>الموضوع</th>
          <?php if (!empty($ticket->description)): ?>
  <div class="text-muted small">
    <?php
      $desc = (string)$ticket->description;
      $short = mb_substr($desc, 0, 80);
      echo htmlspecialchars($short, ENT_QUOTES, 'UTF-8') . (mb_strlen($desc) > 80 ? '…' : '');
    ?>
  </div>
<?php endif; ?>

  <tr>
    <th style="white-space:nowrap;">رقم</th>
    <th>الموضوع</th>
    <th style="white-space:nowrap;">صاحب الطلب</th>
    <th style="white-space:nowrap;">المطلوبة لـ</th>
    <th style="white-space:nowrap;">المسؤول</th>
    <th style="white-space:nowrap;">الأصل</th>
    <th style="white-space:nowrap;">القسم</th>
    <th style="white-space:nowrap;">الحالة</th>
    <th style="white-space:nowrap;">الأولوية</th>

    <!-- جديد -->
    <th style="white-space:nowrap;">تحديثات</th>
    <th style="white-space:nowrap;">مرفقات</th>

    <th style="white-space:nowrap;">آخر تحديث</th>
    <th style="white-space:nowrap;">إجراءات</th>
  </tr>


          <?php
$st = $ticket->status ?? '';
$stClass = 'bg-secondary';
if ($st === 'Open') $stClass = 'bg-success';
elseif ($st === 'In Progress') $stClass = 'bg-warning text-dark';
elseif ($st === 'Resolved') $stClass = 'bg-info text-dark';
elseif ($st === 'Closed') $stClass = 'bg-dark';
?>
<span class="badge <?php echo $stClass; ?>"><?php echo htmlspecialchars($st ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>

          <?php
$pr = $ticket->priority ?? '';
$prClass = 'bg-secondary';
if ($pr === 'High') $prClass = 'bg-danger';
elseif ($pr === 'Medium') $prClass = 'bg-warning text-dark';
elseif ($pr === 'Low') $prClass = 'bg-secondary';
?>
<span class="badge <?php echo $prClass; ?>"><?php echo htmlspecialchars($pr ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>

          <th style="white-space:nowrap;">آخر تحديث</th>
          <th style="white-space:nowrap;">إجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($data['tickets'])): ?>
          <tr><td colspan="11" class="text-center">لا توجد تذاكر حالياً.</td></tr>
        <?php else: ?>
          <?php foreach ($data['tickets'] as $ticket): ?>
            <tr>
              <td style="white-space:nowrap;"><?php echo htmlspecialchars($ticket->ticket_no ?? ('#' . $ticket->id)); ?></td>

              <td style="min-width:260px;">
                <div class="fw-bold"><?php echo htmlspecialchars($ticket->subject ?? ''); ?></div>
                <?php if (!empty($ticket->description)): ?>
                  <div class="text-muted small">
                    <?php
                      $desc = (string)$ticket->description;
                      $desc = mb_substr($desc, 0, 80);
                      echo htmlspecialchars($desc) . (mb_strlen((string)$ticket->description) > 80 ? '…' : '');
                    ?>
                  </div>
                <?php endif; ?>
              </td>

              <td style="white-space:nowrap;"><?php echo htmlspecialchars($ticket->user_name ?? '-'); ?></td>
              <td style="white-space:nowrap;"><?php echo htmlspecialchars($ticket->requested_for_name ?? '-'); ?></td>
              <td style="white-space:nowrap;"><?php echo htmlspecialchars($ticket->assigned_to_name ?? 'غير مسند', ENT_QUOTES, 'UTF-8'); ?></td>
              <td style="white-space:nowrap;"><?php echo htmlspecialchars($ticket->asset_tag ?? '-'); ?></td>
              <td style="white-space:nowrap;"><?php echo htmlspecialchars($ticket->team ?? '-'); ?></td>

              <td style="white-space:nowrap;">
                <?php if (($ticket->status ?? '') === 'Open'): ?>
                  <span class="badge bg-success">Open</span>
                <?php elseif (($ticket->status ?? '') === 'Closed'): ?>
                  <span class="badge bg-dark">Closed</span>
                <?php elseif (($ticket->status ?? '') === 'Resolved'): ?>
                  <span class="badge bg-info text-dark">Resolved</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($ticket->status ?? ''); ?></span>
                <?php endif; ?>
              </td>

              <td style="white-space:nowrap;">
                <?php if (($ticket->priority ?? '') === 'High'): ?>
                  <span class="badge bg-danger">High</span>
                <?php elseif (($ticket->priority ?? '') === 'Medium'): ?>
                  <span class="badge bg-warning text-dark">Medium</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Low</span>
                <?php endif; ?>
              </td>

              <td style="white-space:nowrap;"><?php echo htmlspecialchars($ticket->updated_at ?? ($ticket->created_at ?? '')); ?></td>

              <td style="white-space:nowrap;">
                <a href="<?php echo URLROOT; ?>/index.php?page=tickets/show&id=<?php echo (int)$ticket->id; ?>"class="btn btn-outline-primary btn-sm">
                  <i class="fa fa-eye"></i> تفاصيل
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    <?php $pg = $data['pagination'] ?? ['page'=>1,'pages'=>1,'total'=>0]; ?>

<?php if (($pg['pages'] ?? 1) > 1): ?>
  <?php
    $params = $_GET ?? [];
    $params['page'] = 'tickets/index';
    $makeUrl = function($p) use ($params) {
      $q = $params;
      $q['p'] = $p;
      return URLROOT . '/index.php?' . http_build_query($q);
    };
  ?>

  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
      الإجمالي: <?php echo (int)$pg['total']; ?> — صفحة <?php echo (int)$pg['page']; ?> من <?php echo (int)$pg['pages']; ?>
    </div>

    <nav>
      <ul class="pagination mb-0">
        <?php $cur = (int)$pg['page']; $pages = (int)$pg['pages']; ?>

        <li class="page-item <?php echo ($cur <= 1) ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo $makeUrl(max(1, $cur-1)); ?>">السابق</a>
        </li>

        <?php for ($i = max(1, $cur-2); $i <= min($pages, $cur+2); $i++): ?>
          <li class="page-item <?php echo ($i === $cur) ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo $makeUrl($i); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>

        <li class="page-item <?php echo ($cur >= $pages) ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo $makeUrl(min($pages, $cur+1)); ?>">التالي</a>
        </li>
      </ul>
    </nav>
  </div>
<?php endif; ?>

  </div>

  <!-- Pagination -->
  <?php if (($pg['pages'] ?? 1) > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
      <div class="text-muted small">
        الإجمالي: <?php echo (int)($pg['total'] ?? 0); ?> — صفحة <?php echo (int)($pg['page'] ?? 1); ?> من <?php echo (int)($pg['pages'] ?? 1); ?>
      </div>

      <nav>
        <ul class="pagination mb-0">
          <?php $cur = (int)($pg['page'] ?? 1); $pages = (int)($pg['pages'] ?? 1); ?>

          <li class="page-item <?php echo ($cur <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo buildTicketsUrl(['p' => max(1, $cur-1)]); ?>">السابق</a>
          </li>

          <?php
            $start = max(1, $cur - 2);
            $end   = min($pages, $cur + 2);
            for ($i = $start; $i <= $end; $i++):
          ?>
            <li class="page-item <?php echo ($i === $cur) ? 'active' : ''; ?>">
              <a class="page-link" href="<?php echo buildTicketsUrl(['p' => $i]); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>

          <li class="page-item <?php echo ($cur >= $pages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo buildTicketsUrl(['p' => min($pages, $cur+1)]); ?>">التالي</a>
          </li>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
