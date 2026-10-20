# DevTech Ventures website

Multi-page PHP + MySQL site. Customers enquire by **WhatsApp** or **phone call** only (no online payments).

## Run it locally (XAMPP)
1. Start **Apache** and **MySQL** in the XAMPP control panel.
2. Copy this `devtech-ventures` folder into `C:\xampp\htdocs\`.
3. Open http://localhost/phpmyadmin → **Import** → choose `database/schema.sql` → Go.
4. Visit http://localhost/devtech-ventures/

(Quick test without Apache: run `php -S 127.0.0.1:8765` inside this folder, then open http://127.0.0.1:8765)

## Admin
- URL: `/admin/`
- Default login: `admin` / `ChangeMe@2026` — **change it straight away** (Dashboard → Change password).
- Add/edit/delete products and categories, upload photos, set prices (leave a price empty to show "Call or WhatsApp for price"), tick **Featured** to show a product on the homepage.

## Settings
Phone, WhatsApp number, site name and database login are in `includes/config.php`.
On a live server, put your own values in `includes/config.local.php` (e.g. `define('DB_USER','...'); define('DB_PASS','...');`).

## Going live checklist
- Create a dedicated MySQL user (not `root`) and set it in `config.local.php`.
- Change the admin password; serve the site over HTTPS.
- Replace the sample products/photos with your real stock.
