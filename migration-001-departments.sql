-- Migration 001: departments become editable from the admin area.
-- Run ONCE on a database created from the OLD schema (where categories.department was an ENUM).
-- Fresh installs using the current schema.sql do NOT need this.

CREATE TABLE IF NOT EXISTS departments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    image       VARCHAR(255) NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO departments (name, slug, description, image, sort_order) VALUES
('Home & Kitchen',     'home-kitchen',     'Fridges, microwaves, non-stick sufurias, cookware and household essentials.', 'dept-home-kitchen.jpg',     1),
('Bags & Accessories', 'bags-accessories', 'Caps, tote bags, handbags, backpacks and travel bags.',                       'dept-bags-accessories.jpg', 2)
ON DUPLICATE KEY UPDATE name = VALUES(name);

ALTER TABLE categories ADD COLUMN department_id INT UNSIGNED NULL AFTER id;
UPDATE categories c JOIN departments d ON d.slug = c.department SET c.department_id = d.id;
ALTER TABLE categories
    MODIFY department_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_categories_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    DROP COLUMN department;
