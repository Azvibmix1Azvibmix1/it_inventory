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

    // ---------- Helpers ----------
    private function getAssetsForCurrentUser() {
        if (isSuperAdmin() || isManager()) {
            return $this->assetModel->getAllAssets();
        }
        if (method_exists($this->assetModel, 'getAssetsByUserId')) {
            return $this->assetModel->getAssetsByUserId($_SESSION['user_id']);
        }
        return [];
    }

    private function getUsersForAssignment() {
        if (isSuperAdmin() && method_exists($this->userModel, 'getUsers')) {
            return $this->userModel->getUsers();
        }
        if (isManager() && method_exists($this->userModel, 'getUsersByManager')) {
            return $this->userModel->getUsersByManager($_SESSION['user_id']);
        }
        return [];
    }

    private function canAccessTicket($ticket) {
        if (!$ticket) return false;

        $me = (int)$_SESSION['user_id'];

        if (isSuperAdmin()) return true;

        $createdBy  = isset($ticket->created_by) ? (int)$ticket->created_by : 0;
        $assignedTo = isset($ticket->assigned_to) ? (int)$ticket->assigned_to : 0;

        if (isManager()) {
            // مدير يشوف تذاكره + تذاكر فريقه
            if ($createdBy === $me || $assignedTo === $me) return true;

            if (method_exists($this->userModel, 'getUsersByManager')) {
                $team = $this->userModel->getUsersByManager($me);
                $teamIds = array_map(fn($u) => (int)$u->id, $team);
                return in_array($createdBy, $teamIds, true) || in_array($assignedTo, $teamIds, true);
            }
            return false;
        }

        // موظف: تذاكره أو المكلف بها
        return ($createdBy === $me || $assignedTo === $me);
    }

    // ---------- Index ----------
    public function index() {
        if (isSuperAdmin()) {
            $tickets = $this->ticketModel->getAll();
        } elseif (isManager() && method_exists($this->ticketModel, 'getTicketsByManagerId')) {
            $tickets = $this->ticketModel->getTicketsByManagerId($_SESSION['user_id']);
        } elseif (isManager()) {
            $tickets = $this->ticketModel->getAll(); // مؤقتاً
        } else {
            $tickets = $this->ticketModel->getTicketsByUserId($_SESSION['user_id']);
        }

        $this->view('tickets/index', ['tickets' => $tickets]);
    }

    // ---------- Add ----------
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $createdBy = (int)$_SESSION['user_id'];

            $data = [
                // مهم: موديلك القديم كان يستخدم user_id — نخلي الاثنين للتوافق
                'created_by' => $createdBy,
                'user_id'    => $createdBy,

                'asset_id'      => !empty($_POST['asset_id']) ? (int)$_POST['asset_id'] : null,
                'subject'       => trim($_POST['subject'] ?? ''),
                'description'   => trim($_POST['description'] ?? ''),
                'contact_info'  => trim($_POST['contact_info'] ?? ''),
                'priority'      => trim($_POST['priority'] ?? 'Medium'),

                // للتطوير (التصعيد/الفريق)
                'team'          => trim($_POST['team'] ?? 'field_it'),

                'assets'        => [],
                'subject_err'   => '',
                'description_err' => '',
                'contact_err'   => '',
            ];

            if ($data['subject'] === '')      $data['subject_err'] = 'اكتب عنوان مختصر للمشكلة';
            if ($data['description'] === '')  $data['description_err'] = 'اكتب وصف تفصيلي للمشكلة';
            if ($data['contact_info'] === '') $data['contact_err'] = 'اكتب رقم/تحويلة للتواصل';

            if ($data['subject_err'] === '' && $data['description_err'] === '' && $data['contact_err'] === '') {
                if ($this->ticketModel->add($data)) {
                    flash('ticket_msg', 'تم فتح التذكرة بنجاح');
                    redirect('index.php?page=tickets/index');
                    exit;
                }
                die('خطأ في قاعدة البيانات أثناء إضافة التذكرة');
            }

            $data['assets'] = $this->getAssetsForCurrentUser();
            $this->view('tickets/add', $data);
            return;
        }

        $this->view('tickets/add', [
            'assets' => $this->getAssetsForCurrentUser(),
            'priority' => 'Medium',
            'subject' => '',
            'description' => '',
            'contact_info' => '',
            'team' => 'field_it',
            'subject_err' => '',
            'description_err' => '',
            'contact_err' => '',
        ]);
    }

    // ---------- Show ----------
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

        $users = (isSuperAdmin() || isManager()) ? $this->getUsersForAssignment() : [];

        $this->view('tickets/show', [
            'ticket' => $ticket,
            'updates' => $updates,
            'attachments' => $attachments,
            'users' => $users,
        ]);
    }

    // ---------- Update Status ----------
    public function update_status() {
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

    // ---------- Escalate ----------
    public function escalate() {
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

    // ---------- Upload ----------
    public function upload() {
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

            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) continue;

            $newName = 'img_' . time() . '_' . $i . '.' . $ext;
            $dest = $uploadDir . $newName;

            if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                $savedAny = true;

                if (method_exists($this->ticketModel, 'addAttachment')) {
                    $this->ticketModel->addAttachment([
                        'ticket_id' => $ticketId,
                        'file_path' => 'uploads/tickets/ticket_' . $ticketId . '/' . $newName,
                        'uploaded_by' => (int)$_SESSION['user_id'],
                    ]);
                }
            }
        }

        if ($savedAny) flash('ticket_msg', 'تم رفع الصور بنجاح');
        else flash('ticket_msg', 'لم يتم رفع أي صورة (تأكد من الامتداد jpg/png/webp)', 'alert alert-warning');

        redirect('index.php?page=tickets/show&id=' . $ticketId);
        exit;
    }
}
