</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand brand-light" href="<?= e($base) ?>index.php">
                <img class="brand-logo" src="<?= e($base) ?>assets/images/logo-emblem.png" width="48" height="48" alt="">
                <span class="brand-name">DevTech <strong>Ventures</strong></span>
            </a>
            <p class="footer-blurb">Quality household essentials, kitchenware, bags and everyday accessories &mdash; all in one place.</p>
        </div>
        <div>
            <h4>Shop</h4>
            <ul>
                <?php foreach (get_departments(true) as $fDept): ?>
                    <li><a href="<?= e(department_url($fDept, $base)) ?>"><?= e($fDept['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4>Company</h4>
            <ul>
                <li><a href="<?= e($base) ?>about.php">About Us</a></li>
                <li><a href="<?= e($base) ?>contact.php">Contact Us</a></li>
            </ul>
        </div>
        <div>
            <h4>Get in touch</h4>
            <ul>
                <li><a href="<?= e(tel_link()) ?>">Call: <?= e(PHONE_DISPLAY) ?></a></li>
                <li><a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener">WhatsApp: <?= e(PHONE_DISPLAY) ?></a></li>
            </ul>
            <p class="footer-note">Orders and enquiries are handled directly by WhatsApp or phone. We do not take online payments.</p>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved. &middot; <a href="<?= e($base) ?>admin/">Staff login</a></div>
    </div>
</footer>

<!-- Mobile quick-contact bar -->
<div class="mobile-bar" role="complementary" aria-label="Contact us">
    <a class="btn btn-whatsapp" href="<?= e($mobileWhatsapp ?? whatsapp_link()) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?><span>WhatsApp</span></a>
    <a class="btn btn-call" href="<?= e(tel_link()) ?>"><?= icon('phone') ?><span>Call</span></a>
</div>

<!-- Desktop floating WhatsApp button -->
<a class="wa-float" href="<?= e($mobileWhatsapp ?? whatsapp_link()) ?>" target="_blank" rel="noopener" aria-label="Enquire on WhatsApp"><?= icon('whatsapp', 30) ?></a>

<script src="<?= e($base) ?>assets/js/script.js"></script>
</body>
</html>
