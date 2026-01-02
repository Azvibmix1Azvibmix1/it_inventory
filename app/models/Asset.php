<?php

class Asset
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    // عدد الأصول (للوحة التحكم)
    public function getCounts()
    {
        $this->db->query("SELECT COUNT(*) AS total FROM assets");
        $result = $this->db->single();
        return $result ? $result->total : 0;
    }

    // جلب جميع الأصول مع اسم الموقع
    public function getAllAssets()
    {
        $this->db->query("
            SELECT 
                assets.*, 
                locations.name_ar AS location_name
            FROM assets
            LEFT JOIN locations 
                ON assets.location_id = locations.id
            ORDER BY assets.created_at DESC
        ");
        return $this->db->resultSet();
    }

    // جلب أصول موظف معيّن (حسب العهدة)
    public function getAssetsByUser($user_id)
    {
        $this->db->query("
            SELECT 
                assets.*, 
                locations.name_ar AS location_name
            FROM assets
            LEFT JOIN locations 
                ON assets.location_id = locations.id
            WHERE assets.assigned_to = :user_id
            ORDER BY assets.created_at DESC
        ");
        $this->db->bind(':user_id', $user_id);
        return $this->db->resultSet();
    }

    // Alias عشان يتوافق مع الكنترولر my_assets()
    public function getAssetsByUserId($user_id)
    {
        return $this->getAssetsByUser($user_id);
    }

    // جلب أصل واحد بالمعرّف
    public function getAssetById($id)
    {
        $this->db->query("
            SELECT 
                assets.*, 
                locations.name_ar AS location_name
            FROM assets
            LEFT JOIN locations 
                ON assets.location_id = locations.id
            WHERE assets.id = :id
            LIMIT 1
        ");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // إضافة أصل جديد
    public function add($data)
    {
        // مهم: هذه الأعمدة لازم تكون موجودة في جدول assets
        // asset_tag, serial_no, brand, model, type, status, location_id, assigned_to
        $this->db->query("
            INSERT INTO assets 
                (asset_tag, serial_no, brand, model, type, status, location_id, assigned_to)
            VALUES
                (:asset_tag, :serial_no, :brand, :model, :type, :status, :location_id, :assigned_to)
        ");

        $this->db->bind(':asset_tag',   $data['asset_tag']   ?? '');
        $this->db->bind(':serial_no',   $data['serial_no']   ?? '');
        $this->db->bind(':brand',       $data['brand']       ?? '');
        $this->db->bind(':model',       $data['model']       ?? '');
        $this->db->bind(':type',        $data['type']        ?? '');
        $this->db->bind(':status',      $data['status']      ?? 'Active');
        $this->db->bind(':location_id', $data['location_id'] ?? null);
        $this->db->bind(':assigned_to', $data['assigned_to'] ?? null);

        return $this->db->execute();
    }

    // تحديث أصل
    public function update($data)
    {
        $this->db->query("
            UPDATE assets
            SET 
                asset_tag   = :asset_tag,
                serial_no   = :serial_no,
                brand       = :brand,
                model       = :model,
                type        = :type,
                status      = :status,
                location_id = :location_id,
                assigned_to = :assigned_to
            WHERE id = :id
        ");

        $this->db->bind(':id',          $data['id']);
        $this->db->bind(':asset_tag',   $data['asset_tag']   ?? '');
        $this->db->bind(':serial_no',   $data['serial_no']   ?? '');
        $this->db->bind(':brand',       $data['brand']       ?? '');
        $this->db->bind(':model',       $data['model']       ?? '');
        $this->db->bind(':type',        $data['type']        ?? '');
        $this->db->bind(':status',      $data['status']      ?? 'Active');
        $this->db->bind(':location_id', $data['location_id'] ?? null);
        $this->db->bind(':assigned_to', $data['assigned_to'] ?? null);

        return $this->db->execute();
    }

    // حذف أصل
    public function delete($id)
    {
        $this->db->query("DELETE FROM assets WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // تغيير حالة الأصل فقط (للاستخدام من لوحة التحكم أو الحركات)
    public function changeStatus($id, $status)
    {
        $this->db->query("
            UPDATE assets 
            SET status = :status 
            WHERE id = :id
        ");
        $this->db->bind(':id', $id);
        $this->db->bind(':status', $status);
        return $this->db->execute();
    }

    // إحصائيات حالة الأجهزة للرسم البياني
    public function getAssetStats()
    {
        $this->db->query("
            SELECT status, COUNT(*) AS count
            FROM assets
            GROUP BY status
        ");
        return $this->db->resultSet();
    }
}
