<?php
require_once __DIR__ . '/includes/functions.php';

$active    = 'about';
$pageTitle = 'About Us';
$pageDesc  = 'Learn about ' . SITE_NAME . ' - what we sell and our commitment to serving customers.';
$mobileWhatsapp = whatsapp_link('Hello ' . SITE_NAME . ', I would like to know more about your products.');
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="index.php">Home</a><span>/</span>About Us</nav>
        <h1>About <?= e(SITE_NAME) ?></h1>
        <p>Practical, quality products for your home and your everyday life &mdash; with friendly, personal service.</p>
    </div>
</section>

<section class="section">
    <div class="container two-col">
        <div class="prose">
            <h2>Our story</h2>
            <p><?= e(SITE_NAME) ?> was started with a simple idea: people should be able to find the everyday things they need &mdash; for the kitchen, the home and on the move &mdash; in one trusted place, from someone they can actually talk to.</p>
            <p>Instead of a faceless online checkout, we keep it personal. You browse our products, message or call us, and we help you directly &mdash; confirming availability, price and delivery so you buy with confidence.</p>
            <h2 style="margin-top:1.6rem">What we sell</h2>
            <ul class="checklist">
                <li><?= icon('check', 20) ?> <span><strong>Home &amp; Kitchen</strong> &mdash; fridges, cookers, microwaves, non-stick sufurias, cookware and household essentials.</span></li>
                <li><?= icon('check', 20) ?> <span><strong>Bags &amp; Accessories</strong> &mdash; caps, tote bags, handbags, backpacks and travel bags.</span></li>
            </ul>
        </div>
        <img src="assets/images/hero.jpg" alt="Household appliances, cookware, bags and travel accessories" loading="lazy">
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head center"><h2>Our commitment to you</h2></div>
        <div class="features">
            <div class="feature"><div class="feature-num">&#10003;</div><h3>Quality you can trust</h3><p>We choose products that are practical, durable and good value for everyday use.</p></div>
            <div class="feature"><div class="feature-num">&#10003;</div><h3>Honest, personal service</h3><p>Speak to a real person on WhatsApp or by phone and get clear answers about every product.</p></div>
            <div class="feature"><div class="feature-num">&#10003;</div><h3>Simple buying</h3><p>No complicated checkout &mdash; we confirm availability, price and delivery directly with you.</p></div>
        </div>
    </div>
</section>

<section class="cta-band">
    <div class="container">
        <h2>Let&rsquo;s help you find what you need</h2>
        <p>Chat to us on WhatsApp or give us a call &mdash; we&rsquo;d love to hear from you.</p>
        <?= contact_buttons(null) ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
