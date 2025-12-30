function currentRole() {
    return $_SESSION['user_role'] ?? 'user';
}

function can($permissionCode) {
    if (!isset($_SESSION['user_id'])) return false;

    // superadmin له كل الصلاحيات (اختياري بس مريح)
    if (currentRole() === 'superadmin') return true;

    try {
        $db = new Database();

        // 1) override على مستوى المستخدم
        $db->query("SELECT allowed FROM user_permissions WHERE user_id = :uid AND permission_code = :p LIMIT 1");
        $db->bind(':uid', (int)$_SESSION['user_id']);
        $db->bind(':p', $permissionCode);
        $row = $db->single();
        if ($row) return (int)$row->allowed === 1;

        // 2) default حسب الدور
        $db->query("SELECT allowed FROM role_permissions WHERE role = :r AND permission_code = :p LIMIT 1");
        $db->bind(':r', currentRole());
        $db->bind(':p', $permissionCode);
        $row2 = $db->single();
        if ($row2) return (int)$row2->allowed === 1;

        return false;
    } catch (Exception $e) {
        // لو صار خطأ في قاعدة البيانات، نكون حذرين ونرجع false
        return false;
    }
}

function requirePermission($permissionCode, $redirectPage = 'dashboard') {
    if (!can($permissionCode)) {
        flash('access_denied', 'ليس لديك صلاحية للوصول لهذه الصفحة', 'alert alert-danger');
        redirect('index.php?page=' . $redirectPage);
        exit;
    }
}
