<?php
class Announcement {
    private $db;
    public function __construct(){ $this->db = new Database; }
    
    public function getLatest(){
        $conn = $this->db->getConnection();
        $stmt = $conn->query("SELECT * FROM announcements WHERE is_published = 1 ORDER BY created_at DESC LIMIT 5");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create($title, $body, $uid){
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO announcements (title, body, created_by) VALUES (:t, :b, :u)";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':t', $title);
        $stmt->bindValue(':b', $body);
        $stmt->bindValue(':u', $uid);
        return $stmt->execute();
    }
}
?>