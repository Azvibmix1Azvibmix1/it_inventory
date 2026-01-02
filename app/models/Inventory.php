<?php

class Inventory
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    // إنشاء جلسة جرد
    public function createSession($lab_id, $user_id)
    {
        $this->db->query("
            INSERT INTO inventory_sessions (lab_id, conducted_by, session_date, status)
            VALUES (:lab_id, :user_id, CURDATE(), 'pending')
        ");

        $this->db->bind(':lab_id', $lab_id);
        $this->db->bind(':user_id', $user_id);

        if ($this->db->execute()) {
            return true;
        }

        return false;
    }

    // جلب تفاصيل الجرد
    public function getSessionDetails($session_id)
    {
        $this->db->query("
            SELECT 
                d.*, 
                a.asset_tag,
                a.brand,
                a.model,
                a.serial_no
            FROM inventory_details d
            JOIN assets a ON d.asset_id = a.id
            WHERE d.session_id = :session_id
        ");

        $this->db->bind(':session_id', $session_id);
        return $this->db->resultSet();
    }

    // تحديث حالة جهاز
    public function updateItemStatus($detail_id, $is_found)
    {
        $this->db->query("
            UPDATE inventory_details
            SET is_found = :is_found
            WHERE id = :id
        ");

        $this->db->bind(':is_found', $is_found);
        $this->db->bind(':id', $detail_id);

        return $this->db->execute();
    }

    // إنهاء الجرد
    public function closeSession($id)
    {
        $this->db->query("
            UPDATE inventory_sessions
            SET status = 'completed'
            WHERE id = :id
        ");

        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
