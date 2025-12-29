<?php
// public/ajax.php
require_once '../app/config/config.php';
require_once '../app/config/db.php';

if(isset($_POST['query'])){
    $db = new Database();
    $conn = $db->getConnection();
    $q = "%" . $_POST['query'] . "%";
    
    // البحث في الأصول
    $sql = "SELECT * FROM assets WHERE asset_tag LIKE :q OR brand LIKE :q OR serial_number LIKE :q LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':q', $q);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if($results){
        foreach($results as $row){
            echo "<a href='index.php?page=assets' class='list-group-item list-group-item-action'>";
            echo "<i class='fa fa-laptop'></i> <strong>" . $row['asset_tag'] . "</strong> - " . $row['brand'] . " " . $row['model'];
            echo "</a>";
        }
    } else {
        echo "<div class='list-group-item text-muted'>لا توجد نتائج...</div>";
    }
}
?>