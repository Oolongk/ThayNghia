-- Tạo database mới
CREATE DATABASE IF NOT EXISTS db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db;

-- 1. Bảng Danh mục (Categories)
CREATE TABLE tbl_categories (
    cat_id INT AUTO_INCREMENT PRIMARY KEY,
    cat_name VARCHAR(100) NOT NULL, -- Ví dụ: Nam, Nữ, Bé Trai
    parent_id INT DEFAULT 0, -- Để làm đa cấp (VD: Nam > Hunter)
    slug VARCHAR(100) NOT NULL
);

-- 2. Bảng Sản phẩm (Inventory)
CREATE TABLE tbl_inventory (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    cat_id INT,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT 0, -- Giá khuyến mãi
    thumbnail VARCHAR(255), -- Ảnh đại diện
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cat_id) REFERENCES tbl_categories(cat_id)
);

-- 3. Bảng Biến thể (Variants - Quản lý Size/Màu/Tồn kho)
CREATE TABLE tbl_product_variants (
    variant_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    size VARCHAR(10) NOT NULL, -- VD: 39, 40, 41
    color VARCHAR(50), -- VD: Cam, Xanh
    stock_quantity INT DEFAULT 0, -- Tồn kho theo từng size
    FOREIGN KEY (product_id) REFERENCES tbl_inventory(product_id) ON DELETE CASCADE
);

-- 4. Bảng Khách hàng (Customers)
CREATE TABLE tbl_customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Sẽ dùng password_hash
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Bảng Đánh giá (Reviews)
CREATE TABLE tbl_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    customer_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES tbl_inventory(product_id),
    FOREIGN KEY (customer_id) REFERENCES tbl_customers(customer_id)
);

-- 6. Bảng Đơn hàng (Orders)
CREATE TABLE tbl_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES tbl_customers(customer_id)
);

-- 7. Bảng Chi tiết đơn hàng (Order Items)
CREATE TABLE tbl_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    variant_id INT, -- Liên kết với biến thể (để biết khách mua Size nào)
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES tbl_orders(order_id),
    FOREIGN KEY (variant_id) REFERENCES tbl_product_variants(variant_id)
);

-- Dữ liệu mẫu (Seed Data)
INSERT INTO tbl_categories (cat_name, slug) VALUES ('Nam', 'nam'), ('Nữ', 'nu'), ('Bé Trai', 'be-trai');
INSERT INTO tbl_inventory (cat_id, product_name, price, sale_price, thumbnail) VALUES 
(1, 'Biti\'s Hunter X - 2K23', 1200000, 1099000, 'hunter_x.jpg'),
(2, 'Biti\'s Hunter Core', 900000, 0, 'hunter_core.jpg');
INSERT INTO tbl_product_variants (product_id, size, stock_quantity) VALUES 
(1, '39', 10), (1, '40', 15), (1, '41', 5),
(2, '36', 20), (2, '37', 10);