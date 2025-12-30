<?php

class TicketsController extends Controller
{
    private $ticketModel;
    private $assetModel;
    private $userModel;

    public function __construct()
    {
        // الأفضل توحيدها: استخدم requireLogin من Controller.php (إذا موجود عندك)
        if (method_exists($this, 'requireLogin')) {
            $this->requireLogin();
        } else {
            if (!isLoggedIn()) {
                redirect('index.php?page=login');
                exit;
            }
        }

        $this->ticketModel = $this->model('Ticket');
        $this->assetModel  = $this->model('Asset');
        $this->userModel   = $this->model('User');
    }

    // ---------- Helpers ----------
    private function getAssetsForCurrentUser()
    {
        // سوبر/مدير يشوف كل الأصول
        if (isSuperAdmin() || isManager()) {
            return $this->assetModel->getAllAssets();
        }

        // الموظف يشوف عهده فقط (إذا الدالة موجودة)
        if (method_exists($this->assetModel, 'getAssetsByUserId')) {
            return $this->assetModel->getAssetsByUserId($_SESSION['user_id']);
        }

        return [];
    }

    private function canAccessTicket($ticket)
    {
        if (!$ticket) return false;

        if (isSuperAdmin()) return true;

        // المدير: الأفضل يشوف تذاكر فريقه فقط
        if (isManager()) {
            // إذا عندك في التذكرة created_by أو assigned_to
            $ownerId = isset($ticket->created_by) ? (int)$ticket->created_by : 0;
            $assigneeId = isset($ticket->assigned_to) ? (int)$ticket->assigned_to : 0;

            // لو عندك دالة في موديل User تجيب التابعين للمدير:
            if (method_exists($this->userModel, 'getUsersByManager')) {
                $team = $this->userModel->getUsersByManager($_SESSION['user_id']);
                $teamIds = array_map(fn($u) => (int)$u->id, $team);

                if (in_array($ownerId, $teamIds, true) || in_array($assigneeId, $teamIds, true)) {
                    return true;
                }
            }

            // fallback: على الأقل لو هو اللي أنشأها أو مكلّف بها
            if ($ownerId === (int)$_SESSION['user_id'] || $assigneeId === (int)$_SESSION['user_id']) {
                return true;
            }

            return false;
        }

        // الموظف: يشوف تذاكره فقط (أو المكلّف بها)
        $createdBy = isset($ticket->created_by) ? (int)$ticket->created_by : 0;
        $assignedTo = isset($ticket->assigned_to) ? (int)$ticket->assigned_to : 0;

        return ($createdBy === (int)$_SESSION['user_id'] || $assignedTo === (int)$_SESSION['user_id']);
    }

    // ---------- Index ----------
    public function index()
    {
        // سوبر أدمن: كل التذاكر
        if (isSuperAdmin()) {
            $tickets = $this->ticketModel->getAll();
        }
        // مدير: تذاكر فريقه (لو عندك دالة بالموديل)
        elseif (isManager() && method_exists($this->ticketModel, 'getTicketsByManagerId')) {
            $tickets = $this->ticketModel->getTicketsByManagerId($_SESSION['user_id']);
        }
        // مدير (fallback): يعرض كل التذاكر (مؤقتاً) — لكن الأفضل تضيف الدالة فوق في الموديل لاحقاً
        elseif (isManager()) {
            $tickets = $this->ticketModel->getAll();
        }
        // موظف: تذاكره
        else {
            $tickets = $this->ticketModel->getTicketsByUserId($_SESSION['user_id']);
        }

        $data = [
            'tickets' => $tickets
        ];

        $this->view('tickets/index', $data);
    }

    // ---------- Add ----------
    public function add()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            // من أنشأ التذكرة
            $createdBy = (int)$_SESSION['user_id'];

            // لمن التذكرة؟ (الأدمن/المدير يقدر يفتحها لموظف عنده)
            $requestedFor = $createdBy;
            if ((isSuperAdmin() || isManager()) && !empty($_POST['requested_for_user_id'])) {
                $requestedFor = (int)$_POST['requested_for_user_id'];

                // حماية: المدير ما يفتح إلا للي تحت إدارته
                if (isManager() && method_exists($this->userModel, 'getUsersByManager')) {
                    $team = $this->userModel->getUsersByManager($_SESSION['user_id']);
                    $teamIds = array_map(fn($u) => (int)$u->id, $team);

                    if (!in_array($requestedFor, $teamIds, true)) {
                        flash('access_denied', 'لا يمكنك فتح تذكرة لمستخدم خارج فريقك', 'alert alert-danger');
                        redirect('index.php?page=tickets/add');
                        exit;
                    }
                }
            }

            $data = [
                // مفاتيح آمنة (ونحط القديم كمان لو موديلك يعتمد عليه)
                'created_by'   => $createdBy,
                'user_id'      => $createdBy,

                'requested_for_user_id' => $requestedFor,

                'asset_id'     => !empty($_POST['asset_id']) ? (int)$_POST['asset_id'] : null,
                'subject'      => trim($_POST['subject'] ?? ''),
                'description'  => trim($_POST['description'] ?? ''),
                'priority'     => trim($_POST['priority'] ?? 'Low'),

                // تجهيزات للتطوير لاحقاً:
                // team: field_it / network / security / electricity
                'team'         => trim($_POST['team'] ?? 'field_it'),
                // assigned_to: مين مسؤول عنها (اختياري)
                'assigned_to'  => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,

                'assets'       => [],

                'subject_err'      => '',
                'description_err'  => ''
            ];

