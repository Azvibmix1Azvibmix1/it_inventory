<?php

class User
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    // إنشاء مستخدم
    public function register($data){
        // ضمان وجود username حتى لو ما أرسل من الكنترولر
    if (!isset($data['username']) || trim((string)$data['username']) === '') {
    $email = trim((string)($data['email'] ?? ''));
    $base = $email !== '' ? (strstr($email, '@', true) ?: '') : '';
    $base = preg_replace('/[^a-zA-Z0-9._-]/', '', (string)$base);
    $data['username'] = $base ?: ('user_' . time());
}

    $this->db->query('
        INSERT INTO users (username, name, email, password, role, manager_id, is_active)
        VALUES (:username, :name, :email, :password, :role, :manager_id, 1)
    ');

    $this->db->bind(':username', $data['username']);
    $this->db->bind(':name', $data['name']);
    $this->db->bind(':email', $data['email']);
    $this->db->bind(':password', $data['password']);
    $this->db->bind(':role', $data['role']);           // user / manager / super_admin
    $this->db->bind(':manager_id', $data['manager_id']); // NULL أو رقم

    return $this->db->execute();
}

    // تسجيل دخول
   public function login($email, $password)
{
    $this->db->query('SELECT * FROM users WHERE email = :email LIMIT 1');
    $this->db->bind(':email', $email);
    $row = $this->db->single();

    if (!$row) return false;

    // منع الدخول إذا الحساب مُعطّل
    $active = isset($row->is_active) ? (int)$row->is_active : 1;
    if ($active !== 1) {
        return 'inactive'; // نرجع قيمة خاصة عشان الكنترولر يعرض رسالة واضحة
    }

    return password_verify($password, $row->password) ? $row : false;
}


    // جلب مستخدم عبر البريد (للتحقق أثناء edit)
    public function getUserByEmail($email)
    {
        $this->db->query('SELECT * FROM users WHERE email = :email LIMIT 1');
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    // تحقق: البريد موجود لمستخدم آخر (استثناء id معيّن)
    public function emailExistsForOtherUser($email, $excludeId)
    {
        $this->db->query('
            SELECT id
            FROM users
            WHERE email = :email AND id <> :id
            LIMIT 1
        ');
        $this->db->bind(':email', $email);
        $this->db->bind(':id', (int)$excludeId);
        $this->db->single();
        return $this->db->rowCount() > 0;
    }

    public function getUserById($id)
    {
        $this->db->query('SELECT * FROM users WHERE id = :id LIMIT 1');
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    public function getRoleById($id)
    {
        $this->db->query('SELECT role FROM users WHERE id = :id LIMIT 1');
        $this->db->bind(':id', (int)$id);
        $row = $this->db->single();
        return $row->role ?? 'user';
    }

    // تحديث بيانات مستخدم
    // ملاحظة: كنترولرك يمرر password دائماً (إما جديدة hashed أو القديمة)، فهنا نحدثها دائمًا
   public function update($data)
{
    $this->db->query('
        UPDATE users
        SET
            username = :username,
            name     = :name,
            email    = :email,
            password = :password,
            role     = :role
        WHERE id = :id
    ');

    $this->db->bind(':id', (int)$data['id']);
    $this->db->bind(':username', $data['username']);
    $this->db->bind(':name', $data['name']);
    $this->db->bind(':email', $data['email']);
    $this->db->bind(':password', $data['password']);
    $this->db->bind(':role', $data['role']); // user / manager / super_admin

    return $this->db->execute();
}



    // تعطيل/تفعيل (Soft)
    public function setActive($id, $active)
    {
        $this->db->query('UPDATE users SET is_active = :active WHERE id = :id');
        $this->db->bind(':active', (int)$active);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    // (اختياري) حذف نهائي — يفضل عدم استخدامه الآن
    public function delete($id)
    {
        $this->db->query('DELETE FROM users WHERE id = :id');
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function getUserCount()
    {
        $this->db->query('SELECT COUNT(*) as total FROM users');
        $result = $this->db->single();
        return (int)($result->total ?? 0);
    }

    public function getUsers()
{
    $this->db->query("
        SELECT id, username, name, email, role, manager_id, created_at, is_active
        FROM users
        ORDER BY id DESC
    ");
    return $this->db->resultSet();
}

public function getUsersByManager($manager_id)
{
    $this->db->query("
        SELECT id, username, name, email, role, manager_id, created_at, is_active
        FROM users
        WHERE manager_id = :manager_id
        ORDER BY id DESC
    ");
    $this->db->bind(':manager_id', (int)$manager_id);
    return $this->db->resultSet();
}

}
