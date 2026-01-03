<?php

class Asset
{
  private $db;

  public function __construct()
  {
    $this->db = new Database();
  }

  /* =========================
   * Dashboard helpers
   * ========================= */

  public function getCounts()
  {
    $this->db->query("SELECT COUNT(*) as total FROM assets");
    $row = $this->db->single();
    return $row ? (int)$row->total : 0;
  }

  public function getAssetStats()
  {
    $this->db->query("SELECT status, COUNT(*) as count FROM assets GROUP BY status");
    return $this->db->resultSet();
  }

  /* =========================
   * Queries
   * ========================= */

  public function getAllAssets()
  {
    $sql = "
      SELECT assets.*,
             locations.name_ar as location_name,
             users.name as assigned_name,
             users.email as assigned_email
      FROM assets
      LEFT JOIN locations ON assets.location_id = locations.id
      LEFT JOIN users ON assets.assigned_to = users.id
      ORDER BY assets.created_at DESC, assets.id DESC
    ";
    $this->db->query($sql);
    return $this->db->resultSet();
  }

  /**
   * فلترة + صلاحيات مواقع
   * $filters:
   * - location_id (int)
   * - q (string)
   * - include_children (مستقبلاً)
   *
   * $allowedLocationIds:
   * - null => الكل (سوبر/مانجر)
   * - [] => لا شيء
   * - [1,2,3] => مواقع محددة
   */
  public function getAssetsFiltered($filters = [], $allowedLocationIds = null)
  {
    $locationId = (int)($filters['location_id'] ?? 0);
    $q          = trim($filters['q'] ?? '');

    // لو ما عنده أي موقع مسموح -> رجّع فاضي بسرعة
    if (is_array($allowedLocationIds) && count($allowedLocationIds) === 0) {
      return [];
    }

    $sql = "
      SELECT assets.*,
             locations.name_ar as location_name,
             users.name as assigned_name,
             users.email as assigned_email
      FROM assets
      LEFT JOIN locations ON assets.location_id = locations.id
      LEFT JOIN users ON assets.assigned_to = users.id
    ";

    $where = [];

    // صلاحيات المواقع
    if (is_array($allowedLocationIds)) {
      $placeholders = [];
      $i = 0;
      foreach ($allowedLocationIds as $lid) {
        $key = ':loc' . $i;
        $placeholders[] = $key;
        $i++;
      }
      $where[] = "assets.location_id IN (" . implode(',', $placeholders) . ")";
    }

    // فلتر موقع
    if ($locationId > 0) {
      $where[] = "assets.location_id = :filter_loc";
    }

    // بحث
    if ($q !== '') {
      $where[] = "(
        assets.asset_tag LIKE :q
        OR assets.serial_no LIKE :q
        OR assets.brand LIKE :q
        OR assets.model LIKE :q
        OR assets.type LIKE :q
        OR assets.notes LIKE :q
      )";
    }

    if (!empty($where)) {
      $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY assets.created_at DESC, assets.id DESC";

    $this->db->query($sql);

    // bind صلاحيات المواقع
    if (is_array($allowedLocationIds)) {
      $i = 0;
      foreach ($allowedLocationIds as $lid) {
        $this->db->bind(':loc' . $i, (int)$lid);
        $i++;
      }
    }

    // bind فلتر موقع
    if ($locationId > 0) {
      $this->db->bind(':filter_loc', $locationId);
    }

    // bind بحث
    if ($q !== '') {
      $this->db->bind(':q', '%' . $q . '%');
    }

    return $this->db->resultSet();
  }

  public function getAssetsByUserId($userId)
  {
    $sql = "
      SELECT assets.*,
             locations.name_ar as location_name
      FROM assets
      LEFT JOIN locations ON assets.location_id = locations.id
      WHERE assets.assigned_to = :uid
      ORDER BY assets.created_at DESC, assets.id DESC
    ";
    $this->db->query($sql);
    $this->db->bind(':uid', (int)$userId);
    return $this->db->resultSet();
  }

  // توافق مع اسم قديم (لو موجود بأي مكان)
  public function getAssetsByUser($user_id)
  {
    return $this->getAssetsByUserId($user_id);
  }

