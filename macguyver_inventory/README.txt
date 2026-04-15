============================================================
  MacGuyver Enterprises Engineering Services
  INVENTORY SYSTEM v1.0
  
  SETUP GUIDE FOR XAMPP
============================================================

REQUIREMENTS:
- XAMPP (Apache + MySQL/MariaDB + PHP 7.4 or higher)
- Web browser (Chrome, Firefox, Edge)

------------------------------------------------------------
STEP-BY-STEP INSTALLATION
------------------------------------------------------------

STEP 1: INSTALL XAMPP
  - Download from: https://www.apachefriends.org
  - Install and open XAMPP Control Panel
  - Start Apache and MySQL

STEP 2: COPY PROJECT FILES
  - Extract/copy the "macguyver_inventory" folder
  - Paste it to: C:\xampp\htdocs\macguyver_inventory

STEP 3: IMPORT DATABASE
  a) Open your browser, go to: http://localhost/phpmyadmin
  b) Click "New" to create a new database
     - Name it: macguyver_inventory
     - Collation: utf8mb4_unicode_ci
     - Click "Create"
  c) Select the "macguyver_inventory" database
  d) Click "Import" tab
  e) Click "Choose File" and select:
        macguyver_inventory/database.sql
  f) Click "Go" to import

  *** OR use the auto-installer (STEP 3 ALTERNATIVE):
  Open browser > go to: http://localhost/macguyver_inventory/install.php
  Follow the on-screen instructions.

STEP 4: CONFIGURE DATABASE (if needed)
  - Open: macguyver_inventory/includes/config.php
  - Check these values:
      define('DB_HOST', 'localhost');
      define('DB_USER', 'root');        ← XAMPP default
      define('DB_PASS', '');            ← XAMPP default (empty)
      define('DB_NAME', 'macguyver_inventory');
  - If your XAMPP MySQL has a password, update DB_PASS

STEP 5: ACCESS THE SYSTEM
  - Open browser and go to:
      http://localhost/macguyver_inventory/
  - You will be redirected to the Login page

------------------------------------------------------------
DEFAULT LOGIN CREDENTIALS
------------------------------------------------------------

  Admin Account:
  Username: admin
  Password: admin123

  Staff Account:
  Username: staff1
  Password: admin123

  *** IMPORTANT: Change the default password after first login!
  Go to: Users > Edit your account > Set new password

------------------------------------------------------------
SYSTEM FEATURES
------------------------------------------------------------

INVENTORY ITEMS
  - Add, edit, delete items with full details
  - Item codes (auto-generate or manual)
  - Category and supplier assignment
  - Stock levels and reorder alerts
  - Storage location tracking

STOCK IN / STOCK OUT
  - Record incoming deliveries (Stock In)
  - Record item releases (Stock Out)
  - Reference number tracking (PO/DR/JO)
  - Automatic quantity update

TRANSACTIONS
  - Complete movement history
  - Filter by type, date range, search
  - Printable records

CATEGORIES & SUPPLIERS
  - Full CRUD management
  - Linked to inventory items

REPORTS (4 types)
  1. Inventory Status Report
  2. Low Stock Report
  3. Transaction Report (by date range)
  4. Inventory Valuation Report

USER MANAGEMENT (Admin only)
  - Add staff and viewer accounts
  - Assign roles: Admin / Staff / Viewer
  - Activate / deactivate accounts

ACTIVITY LOGS (Admin only)
  - All user actions tracked
  - Login/logout history
  - IP address logging

------------------------------------------------------------
FOLDER STRUCTURE
------------------------------------------------------------

macguyver_inventory/
├── index.php              ← Dashboard
├── login.php              ← Login page
├── logout.php             ← Logout handler
├── install.php            ← Auto-installer
├── database.sql           ← Database schema + sample data
│
├── includes/
│   ├── config.php         ← Database config
│   ├── functions.php      ← Auth & helper functions
│   ├── header.php         ← Sidebar + navbar
│   └── footer.php         ← Page footer
│
├── pages/
│   ├── items.php          ← Inventory items
│   ├── stock_in.php       ← Stock In
│   ├── stock_out.php      ← Stock Out
│   ├── transactions.php   ← Transaction history
│   ├── categories.php     ← Categories
│   ├── suppliers.php      ← Suppliers
│   ├── reports.php        ← Reports
│   ├── users.php          ← User management
│   └── logs.php           ← Activity logs
│
└── assets/
    ├── logo.png           ← Company logo
    ├── css/style.css      ← Stylesheet
    └── js/main.js         ← JavaScript

------------------------------------------------------------
TROUBLESHOOTING
------------------------------------------------------------

"Database Connection Failed"
  → Make sure XAMPP MySQL is running
  → Check config.php credentials

"Page Not Found"
  → Make sure folder is in C:\xampp\htdocs\
  → Make sure Apache is running in XAMPP

"Login Failed with admin/admin123"
  → Database not imported yet — run database.sql first
  → Or use install.php auto-installer

White page / PHP errors
  → Make sure PHP version is 7.4+
  → Check XAMPP PHP version in Control Panel

------------------------------------------------------------
  Developed for MacGuyver Enterprises Engineering Services
  Version 1.0 | 2024
============================================================
