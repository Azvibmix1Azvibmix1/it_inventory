<?php

class Location
{
  private $db;

  public function __construct()
  {
    $this->db = new Database();
  }

  // جلب كل المواقع
  public function getAll()
  {
    $this->db->query("SELECT * FROM locations ORDER BY type, name_ar");
    return $this->db->resultSet();
  }

  // جلب المواقع الأساسية (parent null)
  public function getMainLocations()
  {
    $this->db->query("SELECT * FROM locations WHERE parent_id IS NULL ORDER BY name_ar");
    return $this->db->resultSet();
  }

  // جلب موقع واحد
  public function getLocationById($id)
  {
    $this->db->query("SELECT * FROM locations WHERE id = :id");
    $this->db->bind(':id', (int)$id);
    return $this->db->single();
  }

  // إضافة موقع
  public function add($data)
  {
    $this->db->query("INSERT INTO locations (name_ar, name_en, type, parent_id)
                      VALUES (:name_ar, :name_en, :type, :parent_id)");
    $this->db->bind(':name_ar', $data['name_ar']);
    $this->db->bind(':name_en', $data['name_en']);
    $this->db->bind(':type', $data['type']);
    $this->db->bind(':parent_id', $data['parent_id']);
    return $this->db->execute();
  }

  // تعديل موقع
  public function update($data)
  {
    $this->db->query("UPDATE locations
                      SET name_ar = :name_ar, name_en = :name_en, type = :type, parent_id = :parent_id
                      WHERE id = :id");
    $this->db->bind(':name_ar', $data['name_ar']);
    $this->db->bind(':name_en', $data['name_en']);
    $this->db->bind(':type', $data['type']);
    $this->db->bind(':parent_id', $data['parent_id']);
    $this->db->bind(':id', (int)$data['id']);
    return $this->db->execute();
  }

  // حذف موقع
  public function delete($id)
  {
    $this->db->query("DELETE FROM locations WHERE id = :id");
    $this->db->bind(':id', (int)$id);
    return $this->db->execute();
  }

  /**
   * ===========================
   * ✅ وظائف الهيكل (Children)
   * ===========================
   */
  public function getChildren($parentId)
  {
    $this->db->query("SELECT * FROM locations WHERE parent_id = :pid ORDER BY id DESC");
    $this->db->bind(':pid', (int)$parentId);
    return $this->db->resultSet();
  }

  /**
   * ============================================
   *  صلاحيات المواقع (Role + User permissions)
   * ============================================
   */
  public function getRolePerms($locationId)
  {
    $this->db->query("SELECT role, can_manage, can_add_children, can_edit, can_delete
                      FROM locations_permissions
                      WHERE location_id = :loc AND role IS NOT NULL");
    $this->db->bind(':loc', (int)$locationId);
    $rows = $this->db->resultSet();

    $out = [];
    foreach ($rows as $r) {
      $out[$r->role] = [
        'can_manage'       => (int)$r->can_manage,
        'can_add_children' => (int)$r->can_add_children,
        'can_edit'         => (int)$r->can_edit,
        'can_delete'       => (int)$r->can_delete,
      ];
    }
    return $out;
  }

  public function saveRolePerms($locationId, $role, $perms)
  {
    $this->db->query("
      INSERT INTO locations_permissions (location_id, role, can_manage, can_add_children, can_edit, can_delete)
      VALUES (:loc, :role, :m, :a, :e, :d)
      ON DUPLICATE KEY UPDATE
        can_manage = VALUES(can_manage),
        can_add_children = VALUES(can_add_children),
        can_edit = VALUES(can_edit),
        can_delete = VALUES(can_delete)
    ");
    $this->db->bind(':loc', (int)$locationId);
    $this->db->bind(':role', $role);
    $this->db->bind(':m', (int)($perms['can_manage'] ?? 0));
    $this->db->bind(':a', (int)($perms['can_add_children'] ?? 0));
    $this->db->bind(':e', (int)($perms['can_edit'] ?? 0));
    $this->db->bind(':d', (int)($perms['can_delete'] ?? 0));
    return $this->db->execute();
  }

  /**
   * نجيب المستخدمين (استخدمنا SELECT * لتفادي مشاكل اختلاف الأعمدة)
   */
  public function getUsersLite()
  {
    $this->db->query("SELECT * FROM users ORDER BY id DESC");
    return $this->db->resultSet();
  }

  public function getUserPerms($locationId)
  {
    $this->db->query("
      SELECT lp.user_id,
             lp.can_manage, lp.can_add_children, lp.can_edit, lp.can_delete,
             u.*
      FROM locations_permissions lp
      JOIN users u ON u.id = lp.user_id
      WHERE lp.location_id = :loc AND lp.user_id IS NOT NULL
      ORDER BY u.id DESC
    ");
    $this->db->bind(':loc', (int)$locationId);
    return $this->db->resultSet();
  }

  public function addOrUpdateUserPerm($locationId, $userId, $perms)
  {
    $this->db->query("
      INSERT INTO locations_permissions (location_id, user_id, can_manage, can_add_children, can_edit, can_delete)
      VALUES (:loc, :uid, :m, :a, :e, :d)
      ON DUPLICATE KEY UPDATE
        can_manage = VALUES(can_manage),
        can_add_children = VALUES(can_add_children),
        can_edit = VALUES(can_edit),
        can_delete = VALUES(can_delete)
    ");
    $this->db->bind(':loc', (int)$locationId);
    $this->db->bind(':uid', (int)$userId);
    $this->db->bind(':m', (int)($perms['can_manage'] ?? 0));
    $this->db->bind(':a', (int)($perms['can_add_children'] ?? 0));
    $this->db->bind(':e', (int)($perms['can_edit'] ?? 0));
    $this->db->bind(':d', (int)($perms['can_delete'] ?? 0));
    return $this->db->execute();
  }

  public function removeUserPerm($locationId, $userId)
  {
    $this->db->query("DELETE FROM locations_permissions WHERE location_id = :loc AND user_id = :uid");
    $this->db->bind(':loc', (int)$locationId);
    $this->db->bind(':uid', (int)$userId);
    return $this->db->execute();
  }

  /**
   * ===========================
   *  Audit / سجل التعديلات
   * ===========================
   */
  public function audit($locationId, $userId, $action, $details = null)
  {
    $this->db->query("INSERT INTO locations_audit (location_id, user_id, action, details)
                      VALUES (:loc, :uid, :act, :det)");
    $this->db->bind(':loc', (int)$locationId);
    $this->db->bind(':uid', $userId ? (int)$userId : null);
    $this->db->bind(':act', $action);
    $this->db->bind(':det', $details);
    return $this->db->execute();
  }

  public function getAudit($locationId, $limit = 20)
  {
    $limit = (int)$limit;
    if ($limit <= 0) $limit = 20;

    $this->db->query("
      SELECT a.*,
             u.name AS user_name,
             u.username AS user_username,
             u.email AS user_email
      FROM locations_audit a
      LEFT JOIN users u ON u.id = a.user_id
      WHERE a.location_id = :loc
      ORDER BY a.id DESC
      LIMIT $limit
    ");
    $this->db->bind(':loc', (int)$locationId);
    return $this->db->resultSet();
  }
}
