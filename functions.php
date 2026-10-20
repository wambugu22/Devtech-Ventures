<?php
require_once __DIR__ . '/database.php';

/* ------------------------------------------------------------------ basics */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('~[^a-z0-9]+~', '-', $text);
    $text = trim((string) $text, '-');
    return $text !== '' ? $text : 'item';
}

function format_price($price): ?string
{
    if ($price === null || $price === '' || (float) $price <= 0) {
        return null;
    }
    return CURRENCY . ' ' . number_format((float) $price, 0);
}

/* ---------------------------------------------------------- contact links */

function tel_link(): string
{
    return 'tel:' . PHONE_INTL;
}

function whatsapp_link(?string $message = null): string
{
    $message = $message ?: 'Hello ' . SITE_NAME . ', I would like to make an enquiry about your products.';
    return 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . rawurlencode($message);
}

/** Absolute URL of the folder the public pages live in (no trailing slash). */
function site_base_url(): string
{
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $dir    = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if (substr($dir, -6) === '/admin') {
        $dir = substr($dir, 0, -6);
    }
    return ($https ? 'https://' : 'http://') . $host . $dir;
}

function product_url(array $p, string $base = ''): string
{
    return $base . 'product.php?slug=' . rawurlencode($p['slug']);
}

/** Pre-filled WhatsApp enquiry for a specific product. */
function product_whatsapp_link(array $p): string
{
    $msg = 'Hello ' . SITE_NAME . ', I am interested in the "' . $p['name'] . '". '
         . 'Is it available, and what is the price? '
         . site_base_url() . '/product.php?slug=' . $p['slug'];
    return whatsapp_link($msg);
}

/* ---------------------------------------------------------------- departments & products */

function get_departments(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM departments' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order, name';
    return db()->query($sql)->fetchAll();
}

function get_department_by_slug(string $slug, bool $activeOnly = true): ?array
{
    $sql = 'SELECT * FROM departments WHERE slug = ?' . ($activeOnly ? ' AND is_active = 1' : '') . ' LIMIT 1';
    $st = db()->prepare($sql);
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}

function department_url(array $d, string $base = ''): string
{
    // If specific dedicated pages exist for legacy compatibility, use them; otherwise use department.php?slug=...
    if ($d['slug'] === 'home-kitchen' && is_file(dirname(__DIR__) . '/home-kitchen.php')) {
        return $base . 'home-kitchen.php';
    }
    if ($d['slug'] === 'bags-accessories' && is_file(dirname(__DIR__) . '/bags-accessories.php')) {
        return $base . 'bags-accessories.php';
    }
    return $base . 'department.php?dept=' . rawurlencode($d['slug']);
}

function department_image_url(array $d, string $base = ''): string
{
    if (!empty($d['image']) && is_file(UPLOAD_DIR . $d['image'])) {
        return $base . UPLOAD_URL . rawurlencode($d['image']);
    }
    return $base . 'assets/images/hero.jpg';
}

function product_image_url(array $p, string $base = ''): string
{
    if (!empty($p['image']) && is_file(UPLOAD_DIR . $p['image'])) {
        return $base . UPLOAD_URL . rawurlencode($p['image']);
    }
    $dept = $p['department_slug'] ?? ($p['department'] ?? '');
    if ($dept === 'bags-accessories') {
        return $base . 'assets/images/placeholder-bag.svg';
    }
    if ($dept === 'home-kitchen') {
        return $base . 'assets/images/placeholder-home.svg';
    }
    return $base . 'assets/images/placeholder-generic.svg';
}

function gallery_image_url(string $file, string $base = ''): string
{
    return $base . UPLOAD_URL . rawurlencode($file);
}

const PRODUCT_SELECT = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug, 
                               d.id AS department_id, d.name AS department_name, d.slug AS department_slug
                        FROM products p 
                        JOIN categories c ON c.id = p.category_id
                        JOIN departments d ON d.id = c.department_id';

