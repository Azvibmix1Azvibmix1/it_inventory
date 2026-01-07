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
    // تذاكر مستخدم (أنشأها/مطلوبة له/مكلف بها) مع الأسماء
public function getTicketsByUserId($user_id) {
    $this->db->query("
        SELECT
            t.*,
            u.name  AS user_name,
            u.email AS user_email,
            rf.name AS requested_for_name,
            au.name AS assigned_to_name,
            a.asset_tag, a.brand, a.model
        FROM tickets t
        JOIN users u ON t.created_by = u.id
        LEFT JOIN users rf ON t.requested_for_user_id = rf.id
        LEFT JOIN users au ON t.assigned_to = au.id
        LEFT JOIN assets a ON t.asset_id = a.id
        WHERE t.created_by = :uid
           OR t.requested_for_user_id = :uid
           OR t.assigned_to = :uid
        ORDER BY t.created_at DESC
    ");
    $this->db->bind(':uid', (int)$user_id);
    return $this->db->resultSet();
}


    // 6) تذاكر مدير (يشوف تذاكر فريقه) - يفترض وجود manager_id في جدول users
    public function getTicketsByManagerId($manager_id) {
    $this->db->query("
        SELECT
            t.*,
            u.name  AS user_name,
            u.email AS user_email,
            rf.name AS requested_for_name,
            au.name AS assigned_to_name,
            a.asset_tag, a.brand, a.model
        FROM tickets t
        JOIN users u ON t.created_by = u.id
        LEFT JOIN users rf ON t.requested_for_user_id = rf.id
        LEFT JOIN users au ON t.assigned_to = au.id
        LEFT JOIN assets a ON t.asset_id = a.id
        WHERE u.manager_id = :mid
           OR t.assigned_to IN (SELECT id FROM users WHERE manager_id = :mid)
           OR t.requested_for_user_id IN (SELECT id FROM users WHERE manager_id = :mid)
        ORDER BY t.created_at DESC
    ");
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

    // جلب الأقسام/الفرق الموجودة (لـ dropdown)
public function getDistinctTeams(): array {
    $this->db->query("SELECT DISTINCT team FROM tickets WHERE team IS NOT NULL AND team <> '' ORDER BY team ASC");
    $rows = $this->db->resultSet();
    $teams = [];
    foreach ($rows as $r) {
        $teams[] = $r->team;
    }
    return $teams;
}

private function applyFiltersToSql(string $baseSql, array $filters, array &$binds): string {
    $where = [];

    // q: بحث عام
    if (!empty($filters['q'])) {
        $where[] = "(t.ticket_no LIKE :q OR t.subject LIKE :q OR t.description LIKE :q OR a.asset_tag LIKE :q OR u.name LIKE :q OR rf.name LIKE :q OR au.name LIKE :q)";
        $binds[':q'] = '%' . $filters['q'] . '%';
    }

    // status
    if (!empty($filters['status'])) {
        $where[] = "t.status = :status";
        $binds[':status'] = $filters['status'];
    }

    // priority
    if (!empty($filters['priority'])) {
        $where[] = "t.priority = :priority";
        $binds[':priority'] = $filters['priority'];
    }

    // team
    if (!empty($filters['team'])) {
        $where[] = "t.team = :team";
        $binds[':team'] = $filters['team'];
    }

    // assigned_to
    if (!empty($filters['assigned_to']) && (int)$filters['assigned_to'] > 0) {
        $where[] = "t.assigned_to = :assigned_to";
        $binds[':assigned_to'] = (int)$filters['assigned_to'];
    }

    if (!empty($where)) {
        // لو الاستعلام فيه WHERE مسبقًا نضيف AND
        if (stripos($baseSql, ' where ') !== false) {
            $baseSql .= " AND " . implode(" AND ", $where);
        } else {
            $baseSql .= " WHERE " . implode(" AND ", $where);
        }
    }

    return $baseSql;
}

// فلترة للسوبر أدمن
public function searchAll(array $filters = []) {
    $binds = [];

    $sql = "
        SELECT
            t.*,
            u.name  AS user_name,
            rf.name AS requested_for_name,
            au.name AS assigned_to_name,
            a.asset_tag, a.brand, a.model
        FROM tickets t
        JOIN users u ON t.created_by = u.id
        LEFT JOIN users rf ON t.requested_for_user_id = rf.id
        LEFT JOIN users au ON t.assigned_to = au.id
        LEFT JOIN assets a ON t.asset_id = a.id
    ";

    $sql = $this->applyFiltersToSql($sql, $filters, $binds);
    $sql .= " ORDER BY t.created_at DESC";

    $this->db->query($sql);
    foreach ($binds as $k => $v) $this->db->bind($k, $v);
    return $this->db->resultSet();
}

// فلترة للمدير (فريقه + اللي معيّنة عليهم/مطلوبة لهم)
public function searchByManagerId(int $managerId, array $filters = []) {
    $binds = [':mid' => $managerId];

    $sql = "
        SELECT
            t.*,
            u.name  AS user_name,
            rf.name AS requested_for_name,
            au.name AS assigned_to_name,
            a.asset_tag, a.brand, a.model
        FROM tickets t
        JOIN users u ON t.created_by = u.id
        LEFT JOIN users rf ON t.requested_for_user_id = rf.id
        LEFT JOIN users au ON t.assigned_to = au.id
        LEFT JOIN assets a ON t.asset_id = a.id
        WHERE
            u.manager_id = :mid
            OR t.assigned_to IN (SELECT id FROM users WHERE manager_id = :mid)
            OR t.requested_for_user_id IN (SELECT id FROM users WHERE manager_id = :mid)
    ";

    $sql = $this->applyFiltersToSql($sql, $filters, $binds);
    $sql .= " ORDER BY t.created_at DESC";

    $this->db->query($sql);
    foreach ($binds as $k => $v) $this->db->bind($k, $v);
    return $this->db->resultSet();
}

// فلترة للموظف (تذاكره/المطلوبة له/المكلف بها)
public function searchByUserId(int $userId, array $filters = []) {
    $binds = [':uid' => $userId];

    $sql = "
        SELECT
            t.*,
            u.name  AS user_name,
            rf.name AS requested_for_name,
            au.name AS assigned_to_name,
            a.asset_tag, a.brand, a.model
        FROM tickets t
        JOIN users u ON t.created_by = u.id
        LEFT JOIN users rf ON t.requested_for_user_id = rf.id
        LEFT JOIN users au ON t.assigned_to = au.id
        LEFT JOIN assets a ON t.asset_id = a.id
        WHERE
            t.created_by = :uid
            OR t.requested_for_user_id = :uid
            OR t.assigned_to = :uid
    ";

    // ملاحظة: الموظف ممكن يفلتر داخل نطاقه عادي
    $sql = $this->applyFiltersToSql($sql, $filters, $binds);
    $sql .= " ORDER BY t.created_at DESC";

    $this->db->query($sql);
    foreach ($binds as $k => $v) $this->db->bind($k, $v);
    return $this->db->resultSet();
}

}
