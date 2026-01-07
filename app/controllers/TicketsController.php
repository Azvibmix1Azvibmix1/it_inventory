<?php


class TicketsController extends Controller {
    private $ticketModel;
    private $assetModel;
    private $userModel;

    public function __construct() {
        if (!function_exists('isLoggedIn') || !isLoggedIn()) {
            redirect('index.php?page=login');
            exit;
        }

        $this->ticketModel = $this->model('Ticket');
        $this->assetModel  = $this->model('Asset');
        $this->userModel   = $this->model('User');
    }

    // ---------------- Helpers ----------------

   private function getAssetsForCurrentUser(): array {
        if (function_exists('isSuperAdmin') && isSuperAdmin()) {
            return $this->assetModel->getAllAssets();
        }
        if (function_exists('isManager') && isManager()) {
            return $this->assetModel->getAllAssets();
        }

        if (method_exists($this->assetModel, 'getAssetsByUserId')) {
            return $this->assetModel->getAssetsByUserId($_SESSION['user_id']);
        }
        return [];
    }

    private function getUsersForForm(): array {
        // نعرض قائمة المستخدمين فقط للمدير/السوبر (للتعيين وفتح تذكرة لموظف)
        if (function_exists('isSuperAdmin') && isSuperAdmin()) {
            return method_exists($this->userModel, 'getUsers') ? $this->userModel->getUsers() : [];
        }

        if (function_exists('isManager') && isManager()) {
            return method_exists($this->userModel, 'getUsersByManager')
                ? $this->userModel->getUsersByManager($_SESSION['user_id'])
                : [];
        }

        return [];
    }

    private function canAccessTicket($ticket): bool {
        if (!$ticket) return false;

        $me = (int)$_SESSION['user_id'];

        if (function_exists('isSuperAdmin') && isSuperAdmin()) {
            return true;
        }

        $createdBy  = isset($ticket->created_by) ? (int)$ticket->created_by : 0;
        $requested  = isset($ticket->requested_for_user_id) ? (int)$ticket->requested_for_user_id : 0;
        $assignedTo = isset($ticket->assigned_to) ? (int)$ticket->assigned_to : 0;

        if (function_exists('isManager') && isManager()) {
            // المدير يشوف تذاكر فريقه + تذاكره
            if ($createdBy === $me || $requested === $me || $assignedTo === $me) return true;

            if (method_exists($this->userModel, 'getUsersByManager')) {
                $team = $this->userModel->getUsersByManager($me);
                $teamIds = array_map(fn($u) => (int)$u->id, $team);
                return in_array($createdBy, $teamIds, true)
                    || in_array($requested, $teamIds, true)
                    || in_array($assignedTo, $teamIds, true);
            }
            return false;
        }

        // موظف: يشوف تذاكره أو اللي مكلف فيها أو اللي مطلوبة له
        return ($createdBy === $me || $requested === $me || $assignedTo === $me);
    }

