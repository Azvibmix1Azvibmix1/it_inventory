<?php
class SparePart {
  private $db;

  public function __construct() {
    $this->db = new Database;
  }

  public function getById(int $id) {
    $this->db->query("
      SELECT sp.*,
             l.name_ar AS location_name_ar, l.name_en AS location_name_en
      FROM spare_parts sp
      LEFT JOIN locations l ON l.id = sp.location_id
      WHERE sp.id = :id
      LIMIT 1
    ");
    $this->db->bind(':id', $id);
    return $this->db->single();
  }

  public function getFiltered(array $filters = []) : array {
    $q = trim($filters['q'] ?? '');
    $locationId = (int)($filters['location_id'] ?? 0);

    $where = [];
    if ($q !== '') {
      $where[] = "(sp.name LIKE :q OR sp.part_number LIKE :q)";
    }
    if ($locationId > 0) {
      $where[] = "sp.location_id = :loc";
    }

    $sql = "
      SELECT sp.*,
             l.name_ar AS location_name_ar, l.name_en AS location_name_en
      FROM spare_parts sp
      LEFT JOIN locations l ON l.id = sp.location_id
    ";
    if (!empty($where)) {
      $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY sp.id DESC";

    $this->db->query($sql);

    if ($q !== '') {
      $this->db->bind(':q', '%' . $q . '%');
    }
    if ($locationId > 0) {
      $this->db->bind(':loc', $locationId);
    }

    return $this->db->resultSet();
  }

  public function add(array $data) : bool {
    $this->db->query("
      INSERT INTO spare_parts
        (name, part_number, quantity, min_quantity, location_id, location, description, created_at)
      VALUES
        (:name, :part_number, :quantity, :min_quantity, :location_id, :location, :description, NOW())
    ");
    $this->db->bind(':name', trim($data['name'] ?? ''));
    $this->db->bind(':part_number', trim($data['part_number'] ?? '') ?: null);
    $this->db->bind(':quantity', (int)($data['quantity'] ?? 0));
    $this->db->bind(':min_quantity', (int)($data['min_quantity'] ?? 0));
    $this->db->bind(':location_id', !empty($data['location_id']) ? (int)$data['location_id'] : null);
    $this->db->bind(':location', trim($data['location'] ?? '') ?: null);
    $this->db->bind(':description', trim($data['description'] ?? '') ?: null);

    return $this->db->execute();
  }

  public function update(array $data) : bool {
    $this->db->query("
      UPDATE spare_parts
      SET name = :name,
          part_number = :part_number,
          quantity = :quantity,
          min_quantity = :min_quantity,
          location_id = :location_id,
          location = :location,
          description = :description
      WHERE id = :id
      LIMIT 1
    ");
    $this->db->bind(':id', (int)$data['id']);
    $this->db->bind(':name', trim($data['name'] ?? ''));
    $this->db->bind(':part_number', trim($data['part_number'] ?? '') ?: null);
    $this->db->bind(':quantity', (int)($data['quantity'] ?? 0));
    $this->db->bind(':min_quantity', (int)($data['min_quantity'] ?? 0));
    $this->db->bind(':location_id', !empty($data['location_id']) ? (int)$data['location_id'] : null);
    $this->db->bind(':location', trim($data['location'] ?? '') ?: null);
    $this->db->bind(':description', trim($data['description'] ?? '') ?: null);

    return $this->db->execute();
  }

  public function delete(int $id) : bool {
    $this->db->query("DELETE FROM spare_parts WHERE id = :id LIMIT 1");
    $this->db->bind(':id', $id);
    return $this->db->execute();
  }

  public function getParts(){
  $this->db->query("
    SELECT sp.*,
           l.name_ar AS location_name_ar,
           l.name_en AS location_name_en
    FROM spare_parts sp
    LEFT JOIN locations l ON l.id = sp.location_id
    ORDER BY sp.created_at DESC
  ");
  return $this->db->resultSet();
}


  public function getPartById($id){
  $this->db->query("SELECT * FROM spare_parts WHERE id = :id LIMIT 1");
  $this->db->bind(':id', (int)$id);
  return $this->db->single();
}

public function adjustQuantity($id, $delta){
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


public function getAll(){
  $this->db->query("SELECT * FROM spare_parts ORDER BY created_at DESC");
  return $this->db->resultSet();
}

public function addMovement($sparePartId, $locationId, $delta, $note = null, $createdBy = null){
  $this->db->query("
    INSERT INTO spare_movements (spare_part_id, location_id, delta, note, created_by, created_at)
    VALUES (:spare_part_id, :location_id, :delta, :note, :created_by, NOW())
  ");
  $this->db->bind(':spare_part_id', (int)$sparePartId);
  $this->db->bind(':location_id', $locationId ? (int)$locationId : null);
  $this->db->bind(':delta', (int)$delta);
  $this->db->bind(':note', $note ? trim($note) : null);
  $this->db->bind(':created_by', $createdBy ? (int)$createdBy : null);
  return $this->db->execute();
}

public function getMovementsByLocation($locationId, $limit = 20){
  $this->db->query("
    SELECT m.*, sp.name AS part_name
    FROM spare_movements m
    LEFT JOIN spare_parts sp ON sp.id = m.spare_part_id
    WHERE m.location_id = :loc
    ORDER BY m.id DESC
    LIMIT {$limit}
  ");
  $this->db->bind(':loc', (int)$locationId);
  return $this->db->resultSet();
}

}
