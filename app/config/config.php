<?php
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'it_inventory');

// مسار التطبيق
define('APPROOT', dirname(dirname(__FILE__)));

// رابط الموقع (هام: يجب أن ينتهي بـ /public)
define('URLROOT', 'http://localhost/it_inventory/public');

// اسم الموقع
define('SITENAME', 'نظام إدارة العهد');

define('APPVERSION', '1.0.0');
?>