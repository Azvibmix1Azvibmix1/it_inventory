<?php
// app/helpers/session_helper.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Flash messages
function flash($name = '', $message = '', $class = 'alert alert-success')
{
    if (!empty($name)) {
        if (!empty($message) && empty($_SESSION[$name])) {
            $_SESSION[$name] = $message;
            $_SESSION[$name . '_class'] = $class;
        } elseif (empty($message) && !empty($_SESSION[$name])) {
            $class = $_SESSION[$name . '_class'] ?? 'alert alert-success';
            echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);
        }
    }
}

function redirect($location)
{
    header('Location: ' . $location);
    exit;
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function currentRole()
{
    return $_SESSION['user_role'] ?? 'user';
}

function isUser()
{
    return currentRole() === 'user';
}

function isManager()
{
    $role = currentRole();
    return $role === 'manager' || $role === 'admin';
}

function isSuperAdmin()
{
    return currentRole() === 'superadmin';
}

/**
 * ✅ تحديث دور المستخدم من قاعدة البيانات لو تغيّر
 * مفيد لما تغيّر دور نفسك من صفحة المستخدمين وما تبغى تسوي logout/login.
 */
function syncSessionRole()
{
    if (!isLoggedIn()) return;

    try {
        $db = new Database();
        $db->query("SELECT role FROM users WHERE id = :id LIMIT 1");
        $db->bind(':id', (int)$_SESSION['user_id']);
        $row = $db->single();

        if ($row && isset($row->role)) {
            if (($_SESSION['user_role'] ?? null) !== $row->role) {
                $_SESSION['user_role'] = $row->role;
            }
        }
    } catch (Exception $e) {
        // لو DB فيها مشكلة ما نكسر الموقع
    }
}
