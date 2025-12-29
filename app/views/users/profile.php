<?php
// ==========================================
// 1. الاتصال والمنطق (Backend)
// ==========================================
$host = 'localhost';
$dbname = 'it_inventory'; 
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { /* تجاهل الخطأ مؤقتا */ }

$user_id = $_SESSION['user_id'] ?? 1; 
$message = "";

// جلب البيانات
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { $user = []; } 

// معالجة الحفظ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_profile'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $lang = $_POST['lang'] ?? 'ar';
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
    
    // ========================
    // إصلاح مشكلة رفع الصورة
    // ========================
    $avatar_path = $user['avatar'] ?? 'default.png';
    
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $uploadDir = "../public/uploads/avatars/";
        
        // إنشاء المجلد إذا لم يكن موجوداً (حل المشكلة)
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_name = "user_{$user_id}_" . time() . ".$ext";
            if(move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $new_name)){
                $avatar_path = $new_name;
            }
        }
    }

    // التحديث في قاعدة البيانات
    // ملاحظة: تأكد أن اسم العمود في جدولك هو 'name' وليس 'username'
    // إذا ظهر خطأ، غير كلمة name في السطر الأسفل إلى username
    $sql = "UPDATE users SET name=?, email=?, phone=?, avatar=?, lang=?, dark_mode=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$name, $email, $phone, $avatar_path, $lang, $dark_mode, $user_id])) {
        $message = "<div class='alert alert-success fw-bold text-center'>✅ تم حفظ التغييرات بنجاح!</div>";
        // تحديث الصفحة تلقائياً لرؤية النتيجة
        header("Refresh:1");
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f0f2f5;
        }
        .profile-header {
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
            padding-bottom: 100px;
            border-radius: 0 0 30px 30px;
            color: white;
            text-align: center;
            padding-top: 50px;
            margin-bottom: -80px;
            position: relative;
        }
        .profile-img-wrap {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }
        .profile-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            object-fit: cover;
            background: #fff;
        }
        /* تحسين زر الكاميرا */
        .camera-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            transition: transform 0.2s;
            z-index: 10;
        }
        .camera-btn:hover {
            transform: scale(1.1);
            background: #f8f9fa;
        }
        .camera-btn i {
            color: #4e54c8;
            font-size: 1.2rem;
        }
        .main-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            background: white;
            padding: 30px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e1e5eb;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(78, 84, 200, 0.1);
            border-color: #4e54c8;
        }
        .btn-save {
            background: #4e54c8;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-save:hover {
            background: #3b40a1;
            transform: translateY(-2px);
        }
        .section-title {
            color: #4e54c8;
            font-weight: 700;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f2f5;
            padding-bottom: 10px;
        }
        /* زر العودة */
        .back-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.3s;
            backdrop-filter: blur(5px);
        }
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.4);
            color: white;
        }
    </style>
</head>
<body>

    <div class="profile-header">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-right ms-2"></i> رجوع للوحة التحكم
        </a>

        <h2>أهلاً بك، <?= htmlspecialchars($user['name'] ?? 'مستخدم') ?> 👋</h2>
        <p>إدارة بياناتك الشخصية وتفضيلات النظام</p>
    </div>

    <div class="container pb-5">
        <?php if($message) echo $message; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row justify-content-center">
                
                <div class="col-md-3 text-center mb-4">
                    <div class="profile-img-wrap">
                        <img src="../public/uploads/avatars/<?= !empty($user['avatar']) ? $user['avatar'] : 'default.png' ?>" class="profile-img" id="previewImg">
                        
                        <label for="avatarUpload" class="camera-btn" title="تغيير الصورة">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" name="avatar" id="avatarUpload" hidden accept="image/*" onchange="previewFile()">
                    </div>
                    
                    <div class="mt-3">
                        <h5 class="fw-bold"><?= htmlspecialchars($user['name'] ?? 'User') ?></h5>
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                            <?= htmlspecialchars($user['job_title'] ?? 'موظف IT') ?>
                        </span>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="main-card">
                        
                        <h5 class="section-title"><i class="fas fa-user-edit me-2"></i> المعلومات الأساسية</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-muted">الاسم الكامل</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">رقم الهاتف</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">اللغة المفضلة</label>
                                <select name="lang" class="form-select">
                                    <option value="ar" <?= ($user['lang'] ?? 'ar') == 'ar' ? 'selected' : '' ?>>العربية</option>
                                    <option value="en" <?= ($user['lang'] ?? 'ar') == 'en' ? 'selected' : '' ?>>English</option>
                                </select>
                            </div>
                        </div>

                        <h5 class="section-title"><i class="fas fa-shield-alt me-2"></i> الأمان والمظهر</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">كلمة المرور الجديدة</label>
                                <input type="password" name="new_password" class="form-control" placeholder="••• اتركها فارغة إذا لم تغيرها">
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check form-switch p-0 m-0 d-flex align-items-center gap-2">
                                    <input class="form-check-input ms-0" type="checkbox" name="dark_mode" id="darkModeSwitch" <?= ($user['dark_mode'] ?? 0) ? 'checked' : '' ?> style="width: 3em; height: 1.5em;">
                                    <label class="form-check-label fw-bold" for="darkModeSwitch">تفعيل الوضع الليلي 🌙</label>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-5">
                            <button type="submit" name="save_profile" class="btn btn-save">
                                <i class="fas fa-save me-2"></i> حفظ التغييرات
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </form>
    </div>

    <script>
        // دالة استعراض الصورة فور اختيارها
        function previewFile() {
            const preview = document.getElementById('previewImg');
            const file = document.getElementById('avatarUpload').files[0];
            const reader = new FileReader();

            reader.addEventListener("load", function () {
                preview.src = reader.result;
            }, false);

            if (file) {
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>