    // ---------------- Actions ----------------

    

   
   public function index()
{
    $filters = [
        'q'          => trim($_GET['q'] ?? ''),
        'status'     => trim($_GET['status'] ?? ''),
        'priority'   => trim($_GET['priority'] ?? ''),
        'team'       => trim($_GET['team'] ?? ''),
        'assigned_to'=> (int)($_GET['assigned_to'] ?? 0),
    ];

    $teams = method_exists($this->ticketModel, 'getDistinctTeams')
        ? $this->ticketModel->getDistinctTeams()
        : [];

    $usersForFilter = $this->getUsersForForm();

    $page    = max(1, (int)($_GET['p'] ?? 1));
    $perPage = 15;
    $offset  = ($page - 1) * $perPage;

    // حسب الصلاحيات
    if (function_exists('isSuperAdmin') && isSuperAdmin()) {
        $total = method_exists($this->ticketModel, 'countSearchAll')
            ? $this->ticketModel->countSearchAll($filters)
            : 0;

        $tickets = method_exists($this->ticketModel, 'searchAllPaged')
            ? $this->ticketModel->searchAllPaged($filters, $perPage, $offset)
            : (method_exists($this->ticketModel, 'searchAll') ? $this->ticketModel->searchAll($filters) : $this->ticketModel->getAll());

    } elseif (function_exists('isManager') && isManager()) {
        $total = method_exists($this->ticketModel, 'countSearchByManagerId')
            ? $this->ticketModel->countSearchByManagerId((int)$_SESSION['user_id'], $filters)
            : 0;

        $tickets = method_exists($this->ticketModel, 'searchByManagerIdPaged')
            ? $this->ticketModel->searchByManagerIdPaged((int)$_SESSION['user_id'], $filters, $perPage, $offset)
            : (method_exists($this->ticketModel, 'searchByManagerId') ? $this->ticketModel->searchByManagerId((int)$_SESSION['user_id'], $filters) : []);
    } else {
        $total = method_exists($this->ticketModel, 'countSearchByUserId')
            ? $this->ticketModel->countSearchByUserId((int)$_SESSION['user_id'], $filters)
            : 0;

        $tickets = method_exists($this->ticketModel, 'searchByUserIdPaged')
            ? $this->ticketModel->searchByUserIdPaged((int)$_SESSION['user_id'], $filters, $perPage, $offset)
            : (method_exists($this->ticketModel, 'searchByUserId') ? $this->ticketModel->searchByUserId((int)$_SESSION['user_id'], $filters) : $this->ticketModel->getTicketsByUserId((int)$_SESSION['user_id']));
    }

    // fallback لو count غير متوفر
    if ($total <= 0 && is_array($tickets)) {
        $total = count($tickets);
    }

    // fallback ticket_no/updated_at
    if (is_array($tickets)) {
        foreach ($tickets as $t) {
            if (!isset($t->ticket_no) || $t->ticket_no === null || $t->ticket_no === '') {
                $id = isset($t->id) ? (int)$t->id : 0;
                $t->ticket_no = $id > 0 ? ('TCK-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT)) : '-';
            }
            if (!isset($t->updated_at) || $t->updated_at === null || $t->updated_at === '') {
                $t->updated_at = $t->created_at ?? '';
            }
        }
    }

    $pages = (int)ceil($total / $perPage);
    if ($pages < 1) $pages = 1;
    if ($page > $pages) $page = $pages;

    $this->view('tickets/index', [
        'tickets' => $tickets,
        'filters' => $filters,
        'teams'   => $teams,
        'users'   => $usersForFilter,
        'pagination' => [
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pages' => $pages,
        ],
    ]);
}



    public function add() {
        requirePermission('tickets.add', 'tickets/index');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $createdBy = (int)$_SESSION['user_id'];

            // افتراضي: التذكرة لنفس المنشئ
            $requestedFor = $createdBy;

            // المدير/السوبر يقدر يفتحها لموظف
            if (((function_exists('isSuperAdmin') && isSuperAdmin()) || (function_exists('isManager') && isManager()))
                && !empty($_POST['requested_for_user_id'])) {

                $requestedFor = (int)$_POST['requested_for_user_id'];

                // حماية: المدير فقط لموظفيه
                if (function_exists('isManager') && isManager() && method_exists($this->userModel, 'getUsersByManager')) {
                    $team = $this->userModel->getUsersByManager($_SESSION['user_id']);
                    $teamIds = array_map(fn($u) => (int)$u->id, $team);
                    if (!in_array($requestedFor, $teamIds, true)) {
                        flash('access_denied', 'لا يمكنك فتح تذكرة لمستخدم خارج فريقك', 'alert alert-danger');
                        redirect('index.php?page=tickets/add');
                        exit;
                    }
                }
            }

            $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;

            // حماية التعيين للمدير
            if ($assignedTo && (function_exists('isManager') && isManager()) && method_exists($this->userModel, 'getUsersByManager')) {
                $team = $this->userModel->getUsersByManager($_SESSION['user_id']);
                $teamIds = array_map(fn($u) => (int)$u->id, $team);
                if (!in_array($assignedTo, $teamIds, true)) {
                    $assignedTo = null; // لا نسمح بتعيين خارج الفريق
                }
            }

            $data = [
                // للتوافق
                'created_by' => $createdBy,
                'user_id'    => $createdBy,

                'requested_for_user_id' => $requestedFor,
                'assigned_to'           => $assignedTo,

                'asset_id'     => !empty($_POST['asset_id']) ? (int)$_POST['asset_id'] : null,
                'team'         => trim($_POST['team'] ?? 'field_it'),
                'priority'     => trim($_POST['priority'] ?? 'Medium'),

                'subject'      => trim($_POST['subject'] ?? ''),
                'description'  => trim($_POST['description'] ?? ''),
                'contact_info' => trim($_POST['contact_info'] ?? ''),

                // لإعادة العرض
                'assets' => [],
                'users'  => [],

                // Errors
                'subject_err' => '',
                'description_err' => '',
                'contact_err' => '',
            ];

            if ($data['subject'] === '')     $data['subject_err'] = 'الرجاء كتابة عنوان للمشكلة';
            if ($data['description'] === '') $data['description_err'] = 'الرجاء كتابة وصف تفصيلي';
            if ($data['contact_info'] === '') $data['contact_err'] = 'الرجاء كتابة رقم/تحويلة للتواصل';

            if ($data['subject_err'] === '' && $data['description_err'] === '' && $data['contact_err'] === '') {
                if ($this->ticketModel->add($data)) {
                    flash('ticket_msg', 'تم فتح التذكرة بنجاح');
                    redirect('index.php?page=tickets/index');
                    exit;
                }
                die('حدث خطأ في قاعدة البيانات أثناء إضافة التذكرة');
            }

            // إعادة تعبئة القوائم
            $data['assets'] = $this->getAssetsForCurrentUser();
            $data['users']  = $this->getUsersForForm();

            $this->view('tickets/add', $data);
            return;
        }

        // GET
        $this->view('tickets/add', [
            'assets' => $this->getAssetsForCurrentUser(),
            'users'  => $this->getUsersForForm(),

            'team' => 'field_it',
            'priority' => 'Medium',
            'asset_id' => '',
            'subject' => '',
            'description' => '',
            'contact_info' => '',
            'requested_for_user_id' => '',
            'assigned_to' => '',

            'subject_err' => '',
            'description_err' => '',
            'contact_err' => '',
        ]);
    }

