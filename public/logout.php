<?php
session_start(); // بدء الجلسة للوصول إليها

// إفراغ جميع متغيرات الجلسة
session_unset();

// تدمير الجلسة نهائياً
session_destroy();

// توجيه المستخدم لصفحة تسجيل الدخول
header("Location: login.php");
exit();
?>