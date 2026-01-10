<?php
// app/views/tickets/index.php  ✅ نسخة نظيفة + تضمين الهيدر/الفوتر + UI جديد

// ===== Include layout =====
if (defined('APPROOT')) {
  require APPROOT . '/views/layouts/header.php';
} else {
  require __DIR__ . '/../layouts/header.php';
}

// ===== Helpers =====
if (!function_exists('h')) {
  function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
function urlroot_prefix(): string {
  return defined('URLROOT') ? rtrim(URLROOT, '/') : '';
}
function buildTicketsUrl(array $overrides = []): string {
  $base = $_GET ?? [];
  $base['page'] = 'tickets/index';

  foreach ($overrides as $k => $v) $base[$k] = $v;

  // remove empty
  foreach ($base as $k => $v) {
    if ($v === '' || $v === null) unset($base[$k]);
  }

  $prefix = urlroot_prefix();
  return ($prefix !== '' ? $prefix . '/index.php?' : 'index.php?') . http_build_query($base);
}

function statusUi(string $st): array {
  $st = trim($st);
  if ($st === 'Open')        return ['open',    'مفتوحة',      'bi-circle-fill'];
  if ($st === 'In Progress') return ['pending', 'قيد المعالجة', 'bi-arrow-repeat'];
  if ($st === 'Resolved')    return ['pending', 'تم الحل',      'bi-check2-circle'];
  if ($st === 'Closed')      return ['closed',  'مغلقة',        'bi-lock-fill'];
  return ['open', ($st ?: '—'), 'bi-circle-fill'];
}
function priorityUi(string $pr): array {
  $pr = trim($pr);
  if ($pr === 'High')   return ['background:rgba(10,14,21,.14);', 'عالية',   'bi-exclamation-triangle-fill'];
  if ($pr === 'Medium') return ['background:rgba(10,14,21,.10);', 'متوسطة',  'bi-dash-circle'];
  if ($pr === 'Low')    return ['background:rgba(10,14,21,.06);', 'منخفضة',  'bi-arrow-down-circle'];
  return ['', ($pr ?: '—'), 'bi-dash-circle'];
}

// ===== Data =====
$tickets = $data['tickets'] ?? [];
if (!is_array($tickets)) $tickets = [];

$filters = $data['filters'] ?? [];
$q        = (string)($filters['q'] ?? ($_GET['q'] ?? ''));
$status   = (string)($filters['status'] ?? ($_GET['status'] ?? ''));
$priority = (string)($filters['priority'] ?? ($_GET['priority'] ?? ''));
$team     = (string)($filters['team'] ?? ($_GET['team'] ?? ''));
$assTo    = (string)($filters['assigned_to'] ?? ($_GET['assigned_to'] ?? ''));

$teamsList = $data['teams'] ?? [];
$usersList = $data['users'] ?? [];

$pagination = $data['pagination'] ?? ['page'=>1,'perPage'=>15,'total'=>0,'pages'=>1];
$page  = (int)($pagination['page'] ?? 1);  if ($page < 1) $page = 1;
$pages = (int)($pagination['pages'] ?? 1); if ($pages < 1) $pages = 1;
$total = (int)($pagination['total'] ?? 0);

// Options
$statusOptions   = ['Open', 'In Progress', 'Resolved', 'Closed'];
$priorityOptions = ['High', 'Medium', 'Low'];

// URLs
$prefix = urlroot_prefix();
$addUrl = ($prefix !== '' ? $prefix . '/index.php?page=tickets/add' : 'index.php?page=tickets/add');
?>

<div class="page-wrap">

  <div class="page-head">
    <div>
      <h1 class="page-title">التذاكر والدعم الفني</h1>
      <div class="page-sub">متابعة الطلبات، المسؤولين، والمرفقات بشكل سريع.</div>
    </div>

    <div class="page-actions">
      <a class="btn btn-dark btn-soft" href="<?= h($addUrl) ?>">
        <i class="bi bi-plus-lg ms-1"></i> فتح تذكرة جديدة
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div class="cardx mb-3">
    <div class="cardx-body">
      <div class="cardx-title">بحث وفلترة</div>

      <form method="get" class="filters">
        <input type="hidden" name="page" value="tickets/index">
        <input type="hidden" name="p" value="1"><!-- reset page -->

        <input class="form-control input-soft" name="q" value="<?= h($q) ?>"
               placeholder="بحث (رقم/عنوان/وصف/أصل/اسم)">

        <select class="form-select select-soft" name="status">
          <option value="">كل الحالات</option>
          <?php foreach ($statusOptions as $opt): ?>
            <option value="<?= h($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>>
              <?= h($opt) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select class="form-select select-soft" name="priority">
          <option value="">كل الأولويات</option>
          <?php foreach ($priorityOptions as $opt): ?>
            <option value="<?= h($opt) ?>" <?= $priority === $opt ? 'selected' : '' ?>>
              <?= h($opt) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select class="form-select select-soft" name="team">
          <option value="">كل الأقسام</option>
          <?php foreach ($teamsList as $t): ?>
            <?php
              $val = is_object($t) ? (string)($t->team ?? $t->name ?? '') : (string)$t;
              if ($val === '') continue;
            ?>
            <option value="<?= h($val) ?>" <?= $team === $val ? 'selected' : '' ?>>
              <?= h($val) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select class="form-select select-soft" name="assigned_to">
          <option value="">كل المسؤولين</option>
          <?php foreach ($usersList as $u): ?>
            <?php
              $uid  = is_object($u) ? (string)($u->id ?? '') : (string)($u['id'] ?? '');
              $name = is_object($u) ? (string)($u->name ?? $u->full_name ?? '') : (string)($u['name'] ?? $u['full_name'] ?? '');
              if ($uid === '') continue;
            ?>
            <option value="<?= h($uid) ?>" <?= $assTo === $uid ? 'selected' : '' ?>>
              <?= h($name !== '' ? $name : ('ID ' . $uid)) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div class="d-flex gap-2" style="grid-column: 1 / -1; justify-content:flex-start;">
          <button class="btn btn-dark btn-soft" type="submit">
            <i class="bi bi-funnel ms-1"></i> تطبيق
          </button>
          <a class="btn btn-light border btn-soft" href="<?= h(buildTicketsUrl(['q'=>'','status'=>'','priority'=>'','team'=>'','assigned_to'=>'','p'=>1])) ?>">
            مسح
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="cardx">
    <div class="cardx-body p-0">
      <div class="table-responsive">
        <table class="table tablex mb-0">
          <thead>
            <tr>
              <th>رقم</th>
              <th>الموضوع</th>
              <th>صاحب الطلب</th>
              <th>المطلوبة لـ</th>
              <th>المسؤول</th>
              <th>الأصل</th>
              <th>القسم</th>
              <th>الحالة</th>
              <th>الأولوية</th>
              <th>💬 تحديثات</th>
              <th>📎 مرفقات</th>
              <th>آخر تحديث</th>
              <th class="text-start">إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($tickets)): ?>
              <tr>
                <td colspan="13" class="text-center td-muted py-4">لا توجد تذاكر حالياً.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($tickets as $ticket): ?>
                <?php
                  $id = (int)($ticket->id ?? 0);

                  $ticketNo = (string)($ticket->ticket_number ?? ($ticket->ticket_no ?? ('TCK-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT))));
                  $subject  = (string)($ticket->subject ?? '');
                  $desc     = (string)($ticket->description ?? '');

                  $requester = (string)($ticket->user_name ?? $ticket->requester_name ?? '-');
                  $forName   = (string)($ticket->requested_for_name ?? '-');
                  $assigned  = (string)($ticket->assigned_to_name ?? 'غير مسند');

                  $dept     = (string)($ticket->department ?? ($ticket->team ?? '-'));
                  $assetTag = (string)($ticket->asset_tag ?? '-');

                  $updatesCount = (int)($ticket->updates_count ?? 0);
                  $attachCount  = (int)($ticket->attachments_count ?? 0);

                  $dt = $ticket->updated_at ?? ($ticket->created_at ?? '-');

                  [$stCls, $stLbl, $stIcon] = statusUi((string)($ticket->status ?? ''));
                  [$prStyle, $prLbl, $prIcon] = priorityUi((string)($ticket->priority ?? ''));

                  $showUrl = ($prefix !== '' ? $prefix . '/index.php?page=tickets/show&id=' : 'index.php?page=tickets/show&id=') . $id;

                  $short = $desc;
                  if (function_exists('mb_substr')) {
                    $short = mb_substr($desc, 0, 90);
                    if (mb_strlen($desc) > 90) $short .= '…';
                  } else {
                    $short = substr($desc, 0, 90);
                    if (strlen($desc) > 90) $short .= '…';
                  }
                ?>
                <tr>
                  <td class="td-muted"><?= h($ticketNo) ?></td>

                  <td style="min-width:260px;">
                    <div style="font-weight:900;"><?= h($subject !== '' ? $subject : '—') ?></div>
                    <?php if (trim($desc) !== ''): ?>
                      <div class="td-muted" style="font-size:12px; margin-top:2px;">
                        <?= h($short) ?>
                      </div>
                    <?php endif; ?>
                  </td>

                  <td class="td-muted"><?= h($requester) ?></td>
                  <td class="td-muted"><?= h($forName) ?></td>
                  <td><?= h($assigned) ?></td>
                  <td class="td-muted"><?= h($assetTag) ?></td>
                  <td class="td-muted"><?= h($dept) ?></td>

                  <td>
                    <span class="badgex <?= h($stCls) ?>">
                      <i class="bi <?= h($stIcon) ?>"></i> <?= h($stLbl) ?>
                    </span>
                  </td>

                  <td>
                    <span class="badgex" style="<?= h($prStyle) ?>">
                      <i class="bi <?= h($prIcon) ?>"></i> <?= h($prLbl) ?>
                    </span>
                  </td>

                  <td class="td-muted"><?= (int)$updatesCount ?></td>
                  <td class="td-muted"><?= (int)$attachCount ?></td>
                  <td class="td-muted"><?= h((string)$dt) ?></td>

                  <td class="text-start">
                    <a class="icon-btn" href="<?= h($showUrl) ?>" title="تفاصيل">
                      <i class="bi bi-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="cardx-body d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="td-muted" style="font-weight:900;">
        الإجمالي: <?= (int)$total ?> — صفحة <?= (int)$page ?> من <?= (int)$pages ?>
      </div>

      <div class="d-flex gap-2">
        <?php $prev = max(1, $page - 1); ?>
        <?php $next = min($pages, $page + 1); ?>

        <a class="btn btn-light border btn-soft <?= $page <= 1 ? 'disabled' : '' ?>"
           href="<?= h(buildTicketsUrl(['p' => $prev])) ?>">
          السابق
        </a>

        <a class="btn btn-light border btn-soft <?= $page >= $pages ? 'disabled' : '' ?>"
           href="<?= h(buildTicketsUrl(['p' => $next])) ?>">
          التالي
        </a>
      </div>
    </div>
  </div>

</div>

<?php
// ===== Include footer =====
if (defined('APPROOT')) {
  require APPROOT . '/views/layouts/footer.php';
} else {
  require __DIR__ . '/../layouts/footer.php';
}
