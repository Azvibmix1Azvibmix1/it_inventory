<?php
class Location {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    // جلب كل المواقع (نستخدمها في الأصول وقطع الغيار والتذاكر)
    public function getAll() {
        $this->db->query("SELECT * FROM locations ORDER BY type, name_ar");
        return $this->db->resultSet();
    }

    // جلب المواقع الأساسية (فروع / كليات) لو حبيت تستخدمها في الهيكل
    public function getMainLocations() {
        $this->db->query("SELECT * FROM locations 
                          WHERE parent_id IS NULL 
                          ORDER BY name_ar");
        return $this->db->resultSet();
    }

    // جلب موقع واحد بالرقم
    public function getLocationById($id) {
        $this->db->query("SELECT * FROM locations WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // إضافة موقع جديد
    public function add($data) {
        $this->db->query("INSERT INTO locations (name_ar, name_en, type, parent_id) 
                          VALUES (:name_ar, :name_en, :type, :parent_id)");

        $this->db->bind(':name_ar', $data['name_ar']);
        $this->db->bind(':name_en', $data['name_en']);
        $this->db->bind(':type',     $data['type']);
        $this->db->bind(':parent_id', $data['parent_id']);

        return $this->db->execute();
    }

    // تعديل موقع
    public function update($data) {
        $this->db->query("UPDATE locations 
                          SET name_ar = :name_ar,
                              name_en = :name_en,
                              type    = :type,
                              parent_id = :parent_id
                          WHERE id = :id");

        $this->db->bind(':name_ar', $data['name_ar']);
        $this->db->bind(':name_en', $data['name_en']);
        $this->db->bind(':type',     $data['type']);
        $this->db->bind(':parent_id', $data['parent_id']);
        $this->db->bind(':id',        $data['id']);

        return $this->db->execute();
    }

    // حذف موقع
    public function delete($id) {
        $this->db->query("DELETE FROM locations WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
