<?php

class Announcement
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    // جلب آخر الإعلانات
    public function getLatest()
    {
        $this->db->query("
            SELECT *
            FROM announcements
            WHERE is_published = 1
            ORDER BY created_at DESC
            LIMIT 5
        ");

        return $this->db->resultSet();
    }

    // إضافة إعلان جديد
    public function create($title, $body, $user_id)
    {
        $this->db->query("
            INSERT INTO announcements (title, body, created_by)
            VALUES (:title, :body, :user_id)
        ");

        $this->db->bind(':title', $title);
        $this->db->bind(':body', $body);
        $this->db->bind(':user_id', $user_id);

        return $this->db->execute();
    }
}