    public function show() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            redirect('index.php?page=tickets/index');
            exit;
        }

        $ticket = $this->ticketModel->getTicketById($id);
        if (!$this->canAccessTicket($ticket)) {
            flash('access_denied', 'لا تملك صلاحية عرض هذه التذكرة', 'alert alert-danger');
            redirect('index.php?page=tickets/index');
            exit;
        }

        $updates = method_exists($this->ticketModel, 'getUpdatesByTicketId')
            ? $this->ticketModel->getUpdatesByTicketId($id)
            : [];

        $attachments = method_exists($this->ticketModel, 'getAttachmentsByTicketId')
            ? $this->ticketModel->getAttachmentsByTicketId($id)
            : [];

        $users = $this->getUsersForForm();

        $this->view('tickets/show', [
            'ticket' => $ticket,
            'updates' => $updates,
            'attachments' => $attachments,
            'users' => $users,
        ]);
    }

    public function update_status() {
        requirePermission('tickets.assign', 'tickets/index');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tickets/index');
            exit;
        }

        $ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
        $status   = trim($_POST['status'] ?? '');
        $comment  = trim($_POST['comment'] ?? '');

        if ($ticketId <= 0 || $status === '') {
            flash('ticket_msg', 'بيانات غير مكتملة', 'alert alert-danger');
            redirect('index.php?page=tickets/index');
            exit;
        }

        $ticket = $this->ticketModel->getTicketById($ticketId);
        if (!$this->canAccessTicket($ticket)) {
            flash('access_denied', 'لا تملك صلاحية تعديل هذه التذكرة', 'alert alert-danger');
            redirect('index.php?page=tickets/index');
            exit;
        }

        // (اختياري) تحديث المسؤول assigned_to (لو أضفت حقل في الفورم لاحقاً)
        if (!empty($_POST['assigned_to']) && ((function_exists('isSuperAdmin') && isSuperAdmin()) || (function_exists('isManager') && isManager()))
            && method_exists($this->ticketModel, 'updateAssignedTo')) {

            $assignedTo = (int)$_POST['assigned_to'];

            // حماية للمدير: داخل الفريق فقط
            if (function_exists('isManager') && isManager() && method_exists($this->userModel, 'getUsersByManager')) {
                $team = $this->userModel->getUsersByManager($_SESSION['user_id']);
                $teamIds = array_map(fn($u) => (int)$u->id, $team);
                if (in_array($assignedTo, $teamIds, true)) {
                    $this->ticketModel->updateAssignedTo($ticketId, $assignedTo);
                }
            } else {
                $this->ticketModel->updateAssignedTo($ticketId, $assignedTo);
            }
        }

        // سجل تحديثات (إذا عندك جدول updates مستقبلاً)
        if (method_exists($this->ticketModel, 'addUpdate')) {
            $this->ticketModel->addUpdate([
                'ticket_id' => $ticketId,
                'user_id'   => (int)$_SESSION['user_id'],
                'status'    => $status,
                'comment'   => $comment
            ]);
        }

        if ($this->ticketModel->updateStatus($ticketId, $status)) {
            flash('ticket_msg', 'تم تحديث حالة التذكرة');
        } else {
            flash('ticket_msg', 'حدث خطأ أثناء التحديث', 'alert alert-danger');
        }

        redirect('index.php?page=tickets/show&id=' . $ticketId);
        exit;
    }

    public function escalate() {
        requirePermission('tickets.escalate', 'tickets/index');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tickets/index');
            exit;
        }

        $ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
        $team     = trim($_POST['team'] ?? '');
        $comment  = trim($_POST['comment'] ?? '');

        if ($ticketId <= 0 || $team === '') {
            flash('ticket_msg', 'بيانات التصعيد غير مكتملة', 'alert alert-danger');
            redirect('index.php?page=tickets/index');
            exit;
        }

        $ticket = $this->ticketModel->getTicketById($ticketId);
        if (!$this->canAccessTicket($ticket)) {
            flash('access_denied', 'لا تملك صلاحية التصعيد لهذه التذكرة', 'alert alert-danger');
            redirect('index.php?page=tickets/index');
            exit;
        }

        if (method_exists($this->ticketModel, 'updateTeam')) {
            $this->ticketModel->updateTeam($ticketId, $team);
        } else {
            flash('ticket_msg', 'ميزة التصعيد غير مفعلة في قاعدة البيانات حالياً', 'alert alert-warning');
            redirect('index.php?page=tickets/show&id=' . $ticketId);
            exit;
        }

        if (method_exists($this->ticketModel, 'addUpdate')) {
            $this->ticketModel->addUpdate([
                'ticket_id' => $ticketId,
                'user_id'   => (int)$_SESSION['user_id'],
                'status'    => 'Escalated',
                'comment'   => ($comment !== '') ? $comment : ('تم تصعيد التذكرة إلى: ' . $team),
            ]);
        }

        flash('ticket_msg', 'تم تصعيد التذكرة بنجاح');
        redirect('index.php?page=tickets/show&id=' . $ticketId);
        exit;
    }

    public function upload()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('index.php?page=tickets/index');
        exit;
    }

    $ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
    if ($ticketId <= 0) {
        redirect('index.php?page=tickets/index');
        exit;
    }

    $ticket = $this->ticketModel->getTicketById($ticketId);
    if (!$this->canAccessTicket($ticket)) {
        flash('access_denied', 'لا تملك صلاحية رفع مرفقات لهذه التذكرة', 'alert alert-danger');
        redirect('index.php?page=tickets/show&id=' . $ticketId);
        exit;
    }

    if (!isset($_FILES['files'])) {
        flash('ticket_msg', 'لم يتم اختيار ملفات', 'alert alert-warning');
        redirect('index.php?page=tickets/show&id=' . $ticketId);
        exit;
    }

    $allowed = ['jpg','jpeg','png','webp','pdf','doc','docx','xls','xlsx','txt','zip'];

    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/tickets/ticket_' . $ticketId . '/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

    $files = $_FILES['files'];
    $count = is_array($files['name']) ? count($files['name']) : 0;

    $savedAny = false;

    for ($i = 0; $i < $count; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;

        $originalName = (string)$files['name'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) continue;

        $newName = 'file_' . time() . '_' . $i . '.' . $ext;
        $dest = $uploadDir . $newName;

        if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
            $savedAny = true;

            if (method_exists($this->ticketModel, 'addAttachment')) {
                $this->ticketModel->addAttachment([
                    'ticket_id' => $ticketId,
                    'file_path' => 'uploads/tickets/ticket_' . $ticketId . '/' . $newName,
                    'original_name' => $originalName,
                    'uploaded_by' => (int)$_SESSION['user_id'],
                ]);
            }
        }
    }

    if ($savedAny) flash('ticket_msg', 'تم رفع المرفقات بنجاح');
    else flash('ticket_msg', 'لم يتم رفع أي ملف (تحقق من الامتدادات)', 'alert alert-warning');

    redirect('index.php?page=tickets/show&id=' . $ticketId);
    exit;
}

}
           

    

    

    
