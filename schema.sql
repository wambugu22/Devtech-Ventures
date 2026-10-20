-- DevTech Ventures database schema + starter data
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < database/schema.sql

CREATE DATABASE IF NOT EXISTS devtech_ventures CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE devtech_ventures;

CREATE TABLE IF NOT EXISTS admins (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS departments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    image       VARCHAR(255) NULL,           -- tile photo file name in assets/images/products/
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    name          VARCHAR(100) NOT NULL,
    slug          VARCHAR(120) NOT NULL UNIQUE,
    description   VARCHAR(255) NULL,
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id       INT UNSIGNED NOT NULL,
    name              VARCHAR(150) NOT NULL,
    slug              VARCHAR(170) NOT NULL UNIQUE,
    short_description VARCHAR(255) NULL,
    description       TEXT NULL,
    price             DECIMAL(10,2) NULL,          -- NULL = "call or WhatsApp for price"
    image             VARCHAR(255) NULL,           -- main image file name in assets/images/products/
    featured          TINYINT(1) NOT NULL DEFAULT 0,
    is_active         TINYINT(1) NOT NULL DEFAULT 1,
    sort_order        INT NOT NULL DEFAULT 0,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_products_listing (is_active, featured, sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_images (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image      VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_gallery_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Starter data
-- ---------------------------------------------------------------------------

-- Default admin login:  admin / ChangeMe@2026   (CHANGE THIS after first login)
INSERT INTO admins (username, password_hash) VALUES
('admin', '$2y$10$2eRyOZjgjWhJAqBJdEMt7Ouglhe0E.N.pfdJlaGi1.MfTiEOJrNJi')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO departments (name, slug, description, image, sort_order) VALUES
('Home & Kitchen',     'home-kitchen',     'Fridges, microwaves, non-stick sufurias, cookware and household essentials.', 'dept-home-kitchen.jpg',     1),
('Bags & Accessories', 'bags-accessories', 'Caps, tote bags, handbags, backpacks and travel bags.',                       'dept-bags-accessories.jpg', 2)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO categories (department_id, name, slug, description, sort_order)
SELECT d.id, v.name, v.slug, v.description, v.sort_order
FROM (
    SELECT 'home-kitchen' AS dept, 'Fridges & Freezers' AS name, 'fridges-freezers' AS slug, 'Keep food fresh and drinks cold.' AS description, 1 AS sort_order
    UNION ALL SELECT 'home-kitchen',     'Cookers & Ovens',      'cookers-ovens',       'Gas cookers and ovens for every kitchen.', 2
    UNION ALL SELECT 'home-kitchen',     'Microwaves',           'microwaves',          'Quick, convenient heating and cooking.',   3
    UNION ALL SELECT 'home-kitchen',     'Cookware & Sufurias',  'cookware-sufurias',   'Non-stick sufurias, pots and pans.',       4
    UNION ALL SELECT 'home-kitchen',     'Household Essentials', 'household-essentials','Everyday items for a well-run home.',      5
    UNION ALL SELECT 'bags-accessories', 'Caps',                 'caps',                'Everyday and sporty caps.',                1
    UNION ALL SELECT 'bags-accessories', 'Tote Bags',            'tote-bags',           'Roomy totes for shopping and daily use.',  2
    UNION ALL SELECT 'bags-accessories', 'Handbags',             'handbags',            'Stylish handbags for every occasion.',     3
    UNION ALL SELECT 'bags-accessories', 'Backpacks',            'backpacks',           'School, work and weekend backpacks.',      4
    UNION ALL SELECT 'bags-accessories', 'Travel Bags',          'travel-bags',         'Suitcases, duffels and travel bags.',      5
) AS v
JOIN departments d ON d.slug = v.dept
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Sample products. Prices are left empty (customers are invited to call / WhatsApp for a price).
-- Edit these, add real prices and upload real photos from /admin/products.php.
INSERT INTO products (category_id, name, slug, short_description, description, price, image, featured, sort_order)
SELECT c.id, v.name, v.slug, v.short_description, v.description, NULL, v.image, v.featured, v.sort_order
FROM (
    SELECT 'fridges-freezers' AS cat, 'Miray RM-203HI Refrigerator' AS name, 'miray-rm-203hi-refrigerator' AS slug,
           'Reliable Miray refrigerator for fresh food and cold drinks.' AS short_description,
           'The Miray RM-203HI refrigerator keeps your food fresh and your drinks cold every day.\n\nContact us on WhatsApp or by phone to confirm availability, full specifications, price and delivery.' AS description,
           NULL AS image, 1 AS featured, 1 AS sort_order
    UNION ALL
    SELECT 'cookers-ovens', 'Miray Rubi Plus GN Gas Cooker', 'miray-rubi-plus-gn-gas-cooker',
           'Miray gas cooker for everyday home cooking.',
           'The Miray Rubi Plus GN is a gas cooker designed for everyday family cooking.\n\nContact us on WhatsApp or by phone to confirm availability, full specifications, price and delivery.',
           NULL, 1, 2
    UNION ALL
    SELECT 'microwaves', 'Miray HMM-20N Microwave Oven', 'miray-hmm-20n-microwave-oven',
           'Miray microwave oven for quick heating and cooking.',
           'The Miray HMM-20N microwave oven makes reheating and cooking fast and convenient.\n\nContact us on WhatsApp or by phone to confirm availability, full specifications, price and delivery.',
           NULL, 1, 3
    UNION ALL
    SELECT 'cookware-sufurias', 'Miray JOM-1304 Cookware Set', 'miray-jom-1304-cookware-set',
           'Miray set of pots (jgo. ollas) for the whole kitchen.',
           'The Miray JOM-1304 is a complete set of cooking pots for everyday use.\n\nContact us on WhatsApp or by phone to confirm what is included in the set, availability, price and delivery.',
           'miray-jom-1304.jpg', 1, 4
    UNION ALL
    SELECT 'cookware-sufurias', 'Non-Stick Sufuria Set', 'non-stick-sufuria-set',
           'Non-stick sufurias that make cooking and cleaning easier.',
           'A practical set of non-stick sufurias for everyday cooking.\n\nContact us for sizes, availability and price.',
           NULL, 0, 5
    UNION ALL
    SELECT 'backpacks', 'Everyday Backpack', 'everyday-backpack',
           'Durable backpack with plenty of pockets for work, school and travel.',
           'A durable everyday backpack with multiple zip pockets, padded straps and room for your daily essentials.\n\nContact us for colours, availability and price.',
           'everyday-backpack.jpg', 1, 1
    UNION ALL
    SELECT 'tote-bags', 'Canvas Tote Bag', 'canvas-tote-bag',
           'Roomy canvas tote for shopping and daily errands.',
           'A roomy, durable tote bag for shopping, errands and everyday carrying.\n\nContact us for colours, availability and price.',
           NULL, 1, 2
    UNION ALL
    SELECT 'handbags', 'Classic Handbag', 'classic-handbag',
           'Stylish handbag for work, outings and special occasions.',
           'A stylish handbag with comfortable handles and room for your essentials.\n\nContact us for colours, availability and price.',
           NULL, 0, 3
    UNION ALL
    SELECT 'travel-bags', 'Hard-Shell Travel Suitcase', 'hard-shell-travel-suitcase',
           'Sturdy rolling suitcase for trips near and far.',
           'A sturdy travel suitcase on wheels with a telescopic handle.\n\nContact us for sizes, colours, availability and price.',
           NULL, 1, 4
    UNION ALL
    SELECT 'caps', 'Plain Baseball Cap', 'plain-baseball-cap',
           'Comfortable everyday cap in a choice of colours.',
           'A comfortable, adjustable baseball cap for everyday wear.\n\nContact us for colours, availability and price.',
           NULL, 0, 5
) AS v
JOIN categories c ON c.slug = v.cat
ON DUPLICATE KEY UPDATE name = VALUES(name);
