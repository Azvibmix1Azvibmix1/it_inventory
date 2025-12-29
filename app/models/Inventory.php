<?php
// app/models/Inventory.php
class Inventory {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    // إنشاء جلسة جرد جديدة
    public function createSession($lab_id, $user_id){
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO inventory_sessions (lab_id, conducted_by, session_date, status) VALUES (:lab, :user, CURDATE(), 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':lab', $lab_id);
        $stmt->bindValue(':user', $user_id);
        
        if($stmt->execute()){
            // الآن نأتي بالمعرف الجديد للجلسة
            $session_id = $conn->lastInsertId();
            
            // ونسحب كل الأجهزة الموجودة في هذا المعمل ونضيفها لتفاصيل الجرد
            // لكي نقوم بفحصها واحداً تلو الآخر
            $sql_assets = "INSERT INTO inventory_details (session_id, asset_id, is_found) 
                           SELECT :sess_id, id, 0 FROM assets WHERE location_id = :lab_id";
            
            $stmt2 = $conn->prepare($sql_assets);
            $stmt2->bindValue(':sess_id', $session_id);
            $stmt2->bindValue(':lab_id', $lab_id);
            $stmt2->execute();

            return $session_id;
        }
        return false;
    }

    // جلب تفاصيل الجلسة (الأجهزة وحالتها)
    public function getSessionDetails($session_id){
        $conn = $this->db->getConnection();
        $sql = "SELECT d.*, a.asset_tag, a.brand, a.model, a.serial_number 
                FROM inventory_details d
                JOIN assets a ON d.asset_id = a.id
                WHERE d.session_id = :sid";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':sid', $session_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // تحديث حالة جهاز (وجدناه أم لا)
    public function updateItemStatus($detail_id, $is_found){
        $conn = $this->db->getConnection();
        $sql = "UPDATE inventory_details SET is_found = :found WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':found', $is_found);
        $stmt->bindValue(':id', $detail_id);
        return $stmt->execute();
    }
    
    // إنهاء الجلسة
    public function closeSession($id){
        $conn = $this->db->getConnection();
        $sql = "UPDATE inventory_sessions SET status = 'completed' WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }
}
?>