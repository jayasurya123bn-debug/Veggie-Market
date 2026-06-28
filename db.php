<?php
// ============================================================
//  DATABASE CONFIGURATION — Veggie Market
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'veggie_market');

// ── ADMIN WHITELIST ──────────────────────────────────────────
// Only this email address has full admin access
define('ADMIN_EMAIL', 'jayasurya123bn@gmail.com');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create & select database
$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
$conn->select_db(DB_NAME);

// ── USERS TABLE ──────────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        username    VARCHAR(50)  UNIQUE NOT NULL,
        email       VARCHAR(100) UNIQUE NOT NULL,
        password    VARCHAR(255) NOT NULL,
        full_name   VARCHAR(100),
        is_admin    TINYINT(1)   DEFAULT 0,
        is_banned   TINYINT(1)   DEFAULT 0,
        created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    )
");

// Add columns if they don't exist (for existing installations)
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin  TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) DEFAULT 0");

// Auto-grant admin to the designated email (if account exists)
$conn->query("UPDATE users SET is_admin=1 WHERE email='" . ADMIN_EMAIL . "'");

// ── PRODUCTS TABLE ───────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS products (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(100) NOT NULL,
        description TEXT,
        price       DECIMAL(10,2) NOT NULL,
        unit        VARCHAR(20)  DEFAULT 'kg',
        stock       INT          DEFAULT 0,
        category    VARCHAR(50),
        image       VARCHAR(255),
        is_featured TINYINT(1)   DEFAULT 0,
        added_by    INT,
        created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (added_by) REFERENCES users(id)
    )
");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) DEFAULT 0");

// ── CART TABLE ───────────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS cart (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        product_id INT NOT NULL,
        quantity   INT DEFAULT 1,
        added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id)    REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )
");

// ── SITE SETTINGS TABLE ──────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS site_settings (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        skey      VARCHAR(100) UNIQUE NOT NULL,
        sval      TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");
// Default settings
$conn->query("INSERT IGNORE INTO site_settings (skey,sval) VALUES
    ('site_name',   'Veggie Market'),
    ('tagline',     'Fresh, Local, Delicious'),
    ('maintenance', '0'),
    ('currency',    '₹')
");

// ── SESSION START ────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── HELPER: check if current user is admin ───────────────────
function isAdmin(): bool {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}
?>
