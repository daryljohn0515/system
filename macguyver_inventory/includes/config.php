<?php
// ============================================
// MacGuyver Enterprises - DB Configuration
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'macguyver_inventory');
define('SITE_NAME', 'MacGuyver Enterprises');
define('SITE_SUBTITLE', 'Engineering Services - Inventory System');
define('VERSION', '1.0.0');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('<div style="padding:20px;color:red;font-family:Arial;">
            <h3>Database Connection Failed</h3>
            <p>' . $conn->connect_error . '</p>
            <p>Please make sure XAMPP MySQL is running and the database <strong>' . DB_NAME . '</strong> exists.</p>
        </div>');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
