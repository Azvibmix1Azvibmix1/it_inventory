<?php
class Ticket {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    // 1. جلب جميع التذاكر (للداشبورد وصفحة التذاكر)
    public function getAll() {
        // تم التصحيح: tickets.created_by بدلاً من user_id
        $this->db->query("SELECT tickets.*, users.name as user_name 
                          FROM tickets 
                          JOIN users ON tickets.created_by = users.id 
                          ORDER BY tickets.created_at DESC");
        return $this->db->resultSet();
    }

    // دالة احتياطية (تكرار للسابقة)
    public function getAllTickets() {
        return $this->getAll();
    }

    // 2. إحصائيات (العدد الكلي للداشبورد)
    public function getCount(){
        $this->db->query("SELECT COUNT(*) as count FROM tickets");
        $result = $this->db->single();
        return $result->count;
    }

    // 3. جلب آخر 5 تذاكر (للداشبورد)
    public function getRecentTickets(){
        // تم التصحيح: tickets.created_by بدلاً من user_id
        $this->db->query("SELECT tickets.*, users.name as user_name 
                          FROM tickets 
                          JOIN users ON tickets.created_by = users.id 
                          ORDER BY tickets.created_at DESC LIMIT 5");
        return $this->db->resultSet();
    }

    // 4. إضافة تذكرة
    public function add($data) {
        // تم التصحيح: إدخال في created_by
        $this->db->query("INSERT INTO tickets (created_by, asset_id, subject, description, status, priority) 
                          VALUES (:user_id, :asset_id, :subject, :description, 'Open', :priority)");
        
        $this->db->bind(':user_id', $data['user_id']); // القيمة تأتي من الجلسة user_id، لكن العمود في الجدول created_by
        $this->db->bind(':asset_id', $data['asset_id']); 
        $this->db->bind(':subject', $data['subject']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':priority', $data['priority']);
        
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    // 5. جلب تذاكر موظف معين
    public function getTicketsByUserId($user_id) {
        // تم التصحيح: البحث في created_by
        $this->db->query("SELECT tickets.*, assets.asset_tag 
                          FROM tickets 
                          LEFT JOIN assets ON tickets.asset_id = assets.id 
                          WHERE tickets.created_by = :user_id 
                          ORDER BY tickets.created_at DESC");
        
        $this->db->bind(':user_id', $user_id);
        return $this->db->resultSet();
    }

    // 6. جلب تفاصيل تذكرة واحدة
    public function getTicketById($id) {
        // تم التصحيح: tickets.created_by
        $this->db->query("SELECT tickets.*, 
                                 users.name as user_name, 
                                 users.email as user_email,
                                 assets.asset_tag, assets.brand, assets.model
                          FROM tickets 
                          JOIN users ON tickets.created_by = users.id 
                          LEFT JOIN assets ON tickets.asset_id = assets.id 
                          WHERE tickets.id = :id");
        
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // 7. تحديث حالة التذكرة
    public function updateStatus($id, $status) {
        $this->db->query("UPDATE tickets SET status = :status WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}