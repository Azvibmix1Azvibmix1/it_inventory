<?php
// app/controllers/SparePartsController.php

class SparePartsController extends Controller
{
  private $spareModel;
  private $locationModel;

  public function __construct()
  {
    if (function_exists('requireLogin')) {
      requireLogin();
    }

    $this->spareModel = new SparePart();
    $this->locationModel = $this->model('Location');
  }

  public function index() {
  // اجلب كل القطع
  $allParts = method_exists($this->spareModel, 'getParts')
    ? $this->spareModel->getParts()
    : (method_exists($this->spareModel, 'getAll') ? $this->spareModel->getAll() : []);

  if (!is_array($allParts)) $allParts = [];

  // فلاتر GET
  $q = trim($_GET['q'] ?? '');
  $locationId = (int)($_GET['location_id'] ?? 0);   // 0 = الكل
  $status = trim($_GET['status'] ?? '');            // '', 'ok', 'low', 'out'

  // فلترة
  $parts = array_values(array_filter($allParts, function($p) use ($q, $locationId, $status) {
    $name = mb_strtolower((string)($p->name ?? ''));
    $pn   = mb_strtolower((string)($p->part_number ?? ''));
    $qty  = (int)($p->quantity ?? 0);
    $min  = (int)($p->min_quantity ?? 0);
    $loc  = (int)($p->location_id ?? 0);

    // بحث بالاسم أو PN
    if ($q !== '') {
      $qq = mb_strtolower($q);
      if (mb_strpos($name, $qq) === false && mb_strpos($pn, $qq) === false) {
        return false;
      }
    }

    // فلتر الموقع
    if ($locationId > 0 && $loc !== $locationId) {
      return false;
    }

    // فلتر الحالة
    if ($status === 'out') {
      if ($qty > 0) return false;
    } elseif ($status === 'low') {
      if (!($qty > 0 && $qty <= $min)) return false;
    } elseif ($status === 'ok') {
      if (!($qty > $min)) return false;
    }

    return true;
  }));

  // احصائيات على النتائج بعد الفلترة
  $totalParts = count($parts);
  $outOfStock = 0;
  $lowStock   = 0;

  foreach ($parts as $part) {
    $qty = (int)($part->quantity ?? 0);
    $min = (int)($part->min_quantity ?? 0);
    if ($qty <= 0) $outOfStock++;
    elseif ($qty <= $min) $lowStock++;
  }

  // مواقع (للقائمة المنسدلة)
  $locations = $this->locationModel->getAll();
$sort = trim($_GET['sort'] ?? 'name');   // name, qty, location, status
$dir  = strtolower(trim($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

usort($parts, function($a, $b) use ($sort, $dir) {
  $cmp = 0;

  if ($sort === 'qty') {
    $cmp = ((int)($a->quantity ?? 0)) <=> ((int)($b->quantity ?? 0));
  } elseif ($sort === 'location') {
    $cmp = strcmp((string)($a->location_name ?? ''), (string)($b->location_name ?? ''));
    // إذا ما عندك location_name خليها id:
    if ($cmp === 0) $cmp = ((int)($a->location_id ?? 0)) <=> ((int)($b->location_id ?? 0));
  } elseif ($sort === 'status') {
    // out=0, low=1, ok=2
    $qa = (int)($a->quantity ?? 0); $ma = (int)($a->min_quantity ?? 0);
    $qb = (int)($b->quantity ?? 0); $mb = (int)($b->min_quantity ?? 0);
    $sa = ($qa <= 0) ? 0 : (($qa <= $ma) ? 1 : 2);
    $sb = ($qb <= 0) ? 0 : (($qb <= $mb) ? 1 : 2);
    $cmp = $sa <=> $sb;
  } else { // name
    $cmp = strcmp(mb_strtolower((string)($a->name ?? '')), mb_strtolower((string)($b->name ?? '')));
  }

  return $dir === 'desc' ? -$cmp : $cmp;
});

// ====== PAGINATION ======
$perPage = 10;
$pageNum = (int)($_GET['p'] ?? 1);
if ($pageNum < 1) $pageNum = 1;

$totalRows = count($parts);
$totalPages = (int)ceil($totalRows / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($pageNum > $totalPages) $pageNum = $totalPages;

$offset = ($pageNum - 1) * $perPage;
$partsPage = array_slice($parts, $offset, $perPage);

// استبدل parts اللي يروح للواجهة بنسخة الصفحة فقط
$parts = $partsPage;

// خزّن معلومات الصفحات للواجهة
$pagination = [
  'page' => $pageNum,
  'per_page' => $perPage,
  'total_rows' => $totalRows,
  'total_pages' => $totalPages,
];


$data['filters']['sort'] = $sort;
$data['filters']['dir']  = $dir;

  $data = [
    'parts'        => $parts,
    'total_parts'  => $totalParts,
    'out_of_stock' => $outOfStock,
    'low_stock'    => $lowStock,
    'pagination' => $pagination,


    // للفلاتر في الواجهة
    'filters' => [
      'q' => $q,
      'location_id' => $locationId,
      'status' => $status,
    ],
    'locations' => $locations,
  ];

  $this->view('spare_parts/index', $data);
}


  public function add()
  {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

      $data = [
        'name' => trim($_POST['name'] ?? ''),
        'part_number' => trim($_POST['part_number'] ?? ''),
        'quantity' => (int)($_POST['quantity'] ?? 0),
        'min_quantity' => (int)($_POST['min_quantity'] ?? 0),
        'location_id' => !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null,
        'description' => trim($_POST['description'] ?? ''),
        'locations' => $this->locationModel->getAll(),
        'name_err' => ''
      ];

      if (empty($data['name'])) {
        $data['name_err'] = 'الرجاء كتابة اسم القطعة';
      }

      if (empty($data['name_err'])) {
        if ($this->spareModel->add($data)) {
          flash('part_message', 'تم إضافة قطعة الغيار بنجاح');
          redirect('index.php?page=SpareParts/index');
          return;
        }
        die('حدث خطأ في قاعدة البيانات');
      }

      $this->view('spare_parts/add', $data);
      return;
    }

    // GET
    $locations = $this->locationModel->getAll();
    $prefillLoc = (int)($_GET['location_id'] ?? 0);

    $data = [
      'name' => '',
      'part_number' => '',
      'quantity' => 1,
      'min_quantity' => 5,
      'location_id' => $prefillLoc > 0 ? $prefillLoc : '',
      'description' => '',
      'locations' => $locations,
      'name_err' => ''
    ];

    $this->view('spare_parts/add', $data);
  }

  public function edit($id = null)
  {
    $id = $id ?? ($_GET['id'] ?? null);
    $id = (int)$id;

    if ($id <= 0) {
      flash('part_message', 'معرّف القطعة غير صحيح', 'alert alert-danger');
      redirect('index.php?page=SpareParts/index');
      return;
    }

    $locations = $this->locationModel->getAll();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

      $data = [
        'id' => $id,
        'name' => trim($_POST['name'] ?? ''),
        'part_number' => trim($_POST['part_number'] ?? ''),
        'quantity' => (int)($_POST['quantity'] ?? 0),
        'min_quantity' => (int)($_POST['min_quantity'] ?? 0),
        'location_id' => !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null,
        'description' => trim($_POST['description'] ?? ''),
        'locations' => $locations,
        'name_err' => ''
      ];

      if (empty($data['name'])) {
        $data['name_err'] = 'الرجاء كتابة اسم القطعة';
        $this->view('spare_parts/edit', $data);
        return;
      }

      if ($this->spareModel->update($data)) {
        flash('part_message', 'تم تحديث قطعة الغيار بنجاح');
        redirect('index.php?page=SpareParts/index');
        return;
      }

      die('حدث خطأ في قاعدة البيانات');
    }

    // GET
    $part = method_exists($this->spareModel, 'getPartById')
      ? $this->spareModel->getPartById($id)
      : null;

    if (!$part) {
      flash('part_message', 'القطعة غير موجودة', 'alert alert-danger');
      redirect('index.php?page=SpareParts/index');
      return;
    }

    $data = [
      'id' => $part->id,
      'name' => $part->name,
      'part_number' => $part->part_number,
      'quantity' => $part->quantity,
      'min_quantity' => $part->min_quantity,
      'location_id' => $part->location_id,
      'description' => $part->description,
      'locations' => $locations,
      'name_err' => ''
    ];

    $this->view('spare_parts/edit', $data);
  }

  public function delete($id = null) {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php?page=spareparts/index');
    return;
  }

  $returnTo = trim($_POST['return_to'] ?? '');

  $id = $id ?? ($_POST['id'] ?? ($_GET['id'] ?? null));
  $id = (int)$id;

  if ($id <= 0) {
    flash('part_message', 'معرّف غير صحيح', 'alert alert-danger');
    redirect($returnTo ?: 'index.php?page=spareparts/index');
    return;
  }

  if ($this->spareModel->delete($id)) {
    flash('part_message', 'تم حذف القطعة');
    redirect($returnTo ?: 'index.php?page=spareparts/index');
    return;
  }

  flash('part_message', 'فشل حذف القطعة', 'alert alert-danger');
  redirect($returnTo ?: 'index.php?page=spareparts/index');
}