  public function getAssetById($id)
  {
    $sql = "
      SELECT assets.*,
             locations.name_ar as location_name,
             users.name as assigned_name,
             users.email as assigned_email
      FROM assets
      LEFT JOIN locations ON assets.location_id = locations.id
      LEFT JOIN users ON assets.assigned_to = users.id
      WHERE assets.id = :id
      LIMIT 1
    ";
    $this->db->query($sql);
    $this->db->bind(':id', (int)$id);
    return $this->db->single();
  }

  /* =========================
   * Mutations
   * ========================= */

  public function add($data)
  {
    // ✅ Backward compatibility: لو جاءك name/serial_number من أي كود قديم
    if (empty($data['asset_tag']) && !empty($data['name'])) {
      $data['asset_tag'] = $data['name'];
    }
    if (empty($data['serial_no']) && !empty($data['serial_number'])) {
      $data['serial_no'] = $data['serial_number'];
    }

    $sql = "
      INSERT INTO assets
        (asset_tag, serial_no, model, brand, type, purchase_date, warranty_expiry, status,
         location_id, assigned_to, notes, created_by)
      VALUES
        (:asset_tag, :serial_no, :model, :brand, :type, :purchase_date, :warranty_expiry, :status,
         :location_id, :assigned_to, :notes, :created_by)
    ";

    $this->db->query($sql);

    $this->db->bind(':asset_tag', trim($data['asset_tag'] ?? ''));
    $this->db->bind(':serial_no', trim($data['serial_no'] ?? ''));
    $this->db->bind(':model', trim($data['model'] ?? ''));
    $this->db->bind(':brand', trim($data['brand'] ?? ''));
    $this->db->bind(':type', trim($data['type'] ?? ''));

    $this->db->bind(':purchase_date', !empty($data['purchase_date']) ? $data['purchase_date'] : null);
    $this->db->bind(':warranty_expiry', !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null);

    $this->db->bind(':status', $data['status'] ?? 'Active');
    $this->db->bind(':location_id', !empty($data['location_id']) ? (int)$data['location_id'] : null);
    $this->db->bind(':assigned_to', !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null);
    $this->db->bind(':notes', $data['notes'] ?? null);
    $this->db->bind(':created_by', !empty($data['created_by']) ? (int)$data['created_by'] : null);

    return $this->db->execute();
  }

  public function update($data)
  {
    // ✅ Backward compatibility
    if (empty($data['asset_tag']) && !empty($data['name'])) {
      $data['asset_tag'] = $data['name'];
    }
    if (empty($data['serial_no']) && !empty($data['serial_number'])) {
      $data['serial_no'] = $data['serial_number'];
    }

    $sql = "
      UPDATE assets SET
        asset_tag = :asset_tag,
        serial_no = :serial_no,
        model = :model,
        brand = :brand,
        type = :type,
        purchase_date = :purchase_date,
        warranty_expiry = :warranty_expiry,
        status = :status,
        location_id = :location_id,
        assigned_to = :assigned_to,
        notes = :notes
      WHERE id = :id
    ";

    $this->db->query($sql);

    $this->db->bind(':id', (int)$data['id']);
    $this->db->bind(':asset_tag', trim($data['asset_tag'] ?? ''));
    $this->db->bind(':serial_no', trim($data['serial_no'] ?? ''));
    $this->db->bind(':model', trim($data['model'] ?? ''));
    $this->db->bind(':brand', trim($data['brand'] ?? ''));
    $this->db->bind(':type', trim($data['type'] ?? ''));

    $this->db->bind(':purchase_date', !empty($data['purchase_date']) ? $data['purchase_date'] : null);
    $this->db->bind(':warranty_expiry', !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null);

    $this->db->bind(':status', $data['status'] ?? 'Active');
    $this->db->bind(':location_id', !empty($data['location_id']) ? (int)$data['location_id'] : null);
    $this->db->bind(':assigned_to', !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null);
    $this->db->bind(':notes', $data['notes'] ?? null);

    return $this->db->execute();
  }

  public function delete($id)
  {
    $this->db->query('DELETE FROM assets WHERE id = :id');
    $this->db->bind(':id', (int)$id);
    return $this->db->execute();
  }

  public function changeStatus($id, $status)
  {
    $this->db->query('UPDATE assets SET status = :status WHERE id = :id');
    $this->db->bind(':id', (int)$id);
    $this->db->bind(':status', $status);
    return $this->db->execute();
  }
}
