<?php

class Core
{
  protected string $currentController = 'Dashboard';
  protected string $currentMethod = 'index';
  protected array $params = [];

  protected $controllerInstance;

  public function __construct()
  {
    $url = $this->getUrl();

    // Controller
    if (!empty($url[0])) {
      $candidate = ucwords($url[0]);
      $file = APPROOT . '/controllers/' . $candidate . 'Controller.php';
      if (file_exists($file)) {
        $this->currentController = $candidate;
        unset($url[0]);
      }
    }

    $controllerFile = APPROOT . '/controllers/' . $this->currentController . 'Controller.php';
    if (!file_exists($controllerFile)) {
      $fallback = APPROOT . '/controllers/PagesController.php';
      if (file_exists($fallback)) {
        $this->currentController = 'Pages';
        $controllerFile = $fallback;
      } else {
        die("Controller file not found: " . htmlspecialchars($controllerFile));
      }
    }

    require_once $controllerFile;

    $class = $this->currentController . 'Controller';
    if (!class_exists($class)) {
      die("Controller class not found: " . htmlspecialchars($class));
    }

    $this->controllerInstance = new $class;

    // Method
    if (isset($url[1]) && method_exists($this->controllerInstance, $url[1])) {
      $this->currentMethod = $url[1];
      unset($url[1]);
    }

    // Params
    $this->params = $url ? array_values($url) : [];

    call_user_func_array([$this->controllerInstance, $this->currentMethod], $this->params);
  }

  protected function getUrl(): array
  {
    $page = $_GET['page'] ?? '';
    $page = trim($page, '/');
    if ($page === '') return [];

    $page = filter_var($page, FILTER_SANITIZE_URL);
    return explode('/', $page);
  }
}
