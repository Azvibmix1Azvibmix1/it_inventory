<?php
class SparePart {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    // 1. جلب جميع قطع الغيار (مع اسم الموقع)
    // قمنا بتعديل هذه الدالة لتربط الجدولين وتجلب اسم الموقع بدلاً من رقمه
    public function getParts() {
        $this->db->query("SELECT parts.*, loc.name_ar as location_name 
                          FROM spare_parts parts
                          LEFT JOIN locations loc ON parts.location_id = loc.id
                          ORDER BY parts.created_at DESC");
        return $this->db->resultSet();
    }

    // دالة إضافية لجلب القطع لغرض التصدير أو غيره (بدون Join معقد)
    public function getAll() {
        $this->db->query("SELECT * FROM spare_parts ORDER BY created_at DESC");
        return $this->db->resultSet();
    }

    // 2. الدالة التي طلبتها (للاقتراحات)
    public function getExistingTypes() {
        $this->db->query("SELECT DISTINCT name FROM spare_parts ORDER BY name ASC");
        return $this->db->resultSet();
    }

    // 3. إضافة قطعة جديدة
    public function add($data) {
        // لاحظ: غيرنا location إلى location_id
        $this->db->query("INSERT INTO spare_parts (name, part_number, quantity, min_quantity, location_id, description) 
                          VALUES (:name, :part_num, :qty, :min_qty, :location_id, :desc)");
        
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':part_num', $data['part_number']);
        $this->db->bind(':qty', $data['quantity']);
        $this->db->bind(':min_qty', $data['min_quantity']);
        
        // التعامل مع الموقع (إذا كان فارغاً نرسل NULL)
        if(empty($data['location_id'])){
            $this->db->bind(':location_id', null);
        } else {
            $this->db->bind(':location_id', $data['location_id']);
        }

        $this->db->bind(':desc', $data['description']);

        return $this->db->execute();
    }

    // 4. جلب قطعة واحدة للتعديل
    public function getPartById($id) {
        $this->db->query("SELECT * FROM spare_parts WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // 5. تحديث البيانات
    public function update($data) {
        // تحديث location_id
        $this->db->query("UPDATE spare_parts SET 
                            name = :name, 
                            part_number = :part_num, 
                            quantity = :qty, 
                            min_quantity = :min_qty, 
                            location_id = :location_id, 
                            description = :desc 
                          WHERE id = :id");
        
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':part_num', $data['part_number']);
        $this->db->bind(':qty', $data['quantity']);
        $this->db->bind(':min_qty', $data['min_quantity']);
        
        // التعامل مع الموقع عند التحديث
        if(empty($data['location_id'])){
            $this->db->bind(':location_id', null);
        } else {
            $this->db->bind(':location_id', $data['location_id']);
        }

        $this->db->bind(':desc', $data['description']);
        $this->db->bind(':id', $data['id']);

        return $this->db->execute();
    }

    // 6. الحذف
    public function delete($id) {
        $this->db->query("DELETE FROM spare_parts WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    // 7. إحصائيات العدد
    public function getCounts() {
        $this->db->query("SELECT COUNT(*) as count FROM spare_parts");
        $row = $this->db->single();
        return $row->count;
    }
}