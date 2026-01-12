<?php
// app/config/config.php

// بيئة التطبيق
define('APP_ENV', 'development');

// مسار مجلد app
define('APPROOT', dirname(__DIR__));

// رابط المشروع (مهم جدًا يكون ينتهي بـ /public)
define('URLROOT', 'http://localhost/it_inventory/public');

// اسم الموقع
define('SITENAME', 'IT Inventory');

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'it_inventory');
define('DB_CHARSET', 'utf8mb4');
