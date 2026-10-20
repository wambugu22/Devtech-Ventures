<?php
/** Admin layout header. Expects $adminTitle and optional $adminActive. */
$adminActive = $adminActive ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($adminTitle ?? 'Admin') ?> | <?= e(SITE_NAME) ?> Admin</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php if (admin_logged_in()): ?>
<header class="admin-bar">
    <div class="container">
        <a class="brand" href="index.php"><img class="brand-logo" src="../assets/images/logo-emblem.png" width="40" height="40" alt=""><span class="brand-name">Admin</span></a>
        <nav class="admin-nav" aria-label="Admin">
            <a href="index.php"<?= $adminActive === 'dashboard' ? ' class="is-active"' : '' ?>>Dashboard</a>
            <a href="departments.php"<?= $adminActive === 'departments' ? ' class="is-active"' : '' ?>>Departments</a>
            <a href="categories.php"<?= $adminActive === 'categories' ? ' class="is-active"' : '' ?>>Categories</a>
            <a href="products.php"<?= $adminActive === 'products' ? ' class="is-active"' : '' ?>>Products</a>
            <a href="../index.php" target="_blank" rel="noopener">View site</a>
            <a href="logout.php">Log out</a>
        </nav>
    </div>
</header>
<?php endif; ?>
<main class="admin-wrap">
    <div class="container">
        <?= flash_render() ?>