  /**
   * تعديل سريع للكمية (توريد/صرف) + تسجيل حركة
   * POST: id, delta, location_id, return_to, note(optional)
   */
  public function adjust($id = null)
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect('index.php?page=spareparts/index');
      return;
    }

    $id = $id ?? ($_POST['id'] ?? 0);
    $id = (int)$id;

    $delta = (int)($_POST['delta'] ?? 0);
    $locationId = (int)($_POST['location_id'] ?? 0);
    $returnTo = trim($_POST['return_to'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($id <= 0 || $delta === 0) {
      flash('part_message', 'بيانات غير صحيحة', 'alert alert-danger');
      redirect($returnTo ?: 'index.php?page=spareParts/index');
      return;
    }

    $part = method_exists($this->spareModel, 'getPartById')
      ? $this->spareModel->getPartById($id)
      : null;

    if (!$part) {
      flash('part_message', 'القطعة غير موجودة', 'alert alert-danger');
      redirect($returnTo ?: 'index.php?page=spareParts/index');
      return;
    }

    $partLocId = (int)($part->location_id ?? 0);

    // صلاحية تعديل الموقع (لو عندك الدالة)
    if ($partLocId > 0 && function_exists('requireLocationPermission')) {
      requireLocationPermission($partLocId, 'edit', $returnTo ?: 'index.php?page=spareParts/index');
    }

    // نفّذ تعديل الكمية
    $ok = method_exists($this->spareModel, 'adjustQuantity')
      ? $this->spareModel->adjustQuantity($id, $delta)
      : false;

    if ($ok) {
      // سجل الحركة (مين سوّاها)
      $createdBy = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
      $useLoc = $partLocId > 0 ? $partLocId : ($locationId > 0 ? $locationId : null);

      if (method_exists($this->spareModel, 'addMovement')) {
        $this->spareModel->addMovement($id, $useLoc, $delta, $note, $createdBy);
      }

      flash('part_message', 'تم تحديث الكمية بنجاح');
    } else {
      flash('part_message', 'فشل تحديث الكمية', 'alert alert-danger');
    }

    // ✅ Redirect آمن: نخلي الرجوع فقط داخل مشروعك (index.php ...)
$returnTo = trim($returnTo);
if ($returnTo !== '') {
  // اسمح فقط بالرجوع داخل النظام (بدون روابط خارجية)
  $isSafe =
    str_starts_with($returnTo, 'index.php') ||
    str_starts_with($returnTo, '/it_inventory/public/index.php') ||  // إذا مسارك كذا
    str_contains($returnTo, 'index.php?page=');

  if ($isSafe) {
    redirect($returnTo);
    return;
  }
}

// fallback
if ($locationId > 0) {
  redirect("index.php?page=locations/edit&id={$locationId}");
  return;
}

redirect('index.php?page=spareParts/index');
return;

  }

  

  /**
 * نقل قطعة لموقع آخر + تسجيل حركة (delta=0)
 * POST: id, to_location_id, return_to
 */
public function transfer() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php?page=spareParts/index');
    return;
  }

  $id = (int)($_POST['id'] ?? 0);
  $toLocationId = (int)($_POST['to_location_id'] ?? 0);
  $returnTo = trim($_POST['return_to'] ?? '');

  if ($id <= 0 || $toLocationId <= 0) {
    flash('part_message', 'بيانات غير صحيحة', 'alert alert-danger');
    redirect($returnTo ?: 'index.php?page=spareParts/index');
    return;
  }

  $part = method_exists($this->spareModel, 'getPartById') ? $this->spareModel->getPartById($id) : null;
  if (!$part) {
    flash('part_message', 'القطعة غير موجودة', 'alert alert-danger');
    redirect($returnTo ?: 'index.php?page=spareParts/index');
    return;
  }

  $fromLocId = (int)($part->location_id ?? 0);
  if ($fromLocId > 0 && $toLocationId === $fromLocId) {
    flash('part_message', 'اختر موقع مختلف للنقل', 'alert alert-warning');
    redirect($returnTo ?: 'index.php?page=spareParts/index');
    return;
  }

  // صلاحية (لو عندك الدالة)
  if ($fromLocId > 0 && function_exists('requireLocationPermission')) {
    requireLocationPermission($fromLocId, 'edit', $returnTo ?: 'index.php?page=spareParts/index');
  }

  $ok = method_exists($this->spareModel, 'transferLocation')
    ? $this->spareModel->transferLocation($id, $toLocationId)
    : false;

  if ($ok) {
    $createdBy = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    // سجل حركة في الموقع القديم + الجديد (delta=0)
    if (method_exists($this->spareModel, 'addMovement')) {
      if ($fromLocId > 0) {
        $this->spareModel->addMovement($id, $fromLocId, 0, "نقل إلى موقع #{$toLocationId}", $createdBy);
      }
      $this->spareModel->addMovement($id, $toLocationId, 0, "نقل من موقع #{$fromLocId}", $createdBy);
    }

    flash('part_message', 'تم نقل القطعة بنجاح');
  } else {
    flash('part_message', 'فشل نقل القطعة', 'alert alert-danger');
  }

  redirect($returnTo ?: 'index.php?page=spareParts/index');
}


