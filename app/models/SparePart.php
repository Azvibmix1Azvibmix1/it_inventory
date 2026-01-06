<?php
// app/models/SparePart.php

class SparePart
{
  private $db;

  public function __construct()
  {
    $this->db = new Database();
  }

  /* =========================
     Lists
  ========================= */

  // جلب جميع قطع الغيار (مع اسم الموقع)
  public function getParts()
  {
    $this->db->query("
      SELECT sp.*,
             l.name_ar AS location_name_ar,
             l.name_en AS location_name_en
      FROM spare_parts sp
      LEFT JOIN locations l ON sp.location_id = l.id
      ORDER BY sp.created_at DESC
    ");
    return $this->db->resultSet();
  }

  // جلب جميع قطع الغيار بدون Join
  public function getAll()
  {
    $this->db->query("SELECT * FROM spare_parts ORDER BY created_at DESC");
    return $this->db->resultSet();
  }

  // للاقتراحات (أسماء قطع مكررة/موجودة)
  public function getExistingTypes()
  {
    $this->db->query("SELECT DISTINCT name FROM spare_parts ORDER BY name ASC");
    return $this->db->resultSet();
  }

  /* =========================
     CRUD
  ========================= */

  public function add($data)
  {
    $this->db->query("
      INSERT INTO spare_parts
        (name, part_number, quantity, min_quantity, location_id, description, created_at)
      VALUES
        (:name, :part_num, :qty, :min_qty, :location_id, :desc, NOW())
    ");

    $this->db->bind(':name', trim($data['name'] ?? ''));
    $this->db->bind(':part_num', trim($data['part_number'] ?? ''));
    $this->db->bind(':qty', (int)($data['quantity'] ?? 0));
    $this->db->bind(':min_qty', (int)($data['min_quantity'] ?? 0));

    // إذا فاضي نخزن NULL
    $loc = $data['location_id'] ?? null;
    $this->db->bind(':location_id', !empty($loc) ? (int)$loc : null);

    $this->db->bind(':desc', trim($data['description'] ?? ''));

    return $this->db->execute();
  }

  public function getPartById($id)
  {
    $this->db->query("SELECT * FROM spare_parts WHERE id = :id LIMIT 1");
    $this->db->bind(':id', (int)$id);
    return $this->db->single();
  }

  public function update($data)
  {
    $this->db->query("
      UPDATE spare_parts
      SET name = :name,
          part_number = :part_num,
          quantity = :qty,
          min_quantity = :min_qty,
          location_id = :location_id,
          description = :desc
      WHERE id = :id
      LIMIT 1
    ");

    $this->db->bind(':name', trim($data['name'] ?? ''));
    $this->db->bind(':part_num', trim($data['part_number'] ?? ''));
    $this->db->bind(':qty', (int)($data['quantity'] ?? 0));
    $this->db->bind(':min_qty', (int)($data['min_quantity'] ?? 0));

    $loc = $data['location_id'] ?? null;
    $this->db->bind(':location_id', !empty($loc) ? (int)$loc : null);

    $this->db->bind(':desc', trim($data['description'] ?? ''));
    $this->db->bind(':id', (int)($data['id'] ?? 0));

    return $this->db->execute();
  }

 public function delete($id) {
  $id = (int)$id;

  // امسح الحركات أولاً (لو الجدول موجود)
  $this->db->query("DELETE FROM spare_movements WHERE spare_part_id = :id");
  $this->db->bind(':id', $id);
  $this->db->execute();

  // بعدها امسح القطعة
  $this->db->query("DELETE FROM spare_parts WHERE id = :id LIMIT 1");
  $this->db->bind(':id', $id);
  return $this->db->execute();
}






  public function getCounts()
  {
    $this->db->query("SELECT COUNT(*) AS count FROM spare_parts");
    $row = $this->db->single();
    return (int)($row->count ?? 0);
  }

  /* =========================
     Location helpers
  ========================= */

  public function getSpareStocksByLocation($locationId)
  {
    $this->db->query("
      SELECT *
      FROM spare_parts
      WHERE location_id = :loc
      ORDER BY created_at DESC
    ");
    $this->db->bind(':loc', (int)$locationId);
    return $this->db->resultSet();
  }

  public function getSpareStockSummary($locationId)
  {
    $this->db->query("
      SELECT
        COUNT(*) AS total_items,
        COALESCE(SUM(quantity), 0) AS total_qty,
        COALESCE(SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END), 0) AS out_count,
        COALESCE(SUM(CASE WHEN quantity > 0 AND quantity <= min_quantity THEN 1 ELSE 0 END), 0) AS low_count
      FROM spare_parts
      WHERE location_id = :loc
    ");
    $this->db->bind(':loc', (int)$locationId);
    return $this->db->single();
  }

  /* =========================
     Quick adjust
  ========================= */

  public function adjustQuantity($id, $delta)
  {
    $this->db->query("
      UPDATE spare_parts
      SET quantity = CASE
        WHEN (quantity + :delta) < 0 THEN 0
        ELSE (quantity + :delta)
      END
      WHERE id = :id
      LIMIT 1
    ");
    $this->db->bind(':delta', (int)$delta);
    $this->db->bind(':id', (int)$id);
    return $this->db->execute();
  }

  /* =========================
     Movements (who did what)
     Requires table: spare_movements
  ========================= */

  public function addMovement($sparePartId, $locationId, $delta, $note = null, $createdBy = null)
{
  $this->db->query("
    INSERT INTO spare_movements (spare_part_id, location_id, delta, note, created_by, created_at)
    VALUES (:spare_part_id, :location_id, :delta, :note, :created_by, NOW())
  ");
  $this->db->bind(':spare_part_id', (int)$sparePartId);
  $this->db->bind(':location_id', $locationId ? (int)$locationId : null);
  $this->db->bind(':delta', (int)$delta);
  $this->db->bind(':note', ($note !== null && trim($note) !== '') ? trim($note) : null);
  $this->db->bind(':created_by', $createdBy ? (int)$createdBy : null);
  return $this->db->execute();
}


  public function getMovementsByLocation($locationId, $limit = 20)
{
  $limit = (int)$limit;
  if ($limit <= 0) $limit = 20;

  $this->db->query("
    SELECT 
      m.*,
      sp.name AS part_name,
      u.name AS user_name,
      u.username AS username
    FROM spare_movements m
    LEFT JOIN spare_parts sp ON sp.id = m.spare_part_id
    LEFT JOIN users u ON u.id = m.created_by
    WHERE m.location_id = :loc
    ORDER BY m.id DESC
    LIMIT {$limit}
  ");
  $this->db->bind(':loc', (int)$locationId);
  return $this->db->resultSet();
}

public function transferLocation($id, $toLocationId) {
  $this->db->query("UPDATE spare_parts SET location_id = :to_loc WHERE id = :id LIMIT 1");
  $this->db->bind(':to_loc', (int)$toLocationId);
  $this->db->bind(':id', (int)$id);
  return $this->db->execute();
}

public function getMovementsByPart($sparePartId, $limit = 10)
{
  $sparePartId = (int)$sparePartId;
  $limit = (int)$limit;

  $this->db->query("
    SELECT 
      m.created_at,
      m.delta,
      m.note,
      u.username AS user_name,
      l.name_ar AS location_name
    FROM spare_movements m
    LEFT JOIN users u ON u.id = m.created_by
    LEFT JOIN locations l ON l.id = m.location_id
    WHERE m.spare_part_id = :id
    ORDER BY m.created_at DESC
    LIMIT {$limit}
  ");
  $this->db->bind(':id', $sparePartId);
  return $this->db->resultSet();
}

}