function get_featured_products(int $limit = 8): array
{
    $st = db()->prepare(PRODUCT_SELECT . ' WHERE p.is_active = 1 AND d.is_active = 1 AND p.featured = 1
                         ORDER BY p.sort_order, p.created_at DESC LIMIT ' . (int) $limit);
    $st->execute();
    return $st->fetchAll();
}

function get_categories(?int $departmentId = null, ?string $departmentSlug = null): array
{
    if ($departmentId !== null) {
        $st = db()->prepare('SELECT * FROM categories WHERE department_id = ? ORDER BY sort_order, name');
        $st->execute([$departmentId]);
        return $st->fetchAll();
    }
    if ($departmentSlug !== null) {
        $st = db()->prepare('SELECT c.* FROM categories c JOIN departments d ON d.id = c.department_id WHERE d.slug = ? ORDER BY c.sort_order, c.name');
        $st->execute([$departmentSlug]);
        return $st->fetchAll();
    }
    return db()->query('SELECT c.*, d.name AS department_name FROM categories c JOIN departments d ON d.id = c.department_id ORDER BY d.sort_order, c.sort_order, c.name')->fetchAll();
}

function get_department_products(string $departmentSlug, ?string $categorySlug = null, string $search = ''): array
{
    $sql    = PRODUCT_SELECT . ' WHERE p.is_active = 1 AND d.is_active = 1 AND d.slug = ?';
    $params = [$departmentSlug];
    if ($categorySlug) {
        $sql .= ' AND c.slug = ?';
        $params[] = $categorySlug;
    }
    if ($search !== '') {
        $sql .= ' AND (p.name LIKE ? OR p.short_description LIKE ? OR p.description LIKE ?)';
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
        array_push($params, $like, $like, $like);
    }
    $sql .= ' ORDER BY p.featured DESC, p.sort_order, p.created_at DESC';
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function search_products(string $query, ?int $departmentId = null): array
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }
    $sql = PRODUCT_SELECT . ' WHERE p.is_active = 1 AND d.is_active = 1';
    $params = [];
    if ($departmentId !== null) {
        $sql .= ' AND d.id = ?';
        $params[] = $departmentId;
    }
    $sql .= ' AND (p.name LIKE ? OR p.short_description LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR d.name LIKE ?)';
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
    array_push($params, $like, $like, $like, $like, $like);
    $sql .= ' ORDER BY p.featured DESC, p.sort_order, p.created_at DESC';
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function get_product_by_slug(string $slug): ?array
{
    $st = db()->prepare(PRODUCT_SELECT . ' WHERE p.slug = ? AND p.is_active = 1 AND d.is_active = 1 LIMIT 1');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}

function get_product_gallery(int $productId): array
{
    $st = db()->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
    $st->execute([$productId]);
    return $st->fetchAll();
}

function get_related_products(array $p, int $limit = 4): array
{
    $st = db()->prepare(PRODUCT_SELECT . ' WHERE p.is_active = 1 AND d.is_active = 1 AND p.category_id = ? AND p.id <> ?
                         ORDER BY p.featured DESC, p.sort_order LIMIT ' . (int) $limit);
    $st->execute([$p['category_id'], $p['id']]);
    return $st->fetchAll();
}

/* ------------------------------------------------------------------- icons */

function icon(string $name, int $size = 20): string
{
    $s = (int) $size;
    switch ($name) {
        case 'whatsapp':
            return '<svg class="icon" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
        case 'phone':
            return '<svg class="icon" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
        case 'arrow':
            return '<svg class="icon" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
        case 'check':
            return '<svg class="icon" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
        case 'search':
            return '<svg class="icon" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>';
        case 'chevron-down':
            return '<svg class="icon" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    }
    return '';
}

/* ----------------------------------------------------------- contact buttons */

/** Renders the WhatsApp + Call button pair. $product enables the pre-filled message. */
function contact_buttons(?array $product = null, string $class = ''): string
{
    $wa = $product ? product_whatsapp_link($product) : whatsapp_link();
    return '<div class="btn-group ' . e($class) . '">'
         . '<a class="btn btn-whatsapp" href="' . e($wa) . '" target="_blank" rel="noopener">' . icon('whatsapp') . '<span>Enquire on WhatsApp</span></a>'
         . '<a class="btn btn-call" href="' . e(tel_link()) . '">' . icon('phone') . '<span>Call Us</span></a>'
         . '</div>';
}

function calculate_discount_percent($price, $originalPrice): ?int
{
    if ($price && $originalPrice && (float)$originalPrice > (float)$price) {
        $p = (float)$price;
        $orig = (float)$originalPrice;
        return (int) round((($orig - $p) / $orig) * 100);
    }
    return null;
}

function render_price_html($price, $originalPrice = null): string
{
    $formattedPrice = format_price($price);
    if (!$formattedPrice) {
        return '<p class="card-price is-ask">Call or WhatsApp for price</p>';
    }
    $origFormatted = format_price($originalPrice);
    $discount = calculate_discount_percent($price, $originalPrice);

    if ($origFormatted && $discount && $discount > 0) {
        return '<div class="price-wrap">'
             . '<span class="price-current">' . e($formattedPrice) . '</span> '
             . '<span class="price-original">' . e($origFormatted) . '</span>'
             . '</div>';
    }
    return '<p class="card-price"><span class="price-current">' . e($formattedPrice) . '</span></p>';
}

/* -------------------------------------------------------------- product card */

function render_product_card(array $p, string $base = ''): string
{
    $url      = e(product_url($p, $base));
    $discount = calculate_discount_percent($p['price'] ?? null, $p['original_price'] ?? null);
    $out      = '<article class="card">';
    $out     .= '<a class="card-media" href="' . $url . '"><img src="' . e(product_image_url($p, $base)) . '" alt="' . e($p['name']) . '" loading="lazy">';
    
    $badges = '';
    if (!empty($p['featured'])) {
        $badges .= '<span class="badge">Featured</span>';
    }
    if ($discount && $discount > 0) {
        $badges .= '<span class="badge badge-discount">-' . $discount . '% OFF</span>';
    }
    if ($badges !== '') {
        $out .= '<div class="badge-cluster">' . $badges . '</div>';
    }

    $out  .= '</a><div class="card-body">';
    $out  .= '<p class="card-cat">' . e($p['category_name']) . '</p>';
    $out  .= '<h3 class="card-title"><a href="' . $url . '">' . e($p['name']) . '</a></h3>';
    if (!empty($p['short_description'])) {
        $out .= '<p class="card-text">' . e($p['short_description']) . '</p>';
    }
    $out  .= render_price_html($p['price'] ?? null, $p['original_price'] ?? null);
    $out  .= '<div class="card-actions">'
           . '<a class="btn btn-whatsapp btn-sm" href="' . e(product_whatsapp_link($p)) . '" target="_blank" rel="noopener">' . icon('whatsapp', 18) . '<span>WhatsApp</span></a>'
           . '<a class="btn btn-outline btn-sm" href="' . $url . '"><span>Details</span></a>'
           . '</div></div></article>';
    return $out;
}
