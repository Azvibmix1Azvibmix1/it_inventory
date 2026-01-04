<?php
class AssetLog {
  private $db;

  public function __construct(){
    $this->db = new Database();
  }

  public function add($assetId, $userId, $action, $details = null){
    $this->db->query("INSERT INTO asset_logs (asset_id, user_id, action, details)
                      VALUES (:asset_id, :user_id, :action, :details)");
    $this->db->bind(':asset_id', (int)$assetId);
    $this->db->bind(':user_id', $userId ? (int)$userId : null);
    $this->db->bind(':action', (string)$action);
    $this->db->bind(':details', $details);
    return $this->db->execute();
  }

  public function getByAsset($assetId, $limit = 30){
    $limit = max(1, (int)$limit);
    $this->db->query("SELECT * FROM asset_logs WHERE asset_id = :asset_id
                      ORDER BY id DESC LIMIT {$limit}");
    $this->db->bind(':asset_id', (int)$assetId);
    return $this->db->resultSet();
  }
}
