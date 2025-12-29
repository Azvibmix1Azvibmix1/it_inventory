<?php
class Location {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    // 1. جلب كل المواقع (للقوائم وترتيبها)
    public function getAllWithHierarchy() {
        $this->db->query("SELECT * FROM locations ORDER BY name_ar ASC");
        return $this->db->resultSet();
    }

    // 2. جلب جميع المواقع (حسب التاريخ)
    public function getAll() {
        $this->db->query("SELECT * FROM locations ORDER BY created_at DESC");
        return $this->db->resultSet();
    }

    // 3. جلب المواقع الرئيسية فقط
    public function getMainLocations() {
        $this->db->query("SELECT * FROM locations WHERE parent_id IS NULL OR parent_id = 0 ORDER BY id ASC");
        return $this->db->resultSet();
    }

    // 4. إضافة موقع جديد
    public function add($data) {
        $this->db->query("INSERT INTO locations (name_ar, name_en, type, parent_id) VALUES (:name_ar, :name_en, :type, :parent_id)");
        
        $this->db->bind(':name_ar', $data['name_ar']);
        $this->db->bind(':name_en', $data['name_en']);
        $this->db->bind(':type', $data['type']);
        $this->db->bind(':parent_id', !empty($data['parent_id']) ? $data['parent_id'] : null);

        return $this->db->execute();
    }

    // 5. تحديث موقع (هذه الدالة كانت ناقصة وأضفتها لك)
    public function update($data) {
        $this->db->query('UPDATE locations SET name_ar = :name_ar, name_en = :name_en, type = :type, parent_id = :parent_id WHERE id = :id');
        
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name_ar', $data['name_ar']);
        $this->db->bind(':name_en', $data['name_en']);
        $this->db->bind(':type', $data['type']);
        $this->db->bind(':parent_id', !empty($data['parent_id']) ? $data['parent_id'] : null);

        return $this->db->execute();
    }

    // 6. حذف موقع
    public function delete($id) {
        // حذف الأبناء أولاً
        $this->db->query("DELETE FROM locations WHERE parent_id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();

        // حذف الموقع نفسه
        $this->db->query("DELETE FROM locations WHERE id = :id");
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    // 7. جلب موقع واحد للتعديل
    public function getLocationById($id){
        $this->db->query("SELECT * FROM locations WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }


    // جلب المواقع الفرعية (ضرورية للعرض الهرمي في صفحة الاندكس)
    public function getSubLocations($parent_id) {
        $this->db->query("SELECT * FROM locations WHERE parent_id = :parent_id ORDER BY id ASC");
        $this->db->bind(':parent_id', $parent_id);
        return $this->db->resultSet();
    }
}