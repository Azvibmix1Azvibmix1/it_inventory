<?php

class ApiController extends Controller
{
  private $locationModel;

  public function __construct()
  {
    if (function_exists('requireLogin')) {
      requireLogin();
    }
    $this->locationModel = $this->model('Location');
  }

  private function json($data, int $code = 200): void
  {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
  }

  private function currentRole(): string
  {
    if (function_exists('currentRole')) return (string)currentRole();
    return (string)($_SESSION['user_role'] ?? 'user');
  }

  private function currentUserId(): int
  {
    return (int)($_SESSION['user_id'] ?? 0);
  }

  private function buildMaps(array $locations): array
  {
    $byId = [];
    $children = []; // parent_id => [childIds]

    foreach ($locations as $l) {
      $id = (int)($l->id ?? 0);
      if ($id <= 0) continue;

      $byId[$id] = $l;

      $pid = (int)($l->parent_id ?? 0);
      if (!isset($children[$pid])) $children[$pid] = [];
      $children[$pid][] = $id;
    }
    return [$byId, $children];
  }

  private function buildPath(int $id, array $byId): string
  {
    if ($id <= 0 || !isset($byId[$id])) return '';
    $parts = [];
    $cur = $byId[$id];
    $parts[] = (string)($cur->name_ar ?? $cur->name_en ?? ('موقع#'.$id));

    $guard = 0;
    while ($guard < 30) {
      $guard++;
      $pid = (int)($cur->parent_id ?? 0);
      if ($pid <= 0 || !isset($byId[$pid])) break;
      $cur = $byId[$pid];
      array_unshift($parts, (string)($cur->name_ar ?? $cur->name_en ?? ('موقع#'.$pid)));
    }
    return implode(' › ', $parts);
  }

  private function descendants(int $rootId, array $children): array
  {
    $out = [];
    $q = [$rootId];
    $seen = [];

    while ($q) {
      $cur = (int)array_shift($q);
      if ($cur <= 0 || isset($seen[$cur])) continue;
      $seen[$cur] = true;

      foreach (($children[$cur] ?? []) as $ch) {
        $ch = (int)$ch;
        if ($ch <= 0) continue;
        $out[] = $ch;
        $q[] = $ch;
      }
    }
    return array_values(array_unique($out));
  }

  private function allowedAddRoots(): ?array
  {
    $role = $this->currentRole();
    if (in_array($role, ['superadmin', 'super_admin', 'manager'], true)) {
      return null; // كل المواقع
    }

    $uid = $this->currentUserId();
    if ($uid <= 0) return [];

    try {
      $db = new Database();
      $db->query("
        SELECT DISTINCT location_id
        FROM locations_permissions
        WHERE (user_id = :uid OR role = :role)
          AND (can_manage = 1 OR can_add_children = 1)
      ");
      $db->bind(':uid', $uid);
      $db->bind(':role', $role);
      $rows = $db->resultSet();

      $ids = [];
      foreach ($rows as $r) {
        $ids[] = (int)($r->location_id ?? 0);
      }
      $ids = array_values(array_unique(array_filter($ids, fn($x)=>$x>0)));
      return $ids;
    } catch (Throwable $e) {
      return [];
    }
  }

  private function allowedExpandedIds(array $roots, array $children): array
  {
    $set = [];
    foreach ($roots as $id) {
      $id = (int)$id;
      if ($id <= 0) continue;
      $set[$id] = true;
      foreach ($this->descendants($id, $children) as $d) $set[(int)$d] = true;
    }
    return array_map('intval', array_keys($set));
  }

  // GET: index.php?page=api/locations&q=...&limit=30
  public function locations(): void
  {
    $q = trim((string)($_GET['q'] ?? ''));
    $limit = (int)($_GET['limit'] ?? 20);
    if ($limit <= 0) $limit = 20;
    if ($limit > 50) $limit = 50;

    if (mb_strlen($q, 'UTF-8') < 2) {
      $this->json(['ok' => true, 'items' => []]);
    }

    $all = method_exists($this->locationModel, 'getAll') ? $this->locationModel->getAll() : [];
    if (!is_array($all)) $all = [];

    [$byId, $children] = $this->buildMaps($all);

    $roots = $this->allowedAddRoots();
    $allowedIds = null;
    if (is_array($roots)) {
      $allowedIds = $this->allowedExpandedIds($roots, $children);
    }

    $qLower = mb_strtolower($q, 'UTF-8');
    $items = [];

    foreach ($all as $loc) {
      $id = (int)($loc->id ?? 0);
      if ($id <= 0) continue;

      if (is_array($allowedIds) && !in_array($id, $allowedIds, true)) {
        continue;
      }

      $nameAr = (string)($loc->name_ar ?? '');
      $nameEn = (string)($loc->name_en ?? '');
      $type   = (string)($loc->type ?? '');

      $hay = mb_strtolower($nameAr.' '.$nameEn.' '.$type.' '.$id, 'UTF-8');
      if (mb_strpos($hay, $qLower) === false) continue;

      $items[] = [
        'id'   => $id,
        'name' => $nameAr ?: ($nameEn ?: ('موقع#'.$id)),
        'path' => $this->buildPath($id, $byId),
        'type' => $type,
      ];

      if (count($items) >= $limit) break;
    }

    $this->json(['ok' => true, 'items' => $items]);
  }

  // GET: index.php?page=api/location_path&id=123
  public function location_path(): void
  {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) $this->json(['ok' => false, 'message' => 'ID غير صالح'], 400);

    $all = method_exists($this->locationModel, 'getAll') ? $this->locationModel->getAll() : [];
    if (!is_array($all)) $all = [];

    [$byId, $children] = $this->buildMaps($all);

    // احترام صلاحيات الإضافة
    $roots = $this->allowedAddRoots();
    if (is_array($roots)) {
      $allowedIds = $this->allowedExpandedIds($roots, $children);
      if (!in_array($id, $allowedIds, true)) {
        $this->json(['ok' => false, 'message' => 'غير مصرح'], 403);
      }
    }

    $path = $this->buildPath($id, $byId);
    if ($path === '') $this->json(['ok' => false, 'message' => 'الموقع غير موجود'], 404);

    $this->json(['ok' => true, 'id' => $id, 'path' => $path]);
  }
}
