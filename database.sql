CREATE DATABASE IF NOT EXISTS myntra_replica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE myntra_replica;

CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    category ENUM('Men','Women','Kids','Home','Beauty') NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    discount TINYINT UNSIGNED NOT NULL DEFAULT 0,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    image VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wishlist (
    wishlist_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_product_wishlist (user_id, product_id),
    CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_wishlist_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart (
    cart_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    size VARCHAR(12) NOT NULL DEFAULT 'M',
    UNIQUE KEY user_product_size_cart (user_id, product_id, size),
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    order_status VARCHAR(30) NOT NULL DEFAULT 'Placed (simulated)',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT IGNORE INTO users (name,email,password,role)
VALUES ('Studio Admin','admin@myntra.local','$2y$10$1Xo1ZvCX74AMJTnQWff51.uf4.EYsaqNPIL7.KgjMQHeauciyhM.W','admin')
;

INSERT INTO products (name,brand,category,description,price,discount,stock,image) VALUES
('Relaxed cotton poplin shirt','Roadster','Men','A crisp cotton-poplin shirt with an easy relaxed fit, curved hem and button-down front.',1999,35,18,'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=800&q=85'),
('Textured overshirt','H&M','Men','A lightweight textured overshirt made for effortless layering from day to night.',2799,30,12,'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?auto=format&fit=crop&w=800&q=85'),
('Everyday slim jeans','Levis','Men','Classic five-pocket denim in a versatile blue wash with a comfortable stretch finish.',3499,25,9,'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&w=800&q=85'),
('Essential crew-neck tee','Puma','Men','A breathable cotton jersey tee with a clean crew neck and minimal chest logo.',1299,20,24,'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=800&q=85'),
('Lightweight running sneakers','Nike','Men','Responsive everyday trainers with cushioned support and a durable rubber outsole.',5995,18,8,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=85'),
('Pleated midi dress','ONLY','Women','A softly pleated midi dress with a flattering waist and movement-rich silhouette.',3999,40,15,'https://images.unsplash.com/photo-1566174053879-31528523f8ae?auto=format&fit=crop&w=800&q=85'),
('Floral wrap dress','DressBerry','Women','A romantic floral wrap dress in lightweight fabric, finished with a self-tie waist.',2799,35,11,'https://images.unsplash.com/photo-1595777457583-95e059d581b8?auto=format&fit=crop&w=800&q=85'),
('Structured everyday tote','Lavie','Women','A spacious structured tote with polished hardware and room for everyday essentials.',2599,30,6,'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=85'),
('Ribbed knit cardigan','Mango','Women','A soft ribbed knit layer with a relaxed shape and timeless button-front styling.',3299,20,13,'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?auto=format&fit=crop&w=800&q=85'),
('Minimal leather sneakers','Adidas','Women','Low-profile sneakers with a clean court-inspired design and cushioned footbed.',4999,25,10,'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&w=800&q=85'),
('Kids graphic cotton tee','YK','Kids','A soft, playful graphic tee crafted from cotton for all-day comfort.',899,15,20,'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?auto=format&fit=crop&w=800&q=85'),
('Kids denim dungarees','HOP','Kids','Easy pull-on denim dungarees with adjustable straps and practical pockets.',1799,25,7,'https://images.unsplash.com/photo-1519457431-44ccd64a579b?auto=format&fit=crop&w=800&q=85'),
('Canvas slip-on shoes','Crocs','Kids','Lightweight slip-on shoes with a flexible sole for little everyday adventures.',1495,20,14,'https://images.unsplash.com/photo-1514989940723-e8e51635b782?auto=format&fit=crop&w=800&q=85'),
('Printed cotton bedsheet set','Home Centre','Home','A breathable printed cotton bedsheet set that brings a calm, considered feel to the bedroom.',2899,30,5,'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=800&q=85'),
('Sculptural ceramic vase','Ellementry','Home','Hand-finished ceramic decor with a sculptural profile, designed for shelves and side tables.',1599,15,16,'https://images.unsplash.com/photo-1578500494198-246f612d3b3d?auto=format&fit=crop&w=800&q=85'),
('Woven cushion cover set','Home Centre','Home','A set of two tactile woven cushion covers in versatile neutral tones.',1299,20,21,'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&w=800&q=85'),
('Hydrating face moisturiser','The Face Shop','Beauty','A daily lightweight moisturiser that leaves skin feeling soft and refreshed.',1199,10,19,'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?auto=format&fit=crop&w=800&q=85'),
('Everyday tinted lip balm','Lakme','Beauty','A sheer, comfortable tint with a nourishing balm feel for an easy everyday finish.',499,12,30,'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=800&q=85'),
('Gentle cleansing face wash','Cetaphil','Beauty','A mild daily cleanser designed to leave skin feeling fresh without a tight finish.',799,8,4,'https://images.unsplash.com/photo-1556229010-6c3f2c9ca5f8?auto=format&fit=crop&w=800&q=85'),
('Classic analog wristwatch','Fossil','Men','A refined analog watch with a minimalist dial and a versatile stainless-steel bracelet.',8995,35,3,'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=85'),
('Soft drape wide-leg trousers','AND','Women','Tailored wide-leg trousers with an elegant drape and a comfortable mid-rise waist.',2999,30,12,'https://images.unsplash.com/photo-1506629905607-d9f2d89e110a?auto=format&fit=crop&w=800&q=85'),
('Everyday crossbody bag','Baggit','Women','A compact crossbody bag with an adjustable strap and smart organised storage.',1899,22,17,'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=800&q=85');
