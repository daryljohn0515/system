-- ============================================
-- MacGuyver Enterprises Engineering Services
-- Inventory System Database
-- ============================================

CREATE DATABASE IF NOT EXISTS macguyver_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE macguyver_inventory;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin','staff','viewer') DEFAULT 'staff',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Suppliers Table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(30),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Items/Products Table
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    category_id INT,
    supplier_id INT,
    unit VARCHAR(30) DEFAULT 'pcs',
    quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 5,
    unit_price DECIMAL(12,2) DEFAULT 0.00,
    location VARCHAR(100),
    image VARCHAR(255),
    status ENUM('active','inactive','discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- Inventory Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(50) NOT NULL UNIQUE,
    item_id INT NOT NULL,
    type ENUM('stock_in','stock_out','adjustment','return') NOT NULL,
    quantity INT NOT NULL,
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    unit_price DECIMAL(12,2) DEFAULT 0.00,
    reference_no VARCHAR(100),
    remarks TEXT,
    performed_by INT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Activity Logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Default Admin User (password: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@macguyver.com', 'admin'),
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff User', 'staff@macguyver.com', 'staff');

-- Default Categories
INSERT INTO categories (name, description) VALUES
('Electrical', 'Electrical components and materials'),
('Mechanical', 'Mechanical parts and equipment'),
('Solar Panels', 'Solar panel systems and accessories'),
('Tools & Equipment', 'Hand tools and power tools'),
('Safety Equipment', 'PPE and safety gear'),
('Pipes & Fittings', 'Plumbing and piping materials'),
('Cables & Wires', 'Electrical cables and wiring'),
('Hardware', 'General hardware and fasteners');

-- Default Suppliers
INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES
('ABC Hardware Supply', 'Juan dela Cruz', '09171234567', 'abc@hardware.com', 'Calamba, Laguna'),
('Solar Tech Philippines', 'Maria Santos', '09281234567', 'info@solartech.ph', 'Sta. Rosa, Laguna'),
('Engineering Depot', 'Pedro Reyes', '09391234567', 'sales@engdepot.com', 'Manila');

-- Sample Items
INSERT INTO items (item_code, name, description, category_id, supplier_id, unit, quantity, reorder_level, unit_price, location) VALUES
('ITM-001', 'Solar Panel 300W Monocrystalline', '300W 24V Monocrystalline Solar Panel', 3, 2, 'pcs', 25, 5, 8500.00, 'Warehouse A'),
('ITM-002', 'Circuit Breaker 30A', 'Single pole 30A circuit breaker', 1, 1, 'pcs', 50, 10, 350.00, 'Shelf B-2'),
('ITM-003', 'THHN Wire 12AWG (per meter)', 'THHN stranded wire 12AWG', 7, 1, 'meters', 500, 100, 45.00, 'Shelf C-1'),
('ITM-004', 'Angle Grinder 4"', '4-inch electric angle grinder 800W', 4, 3, 'pcs', 8, 2, 2200.00, 'Tool Room'),
('ITM-005', 'Safety Helmet', 'ANSI certified hard hat', 5, 3, 'pcs', 20, 5, 450.00, 'Shelf D-1'),
('ITM-006', 'PVC Pipe 4" (per length)', 'Schedule 40 PVC pipe 10ft', 6, 1, 'pcs', 40, 10, 280.00, 'Yard Area'),
('ITM-007', 'Solar Charge Controller 40A', 'MPPT Solar Charge Controller 40A 12/24V', 3, 2, 'pcs', 15, 3, 3200.00, 'Warehouse A'),
('ITM-008', 'Bolt & Nut Set M10', 'Stainless steel bolt and nut M10 x 50mm', 8, 1, 'set', 200, 50, 25.00, 'Shelf B-4');
