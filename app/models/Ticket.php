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

    private function baseFromSql(): string
    {
        return "
            FROM tickets t
            JOIN users u ON t.created_by = u.id
            LEFT JOIN users rf ON t.requested_for_user_id = rf.id
            LEFT JOIN users au ON t.assigned_to = au.id
            LEFT JOIN assets a ON t.asset_id = a.id
        ";
    }

    private function applyFiltersToSql(string $baseSql, array $filters, array &$binds): string
    {
        $where = [];

        // q: بحث عام
        if (!empty($filters['q'])) {
            $where[] = "(t.ticket_no LIKE :q
                      OR t.subject LIKE :q
                      OR t.description LIKE :q
                      OR a.asset_tag LIKE :q
                      OR u.name LIKE :q
                      OR rf.name LIKE :q
                      OR au.name LIKE :q)";
            $binds[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['status'])) {
            $where[] = "t.status = :status";
            $binds[':status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $where[] = "t.priority = :priority";
            $binds[':priority'] = $filters['priority'];
        }

        if (!empty($filters['team'])) {
            $where[] = "t.team = :team";
            $binds[':team'] = $filters['team'];
        }

        if (!empty($filters['assigned_to']) && (int)$filters['assigned_to'] > 0) {
            $where[] = "t.assigned_to = :assigned_to";
            $binds[':assigned_to'] = (int)$filters['assigned_to'];
        }

        if (!empty($where)) {
            if (stripos($baseSql, ' where ') !== false) {
                $baseSql .= " AND " . implode(" AND ", $where);
            } else {
                $baseSql .= " WHERE " . implode(" AND ", $where);
            }
        }

        return $baseSql;
    }

    private function applyLimitOffset(string $sql, int $limit, int $offset, array &$binds): string
    {
        if ($limit > 0) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $binds[':limit'] = (int)$limit;
            $binds[':offset'] = max(0, (int)$offset);
        }
        return $sql;
    }

    // ---------------- Basic ----------------

    public function getAll()
    {
        $sql = $this->baseSelectSql() . " ORDER BY t.created_at DESC";
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    public function getAllTickets()
    {
        return $this->getAll();
    }

    public function getCount()
    {
        $this->db->query("SELECT COUNT(*) as count FROM tickets");
        $result = $this->db->single();
        return (int)($result->count ?? 0);
    }

    public function getRecentTickets()
    {
        $sql = $this->baseSelectSql() . " ORDER BY t.created_at DESC LIMIT 5";
        $this->db->query($sql);
        return $this->db->resultSet();
    }

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

        // تعبئة ticket_no إن كان موجود
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

    public function getTicketsByManagerId($manager_id)
    {
        $sql = $this->baseSelectSql() . "
            WHERE u.manager_id = :mid
               OR t.assigned_to IN (SELECT id FROM users WHERE manager_id = :mid)
               OR t.requested_for_user_id IN (SELECT id FROM users WHERE manager_id = :mid)
            ORDER BY t.created_at DESC
        ";
        $this->db->query($sql);
        $this->db->bind(':mid', (int)$manager_id);
        return $this->db->resultSet();
    }

    public function getTicketById($id)
    {
        $sql = $this->baseSelectSql() . " WHERE t.id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    // ---------------- Update ticket (closed_at handled) ----------------

    public function updateStatus($id, $status)
    {
        $status = (string)$status;

        if ($status === 'Closed' || $status === 'Resolved') {
            $this->db->query("UPDATE tickets SET status = :status, closed_at = NOW(), updated_at = NOW() WHERE id = :id");
        } else {
            $this->db->query("UPDATE tickets SET status = :status, closed_at = NULL, updated_at = NOW() WHERE id = :id");
        }

        $this->db->bind(':status', $status);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function updateTeam($id, $team)
    {
        $this->db->query("UPDATE tickets SET team = :team, updated_at = NOW() WHERE id = :id");
        $this->db->bind(':team', $team);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function updateAssignedTo($id, $assigned_to)
    {
        $this->db->query("UPDATE tickets SET assigned_to = :assigned_to, updated_at = NOW() WHERE id = :id");
        $this->db->bind(':assigned_to', !empty($assigned_to) ? (int)$assigned_to : null);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function getDistinctTeams(): array
    {
        $this->db->query("SELECT DISTINCT team FROM tickets WHERE team IS NOT NULL AND team <> '' ORDER BY team ASC");
        $rows = $this->db->resultSet();
        $teams = [];
        foreach ($rows as $r) $teams[] = $r->team;
        return $teams;
    }




    // ---------------- Filters + Pagination ----------------

    public function countSearchAll(array $filters = []): int
    {
        $binds = [];
        $sql = "SELECT COUNT(DISTINCT t.id) AS cnt " . $this->baseFromSql();
        $sql = $this->applyFiltersToSql($sql, $filters, $binds);

        $this->db->query($sql);
        foreach ($binds as $k => $v) $this->db->bind($k, $v);
        $row = $this->db->single();
        return (int)($row->cnt ?? 0);
    }

    public function countSearchByManagerId(int $managerId, array $filters = []): int
    {
        $binds = [':mid' => $managerId];
        $sql = "SELECT COUNT(DISTINCT t.id) AS cnt " . $this->baseFromSql() . "
            WHERE u.manager_id = :mid
               OR t.assigned_to IN (SELECT id FROM users WHERE manager_id = :mid)
               OR t.requested_for_user_id IN (SELECT id FROM users WHERE manager_id = :mid)
        ";
        $sql = $this->applyFiltersToSql($sql, $filters, $binds);

        $this->db->query($sql);
        foreach ($binds as $k => $v) $this->db->bind($k, $v);
        $row = $this->db->single();
        return (int)($row->cnt ?? 0);
    }

    public function countSearchByUserId(int $userId, array $filters = []): int
    {
        $binds = [':uid' => $userId];
        $sql = "SELECT COUNT(DISTINCT t.id) AS cnt " . $this->baseFromSql() . "
            WHERE t.created_by = :uid
               OR t.requested_for_user_id = :uid
               OR t.assigned_to = :uid
        ";
        $sql = $this->applyFiltersToSql($sql, $filters, $binds);

        $this->db->query($sql);
        foreach ($binds as $k => $v) $this->db->bind($k, $v);
        $row = $this->db->single();
        return (int)($row->cnt ?? 0);
    }

    public function searchAllPaged(array $filters = [], int $limit = 15, int $offset = 0)
    {
        $binds = [];
        $sql = $this->baseSelectSql();
        $sql = $this->applyFiltersToSql($sql, $filters, $binds);
        $sql .= " ORDER BY t.created_at DESC";
        $sql = $this->applyLimitOffset($sql, $limit, $offset, $binds);

        $this->db->query($sql);
        foreach ($binds as $k => $v) $this->db->bind($k, $v);
        return $this->db->resultSet();
    }

    public function searchByManagerIdPaged(int $managerId, array $filters = [], int $limit = 15, int $offset = 0)
    {
        $binds = [':mid' => $managerId];
        $sql = $this->baseSelectSql() . "
            WHERE u.manager_id = :mid
               OR t.assigned_to IN (SELECT id FROM users WHERE manager_id = :mid)
               OR t.requested_for_user_id IN (SELECT id FROM users WHERE manager_id = :mid)
        ";
        $sql = $this->applyFiltersToSql($sql, $filters, $binds);
        $sql .= " ORDER BY t.created_at DESC";
        $sql = $this->applyLimitOffset($sql, $limit, $offset, $binds);

        $this->db->query($sql);
        foreach ($binds as $k => $v) $this->db->bind($k, $v);
        return $this->db->resultSet();
    }

   

    // ---------------- Timeline (Updates) ----------------

    public function getUpdatesByTicketId(int $ticketId): array
    {
        try {
            $this->db->query("
                SELECT tu.*, u.name AS user_name
                FROM ticket_updates tu
                LEFT JOIN users u ON tu.user_id = u.id
                WHERE tu.ticket_id = :tid
                ORDER BY tu.created_at DESC
            ");
            $this->db->bind(':tid', $ticketId);
            return $this->db->resultSet();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function addUpdate(array $data): bool
    {
        try {
            $this->db->query("
                INSERT INTO ticket_updates (ticket_id, user_id, status, comment)
                VALUES (:ticket_id, :user_id, :status, :comment)
            ");
            $this->db->bind(':ticket_id', (int)($data['ticket_id'] ?? 0));
            $this->db->bind(':user_id', (int)($data['user_id'] ?? 0));
            $this->db->bind(':status', (string)($data['status'] ?? ''));
            $this->db->bind(':comment', ($data['comment'] ?? ''));

            $ok = $this->db->execute();

            // تحديث updated_at للتذكرة (إن كان موجود)
            if ($ok) {
                $this->db->query("UPDATE tickets SET updated_at = NOW() WHERE id = :id");
                $this->db->bind(':id', (int)($data['ticket_id'] ?? 0));
                $this->db->execute();
            }

            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    // ---------------- Attachments ----------------

    public function getAttachmentsByTicketId(int $ticketId): array
    {
        try {
            $this->db->query("
                SELECT ta.*, u.name AS uploaded_by_name
                FROM ticket_attachments ta
                LEFT JOIN users u ON ta.uploaded_by = u.id
                WHERE ta.ticket_id = :tid
                ORDER BY ta.created_at DESC
            ");
            $this->db->bind(':tid', $ticketId);
            return $this->db->resultSet();
        } catch (Throwable $e) {
            return [];
        }
    }

   






public function addAttachment(array $data): bool
{
    try {
        $this->db->query("
            INSERT INTO ticket_attachments (ticket_id, file_path, original_name, uploaded_by)
            VALUES (:ticket_id, :file_path, :original_name, :uploaded_by)
        ");
        $this->db->bind(':ticket_id', (int)($data['ticket_id'] ?? 0));
        $this->db->bind(':file_path', (string)($data['file_path'] ?? ''));
        $this->db->bind(':original_name', (string)($data['original_name'] ?? ''));
        $this->db->bind(':uploaded_by', (int)($data['uploaded_by'] ?? 0));

        $ok = $this->db->execute();

        if ($ok) {
            $this->db->query("UPDATE tickets SET updated_at = NOW() WHERE id = :id");
            $this->db->bind(':id', (int)($data['ticket_id'] ?? 0));
            $this->db->execute();
        }

        return (bool)$ok;
    } catch (Throwable $e) {
        return false;
    }
}




}
