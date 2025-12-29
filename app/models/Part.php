<?php
// app/models/Part.php
class Part {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    // جلب كل القطع
    public function getAll(){
        $conn = $this->db->getConnection();
        $stmt = $conn->query("SELECT * FROM parts ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // إضافة قطعة جديدة
    public function add($data){
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO parts (name, category, quantity, min_stock, location_id, description) 
                VALUES (:name, :category, :qty, :min, :loc, :desc)";
        $stmt = $conn->prepare($sql);
        
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':category', $data['category']);
        $stmt->bindValue(':qty', $data['quantity']);
        $stmt->bindValue(':min', $data['min_stock']);
        $stmt->bindValue(':loc', $data['location_id']);
        $stmt->bindValue(':desc', $data['description']);
        
        return $stmt->execute();
    }

    // تحديث الكمية (إضافة أو صرف)
    public function updateQuantity($id, $new_quantity){
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE parts SET quantity = :qty WHERE id = :id");
        $stmt->bindValue(':qty', $new_quantity);
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }
}
?>