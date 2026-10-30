-- FRESH HOSTING INSTALL ONLY. Select the database created by your hosting control panel first.
-- No CREATE DATABASE or USE: compatible with provider-prefixed database names.
-- Contains schema and lookup seeds only; no accounts or business records.
CREATE TABLE roles (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, role_name VARCHAR(30) NOT NULL UNIQUE) ENGINE=InnoDB;
CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, role_id INT UNSIGNED NOT NULL, full_name VARCHAR(100) NOT NULL, username VARCHAR(60) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, email VARCHAR(254) NULL UNIQUE, email_verified_at DATETIME NULL, is_active BOOLEAN NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(role_id) REFERENCES roles(id)) ENGINE=InnoDB;
CREATE TABLE product_categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_name VARCHAR(100) NOT NULL UNIQUE, is_active BOOLEAN NOT NULL DEFAULT 1) ENGINE=InnoDB;
CREATE TABLE units (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, unit_name VARCHAR(60) NOT NULL UNIQUE, abbreviation VARCHAR(15) NOT NULL, is_active BOOLEAN NOT NULL DEFAULT 1) ENGINE=InnoDB;
CREATE TABLE products (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id INT UNSIGNED NOT NULL, unit_id INT UNSIGNED NOT NULL, sku VARCHAR(50) NOT NULL UNIQUE, product_name VARCHAR(150) NOT NULL, selling_price DECIMAL(12,2) NOT NULL, stock_qty INT NOT NULL DEFAULT 0, reorder_level INT NOT NULL DEFAULT 5, is_active BOOLEAN NOT NULL DEFAULT 1, FOREIGN KEY(category_id) REFERENCES product_categories(id), FOREIGN KEY(unit_id) REFERENCES units(id), CHECK(selling_price>=0), CHECK(stock_qty>=0), CHECK(reorder_level>=0)) ENGINE=InnoDB;
CREATE TABLE suppliers (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, supplier_name VARCHAR(150) NOT NULL, contact_person VARCHAR(100) NOT NULL DEFAULT '', phone VARCHAR(40) NOT NULL DEFAULT '', address VARCHAR(250) NOT NULL DEFAULT '', is_active BOOLEAN NOT NULL DEFAULT 1) ENGINE=InnoDB;
CREATE TABLE purchases (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, document_no VARCHAR(40) NOT NULL UNIQUE, request_key CHAR(32) NOT NULL UNIQUE, supplier_id INT UNSIGNED NOT NULL, created_by INT UNSIGNED NOT NULL, supplier_reference VARCHAR(100) NOT NULL DEFAULT '', notes VARCHAR(500) NOT NULL DEFAULT '', posted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, status ENUM('POSTED') NOT NULL DEFAULT 'POSTED', total_amount DECIMAL(12,2) NOT NULL, FOREIGN KEY(supplier_id) REFERENCES suppliers(id), FOREIGN KEY(created_by) REFERENCES users(id), CHECK(total_amount>=0)) ENGINE=InnoDB;
CREATE TABLE purchase_items (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, purchase_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, quantity INT NOT NULL, unit_cost DECIMAL(12,2) NOT NULL, FOREIGN KEY(purchase_id) REFERENCES purchases(id), FOREIGN KEY(product_id) REFERENCES products(id), UNIQUE(purchase_id,product_id), CHECK(quantity>0), CHECK(unit_cost>=0)) ENGINE=InnoDB;
CREATE TABLE sales (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, document_no VARCHAR(40) NOT NULL UNIQUE, request_key CHAR(32) NOT NULL UNIQUE, created_by INT UNSIGNED NOT NULL, posted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, status ENUM('POSTED') NOT NULL DEFAULT 'POSTED', total_amount DECIMAL(12,2) NOT NULL, cash_tendered DECIMAL(12,2) NOT NULL, FOREIGN KEY(created_by) REFERENCES users(id), CHECK(total_amount>=0), CHECK(cash_tendered>=total_amount)) ENGINE=InnoDB;
CREATE TABLE sale_items (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, sale_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, product_name_snapshot VARCHAR(150) NOT NULL, sku_snapshot VARCHAR(50) NOT NULL, quantity INT NOT NULL, unit_price DECIMAL(12,2) NOT NULL, FOREIGN KEY(sale_id) REFERENCES sales(id), FOREIGN KEY(product_id) REFERENCES products(id), UNIQUE(sale_id,product_id), CHECK(quantity>0), CHECK(unit_price>=0)) ENGINE=InnoDB;
CREATE TABLE sales_returns (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, document_no VARCHAR(40) NOT NULL UNIQUE, request_key CHAR(32) NOT NULL UNIQUE, sale_id INT UNSIGNED NOT NULL, created_by INT UNSIGNED NOT NULL, posted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, status ENUM('POSTED') NOT NULL DEFAULT 'POSTED', reason VARCHAR(500) NOT NULL, total_amount DECIMAL(12,2) NOT NULL, FOREIGN KEY(sale_id) REFERENCES sales(id), FOREIGN KEY(created_by) REFERENCES users(id), CHECK(total_amount>=0)) ENGINE=InnoDB;
CREATE TABLE sales_return_items (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, return_id INT UNSIGNED NOT NULL, sale_item_id INT UNSIGNED NOT NULL, quantity INT NOT NULL, restock_quantity INT NOT NULL, FOREIGN KEY(return_id) REFERENCES sales_returns(id), FOREIGN KEY(sale_item_id) REFERENCES sale_items(id), UNIQUE(return_id,sale_item_id), CHECK(quantity>0), CHECK(restock_quantity>=0 AND restock_quantity<=quantity)) ENGINE=InnoDB;
CREATE TABLE stock_adjustments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, document_no VARCHAR(40) NOT NULL UNIQUE, request_key CHAR(32) NOT NULL UNIQUE, created_by INT UNSIGNED NOT NULL, posted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, status ENUM('POSTED') NOT NULL DEFAULT 'POSTED', reason VARCHAR(100) NOT NULL, notes VARCHAR(500) NOT NULL DEFAULT '', FOREIGN KEY(created_by) REFERENCES users(id)) ENGINE=InnoDB;
CREATE TABLE stock_adjustment_items (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, adjustment_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, quantity_change INT NOT NULL, FOREIGN KEY(adjustment_id) REFERENCES stock_adjustments(id), FOREIGN KEY(product_id) REFERENCES products(id), UNIQUE(adjustment_id,product_id), CHECK(quantity_change<>0)) ENGINE=InnoDB;
CREATE TABLE activity_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, action VARCHAR(30) NOT NULL, record_type VARCHAR(50) NOT NULL, record_id BIGINT UNSIGNED NOT NULL, description VARCHAR(500) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB;
INSERT INTO roles (role_name) VALUES ('Owner'),('Cashier');
INSERT INTO product_categories (category_name) VALUES ('Food'),('Drinks'),('Household');
INSERT INTO units (unit_name,abbreviation) VALUES ('Piece','pc'),('Bottle','btl'),('Pack','pk');
CREATE TABLE account_invitations (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(254) NOT NULL UNIQUE,
 role_id INT UNSIGNED NOT NULL,
 invited_by INT UNSIGNED NOT NULL,
 otp_hash VARCHAR(255) NULL,
 expires_at DATETIME NULL,
 attempts INT UNSIGNED NOT NULL DEFAULT 0,
 last_sent_at DATETIME NULL,
 send_window_at DATETIME NULL,
 sends_in_window INT UNSIGNED NOT NULL DEFAULT 0,
 used_at DATETIME NULL,
 revoked_at DATETIME NULL,
 user_id INT UNSIGNED NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(role_id) REFERENCES roles(id),
 FOREIGN KEY(invited_by) REFERENCES users(id),
 FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
