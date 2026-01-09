<?php require APPROOT . '/views/layouts/header.php'; ?>

<?php
// ===== Helpers / Defaults =====
$pg = $data['pagination'] ?? ['page' => 1, 'perPage' => 15, 'total' => 0, 'pages' => 1];

function buildTicketsUrl(array $overrides = []): string
{
  $base = $_GET ?? [];
  $base['page'] = 'tickets/index';

  foreach ($overrides as $k => $v) {
    $base[$k] = $v;
  }

  // تنظيف القيم الفاضية
  foreach ($base as $k => $v) {
    if ($v === '' || $v === null) unset($base[$k]);
  }

  return URLROOT . '/index.php?' . http_build_query($base);
}

// Current filters from GET
$q          = (string)($_GET['q'] ?? '');
$status     = (string)($_GET['status'] ?? '');
$priority   = (string)($_GET['priority'] ?? '');
$team       = (string)($_GET['team'] ?? ($_GET['department'] ?? ''));
$assignedTo = (string)($_GET['assigned_to'] ?? '');

// Option lists
$statusOptions   = ['Open', 'In Progress', 'Resolved', 'Closed'];
$priorityOptions = ['High', 'Medium', 'Low'];

// Departments/Teams list: from controller if exists, else derive from tickets
$teamsList = $data['teams'] ?? ($data['departments'] ?? []);
if (empty($teamsList) && !empty($data['tickets'])) {
  $seen = [];
  foreach ($data['tickets'] as $t) {
    $val = (string)($t->department ?? ($t->team ?? ''));
    if ($val !== '' && !isset($seen[$val])) $seen[$val] = true;
  }
  $teamsList = array_keys($seen);
  sort($teamsList);
}

// Users list (for "المسؤول")
$usersList = $data['users'] ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">🎧 التذاكر والدعم الفني</h3>

  <a class="btn btn-primary" href="<?php echo URLROOT; ?>/index.php?page=tickets/add">
    + فتح تذكرة جديدة
  </a>
</div>

<!-- Filters -->
<form method="get" action="<?php echo URLROOT; ?>/index.php" class="card mb-3">
  <div class="card-body">
    <input type="hidden" name="page" value="tickets/index" />

    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label mb-1">بحث (رقم/عنوان/وصف/أصل/اسم)</label>
        <input type="text" name="q" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" class="form-control"
               placeholder="بحث (رقم/عنوان/وصف/أصل/اسم)" />
      </div>

      <div class="col-md-2">
        <label class="form-label mb-1">الحالات</label>
        <select name="status" class="form-select">
          <option value="">كل الحالات</option>
          <?php foreach ($statusOptions as $s): ?>
            <option value="<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>"
              <?php echo ($status === $s) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label mb-1">الأولويات</label>
        <select name="priority" class="form-select">
          <option value="">كل الأولويات</option>
          <?php foreach ($priorityOptions as $p): ?>
            <option value="<?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?>"
              <?php echo ($priority === $p) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label mb-1">الأقسام</label>
        <select name="team" class="form-select">
          <option value="">كل الأقسام</option>
          <?php if (!empty($teamsList)): ?>
            <?php foreach ($teamsList as $t): ?>
              <?php
                $val = is_object($t) ? (string)($t->name ?? $t->team ?? '') : (string)$t;
              ?>
              <?php if ($val !== ''): ?>
                <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"
                  <?php echo ($team === $val) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label mb-1">المسؤولين</label>
        <select name="assigned_to" class="form-select">
          <option value="">كل المسؤولين</option>
          <?php if (!empty($usersList)): ?>
            <?php foreach ($usersList as $u): ?>
              <?php $uid = (string)($u->id ?? ''); ?>
              <option value="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>"
                <?php echo ($assignedTo !== '' && $assignedTo === $uid) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars((string)($u->name ?? ('ID ' . $uid)), ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="submit" class="btn btn-primary">
        <i class="fa fa-filter"></i> تطبيق
      </button>

      <a class="btn btn-outline-secondary" href="<?php echo buildTicketsUrl([
        'q' => '',
        'status' => '',
        'priority' => '',
        'team' => '',
        'department' => '',
        'assigned_to' => '',
        'p' => 1
      ]); ?>">
        مسح
      </a>
    </div>
  </div>
</form>

