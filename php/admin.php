<?php
/**
 * Admin API endpoints for Bella Vista Restaurant
 */

// Define access constant
define('BELLA_VISTA_ACCESS', true);

// Include required files
require_once 'config.php';
require_once 'database.php';

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Initialize database
try {
    $db = new AdminDatabase();
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

// Check admin authentication
if (!isAdminAuthenticated() && $action !== 'login') {
    sendJsonResponse(['success' => false, 'message' => 'Admin authentication required'], 401);
}

switch ($action) {
    case 'login':
        handleAdminLogin();
        break;
    
    case 'logout':
        handleAdminLogout();
        break;
    
    case 'dashboard':
        handleDashboardStats();
        break;
        
    case 'reservations':
        handleReservations($method);
        break;
        
    case 'orders':
        handleOrders($method);
        break;
        
    case 'menu':
        handleMenu($method);
        break;
        
    case 'customers':
        handleCustomers($method);
        break;
        
    case 'analytics':
        handleAnalytics();
        break;
        
    case 'settings':
        handleSettings($method);
        break;
    
    default:
        sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

function isAdminAuthenticated() {
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

function handleAdminLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['username']) || empty($input['password'])) {
        sendJsonResponse(['success' => false, 'message' => 'Username and password are required'], 400);
    }
    
    // Simple admin authentication - in production, use proper admin user management
    if ($input['username'] === 'admin' && $input['password'] === 'admin123') {
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_user'] = 'admin';
        
        logActivity("Admin logged in", 'INFO');
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Admin login successful'
        ]);
    } else {
        logActivity("Failed admin login attempt: " . $input['username'], 'WARNING');
        sendJsonResponse(['success' => false, 'message' => 'Invalid admin credentials'], 401);
    }
}

function handleAdminLogout() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $_SESSION['admin_authenticated'] = false;
    unset($_SESSION['admin_user']);
    session_destroy();
    
    logActivity("Admin logged out", 'INFO');
    sendJsonResponse(['success' => true, 'message' => 'Logout successful']);
}

function handleDashboardStats() {
    global $db;
    
    try {
        $stats = [
            'todayReservations' => $db->getTodayReservationsCount(),
            'activeOrders' => $db->getActiveOrdersCount(),
            'todayRevenue' => $db->getTodayRevenue(),
            'totalCustomers' => $db->getTotalCustomersCount(),
            'recentReservations' => $db->getRecentReservations(5),
            'popularItems' => $db->getPopularMenuItems(5)
        ];
        
        sendJsonResponse(['success' => true, 'data' => $stats]);
        
    } catch (Exception $e) {
        logActivity("Dashboard stats error: " . $e->getMessage(), 'ERROR');
        sendJsonResponse(['success' => false, 'message' => 'Failed to load dashboard data'], 500);
    }
}

