<?php
class TicketsController extends Controller {
    private $ticketModel;
    private $assetModel;
    private $userModel;

    public function __construct(){
        // التحقق من تسجيل الدخول
        if(!isLoggedIn()){
            redirect('index.php?page=login');
        }

        // تحميل الموديلات
        $this->ticketModel = $this->model('Ticket');
        $this->assetModel = $this->model('Asset');
        $this->userModel = $this->model('User');
    }

    public function index(){
        // إذا كان مدير (عام أو قسم) يرى كل التذاكر
        if(isSuperAdmin() || isManager()){
            $tickets = $this->ticketModel->getAll();
        } else {
            // الموظف يرى تذاكره فقط
            $tickets = $this->ticketModel->getTicketsByUserId($_SESSION['user_id']);
        }

        $data = [
            'tickets' => $tickets
        ];

        $this->view('tickets/index', $data);
    }

    public function add(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'user_id' => $_SESSION['user_id'],
                'asset_id' => !empty($_POST['asset_id']) ? $_POST['asset_id'] : null,
                'subject' => trim($_POST['subject']),
                'description' => trim($_POST['description']),
                'priority' => trim($_POST['priority']),
                'assets' => [], // سنملؤها في حال الخطأ
                'subject_err' => '',
                'description_err' => ''
            ];

            // التحقق من البيانات
            if(empty($data['subject'])){
                $data['subject_err'] = 'الرجاء كتابة عنوان للمشكلة';
            }
            if(empty($data['description'])){
                $data['description_err'] = 'الرجاء كتابة وصف تفصيلي';
            }

            if(empty($data['subject_err']) && empty($data['description_err'])){
                if($this->ticketModel->add($data)){
                    flash('ticket_msg', 'تم فتح التذكرة بنجاح');
                    redirect('index.php?page=tickets/index');
                } else {
                    die('حدث خطأ في قاعدة البيانات');
                }
            } else {
                // في حال وجود خطأ، نعيد تحميل الأصول
                if(isSuperAdmin() || isManager()){
                    $data['assets'] = $this->assetModel->getAllAssets();
                } else {
                    // الموظف يرى أصوله فقط
                    // تأكد أن دالة getAssetsByUserId موجودة في موديل Asset، وإلا استخدم getAllAssets مؤقتاً
                    if(method_exists($this->assetModel, 'getAssetsByUserId')){
                        $data['assets'] = $this->assetModel->getAssetsByUserId($_SESSION['user_id']);
                    } else {
                        $data['assets'] = [];
                    }
                }
                
                $this->view('tickets/add', $data);
            }

        } else {
            // GET Request: عرض الصفحة
            
            // جلب الأصول للقائمة المنسدلة
            $assets = [];
            if(isSuperAdmin() || isManager()){
                $assets = $this->assetModel->getAllAssets();
            } else {
                if(method_exists($this->assetModel, 'getAssetsByUserId')){
                    $assets = $this->assetModel->getAssetsByUserId($_SESSION['user_id']);
                }
            }

            $data = [
                'assets' => $assets,
                'subject' => '',
                'description' => '',
                'priority' => 'Low',
                'asset_id' => '',
                'subject_err' => '',
                'description_err' => ''
            ];
            
            $this->view('tickets/add', $data);
        }
    }

    public function show(){
        if(!isset($_GET['id'])){
            redirect('index.php?page=tickets/index');
        }

        $ticket = $this->ticketModel->getTicketById($_GET['id']);

        // التحقق من الصلاحية: هل التذكرة تخص المستخدم الحالي؟ أو أنه مدير؟
        if(!isSuperAdmin() && !isManager() && $ticket->created_by != $_SESSION['user_id']){
            flash('access_denied', 'لا تملك صلاحية عرض هذه التذكرة', 'alert alert-danger');
            redirect('index.php?page=tickets/index');
            exit;
        }

        $data = [
            'ticket' => $ticket
        ];

        $this->view('tickets/show', $data);
    }

    // دالة لتغيير حالة التذكرة (للمدراء فقط)
    // يتم استدعاؤها عبر POST من صفحة show أو index
    public function update_status(){
        if(!isSuperAdmin() && !isManager()){
            redirect('index.php?page=tickets/index');
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $id = $_POST['ticket_id'];
            $status = $_POST['status'];
            
            if($this->ticketModel->updateStatus($id, $status)){
                flash('ticket_msg', 'تم تحديث حالة التذكرة');
            } else {
                flash('ticket_msg', 'حدث خطأ أثناء التحديث', 'alert alert-danger');
            }
            // العودة للصفحة السابقة
            redirect('index.php?page=tickets/index');
        }
    }
}
?>