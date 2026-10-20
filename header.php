<?php
require_once __DIR__ . '/functions.php';

$base      = $base ?? '';
$active    = $active ?? '';
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME . ' | ' . SITE_TAGLINE;
$pageDesc  = $pageDesc ?? 'Quality household essentials, kitchenware, bags and everyday accessories. Enquire on WhatsApp or call ' . PHONE_DISPLAY . '.';
$pageImage = $pageImage ?? ($base . 'assets/images/hero.jpg');

$departmentsNav = get_departments(true);

$isDeptActive = (strpos((string)$active, 'dept-') === 0 || $active === 'products-hub');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDesc) ?>">
    <meta property="og:image" content="<?= e($pageImage) ?>">
    <meta property="og:type" content="website">
    <meta name="theme-color" content="#020f1f">
    <link rel="icon" type="image/png" href="<?= e($base) ?>assets/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e($base) ?>assets/css/style.css">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e($base) ?>index.php" aria-label="<?= e(SITE_NAME) ?> home">
            <img class="brand-logo" src="<?= e($base) ?>assets/images/logo-emblem.png" width="48" height="48" alt="">
            <span class="brand-name">DevTech <strong>Ventures</strong></span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false" aria-controls="site-nav">
            <span></span><span></span><span></span>
        </button>

        <nav id="site-nav" class="site-nav" aria-label="Main">
            <ul>
                <li>
                    <a href="<?= e($base) ?>index.php"<?= $active === 'home' ? ' class="is-active" aria-current="page"' : '' ?>>Home</a>
                </li>
                
                <li>
                    <a href="<?= e($base) ?>products.php"<?= $isDeptActive ? ' class="is-active" aria-current="page"' : '' ?>>Products</a>
                </li>

                <li>
                    <a href="<?= e($base) ?>about.php"<?= $active === 'about' ? ' class="is-active" aria-current="page"' : '' ?>>About Us</a>
                </li>
                <li>
                    <a href="<?= e($base) ?>contact.php"<?= $active === 'contact' ? ' class="is-active" aria-current="page"' : '' ?>>Contact Us</a>
                </li>
            </ul>
            <a class="nav-call" href="<?= e(tel_link()) ?>"><?= icon('phone', 18) ?><span><?= e(PHONE_DISPLAY) ?></span></a>
        </nav>
    </div>
</header>

<main id="main">
