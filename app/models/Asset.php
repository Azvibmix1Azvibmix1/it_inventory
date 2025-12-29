<?php
class Asset {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    // --- الدالة الجديدة التي يطلبها الداشبورد ---
    public function getCounts(){
        $this->db->query("SELECT COUNT(*) as total FROM assets");
        $result = $this->db->single();
        return $result->total;
    }
    // -------------------------------------------

    // جلب جميع الأصول (مع اسم الموقع العربي name_ar)
    public function getAllAssets(){
        $this->db->query('SELECT assets.*, locations.name_ar as location_name 
                          FROM assets 
                          LEFT JOIN locations ON assets.location_id = locations.id 
                          ORDER BY assets.created_at DESC');
        return $this->db->resultSet();
    }

    // جلب أصول موظف محدد
    public function getAssetsByUser($user_id){
        $this->db->query('SELECT assets.*, locations.name_ar as location_name 
                          FROM assets 
                          LEFT JOIN locations ON assets.location_id = locations.id 
                          WHERE assets.assigned_to = :user_id
                          ORDER BY assets.created_at DESC');
        $this->db->bind(':user_id', $user_id);
        return $this->db->resultSet();
    }

    // جلب أصل واحد بواسطة المعرف
    public function getAssetById($id){
        $this->db->query('SELECT assets.*, locations.name_ar as location_name 
                          FROM assets 
                          LEFT JOIN locations ON assets.location_id = locations.id 
                          WHERE assets.id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // إضافة أصل جديد
    public function add($data){
        $this->db->query('INSERT INTO assets (name, serial_number, model, brand, purchase_date, warranty_expiry, status, location_id, assigned_to, notes) 
                          VALUES(:name, :serial_number, :model, :brand, :purchase_date, :warranty_expiry, :status, :location_id, :assigned_to, :notes)');
        
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':serial_number', $data['serial_number']);
        $this->db->bind(':model', $data['model']);
        $this->db->bind(':brand', $data['brand']);
        $this->db->bind(':purchase_date', $data['purchase_date']);
        $this->db->bind(':warranty_expiry', $data['warranty_expiry']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':location_id', $data['location_id']);
        $this->db->bind(':assigned_to', $data['assigned_to']);
        $this->db->bind(':notes', $data['notes']);

        return $this->db->execute();
    }

    // تحديث أصل
    public function update($data){
        $this->db->query('UPDATE assets SET name = :name, serial_number = :serial_number, model = :model, brand = :brand, 
                          purchase_date = :purchase_date, warranty_expiry = :warranty_expiry, status = :status, 
                          location_id = :location_id, assigned_to = :assigned_to, notes = :notes 
                          WHERE id = :id');
        
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':serial_number', $data['serial_number']);
        $this->db->bind(':model', $data['model']);
        $this->db->bind(':brand', $data['brand']);
        $this->db->bind(':purchase_date', $data['purchase_date']);
        $this->db->bind(':warranty_expiry', $data['warranty_expiry']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':location_id', $data['location_id']);
        $this->db->bind(':assigned_to', $data['assigned_to']);
        $this->db->bind(':notes', $data['notes']);

        return $this->db->execute();
    }

    // حذف أصل
    public function delete($id){
        $this->db->query('DELETE FROM assets WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    public function changeStatus($id, $status){
        $this->db->query('UPDATE assets SET status = :status WHERE id = :id');
        $this->db->bind(':id', $id);
        $this->db->bind(':status', $status);
        return $this->db->execute();
    }

    // إحصائيات حالة الأجهزة للرسم البياني
    public function getAssetStats(){
        $this->db->query("SELECT status, COUNT(*) as count FROM assets GROUP BY status");
        return $this->db->resultSet();
    }
}