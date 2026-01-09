<?php

class User
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    public function register($data)
    {
        $this->db->query('
            INSERT INTO users (name, email, password, role, manager_id)
            VALUES (:name, :email, :password, :role, :manager_id)
        ');

        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', $data['password']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':manager_id', $data['manager_id']); // يقبل NULL

        return $this->db->execute();
    }

    public function login($email, $password)
    {
        $this->db->query("SELECT * FROM users WHERE email = :email LIMIT 1");
        $this->db->bind(':email', $email);
        $row = $this->db->single();

        if (!$row) return false;

        if (password_verify($password, $row->password)) {
            return $row;
        }

        return false;
    }

    public function findUserByEmail($email){
        $this->db->query('SELECT id FROM users WHERE email = :email LIMIT 1');
        $this->db->bind(':email', $email);
        $this->db->single();
        return $this->db->rowCount() > 0;
    }

    public function getUserByEmail($email) {
        $this->db->query('SELECT * FROM users WHERE email = :email LIMIT 1');
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    public function emailExistsForOtherUser($email, $excludeId) {
  $this->db->query('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
  $this->db->bind(':email', $email);
  $this->db->bind(':id', (int)$excludeId);
  $this->db->single();
  return $this->db->rowCount() > 0;
}



    public function getUserById($id)
    {
        $this->db->query('SELECT * FROM users WHERE id = :id LIMIT 1');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getRoleById($id)
    {
        $this->db->query('SELECT role FROM users WHERE id = :id LIMIT 1');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        return $row->role ?? 'user';
    }

    public function getUsers()
    {
        $this->db->query("SELECT id, name, email, role, manager_id FROM users ORDER BY id DESC");
        return $this->db->resultSet();
    }

    public function getUsersByManager($manager_id)
    {
        $this->db->query("SELECT id, name, email, role, manager_id FROM users WHERE manager_id = :manager_id ORDER BY id DESC");
        $this->db->bind(':manager_id', $manager_id);
        return $this->db->resultSet();
    }

    public function update($data)
    {
        if (!empty($data['password'])) {
            $this->db->query('
                UPDATE users
                SET name = :name, email = :email, password = :password, role = :role
                WHERE id = :id
            ');
            $this->db->bind(':password', $data['password']);
        } else {
            $this->db->query('
                UPDATE users
                SET name = :name, email = :email, role = :role
                WHERE id = :id
            ');
        }

        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':role', $data['role']);

        return $this->db->execute();
    }

    public function delete($id)
    {
        $this->db->query('DELETE FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getUserCount()
    {
        $this->db->query("SELECT COUNT(*) as total FROM users");
        $result = $this->db->single();
        return $result->total ?? 0;
    }
}
