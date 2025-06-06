<?php
/**
 * Database setup and user management for Bella Vista Restaurant
 */

// Prevent direct access
if (!defined('BELLA_VISTA_ACCESS')) {
    http_response_code(403);
    exit('Access denied');
}

class Database {
    private $dbFile;
    private $pdo;
    
    public function __construct() {
        $this->dbFile = __DIR__ . '/data/users.db';
        $this->initDatabase();
    }
    
    private function initDatabase() {
        // Create data directory if it doesn't exist
        $dataDir = dirname($this->dbFile);
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        try {
            $this->pdo = new PDO("sqlite:" . $this->dbFile);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create users table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username VARCHAR(100) UNIQUE NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    phone VARCHAR(20),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_login DATETIME
                )
            ");
            
            // Create sessions table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS user_sessions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    session_token VARCHAR(255) UNIQUE NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
                )
            ");
            
            // Insert sample customer user if not exists
            $this->createSampleCustomer();
            
        } catch (PDOException $e) {
            logActivity("Database initialization error: " . $e->getMessage(), 'ERROR');
            throw new Exception("Database connection failed");
        }
    }
    
    public function registerUser($username, $email, $password, $firstName, $lastName, $phone = null) {
        try {
            // Check if username or email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetchColumn()) {
                throw new Exception("Username or email already exists");
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, phone) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName, $phone]);
            
            $userId = $this->pdo->lastInsertId();
            logActivity("New user registered: " . $username, 'INFO');
            
            return $this->getUserById($userId);
            
        } catch (PDOException $e) {
            logActivity("Registration error: " . $e->getMessage(), 'ERROR');
            throw new Exception("Registration failed");
        }
    }
    
    public function loginUser($username, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception("Invalid username or password");
            }
            
            // Update last login
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Create session token
            $sessionToken = $this->createSession($user['id']);
            
            logActivity("User logged in: " . $user['username'], 'INFO');
            
            // Remove password from returned data
            unset($user['password']);
            $user['session_token'] = $sessionToken;
            
            return $user;
            
        } catch (PDOException $e) {
            logActivity("Login error: " . $e->getMessage(), 'ERROR');
            throw new Exception("Login failed");
        }
    }
    
    public function createSession($userId) {
        try {
            // Clean up expired sessions
            $this->cleanupExpiredSessions();
            
            $sessionToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_sessions (user_id, session_token, expires_at) 
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([$userId, $sessionToken, $expiresAt]);
            
            return $sessionToken;
            
        } catch (PDOException $e) {
            logActivity("Session creation error: " . $e->getMessage(), 'ERROR');
            throw new Exception("Session creation failed");
        }
    }
    
    public function validateSession($sessionToken) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, s.expires_at 
                FROM users u 
                JOIN user_sessions s ON u.id = s.user_id 
                WHERE s.session_token = ? AND s.expires_at > CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([$sessionToken]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false;
            }
            
            // Remove password from returned data
            unset($user['password']);
            unset($user['expires_at']);
            
            return $user;
            
        } catch (PDOException $e) {
            logActivity("Session validation error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function logoutUser($sessionToken) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$sessionToken]);
            
            logActivity("User logged out", 'INFO');
            return true;
            
        } catch (PDOException $e) {
            logActivity("Logout error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function getUserById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                unset($user['password']);
            }
            
            return $user;
            
        } catch (PDOException $e) {
            logActivity("Get user error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    private function cleanupExpiredSessions() {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE expires_at <= CURRENT_TIMESTAMP");
            $stmt->execute();
        } catch (PDOException $e) {
            logActivity("Session cleanup error: " . $e->getMessage(), 'ERROR');
        }
    }
    

    
    private function createSampleCustomer() {
        try {
            // Check if sample customer already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = 'customer' LIMIT 1");
            $stmt->execute();
            
            if (!$stmt->fetchColumn()) {
                // Create sample customer user
                $hashedPassword = password_hash('customer123', PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password, first_name, last_name, phone) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute(['customer', 'customer@example.com', $hashedPassword, 'John', 'Smith', '+1-555-0123']);
                logActivity("Sample customer user created", 'INFO');
            }
        } catch (PDOException $e) {
            logActivity("Customer creation error: " . $e->getMessage(), 'ERROR');
        }
    }
    

    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $currentHash = $stmt->fetchColumn();
            
            if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
                return false;
            }
            
            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $success = $stmt->execute([$newHash, $userId]);
            
            if ($success) {
                logActivity("Password changed for user ID: " . $userId, 'INFO');
            }
            
            return $success;
            
        } catch (PDOException $e) {
            logActivity("Change password error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
?>