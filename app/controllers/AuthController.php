<?php

class AuthController extends Controller
{
  private $userModel;

  public function __construct()
  {
    $this->userModel = $this->model('User');
  }

  // ---------- Helpers (Views) ----------
  private function viewLogin($data)
  {
    // يفضل auth/login لو موجود
    if (defined('APPROOT') && file_exists(APPROOT . '/views/auth/login.php')) {
      $this->view('auth/login', $data);
      return;
    }
    $this->view('users/login', $data);
  }

  private function viewRegister($data)
  {
    if (defined('APPROOT') && file_exists(APPROOT . '/views/users/register.php')) {
      $this->view('users/register', $data);
      return;
    }
    $this->view('auth/register', $data);
  }

  // ---------- Login ----------
  public function login()
  {
    // لو مسجل دخول مسبقًا
    if (function_exists('isLoggedIn') && isLoggedIn()) {
      redirect('index.php?page=dashboard/index');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

      $data = [
        'email' => trim($_POST['email'] ?? ''),
        'password' => trim($_POST['password'] ?? ''),
        'email_err' => '',
        'password_err' => '',
      ];

      if (empty($data['email'])) {
        $data['email_err'] = 'الرجاء إدخال البريد الإلكتروني';
      }

      if (empty($data['password'])) {
        $data['password_err'] = 'الرجاء إدخال كلمة المرور';
      }

      // تأكد المستخدم موجود
      if (empty($data['email_err']) && method_exists($this->userModel, 'findUserByEmail')) {
        if (!$this->userModel->findUserByEmail($data['email'])) {
          $data['email_err'] = 'هذا البريد الإلكتروني غير مسجل';
        }
      }

      if (empty($data['email_err']) && empty($data['password_err'])) {
        $loggedInUser = $this->userModel->login($data['email'], $data['password']);
        if ($loggedInUser === 'inactive') {
        $data['password_err'] = 'تم تعطيل حسابك. تواصل مع مسؤول النظام.';
        $this->view('users/login', $data);
        return;
}

        if ($loggedInUser) {
          $this->createUserSession($loggedInUser);
          return;
        }
        $data['password_err'] = 'كلمة المرور غير صحيحة';
      }

      $this->viewLogin($data);
      return;
    }

    // GET
    $data = [
      'email' => '',
      'password' => '',
      'email_err' => '',
      'password_err' => '',
    ];
    $this->viewLogin($data);
  }

  // ---------- Session ----------
  private function createUserSession($user){
  // لازم نتأكد إن السيشن شغال
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  // حماية من Session Fixation
  session_regenerate_id(true);

  $_SESSION['user_id'] = $user->id;
  $_SESSION['user_email'] = $user->email ?? '';
  $_SESSION['user_name'] = $user->username ?? ($user->name ?? '');
  $_SESSION['user_role'] = $user->role ?? 'user';

  // مهم في بعض حالات XAMPP عشان تنكتب قبل التحويل
  session_write_close();

  redirect('index.php?page=dashboard/index');
  exit;
}


  // ---------- Logout ----------
  public function logout()
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }

    session_destroy();
    redirect('index.php?page=login');
  }

  // ---------- Register (اختياري) ----------
  public function register()
  {
    // خليه مثل ما كان عندك إذا تحتاجه — ما لمسته هنا
    $data = [];
    $this->viewRegister($data);
  }
}