function handleReservations($method) {
    global $db;
    
    try {
        switch ($method) {
            case 'GET':
                $filter = $_GET['filter'] ?? 'all';
                $reservations = $db->getReservations($filter);
                sendJsonResponse(['success' => true, 'data' => $reservations]);
                break;
                
            case 'PUT':
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? null;
                $status = $input['status'] ?? null;
                
                if (!$id || !$status) {
                    sendJsonResponse(['success' => false, 'message' => 'ID and status are required'], 400);
                }
                
                $result = $db->updateReservationStatus($id, $status);
                if ($result) {
                    logActivity("Reservation $id status updated to $status", 'INFO');
                    sendJsonResponse(['success' => true, 'message' => 'Reservation updated successfully']);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Failed to update reservation'], 500);
                }
                break;
                
            default:
                sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
    } catch (Exception $e) {
        logActivity("Reservations error: " . $e->getMessage(), 'ERROR');
        sendJsonResponse(['success' => false, 'message' => 'Failed to handle reservations'], 500);
    }
}

function handleOrders($method) {
    global $db;
    
    try {
        switch ($method) {
            case 'GET':
                $filter = $_GET['filter'] ?? 'all';
                $orders = $db->getOrders($filter);
                sendJsonResponse(['success' => true, 'data' => $orders]);
                break;
                
            case 'PUT':
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? null;
                $status = $input['status'] ?? null;
                
                if (!$id || !$status) {
                    sendJsonResponse(['success' => false, 'message' => 'ID and status are required'], 400);
                }
                
                $result = $db->updateOrderStatus($id, $status);
                if ($result) {
                    logActivity("Order $id status updated to $status", 'INFO');
                    sendJsonResponse(['success' => true, 'message' => 'Order updated successfully']);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Failed to update order'], 500);
                }
                break;
                
            default:
                sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
    } catch (Exception $e) {
        logActivity("Orders error: " . $e->getMessage(), 'ERROR');
        sendJsonResponse(['success' => false, 'message' => 'Failed to handle orders'], 500);
    }
}

function handleMenu($method) {
    global $db;
    
    try {
        switch ($method) {
            case 'GET':
                $menuItems = $db->getMenuItems();
                sendJsonResponse(['success' => true, 'data' => $menuItems]);
                break;
                
            case 'POST':
                $input = json_decode(file_get_contents('php://input'), true);
                
                $requiredFields = ['name', 'description', 'price', 'category'];
                foreach ($requiredFields as $field) {
                    if (empty($input[$field])) {
                        sendJsonResponse(['success' => false, 'message' => "Field '$field' is required"], 400);
                    }
                }
                
                $result = $db->addMenuItem($input);
                if ($result) {
                    logActivity("Menu item added: " . $input['name'], 'INFO');
                    sendJsonResponse(['success' => true, 'message' => 'Menu item added successfully']);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Failed to add menu item'], 500);
                }
                break;
                
            case 'PUT':
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? null;
                
                if (!$id) {
                    sendJsonResponse(['success' => false, 'message' => 'Item ID is required'], 400);
                }
                
                $result = $db->updateMenuItem($id, $input);
                if ($result) {
                    logActivity("Menu item $id updated", 'INFO');
                    sendJsonResponse(['success' => true, 'message' => 'Menu item updated successfully']);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Failed to update menu item'], 500);
                }
                break;
                
            case 'DELETE':
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? null;
                
                if (!$id) {
                    sendJsonResponse(['success' => false, 'message' => 'Item ID is required'], 400);
                }
                
                $result = $db->deleteMenuItem($id);
                if ($result) {
                    logActivity("Menu item $id deleted", 'INFO');
                    sendJsonResponse(['success' => true, 'message' => 'Menu item deleted successfully']);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Failed to delete menu item'], 500);
                }
                break;
                
            default:
                sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
    } catch (Exception $e) {
        logActivity("Menu error: " . $e->getMessage(), 'ERROR');
        sendJsonResponse(['success' => false, 'message' => 'Failed to handle menu'], 500);
    }
}

function handleCustomers($method) {
    global $db;
    
    try {
        switch ($method) {
            case 'GET':
                $search = $_GET['search'] ?? '';
                $customers = $db->getCustomers($search);
                sendJsonResponse(['success' => true, 'data' => $customers]);
                break;
                
            default:
                sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
    } catch (Exception $e) {
        logActivity("Customers error: " . $e->getMessage(), 'ERROR');
        sendJsonResponse(['success' => false, 'message' => 'Failed to handle customers'], 500);
    }
}

function handleAnalytics() {
    global $db;
    
    try {
        $period = $_GET['period'] ?? 'week';
        $analytics = $db->getAnalytics($period);
        sendJsonResponse(['success' => true, 'data' => $analytics]);
        
    } catch (Exception $e) {
        logActivity("Analytics error: " . $e->getMessage(), 'ERROR');
        sendJsonResponse(['success' => false, 'message' => 'Failed to load analytics'], 500);
    }
}

function handleSettings($method) {
    global $db;
    
    try {
        switch ($method) {
            case 'GET':
                $settings = $db->getSettings();
                sendJsonResponse(['success' => true, 'data' => $settings]);
                break;
                
            case 'PUT':
                $input = json_decode(file_get_contents('php://input'), true);
                $result = $db->updateSettings($input);
                
                if ($result) {
                    logActivity("Restaurant settings updated", 'INFO');
                    sendJsonResponse(['success' => true, 'message' => 'Settings updated successfully']);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Failed to update settings'], 500);
                }
                break;
                
            default:
                sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        
    } catch (Exception $e) {
        logActivity("Settings error: " . $e->getMessage(), 'ERROR');
        sendJsonResponse(['success' => false, 'message' => 'Failed to handle settings'], 500);
    }
}

// Enhanced Database class for admin operations
class AdminDatabase extends Database {
    
    public function __construct() {
        parent::__construct();
        $this->initAdminTables();
    }
    
    private function initAdminTables() {
        try {
            // Create reservations table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS reservations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    customer_name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    reservation_date DATE NOT NULL,
                    reservation_time TIME NOT NULL,
                    party_size INTEGER NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending',
                    special_requests TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Create orders table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS orders (
                    id VARCHAR(20) PRIMARY KEY,
                    customer_name VARCHAR(255) NOT NULL,
                    customer_email VARCHAR(255),
                    items TEXT NOT NULL,
                    total_amount DECIMAL(10,2) NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending',
                    order_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                    estimated_ready_time DATETIME,
                    completed_at DATETIME
                )
            ");
            
            // Create menu_items table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS menu_items (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    category VARCHAR(100) NOT NULL,
                    image_url VARCHAR(500),
                    available BOOLEAN DEFAULT 1,
                    popularity_score INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Create restaurant_settings table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS restaurant_settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT NOT NULL,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Insert sample data if tables are empty
            $this->insertSampleData();
            
        } catch (PDOException $e) {
            logActivity("Admin database initialization error: " . $e->getMessage(), 'ERROR');
            throw new Exception("Admin database setup failed");
        }
    }
    
    private function insertSampleData() {
        // Check if data already exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM reservations");
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            return; // Data already exists
        }
        
        // Insert sample reservations
        $reservations = [
            ['John Smith', 'john@example.com', '+1 (555) 123-4567', '2025-06-07', '19:00', 4, 'confirmed', 'Anniversary dinner'],
            ['Sarah Johnson', 'sarah@example.com', '+1 (555) 987-6543', '2025-06-07', '20:30', 2, 'pending', 'Vegetarian options'],
            ['Michael Brown', 'michael@example.com', '+1 (555) 456-7890', '2025-06-08', '18:00', 6, 'confirmed', 'Birthday celebration']
        ];
        
        foreach ($reservations as $reservation) {
            $stmt = $this->pdo->prepare("
                INSERT INTO reservations (customer_name, email, phone, reservation_date, reservation_time, party_size, status, special_requests)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute($reservation);
        }
        
        // Insert sample orders
        $orders = [
            ['ORD-001', 'Emily Davis', 'emily@example.com', 'Grilled Salmon, Caesar Salad, Tiramisu', 67.50, 'preparing'],
            ['ORD-002', 'Robert Wilson', 'robert@example.com', 'Ribeye Steak, Truffle Risotto', 89.00, 'ready'],
            ['ORD-003', 'Lisa Anderson', 'lisa@example.com', 'Margherita Pizza, Caprese Salad, Wine', 45.25, 'delivered']
        ];
        
        foreach ($orders as $order) {
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (id, customer_name, customer_email, items, total_amount, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute($order);
        }
        
        // Insert sample menu items
        $menuItems = [
            ['Grilled Salmon', 'Fresh Atlantic salmon with herbs and lemon', 28.50, 'Main Course', 95],
            ['Ribeye Steak', 'Premium aged beef with garlic butter', 42.00, 'Main Course', 88],
            ['Truffle Risotto', 'Creamy arborio rice with black truffle', 24.00, 'Main Course', 82],
            ['Caesar Salad', 'Crisp romaine with parmesan and croutons', 16.50, 'Appetizer', 76],
            ['Tiramisu', 'Classic Italian dessert with coffee and mascarpone', 12.00, 'Dessert', 91]
        ];
        
        foreach ($menuItems as $item) {
            $stmt = $this->pdo->prepare("
                INSERT INTO menu_items (name, description, price, category, popularity_score)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute($item);
        }
        
        // Insert default settings
        $settings = [
            ['restaurant_name', 'Bella Vista Restaurant'],
            ['phone', '(555) 123-4567'],
            ['email', 'info@bellavista.com'],
            ['address', '123 Culinary Street, Food District, NY 10001']
        ];
        
        foreach ($settings as $setting) {
            $stmt = $this->pdo->prepare("
                INSERT INTO restaurant_settings (setting_key, setting_value)
                VALUES (?, ?)
            ");
            $stmt->execute($setting);
        }
    }
    
    // Dashboard methods
    public function getTodayReservationsCount() {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_date = ?");
        $stmt->execute([$today]);
        return $stmt->fetchColumn();
    }
    
    public function getActiveOrdersCount() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'preparing', 'ready')");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    public function getTodayRevenue() {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(order_time) = ?");
        $stmt->execute([$today]);
        return $stmt->fetchColumn();
    }
    
    public function getTotalCustomersCount() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    public function getRecentReservations($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM reservations 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPopularMenuItems($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM menu_items 
            WHERE available = 1 
            ORDER BY popularity_score DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Reservation methods
    public function getReservations($filter = 'all') {
        $sql = "SELECT * FROM reservations";
        $params = [];
        
        switch ($filter) {
            case 'today':
                $sql .= " WHERE reservation_date = ?";
                $params[] = date('Y-m-d');
                break;
            case 'tomorrow':
                $tomorrow = date('Y-m-d', strtotime('+1 day'));
                $sql .= " WHERE reservation_date = ?";
                $params[] = $tomorrow;
                break;
            case 'week':
                $weekLater = date('Y-m-d', strtotime('+7 days'));
                $sql .= " WHERE reservation_date BETWEEN ? AND ?";
                $params[] = date('Y-m-d');
                $params[] = $weekLater;
                break;
            case 'pending':
            case 'confirmed':
            case 'cancelled':
                $sql .= " WHERE status = ?";
                $params[] = $filter;
                break;
        }
        
        $sql .= " ORDER BY reservation_date, reservation_time";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateReservationStatus($id, $status) {
        $stmt = $this->pdo->prepare("
            UPDATE reservations 
            SET status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $id]);
    }
    
    // Order methods
    public function getOrders($filter = 'all') {
        $sql = "SELECT * FROM orders";
        $params = [];
        
        if ($filter !== 'all') {
            $sql .= " WHERE status = ?";
            $params[] = $filter;
        }
        
        $sql .= " ORDER BY order_time DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateOrderStatus($id, $status) {
        $completedAt = ($status === 'delivered') ? date('Y-m-d H:i:s') : null;
        
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = ?, completed_at = ?, estimated_ready_time = CASE 
                WHEN ? = 'preparing' THEN datetime('now', '+30 minutes')
                WHEN ? = 'ready' THEN datetime('now', '+5 minutes')
                ELSE estimated_ready_time
            END
            WHERE id = ?
        ");
        return $stmt->execute([$status, $completedAt, $status, $status, $id]);
    }
    
    // Menu methods
    public function getMenuItems() {
        $stmt = $this->pdo->prepare("SELECT * FROM menu_items ORDER BY category, name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function addMenuItem($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO menu_items (name, description, price, category, image_url)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category'],
            $data['image_url'] ?? null
        ]);
    }
    
    public function updateMenuItem($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE menu_items 
            SET name = ?, description = ?, price = ?, category = ?, 
                available = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category'],
            $data['available'] ?? 1,
            $id
        ]);
    }
    
    public function deleteMenuItem($id) {
        $stmt = $this->pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Customer methods
    public function getCustomers($search = '') {
        $sql = "SELECT 
                    u.*,
                    COUNT(o.id) as total_orders,
                    MAX(o.order_time) as last_order
                FROM users u
                LEFT JOIN orders o ON u.email = o.customer_email";
        
        $params = [];
        if (!empty($search)) {
            $sql .= " WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?";
            $searchTerm = "%$search%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }
        
        $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Analytics methods
    public function getAnalytics($period = 'week') {
        $dateFilter = $this->getDateFilter($period);
        
        return [
            'revenue' => $this->getRevenueData($dateFilter),
            'orders' => $this->getOrdersData($dateFilter),
            'customers' => $this->getCustomerData($dateFilter)
        ];
    }
    
    private function getDateFilter($period) {
        switch ($period) {
            case 'week':
                return "DATE(order_time) >= DATE('now', '-7 days')";
            case 'month':
                return "DATE(order_time) >= DATE('now', '-30 days')";
            case 'quarter':
                return "DATE(order_time) >= DATE('now', '-90 days')";
            case 'year':
                return "DATE(order_time) >= DATE('now', '-365 days')";
            default:
                return "DATE(order_time) >= DATE('now', '-7 days')";
        }
    }
    
    private function getRevenueData($dateFilter) {
        $stmt = $this->pdo->prepare("
            SELECT DATE(order_time) as date, SUM(total_amount) as revenue
            FROM orders 
            WHERE $dateFilter
            GROUP BY DATE(order_time)
            ORDER BY date
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getOrdersData($dateFilter) {
        $stmt = $this->pdo->prepare("
            SELECT DATE(order_time) as date, COUNT(*) as count
            FROM orders 
            WHERE $dateFilter
            GROUP BY DATE(order_time)
            ORDER BY date
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getCustomerData($dateFilter) {
        $stmt = $this->pdo->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as new_customers
            FROM users 
            WHERE DATE(created_at) >= DATE('now', '-30 days')
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Settings methods
    public function getSettings() {
        $stmt = $this->pdo->prepare("SELECT * FROM restaurant_settings");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }
    
    public function updateSettings($data) {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($data as $key => $value) {
                $stmt = $this->pdo->prepare("
                    INSERT OR REPLACE INTO restaurant_settings (setting_key, setting_value, updated_at)
                    VALUES (?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$key, $value]);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}

// Utility functions
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function logActivity($message, $level = 'INFO') {
    if (!defined('LOG_ERRORS') || !LOG_ERRORS) {
        return;
    }
    
    $logFile = defined('ERROR_LOG_FILE') ? ERROR_LOG_FILE : __DIR__ . '/logs/admin.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}
?>