<!-- Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead class="table-dark">
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
            <th style="white-space:nowrap;">تحديثات</th>
            <th style="white-space:nowrap;">مرفقات</th>
            <th style="white-space:nowrap;">آخر تحديث</th>
            <th style="white-space:nowrap;">إجراءات</th>
          </tr>
        </thead>

        <tbody>
          <?php if (empty($data['tickets'])): ?>
            <tr>
              <td colspan="13" class="text-center text-muted py-4">لا توجد تذاكر حالياً.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($data['tickets'] as $ticket): ?>
              <?php
                // status badge
                $st = (string)($ticket->status ?? '');
                $stClass = 'bg-secondary';
                if ($st === 'Open') $stClass = 'bg-success';
                elseif ($st === 'In Progress') $stClass = 'bg-warning text-dark';
                elseif ($st === 'Resolved') $stClass = 'bg-info text-dark';
                elseif ($st === 'Closed') $stClass = 'bg-dark';

                // priority badge
                $pr = (string)($ticket->priority ?? '');
                $prClass = 'bg-secondary';
                if ($pr === 'High') $prClass = 'bg-danger';
                elseif ($pr === 'Medium') $prClass = 'bg-warning text-dark';
                elseif ($pr === 'Low') $prClass = 'bg-secondary';

                $assignedName = (string)($ticket->assigned_to_name ?? 'غير مسند');

                $updatesCount = (int)($ticket->updates_count ?? 0);
                $attachCount  = (int)($ticket->attachments_count ?? 0);

                $ticketNo = (string)($ticket->ticket_number ?? ($ticket->ticket_no ?? ('#' . (int)$ticket->id)));

                $dept = (string)($ticket->department ?? ($ticket->team ?? '-'));
                $assetTag = (string)($ticket->asset_tag ?? '-');

                $dt = $ticket->updated_at ?? ($ticket->created_at ?? null);

                $showUrl = URLROOT . '/index.php?page=tickets/show&id=' . (int)$ticket->id;
              ?>
              <tr>
                <td style="white-space:nowrap;" dir="ltr">
                  <?php echo htmlspecialchars($ticketNo, ENT_QUOTES, 'UTF-8'); ?>
                </td>

                <td>
                  <div class="fw-bold">
                    <?php echo htmlspecialchars((string)($ticket->subject ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                  </div>

                  <?php if (!empty($ticket->description)): ?>
                    <div class="text-muted small">
                      <?php
                        $desc = preg_replace("/\s+/", " ", (string)$ticket->description);
                        $short = mb_substr($desc, 0, 80);
                        echo htmlspecialchars($short, ENT_QUOTES, 'UTF-8') . (mb_strlen($desc) > 80 ? '…' : '');
                      ?>
                    </div>
                  <?php endif; ?>
                </td>

                <td style="white-space:nowrap;"><?php echo htmlspecialchars((string)($ticket->user_name ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td style="white-space:nowrap;"><?php echo htmlspecialchars((string)($ticket->requested_for_name ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td style="white-space:nowrap;"><?php echo htmlspecialchars($assignedName, ENT_QUOTES, 'UTF-8'); ?></td>

                <td style="white-space:nowrap;"><?php echo htmlspecialchars($assetTag, ENT_QUOTES, 'UTF-8'); ?></td>
                <td style="white-space:nowrap;"><?php echo htmlspecialchars($dept, ENT_QUOTES, 'UTF-8'); ?></td>

                <td style="white-space:nowrap;">
                  <span class="badge <?php echo $stClass; ?>">
                    <?php echo htmlspecialchars($st ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                </td>

                <td style="white-space:nowrap;">
                  <span class="badge <?php echo $prClass; ?>">
                    <?php echo htmlspecialchars($pr ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                </td>

                <td style="white-space:nowrap;">
                  <span class="badge bg-light text-dark border">💬 <?php echo $updatesCount; ?></span>
                </td>

                <td style="white-space:nowrap;">
                  <span class="badge bg-light text-dark border">📎 <?php echo $attachCount; ?></span>
                </td>

                <td style="white-space:nowrap;">
                  <span dir="ltr">
                    <?php echo $dt ? htmlspecialchars(date('Y-m-d H:i', strtotime((string)$dt)), ENT_QUOTES, 'UTF-8') : '-'; ?>
                  </span>
                </td>

                <td style="white-space:nowrap;">
                  <a href="<?php echo $showUrl; ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-eye"></i> تفاصيل
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Pagination -->
<?php if (($pg['pages'] ?? 1) > 1): ?>
  <?php
    $cur = (int)($pg['page'] ?? 1);
    $pages = (int)($pg['pages'] ?? 1);
  ?>
  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
      الإجمالي: <?php echo (int)($pg['total'] ?? 0); ?> — صفحة <?php echo $cur; ?> من <?php echo $pages; ?>
    </div>

    <nav>
      <ul class="pagination mb-0">
        <li class="page-item <?php echo ($cur <= 1) ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo buildTicketsUrl(['p' => max(1, $cur - 1)]); ?>">السابق</a>
        </li>

        <?php for ($i = max(1, $cur - 2); $i <= min($pages, $cur + 2); $i++): ?>
          <li class="page-item <?php echo ($i === $cur) ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo buildTicketsUrl(['p' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>

        <li class="page-item <?php echo ($cur >= $pages) ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo buildTicketsUrl(['p' => min($pages, $cur + 1)]); ?>">التالي</a>
        </li>
      </ul>
    </nav>
  </div>
<?php endif; ?>

<?php require APPROOT . '/views/layouts/footer.php'; ?>
