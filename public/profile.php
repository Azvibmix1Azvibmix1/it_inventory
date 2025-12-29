<?php
// 1. استدعاء ملف الاتصال بقاعدة البيانات (تأكد من المسار الصحيح)
// غالباً يكون اسمه db.php أو database.php في مجلد config
require_once '../config/database.php'; 

// 2. استدعاء الهيدر (القائمة العلوية التي عدلناها سابقاً)
// افترضنا أن اسم الملف هو header.php، إذا كان مختلفاً عدله هنا
include 'header.php'; 

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// --- معالجة تحديث البيانات عند الضغط على زر الحفظ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // تحديث الاسم واسم المستخدم
    $sql = "UPDATE users SET full_name = ?, username = ? WHERE id = ?";
    
    // إذا قام المستخدم بكتابة كلمة مرور جديدة، نقوم بتحديثها أيضاً
    if (!empty($password)) {
        // إذا كنت تستخدم التشفير (وهو الأفضل) استخدم password_hash
        // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // $sql = "UPDATE users SET full_name = ?, username = ?, password = '$hashed_password' WHERE id = ?";
        
        // للتسهيل حالياً سنفترض تحديثها مباشرة (عدلها حسب نظامك)
        $sql = "UPDATE users SET full_name = ?, username = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $fullname, $username, $password, $user_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $fullname, $username, $user_id);
    }

    if ($stmt->execute()) {
        $msg = "تم تحديث البيانات بنجاح!";
        $msg_type = "success";
        // تحديث متغيرات السيشن ليظهر الاسم الجديد فوراً
        $_SESSION['user_name'] = $username;
    } else {
        $msg = "حدث خطأ أثناء التحديث.";
        $msg_type = "danger";
    }
}

// --- جلب بيانات المستخدم الحالية لعرضها في الحقول ---
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>الملف الشخصي</h5>
            </div>
            <div class="card-body">
                
                <?php if($msg != ""): ?>
                    <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">الاسم الكامل</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user_data['full_name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user_data['username'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">كلمة المرور الجديدة</label>
                        <input type="password" name="password" class="form-control" placeholder="اتركه فارغاً إذا لم ترد تغييرها">
                        <div class="form-text">لا تكتب شيئاً هنا إذا كنت تريد الاحتفاظ بكلمة المرور القديمة.</div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="update_profile" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>حفظ التغييرات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
// استدعاء الفوتر (تأكد من وجود الملف)
// include 'footer.php'; 
?>
</body>
</html>