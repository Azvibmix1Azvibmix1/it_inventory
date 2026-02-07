<?php
// app/config/config.php

// بيئة التطبيق
define('APP_ENV', 'development');

// مسار مجلد app
if (!defined('APPROOT')) define('APPROOT', dirname(__DIR__));

// يجب أن ينتهي رابط المشروع بـ /public لتجنب مشاكل إعادة التوجيه
define('URLROOT', 'http://localhost/it_inventory/public');

// اسم الموقع
define('SITENAME', 'IT Inventory');

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'it_inventory');
define('DB_CHARSET', 'utf8mb4');
