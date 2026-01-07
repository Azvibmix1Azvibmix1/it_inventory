<?php

class Ticket
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    /**
     * استعلام موحد لكل القوائم والتفاصيل
     * يرجع: اسم المنشئ، اسم المطلوب له، اسم المسؤول، ومعلومات الأصل
     */
    private function baseSelectSql(): string
    {
        return "
            SELECT
                t.*,
                u.name  AS user_name,
                u.email AS user_email,
                rf.name AS requested_for_name,
                au.name AS assigned_to_name,
                a.asset_tag,
                a.brand,
                a.model
            FROM tickets t
            JOIN users u ON t.created_by = u.id
            LEFT JOIN users rf ON t.requested_for_user_id = rf.id
            LEFT JOIN users au ON t.assigned_to = au.id
            LEFT JOIN assets a ON t.asset_id = a.id
        ";
    }

    // 1) جلب جميع التذاكر (للسوبر أدمن)
    public function getAll()
    {
        $sql = $this->baseSelectSql() . " ORDER BY t.created_at DESC";
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    // alias (توافق)
    public function getAllTickets()
    {
        return $this->getAll();
    }

    // 2) عدد التذاكر
    public function getCount()
    {
        $this->db->query("SELECT COUNT(*) as count FROM tickets");
        $result = $this->db->single();
        return (int)($result->count ?? 0);
    }

    // 3) آخر 5 تذاكر
    public function getRecentTickets()
    {
        $sql = $this->baseSelectSql() . " ORDER BY t.created_at DESC LIMIT 5";
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    // 4) إضافة تذكرة
    public function add($data)
    {
        $this->db->query("
            INSERT INTO tickets (
                created_by,
                requested_for_user_id,
                assigned_to,
                asset_id,
                subject,
                description,
                contact_info,
                team,
                status,
                priority
            ) VALUES (
                :created_by,
                :requested_for_user_id,
                :assigned_to,
                :asset_id,
                :subject,
                :description,
                :contact_info,
                :team,
                'Open',
                :priority
            )
        ");

        $this->db->bind(':created_by', (int)($data['created_by'] ?? $data['user_id'] ?? 0));
        $this->db->bind(':requested_for_user_id', !empty($data['requested_for_user_id']) ? (int)$data['requested_for_user_id'] : null);
        $this->db->bind(':assigned_to', !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null);
        $this->db->bind(':asset_id', !empty($data['asset_id']) ? (int)$data['asset_id'] : null);

        $this->db->bind(':subject', $data['subject'] ?? '');
        $this->db->bind(':description', $data['description'] ?? '');
        $this->db->bind(':contact_info', $data['contact_info'] ?? null);

        $this->db->bind(':team', $data['team'] ?? 'field_it');
        $this->db->bind(':priority', $data['priority'] ?? 'Medium');

        $ok = $this->db->execute();
        if (!$ok) return false;

        // لو عندك عمود ticket_no (اللي أضفناه بالـ ALTER) نعبّيه تلقائي بعد الإدخال
        if (method_exists($this->db, 'lastInsertId')) {
            $id = (int)$this->db->lastInsertId();
            if ($id > 0) {
                $this->db->query("
                    UPDATE tickets
                    SET ticket_no = CONCAT('TCK-', LPAD(id, 6, '0'))
                    WHERE id = :id AND (ticket_no IS NULL OR ticket_no = '')
                ");
                $this->db->bind(':id', $id);
                $this->db->execute();
            }
        }

        return true;
    }

    // 5) تذاكر مستخدم (يشوف: اللي أنشأها أو اللي مطلوبة له أو المعيّنة عليه)
    public function getTicketsByUserId($user_id)
    {
        $sql = $this->baseSelectSql() . "
            WHERE t.created_by = :uid
               OR t.requested_for_user_id = :uid
               OR t.assigned_to = :uid
            ORDER BY t.created_at DESC
        ";
        $this->db->query($sql);
        $this->db->bind(':uid', (int)$user_id);
        return $this->db->resultSet();
    }

    // 6) تذاكر مدير (يشوف تذاكر فريقه) - يفترض وجود manager_id في جدول users
    public function getTicketsByManagerId($manager_id)
    {
        $sql = $this->baseSelectSql() . "
            WHERE
                u.manager_id = :mid
                OR t.assigned_to IN (SELECT id FROM users WHERE manager_id = :mid)
                OR t.requested_for_user_id IN (SELECT id FROM users WHERE manager_id = :mid)
            ORDER BY t.created_at DESC
        ";
        $this->db->query($sql);
        $this->db->bind(':mid', (int)$manager_id);
        return $this->db->resultSet();
    }

    // 7) تفاصيل تذكرة واحدة
    public function getTicketById($id)
    {
        $sql = $this->baseSelectSql() . " WHERE t.id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    // 8) تحديث الحالة (مع closed_at)
    public function updateStatus($id, $status)
    {
        $status = (string)$status;

        if ($status === 'Closed' || $status === 'Resolved') {
            $this->db->query("UPDATE tickets SET status = :status, closed_at = NOW() WHERE id = :id");
        } else {
            // لو رجعنا فتحها نخلي closed_at فاضي
            $this->db->query("UPDATE tickets SET status = :status, closed_at = NULL WHERE id = :id");
        }

        $this->db->bind(':status', $status);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    // 9) تحديث القسم (للتصعيد)
    public function updateTeam($id, $team)
    {
        $this->db->query("UPDATE tickets SET team = :team WHERE id = :id");
        $this->db->bind(':team', $team);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    // 10) تعيين التذكرة لموظف
    public function updateAssignedTo($id, $assigned_to)
    {
        $this->db->query("UPDATE tickets SET assigned_to = :assigned_to WHERE id = :id");
        $this->db->bind(':assigned_to', !empty($assigned_to) ? (int)$assigned_to : null);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }
}
