<?php
class DashboardController extends Controller {
    private $assetModel;
    private $ticketModel;
    private $userModel;
    private $sparePartModel; // إضافة موديل قطع الغيار

    public function __construct(){
        if(!isLoggedIn()){
            redirect('index.php?page=users/login');
        }

        $this->assetModel = $this->model('Asset');
        $this->ticketModel = $this->model('Ticket');
        $this->userModel = $this->model('User');
        $this->sparePartModel = $this->model('SparePart'); // تحميل الموديل
    }

    public function index(){
        // 1. جلب البيانات الخام (لتجنب أخطاء الدوال غير الموجودة في الموديل)
        // في المشاريع الكبيرة يفضل عمل دوال count في الموديل، لكن هنا سنحسبها برمجياً للسرعة
        
        $all_assets = $this->assetModel->getAllAssets();
        $all_tickets = $this->ticketModel->getAll(); // تأكد أن دالة getAll موجودة في Ticket.php
        $all_parts = $this->sparePartModel->getAll();
        
        // 2. حساب الإحصائيات
        $assets_count = count($all_assets);
        
        // حساب التذاكر "المفتوحة" فقط
        $open_tickets = 0;
        if(!empty($all_tickets)){
            foreach($all_tickets as $ticket){
                if($ticket->status == 'Open') $open_tickets++;
            }
        }

        // حساب قطع الغيار المنخفضة (أقل من 5)
        $low_stock = 0;
        if(!empty($all_parts)){
            foreach($all_parts as $part){
                if($part->quantity <= 5) $low_stock++;
            }
        }

        // إحصائيات أنواع الأصول (للرسم البياني)
        $asset_types = [
            'Laptop' => 0, 'Desktop' => 0, 'Monitor' => 0, 'Printer' => 0, 'Other' => 0
        ];
        
        if(!empty($all_assets)){
            foreach($all_assets as $asset){
                if(isset($asset_types[$asset->type])){
                    $asset_types[$asset->type]++;
                } else {
                    $asset_types['Other']++;
                }
            }
        }

        $data = [
            'title' => 'لوحة التحكم',
            'assets_count' => $assets_count,
            'tickets_count' => $open_tickets, // نرسل التذاكر المفتوحة
            'users_count' => $this->userModel->getUserCount(), // تأكد أن هذه الدالة موجودة في User.php
            'low_stock' => $low_stock,
            'asset_chart_data' => $asset_types, // بيانات الرسم البياني
            'recent_tickets' => array_slice($all_tickets, 0, 5) // آخر 5 تذاكر
        ];

        $this->view('dashboard/index', $data);
    }
    
    public function add_announcement(){
        // دالة مستقبلية
    }
}