// JSON endpoint: movements
public function movements($id = null): void
{
  header('Content-Type: application/json; charset=utf-8');
  if (ob_get_length()) { @ob_clean(); }

  $id = $id ?? ($_GET['id'] ?? 0);
  $id = (int)$id;

  if ($id <= 0) {
    echo json_encode(['ok' => false, 'message' => 'ID غير صحيح'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // بيانات القطعة
  $part = method_exists($this->spareModel, 'getPartById')
    ? $this->spareModel->getPartById($id)
    : null;

  if (!$part) {
    echo json_encode(['ok' => false, 'message' => 'القطعة غير موجودة'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // جلب الحركات
  $moves = method_exists($this->spareModel, 'getMovementsByPart')
    ? $this->spareModel->getMovementsByPart($id, 200)
    : [];

  $rows = [];
  foreach ($moves as $m) {
    $delta = (int)($m->delta ?? 0);

    // نص الحركة
    if ($delta === 0) {
      $moveText = 'نقل';
    } elseif ($delta > 0) {
      $moveText = 'توريد +' . $delta;
    } else {
      $moveText = 'صرف ' . $delta;
    }

    // فصل التاريخ والوقت من created_at
    $dt = (string)($m->created_at ?? '');
    $ts = $dt ? strtotime($dt) : false;
    $time = $ts ? date('H:i:s', $ts) : '';
    $date = $ts ? date('Y-m-d', $ts) : '';

    // Fallback للأسماء إذا الاستعلام ما يرجعها
    $locationText = (string)($m->location_name ?? '');
    if ($locationText === '') {
      $locId = (int)($m->location_id ?? 0);
      $locationText = $locId > 0 ? ("موقع #{$locId}") : '-';
    }

    $userText = (string)($m->user_name ?? '');
    if ($userText === '') {
      $uid = (int)($m->created_by ?? $m->user_id ?? 0);
      $userText = $uid > 0 ? ("User #{$uid}") : '-';
    }

    $rows[] = [
      // حقول منفصلة (أفضل للعرض)
      'date'     => $date,
      'time'     => $time,

      // احتياط لو ودك تستخدمه لاحقًا
      'time_full'=> $dt,

      'move'     => $moveText,
      'location' => $locationText,
      'user'     => $userText,
      'note'     => (string)($m->note ?? ''),
    ];
  }

  echo json_encode([
    'ok'   => true,
    'part' => [
      'id'   => (int)($part->id ?? $id),
      'name' => (string)($part->name ?? ''),
      'pn'   => (string)($part->part_number ?? ''),
    ],
    'rows' => $rows,
  ], JSON_UNESCAPED_UNICODE);

  exit;
}

public function movementsJson($id = null): void
{
  header('Content-Type: application/json; charset=utf-8');

  // لو فيه أي output قبل JSON (warnings/spaces) نظّفه
  if (ob_get_length()) { @ob_clean(); }

  $id = $id ?? ($_GET['id'] ?? 0);
  $id = (int)$id;

  if ($id <= 0) {
    echo json_encode(['ok' => false, 'message' => 'ID غير صحيح'], JSON_UNESCAPED_UNICODE);
    return;
  }

  $part = method_exists($this->spareModel, 'getPartById')
    ? $this->spareModel->getPartById($id)
    : null;

  if (!$part) {
    echo json_encode(['ok' => false, 'message' => 'القطعة غير موجودة'], JSON_UNESCAPED_UNICODE);
    return;
  }

  // جلب الحركات مع اسم الموقع واسم المستخدم
  $rows = [];
  if (method_exists($this->spareModel, 'getMovementsDetailed')) {
    $rows = $this->spareModel->getMovementsDetailed($id);
  } elseif (method_exists($this->spareModel, 'getMovementsByPart')) {
    $rows = $this->spareModel->getMovementsByPart($id);
  }

  // توحيد شكل البيانات للـ JS
  $moves = [];
  foreach ($rows as $r) {
    $moves[] = [
      'time'     => (string)($r->created_at ?? $r->time ?? ''),
      'delta'    => (int)($r->delta ?? 0),
      'location' => (string)($r->location_name ?? $r->location ?? '-'),
      'user'     => (string)($r->user_name ?? $r->user ?? '-'),
      'note'     => (string)($r->note ?? ''),
    ];
  }

  echo json_encode([
    'ok' => true,
    'part' => [
      'id'   => (int)($part->id ?? $id),
      'name' => (string)($part->name ?? ''),
      'pn'   => (string)($part->part_number ?? ''),
    ],
    'movements' => $moves,
  ], JSON_UNESCAPED_UNICODE);

  return;
}

public function exportCsv(): void
{
  // اقفل أي output قبل الهيدر
  while (ob_get_level() > 0) { @ob_end_clean(); }

  // اجلب كل القطع
  $allParts = method_exists($this->spareModel, 'getParts')
    ? $this->spareModel->getParts()
    : (method_exists($this->spareModel, 'getAll') ? $this->spareModel->getAll() : []);

  if (!is_array($allParts)) $allParts = [];

  // فلاتر GET
  $q = trim($_GET['q'] ?? '');
  $locationId = (int)($_GET['location_id'] ?? 0);
  $status = trim($_GET['status'] ?? '');
  $sort = trim($_GET['sort'] ?? 'name');
  $dir  = strtolower(trim($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

  // Unicode-safe labels (بدون أحرف عربية مباشرة داخل الملف)
  $L = [
    'title'   => "\u{0642}\u{0627}\u{0626}\u{0645}\u{0629}\u{0020}\u{0642}\u{0637}\u{0639}\u{0020}\u{0627}\u{0644}\u{063A}\u{064A}\u{0627}\u{0631}",
    'name'    => "\u{0627}\u{0633}\u{0645}\u{0020}\u{0627}\u{0644}\u{0642}\u{0637}\u{0639}\u{0629}",
    'pn'      => "PN",
    'qty'     => "\u{0627}\u{0644}\u{0643}\u{0645}\u{064A}\u{0629}",
    'min'     => "\u{0627}\u{0644}\u{062D}\u{062F}\u{0020}\u{0627}\u{0644}\u{0623}\u{062F}\u{0646}\u{0649}",
    'loc'     => "\u{0627}\u{0644}\u{0645}\u{0648}\u{0642}\u{0639}",
    'status'  => "\u{0627}\u{0644}\u{062D}\u{0627}\u{0644}\u{0629}",
    'ok'      => "\u{0645}\u{062A}\u{0648}\u{0641}\u{0631}",
    'low'     => "\u{0645}\u{0646}\u{062E}\u{0641}\u{0636}",
    'out'     => "\u{0645}\u{0646}\u{062A}\u{0647}\u{064A}\u{0629}",
    'locHash' => "\u{0645}\u{0648}\u{0642}\u{0639}\u{0020}\u{0023}",
  ];

  // فلترة
  $parts = array_values(array_filter($allParts, function($p) use ($q, $locationId, $status) {
    $name = mb_strtolower((string)($p->name ?? ''));
    $pn   = mb_strtolower((string)($p->part_number ?? ''));
    $qLower = mb_strtolower($q);

    if ($qLower !== '' && mb_strpos($name, $qLower) === false && mb_strpos($pn, $qLower) === false) return false;

    $locId = (int)($p->location_id ?? 0);
    if ($locationId > 0 && $locId !== $locationId) return false;

    $qty = (int)($p->quantity ?? 0);
    $min = (int)($p->min_quantity ?? 0);

    if ($status === 'ok')  return $qty > $min;
    if ($status === 'low') return $qty > 0 && $qty <= $min;
    if ($status === 'out') return $qty <= 0;

    return true;
  }));

  // ترتيب
  usort($parts, function($a, $b) use ($sort, $dir) {
    $cmp = 0;

    if ($sort === 'qty') {
      $cmp = ((int)($a->quantity ?? 0)) <=> ((int)($b->quantity ?? 0));
    } elseif ($sort === 'location') {
      $la = (string)($a->location_name_ar ?? $a->location_name_en ?? '');
      $lb = (string)($b->location_name_ar ?? $b->location_name_en ?? '');
      $cmp = strcmp($la, $lb);
      if ($cmp === 0) $cmp = ((int)($a->location_id ?? 0)) <=> ((int)($b->location_id ?? 0));
    } elseif ($sort === 'status') {
      $qa = (int)($a->quantity ?? 0); $ma = (int)($a->min_quantity ?? 0);
      $qb = (int)($b->quantity ?? 0); $mb = (int)($b->min_quantity ?? 0);
      $sa = ($qa <= 0) ? 0 : (($qa <= $ma) ? 1 : 2);
      $sb = ($qb <= 0) ? 0 : (($qb <= $mb) ? 1 : 2);
      $cmp = $sa <=> $sb;
    } else {
      $cmp = strcmp(mb_strtolower((string)($a->name ?? '')), mb_strtolower((string)($b->name ?? '')));
    }

    return $dir === 'desc' ? -$cmp : $cmp;
  });

  // Excel HTML export
  $dt = date('Y-m-d_H-i');
  $filename = "spare_parts_{$dt}.xls";

  header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  header('Pragma: no-cache');
  header('Expires: 0');

  // BOM UTF-8
  echo "\xEF\xBB\xBF";

  $esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

  echo '<html><head><meta charset="utf-8">';
echo '<style>
  body{font-family: Tahoma, Arial, sans-serif; direction: rtl;}
  table{border-collapse:collapse; direction: rtl;}
  th,td{border:1px solid #000; padding:6px 10px; text-align:right; white-space:nowrap;}
  th{background:#f2f2f2;}
</style>';
echo '</head><body>';

  echo '<table border="1" cellpadding="4" cellspacing="0">';
  echo '<tr style="font-weight:bold;">';
  echo '<th>'.$esc($L['name']).'</th>';
  echo '<th>'.$esc($L['pn']).'</th>';
  echo '<th>'.$esc($L['qty']).'</th>';
  echo '<th>'.$esc($L['min']).'</th>';
  echo '<th>'.$esc($L['loc']).'</th>';
  echo '<th>'.$esc($L['status']).'</th>';
  echo '</tr>';

  foreach ($parts as $p) {
    $name = (string)($p->name ?? '');
    $pn   = (string)($p->part_number ?? '');
    $qty  = (int)($p->quantity ?? 0);
    $min  = (int)($p->min_quantity ?? 0);

    $locName = (string)($p->location_name_ar ?? $p->location_name_en ?? '');
    if ($locName === '') {
      $lid = (int)($p->location_id ?? 0);
      $locName = $lid > 0 ? ($L['locHash'].$lid) : '';
    }

    $statusText = $L['ok'];
    if ($qty <= 0) $statusText = $L['out'];
    elseif ($qty <= $min) $statusText = $L['low'];

    echo '<tr>';
    echo '<td>'.$esc($name).'</td>';
    echo '<td>'.$esc($pn).'</td>';
    echo '<td>'.$esc($qty).'</td>';
    echo '<td>'.$esc($min).'</td>';
    echo '<td>'.$esc($locName).'</td>';
    echo '<td>'.$esc($statusText).'</td>';
    echo '</tr>';
  }

  echo '</table></body></html>';
  exit;
}


public function printList(): void{
  // نفس صفحة الفهرس لكن بوضع الطباعة + بدون pagination
  $_GET['print'] = 1;

  // اجلب كل القطع
  $allParts = method_exists($this->spareModel, 'getParts')
    ? $this->spareModel->getParts()
    : (method_exists($this->spareModel, 'getAll') ? $this->spareModel->getAll() : []);

  if (!is_array($allParts)) $allParts = [];

  // فلاتر GET
  $q = trim($_GET['q'] ?? '');
  $locationId = (int)($_GET['location_id'] ?? 0);
  $status = trim($_GET['status'] ?? '');

  $sort = trim($_GET['sort'] ?? 'name');
  $dir  = strtolower(trim($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

  // فلترة
  $parts = array_values(array_filter($allParts, function($p) use ($q, $locationId, $status) {
    $name = mb_strtolower((string)($p->name ?? ''));
    $pn   = mb_strtolower((string)($p->part_number ?? ''));
    $qLower = mb_strtolower($q);

    if ($qLower !== '' && mb_strpos($name, $qLower) === false && mb_strpos($pn, $qLower) === false) return false;

    $locId = (int)($p->location_id ?? 0);
    if ($locationId > 0 && $locId !== $locationId) return false;

    $qty = (int)($p->quantity ?? 0);
    $min = (int)($p->min_quantity ?? 0);

    if ($status === 'ok')  return $qty > $min;
    if ($status === 'low') return $qty > 0 && $qty <= $min;
    if ($status === 'out') return $qty <= 0;

    return true;
  }));

  // ترتيب
  usort($parts, function($a, $b) use ($sort, $dir) {
    $cmp = 0;

    if ($sort === 'qty') {
      $cmp = ((int)($a->quantity ?? 0)) <=> ((int)($b->quantity ?? 0));
    } elseif ($sort === 'location') {
      $la = (string)($a->location_name_ar ?? $a->location_name_en ?? $a->location_name ?? '');
      $lb = (string)($b->location_name_ar ?? $b->location_name_en ?? $b->location_name ?? '');
      $cmp = strcmp($la, $lb);
      if ($cmp === 0) $cmp = ((int)($a->location_id ?? 0)) <=> ((int)($b->location_id ?? 0));
    } elseif ($sort === 'status') {
      $qa = (int)($a->quantity ?? 0); $ma = (int)($a->min_quantity ?? 0);
      $qb = (int)($b->quantity ?? 0); $mb = (int)($b->min_quantity ?? 0);
      $sa = ($qa <= 0) ? 0 : (($qa <= $ma) ? 1 : 2);
      $sb = ($qb <= 0) ? 0 : (($qb <= $mb) ? 1 : 2);
      $cmp = $sa <=> $sb;
    } else {
      $cmp = strcmp(mb_strtolower((string)($a->name ?? '')), mb_strtolower((string)($b->name ?? '')));
    }

    return $dir === 'desc' ? -$cmp : $cmp;
  });

  // احصائيات
  $totalParts = count($parts);
  $outOfStock = 0;
  $lowStock = 0;
  foreach ($parts as $part) {
    $qty = (int)($part->quantity ?? 0);
    $min = (int)($part->min_quantity ?? 0);
    if ($qty <= 0) $outOfStock++;
    elseif ($qty <= $min) $lowStock++;
  }

  $locations = $this->locationModel->getAll();

  $data = [
    'parts' => $parts,                 // ✅ بدون pagination
    'total_parts' => $totalParts,
    'out_of_stock' => $outOfStock,
    'low_stock' => $lowStock,
    'pagination' => null,              // ✅ يخفي pagination بالواجهة
    'filters' => [
      'q' => $q,
      'location_id' => $locationId,
      'status' => $status,
      'sort' => $sort,
      'dir' => $dir,
    ],
    'locations' => $locations,
    'print_mode' => true,              // ✅ نستخدمها لإخفاء الفلاتر
  ];

  $this->view('spare_parts/index', $data);
}

}
