<?php
require_once __DIR__ . '/../includes/auth.php';

// ---- Log in -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['op'] ?? '') === 'login') {
    csrf_verify();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $st = db()->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $st->execute([$username]);
    $user = $st->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = (int) $user['id'];
        $_SESSION['admin_name'] = $username;
        redirect('index.php');
    }
    sleep(1); // slow down password guessing
    flash('err', 'Incorrect username or password.');
    redirect('index.php');
}

// ---- Change password --------------------------------------------------------
if (admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['op'] ?? '') === 'password') {
    csrf_verify();
    $current = (string) ($_POST['current'] ?? '');
    $new     = (string) ($_POST['new'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    $st = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $st->execute([$_SESSION['admin_id']]);
    $hash = $st->fetchColumn();

    if (!$hash || !password_verify($current, $hash)) {
        flash('err', 'Your current password is incorrect.');
    } elseif (strlen($new) < 10) {
        flash('err', 'The new password must be at least 10 characters long.');
    } elseif ($new !== $confirm) {
        flash('err', 'The new passwords do not match.');
    } else {
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
        flash('ok', 'Password updated.');
    }
    redirect('index.php');
}

// ---- Login screen -----------------------------------------------------------
if (!admin_logged_in()) {
    $adminTitle = 'Log in';
    require __DIR__ . '/../includes/admin-header.php';
    ?>
    <div class="panel login-box">
        <img class="logo" src="../assets/images/logo-emblem.png" alt="<?= e(SITE_NAME) ?>">
        <h1 style="text-align:center">Admin log in</h1>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="op" value="login">
            <p><label for="username">Username</label><input type="text" id="username" name="username" required autofocus></p>
            <p><label for="password">Password</label><input type="password" id="password" name="password" required></p>
            <button class="btn btn-primary" style="width:100%" type="submit">Log in</button>
        </form>
    </div>
    <?php
    require __DIR__ . '/../includes/admin-footer.php';
    exit;
}

// ---- Dashboard --------------------------------------------------------------
$db    = db();
$stats = [
    'Products'          => (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'Visible on site'   => (int) $db->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'Featured'          => (int) $db->query('SELECT COUNT(*) FROM products WHERE featured = 1 AND is_active = 1')->fetchColumn(),
    'Without a price'   => (int) $db->query('SELECT COUNT(*) FROM products WHERE price IS NULL')->fetchColumn(),
    'Departments'       => (int) $db->query('SELECT COUNT(*) FROM departments')->fetchColumn(),
    'Categories'        => (int) $db->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
];
$usingDefaultPassword = false;
$st = $db->prepare('SELECT password_hash FROM admins WHERE id = ?');
$st->execute([$_SESSION['admin_id']]);
if (password_verify('ChangeMe@2026', (string) $st->fetchColumn())) {
    $usingDefaultPassword = true;
}

$adminTitle  = 'Dashboard';
$adminActive = 'dashboard';
require __DIR__ . '/../includes/admin-header.php';
?>
<h1>Welcome, <?= e($_SESSION['admin_name']) ?></h1>

<?php if ($usingDefaultPassword): ?>
    <div class="alert warn"><strong>Security:</strong> you are still using the default password. Please change it below before putting the site online.</div>
<?php endif; ?>

<div class="stats">
    <?php foreach ($stats as $label => $n): ?>
        <div class="stat"><b><?= $n ?></b><span><?= e($label) ?></span></div>
    <?php endforeach; ?>
</div>

<div class="panel">
    <h2>Quick actions</h2>
    <div class="btn-group">
        <a class="btn btn-primary" href="products.php?action=new">+ Add product</a>
        <a class="btn btn-outline" href="departments.php">Manage departments</a>
        <a class="btn btn-outline" href="categories.php">Manage categories</a>
        <a class="btn btn-outline" href="products.php">Manage products</a>
    </div>
</div>

<div class="panel" style="max-width:520px">
    <h2>Change password</h2>
    <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="password">
        <p><label for="current">Current password</label><input type="password" id="current" name="current" required></p>
        <p><label for="new">New password <span class="hint">(at least 10 characters)</span></label><input type="password" id="new" name="new" minlength="10" required></p>
        <p><label for="confirm">Confirm new password</label><input type="password" id="confirm" name="confirm" minlength="10" required></p>
        <button class="btn btn-primary" type="submit">Update password</button>
    </form>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