            if (empty($data['subject'])) {
                $data['subject_err'] = 'الرجاء كتابة عنوان للمشكلة';
            }
            if (empty($data['description'])) {
                $data['description_err'] = 'الرجاء كتابة وصف تفصيلي';
            }

            if (empty($data['subject_err']) && empty($data['description_err'])) {

                if ($this->ticketModel->add($data)) {
                    flash('ticket_msg', 'تم فتح التذكرة بنجاح');
                    redirect('index.php?page=tickets/index');
                    exit;
                }

                die('حدث خطأ في قاعدة البيانات');
            }

            // في حال وجود خطأ
            $data['assets'] = $this->getAssetsForCurrentUser();
            $this->view('tickets/add', $data);

        } else {

            $assets = $this->getAssetsForCurrentUser();

            $data = [
                'assets'      => $assets,
                'subject'     => '',
                'description' => '',
                'priority'    => 'Low',
                'asset_id'    => '',
                // تجهيزات للتطوير:
                'team'        => 'field_it',
                'assigned_to' => '',
                'requested_for_user_id' => '',
                'subject_err' => '',
                'description_err' => ''
            ];

            $this->view('tickets/add', $data);
        }
    }

    // ---------- Show ----------
    public function show()
    {
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

        // (اختياري) لو عندك لاحقاً سجل تحديثات + مرفقات
        $updates = method_exists($this->ticketModel, 'getUpdatesByTicketId')
            ? $this->ticketModel->getUpdatesByTicketId($id)
            : [];

        $attachments = method_exists($this->ticketModel, 'getAttachmentsByTicketId')
            ? $this->ticketModel->getAttachmentsByTicketId($id)
            : [];

        $data = [
            'ticket' => $ticket,
            'updates' => $updates,
            'attachments' => $attachments
        ];

        $this->view('tickets/show', $data);
    }

    // ---------- Update Status (User/Manager/Super) ----------
    public function update_status()
    {
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

        // لو موديلك يدعم log للتحديثات
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

    // ---------- Escalate (تصعيد) ----------
    public function escalate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=tickets/index');
            exit;
        }

        $ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
        $team     = trim($_POST['team'] ?? ''); // network/security/electricity/field_it
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

        // إذا موديل Ticket فيه updateTeam / escalateTo
        if (method_exists($this->ticketModel, 'updateTeam')) {
            $this->ticketModel->updateTeam($ticketId, $team);
        } elseif (method_exists($this->ticketModel, 'escalateTo')) {
            $this->ticketModel->escalateTo($ticketId, $team);
        } else {
            // مؤقتاً: لو ما عندك هذا في قاعدة البيانات، بنوقف هنا
            flash('ticket_msg', 'ميزة التصعيد غير مفعلة في قاعدة البيانات حالياً', 'alert alert-warning');
            redirect('index.php?page=tickets/show&id=' . $ticketId);
            exit;
        }

        // سجل تحديث (اختياري)
        if (method_exists($this->ticketModel, 'addUpdate')) {
            $this->ticketModel->addUpdate([
                'ticket_id' => $ticketId,
                'user_id'   => (int)$_SESSION['user_id'],
                'status'    => 'escalated',
                'comment'   => $comment !== '' ? $comment : ('تم تصعيد التذكرة إلى: ' . $team)
            ]);
        }

        flash('ticket_msg', 'تم تصعيد التذكرة بنجاح');
        redirect('index.php?page=tickets/show&id=' . $ticketId);
        exit;
    }

    // ---------- Upload Attachments (صور) ----------
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
            flash('access_denied', 'لا تملك صلاحية رفع صور لهذه التذكرة', 'alert alert-danger');
            redirect('index.php?page=tickets/show&id=' . $ticketId);
            exit;
        }

        if (!isset($_FILES['images'])) {
            flash('ticket_msg', 'لم يتم اختيار صور', 'alert alert-warning');
            redirect('index.php?page=tickets/show&id=' . $ticketId);
            exit;
        }

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/tickets/ticket_' . $ticketId . '/';

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $files = $_FILES['images'];
        $count = is_array($files['name']) ? count($files['name']) : 0;

        $savedAny = false;

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

            $name = $files['name'][$i];
            $tmp  = $files['tmp_name'][$i];

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) continue;

            $newName = 'img_' . time() . '_' . $i . '.' . $ext;
            $dest = $uploadDir . $newName;

            if (move_uploaded_file($tmp, $dest)) {
                $savedAny = true;

                // سجل بالموديل إذا عندك attachments table
                if (method_exists($this->ticketModel, 'addAttachment')) {
                    $this->ticketModel->addAttachment([
                        'ticket_id' => $ticketId,
                        'file_path' => 'uploads/tickets/ticket_' . $ticketId . '/' . $newName,
                        'uploaded_by' => (int)$_SESSION['user_id'],
                    ]);
                }
            }
        }

        if ($savedAny) {
            flash('ticket_msg', 'تم رفع الصور بنجاح');
        } else {
            flash('ticket_msg', 'لم يتم رفع أي صورة (تأكد من الامتداد: jpg/png/webp)', 'alert alert-warning');
        }

        redirect('index.php?page=tickets/show&id=' . $ticketId);
        exit;
    }
}
?>
