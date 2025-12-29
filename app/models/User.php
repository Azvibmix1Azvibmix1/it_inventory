<?php
class User {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    // 1. تسجيل مستخدم جديد (يدعم الهيكلة الإدارية)
    public function register($data) {
        // لاحظ: نستخدم manager_id الآن
        $this->db->query('INSERT INTO users (name, email, password, role, manager_id) VALUES(:name, :email, :password, :role, :manager_id)');
        
        // ربط القيم
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', $data['password']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':manager_id', $data['manager_id']); // يقبل NULL إذا كان سوبر أدمن

        // تنفيذ الاستعلام
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    // 2. تسجيل الدخول
    public function login($email, $password) {
        $this->db->query("SELECT * FROM users WHERE email = :email");
        $this->db->bind(':email', $email);

        $row = $this->db->single();

        if($row) {
            $hashed_password = $row->password;
            if(password_verify($password, $hashed_password)){
                return $row;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // 3. التحقق من وجود البريد (لمنع التكرار)
    public function findUserByEmail($email) {
        $this->db->query('SELECT * FROM users WHERE email = :email');
        $this->db->bind(':email', $email);

        $row = $this->db->single();

        if($this->db->rowCount() > 0){
            return true;
        } else {
            return false;
        }
    }

    // 4. جلب مستخدم واحد بواسطة الـ ID
    public function getUserById($id) {
        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $id);

        return $this->db->single();
    }

    // 5. جلب جميع المستخدمين (للمدير العام - Super Admin)
    public function getUsers() {
        // نستخدم ORDER BY id DESC لضمان السرعة، ويمكنك استخدام created_at لو كان موجوداً
        $this->db->query("SELECT * FROM users ORDER BY id DESC"); 
        return $this->db->resultSet();
    }

    // 6. (جديد) جلب الموظفين التابعين لمدير معين (للمدير الثانوي - Manager)
    public function getUsersByManager($manager_id){
        $this->db->query("SELECT * FROM users WHERE manager_id = :manager_id ORDER BY id DESC");
        $this->db->bind(':manager_id', $manager_id);
        return $this->db->resultSet();
    }

    // 7. حذف المستخدم
    // قمت بتوحيد الاسم ليكون delete بدلاً من deleteUser ليتوافق مع الكنترولر
    public function delete($id) {
        $this->db->query('DELETE FROM users WHERE id = :id');
        $this->db->bind(':id', $id);

        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }
    
    // 8. إحصائيات العدد (للداشبورد)
    public function getUserCount(){
        $this->db->query("SELECT COUNT(*) as total FROM users");
        $result = $this->db->single();
        return $result->total;
    }

    // تحديث بيانات المستخدم
    public function update($data) {
        // تجهيز الاستعلام بناءً على هل تم تغيير الباسورد أم لا
        if(!empty($data['password'])){
            $this->db->query('UPDATE users SET name = :name, email = :email, password = :password, role = :role WHERE id = :id');
            $this->db->bind(':password', $data['password']);
        } else {
            // إذا الباسورد فارغ، لا نحدثه
            $this->db->query('UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id');
        }

        // ربط القيم
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':role', $data['role']);

        // تنفيذ
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }
}