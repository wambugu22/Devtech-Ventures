<?php
require_once __DIR__ . '/includes/functions.php';

$active    = 'contact';
$pageTitle = 'Contact Us';
$pageDesc  = 'Call or WhatsApp ' . SITE_NAME . ' on ' . PHONE_DISPLAY . ' to enquire about our products.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="index.php">Home</a><span>/</span>Contact Us</nav>
        <h1>Contact Us</h1>
        <p>Have a question or want to buy? Enquire about any product &mdash; we&rsquo;re just a message or a call away.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="contact-cards">
            <div class="contact-card">
                <div class="ico wa"><?= icon('whatsapp', 30) ?></div>
                <h2>WhatsApp</h2>
                <span class="num"><?= e(PHONE_DISPLAY) ?></span>
                <p>Send us a message with the product you are interested in, and we will reply with availability, price and delivery details.</p>
                <a class="btn btn-whatsapp btn-lg" href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener"><?= icon('whatsapp', 22) ?><span>Enquire on WhatsApp</span></a>
            </div>
            <div class="contact-card">
                <div class="ico ph"><?= icon('phone', 28) ?></div>
                <h2>Call Us</h2>
                <span class="num"><?= e(PHONE_DISPLAY) ?></span>
                <p>Prefer to talk? Tap the button on your phone to call us directly and speak to our team.</p>
                <a class="btn btn-call btn-lg" href="<?= e(tel_link()) ?>"><?= icon('phone', 22) ?><span>Call Us</span></a>
            </div>
        </div>

        <div class="enquire-box" style="margin-top:2rem;max-width:760px;margin-inline:auto">
            <h2>We&rsquo;d love to hear what you&rsquo;re looking for</h2>
            <p>Whether it is a fridge, a cooker, a set of sufurias, a backpack or a travel bag &mdash; contact us to enquire about any product. All orders and payments are arranged directly with us by WhatsApp or phone; there is no online payment on this website.</p>
            <div class="btn-group">
                <?php foreach (get_departments(true) as $cDept): ?>
                    <a class="btn btn-primary" href="<?= e(department_url($cDept)) ?>">Browse <?= e($cDept['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
