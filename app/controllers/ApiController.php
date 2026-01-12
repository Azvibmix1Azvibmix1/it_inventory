<?php

class ApiController extends Controller
{
  private $locationModel;

  public function __construct()
  {
    // إذا عندك requireLogin وتبغاه للـ API:
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

  private function buildMaps(array $locations): array
  {
    $byId = [];
    foreach ($locations as $l) {
      $id = (int)($l->id ?? 0);
      if ($id > 0) $byId[$id] = $l;
    }
    return $byId;
  }

  private function buildPath(int $id, array $byId): string
  {
    if ($id <= 0 || !isset($byId[$id])) return '';
    $parts = [];
    $cur = $byId[$id];
    $guard = 0;

    $parts[] = (string)($cur->name_ar ?? $cur->name_en ?? ('موقع#'.$id));

    while ($guard < 30) {
      $guard++;
      $pid = (int)($cur->parent_id ?? 0);
      if ($pid <= 0 || !isset($byId[$pid])) break;
      $cur = $byId[$pid];
      array_unshift($parts, (string)($cur->name_ar ?? $cur->name_en ?? ('موقع#'.$pid)));
    }

    return implode(' › ', $parts);
  }

  // GET: index.php?page=api/locations&q=...&limit=20
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

    $byId = $this->buildMaps($all);
    $qLower = mb_strtolower($q, 'UTF-8');

    $items = [];
    foreach ($all as $loc) {
      $id = (int)($loc->id ?? 0);
      if ($id <= 0) continue;

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

    $byId = $this->buildMaps($all);
    $path = $this->buildPath($id, $byId);

    if ($path === '') $this->json(['ok' => false, 'message' => 'الموقع غير موجود'], 404);

    $this->json(['ok' => true, 'id' => $id, 'path' => $path]);
  }
}
