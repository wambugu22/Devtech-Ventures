<?php
require_once __DIR__ . '/includes/functions.php';

$slug    = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$product = $slug !== '' ? get_product_by_slug($slug) : null;

if (!$product) {
    http_response_code(404);
    $active    = '';
    $pageTitle = 'Product not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="section">
        <div class="container empty">
            <h1>Product not found</h1>
            <p>That product may have been removed or is no longer available. Browse our departments or ask us directly.</p>
            <div class="btn-group" style="justify-content:center;margin-bottom:1.2rem">
                <?php foreach (get_departments(true) as $errDept): ?>
                    <a class="btn btn-primary" href="<?= e(department_url($errDept)) ?>"><?= e($errDept['name']) ?></a>
                <?php endforeach; ?>
            </div>
            <?= contact_buttons(null, '') ?>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$dept = [
    'name' => $product['department_name'],
    'slug' => $product['department_slug'],
];
$deptUrl = department_url($dept);
$gallery = get_product_gallery((int) $product['id']);
$related = get_related_products($product, 4);
$price   = format_price($product['price']);

// Build the list of images: main image first, then any extras
$images = [['url' => product_image_url($product), 'alt' => $product['name']]];
foreach ($gallery as $g) {
    if (is_file(UPLOAD_DIR . $g['image'])) {
        $images[] = ['url' => gallery_image_url($g['image']), 'alt' => $product['name']];
    }
}

$active         = 'dept-' . $product['department_slug'];
$pageTitle      = $product['name'];
$pageDesc       = $product['short_description'] ?: ('Enquire about the ' . $product['name'] . ' at ' . SITE_NAME . '.');
$pageImage      = site_base_url() . '/' . ltrim($images[0]['url'], '/');
$mobileWhatsapp = product_whatsapp_link($product);
require __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top:1.4rem">
    <nav class="breadcrumbs on-light" aria-label="Breadcrumb">
        <a href="index.php">Home</a><span>/</span>
        <a href="<?= e($deptUrl) ?>"><?= e($dept['name']) ?></a><span>/</span>
        <a href="<?= e($deptUrl . (strpos($deptUrl, '?') !== false ? '&' : '?') . 'cat=' . rawurlencode($product['category_slug'])) ?>"><?= e($product['category_name']) ?></a>
    </nav>

    <article class="product">
        <div class="gallery">
            <div class="gallery-main"><img id="gallery-main-img" src="<?= e($images[0]['url']) ?>" alt="<?= e($product['name']) ?>"></div>
            <?php if (count($images) > 1): ?>
                <div class="thumbs">
                    <?php foreach ($images as $i => $img): ?>
                        <button type="button" data-full="<?= e($img['url']) ?>" class="<?= $i === 0 ? 'is-active' : '' ?>" aria-label="Show image <?= $i + 1 ?>"><img src="<?= e($img['url']) ?>" alt=""></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="product-info">
            <span class="product-cat"><?= e($product['category_name']) ?></span>
            <h1><?= e($product['name']) ?></h1>

            <?php 
            $discount = calculate_discount_percent($product['price'] ?? null, $product['original_price'] ?? null);
            $origPrice = format_price($product['original_price'] ?? null);
            ?>

            <?php if ($price): ?>
                <div class="product-price-block">
                    <span class="product-price"><?= e($price) ?></span>
                    <?php if ($origPrice && $discount && $discount > 0): ?>
                        <span class="product-price-original"><?= e($origPrice) ?></span>
                        <span class="badge badge-discount">-<?= $discount ?>% OFF</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="product-price is-ask">Call or WhatsApp us for the latest price</p>
            <?php endif; ?>

            <?php if ($product['short_description']): ?>
                <p><strong><?= e($product['short_description']) ?></strong></p>
            <?php endif; ?>
            <div class="product-desc"><?= nl2br(e($product['description'] ?? '')) ?></div>

            <div class="enquire-box">
                <h2>Interested in this product?</h2>
                <p>Message us on WhatsApp with this product already filled in, or call us to confirm availability, price and delivery.</p>
                <?= contact_buttons($product) ?>
            </div>

            <ul class="how-list">
                <li><?= icon('check', 18) ?> No online payment &mdash; we confirm everything with you directly.</li>
                <li><?= icon('check', 18) ?> Phone / WhatsApp: <a href="<?= e(tel_link()) ?>"><?= e(PHONE_DISPLAY) ?></a></li>
            </ul>
        </div>
    </article>
</div>

<?php if ($related): ?>
<section class="section section-alt">
    <div class="container">
        <div class="section-head-row"><h2>You may also like</h2></div>
        <div class="grid">
            <?php foreach ($related as $r) { echo render_product_card($r); } ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
