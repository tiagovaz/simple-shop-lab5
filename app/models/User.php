<?php
// app/models/User.php

class User {
    public ?int $id;
    public string $email;
    public string $passwordHash;

    // Constructor - creates a new User object
    public function __construct(?int $id, string $email, string $passwordHash) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
    }

    // ========== Static Methods (don't need an object) ==========

    // Find user by email
    public static function findByEmail(string $email): ?User {
        $db = db();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $data = $stmt->fetch();

        if (!$data) return null;

        return new User($data['id'], $data['email'], $data['password_hash']);
    }

    // Find user by ID
    public static function findById(int $id): ?User {
        $db = db();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        if (!$data) return null;

        return new User($data['id'], $data['email'], $data['password_hash']);
    }

    // Create and save a new user
    public static function create(string $email, string $password): ?User {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        // Validate password
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/';
        if (!preg_match($pattern, $password)) {
            return null;
        }

        // Check if email exists
        if (self::findByEmail($email) !== null) {
            return null;
        }

        // Save to database
        $db = db();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$email, $hash]);

        $id = (int) $db->lastInsertId();
        return new User($id, $email, $hash);
    }

    // Get currently logged in user
    public static function current(): ?User {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return self::findById($_SESSION['user_id']);
    }

    // Check if someone is logged in
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    // ========== Instance Methods (work on one user object) ==========

    // Check if password is correct
    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->passwordHash);
    }

    // Log this user in
    public function login(): void {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $this->id;
        $_SESSION['user_email'] = $this->email;
    }

    // Log out (static because we don't need a specific user)
    public static function logout(): void {
        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();
        session_start();
    }
}
