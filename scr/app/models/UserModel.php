<?php
require_once __DIR__ . '/../../config/database.php';

class UserModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function isActive($userId)
    {
        $statement = $this->conn->prepare(
            'SELECT id FROM users WHERE id = :id AND status = 1 LIMIT 1'
        );
        $statement->execute([':id' => (int) $userId]);
        return $statement->fetchColumn() !== false;
    }

    public function findProfile($userId)
    {
        $statement = $this->conn->prepare(
            'SELECT id, full_name, email, phone, address FROM users WHERE id = :id AND status = 1 LIMIT 1'
        );
        $statement->execute([':id' => (int) $userId]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProfile($userId, $fullName, $phone, $address)
    {
        $statement = $this->conn->prepare(
            'UPDATE users SET full_name = :full_name, phone = :phone, address = :address
             WHERE id = :id AND status = 1'
        );
        $statement->execute([
            ':full_name' => $fullName,
            ':phone' => $phone !== '' ? $phone : null,
            ':address' => $address !== '' ? $address : null,
            ':id' => (int) $userId,
        ]);
        return $this->isActive($userId);
    }

    public function changePassword($userId, $currentPassword, $newPassword)
    {
        $statement = $this->conn->prepare(
            'SELECT password FROM users WHERE id = :id AND status = 1 LIMIT 1'
        );
        $statement->execute([':id' => (int) $userId]);
        $currentHash = $statement->fetchColumn();
        if ($currentHash === false || !password_verify($currentPassword, $currentHash)) {
            return false;
        }
        $update = $this->conn->prepare(
            'UPDATE users SET password = :password WHERE id = :id AND status = 1 AND password = :current_hash'
        );
        $update->execute([
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id' => (int) $userId,
            ':current_hash' => $currentHash,
        ]);
        return $update->rowCount() > 0;
    }

    public function register($fullName, $email, $password, $phone, $address)
    {
        if (!($this->conn instanceof PDO)) {
            return false;
        }

        $sql = 'INSERT INTO users (full_name, email, password, phone, address, role)
                VALUES (:full_name, :email, :password, :phone, :address, :role)';

        try {
            $statement = $this->conn->prepare($sql);
            $statement->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':phone' => $phone,
                ':address' => $address,
                ':role' => 'customer',
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return false;
            }
            throw $exception;
        }

        return [
            'id' => (int) $this->conn->lastInsertId(),
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'role' => 'customer',
        ];
    }

    public function login($email, $password)
    {
        if (!($this->conn instanceof PDO)) {
            return false;
        }

        $sql = 'SELECT id, full_name, email, password, phone, role, address, status
                FROM users
            WHERE email = :email AND status = 1
                LIMIT 1';

        $statement = $this->conn->prepare($sql);
        $statement->execute([':email' => $email]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        unset($user['password']);
        return $user;
    }
}