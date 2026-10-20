<?php
/**
 * Shared template for department pages.
 * Accepts $dept (array from DB) or $department (slug), $active, and optional $heroText.
 */
require_once __DIR__ . '/functions.php';

if (!isset($dept) || !is_array($dept)) {
    $deptSlug = $department ?? ($_GET['dept'] ?? '');
    $dept = get_department_by_slug((string) $deptSlug);
}

if (!$dept) {
    http_response_code(404);
    $active = '';
    $pageTitle = 'Department not found';
    require __DIR__ . '/header.php';
    echo '<section class="section"><div class="container empty"><h1>Department not found</h1><p>The department you are looking for does not exist.</p><p><a class="btn btn-primary" href="index.php">Go to Homepage</a></p></div></section>';
    require __DIR__ . '/footer.php';
    exit;
}

$deptPage   = department_url($dept);
$deptLabel  = $dept['name'];
$heroText   = $heroText ?? ($dept['description'] ?: 'Browse quality products in ' . $deptLabel . '.');
$categories = get_categories((int) $dept['id']);
$catSlug    = isset($_GET['cat']) ? trim((string) $_GET['cat']) : '';
$search     = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

$validCats = array_column($categories, 'slug');
if ($catSlug !== '' && !in_array($catSlug, $validCats, true)) {
    $catSlug = '';
}
$products = get_department_products($dept['slug'], $catSlug ?: null, $search);

$currentCat = null;
foreach ($categories as $c) {
    if ($c['slug'] === $catSlug) { $currentCat = $c; }
}

$pageTitle = $currentCat ? $currentCat['name'] . ' - ' . $deptLabel : $deptLabel;
$pageDesc  = $heroText . ' Enquire on WhatsApp or call ' . PHONE_DISPLAY . '.';
$mobileWhatsapp = whatsapp_link('Hello ' . SITE_NAME . ', I am looking at your ' . $deptLabel . ' products and would like to enquire.');
require __DIR__ . '/header.php';

function dept_url(string $page, string $cat = '', string $q = ''): string
{
    $parts = parse_url($page);
    $path = $parts['path'] ?? $page;
    $existingQuery = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $existingQuery);
    }
    if ($cat !== '') {
        $existingQuery['cat'] = $cat;
    } else {
        unset($existingQuery['cat']);
    }
    if ($q !== '') {
        $existingQuery['q'] = $q;
    } else {
        unset($existingQuery['q']);
    }
    return $path . ($existingQuery ? '?' . http_build_query($existingQuery) : '');
}
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="index.php">Home</a><span>/</span><?= e($deptLabel) ?></nav>
        <h1><?= e($currentCat ? $currentCat['name'] : $deptLabel) ?></h1>
        <p><?= e($currentCat && $currentCat['description'] ? $currentCat['description'] : $heroText) ?></p>
    </div>
</section>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <div class="filters">
            <div class="chips">
                <a class="chip<?= $catSlug === '' ? ' is-active' : '' ?>" href="<?= e(dept_url($deptPage, '', $search)) ?>">All</a>
                <?php foreach ($categories as $c): ?>
                    <a class="chip<?= $catSlug === $c['slug'] ? ' is-active' : '' ?>" href="<?= e(dept_url($deptPage, $c['slug'], $search)) ?>"><?= e($c['name']) ?></a>
                <?php endforeach; ?>
            </div>
            <form class="search-form" method="get" action="<?= e($deptPage) ?>" role="search">
                <?php if ($catSlug): ?><input type="hidden" name="cat" value="<?= e($catSlug) ?>"><?php endif; ?>
                <?php 
                // Preserve dept parameter if using generic department.php
                if (isset($_GET['dept'])): ?><input type="hidden" name="dept" value="<?= e($_GET['dept']) ?>"><?php endif; ?>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search <?= e(strtolower($deptLabel)) ?>" aria-label="Search products">
                <button class="btn btn-primary btn-sm" type="submit">Search</button>
            </form>
        </div>

        <?php if ($products): ?>
            <div class="grid">
                <?php foreach ($products as $p) { echo render_product_card($p); } ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <p><strong>No products found here yet.</strong></p>
                <p>New stock arrives often &mdash; ask us if we have what you need.</p>
                <?= contact_buttons(null) ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="cta-band">
    <div class="container">
        <h2>Can&rsquo;t find what you&rsquo;re looking for?</h2>
        <p>Message or call us &mdash; we can often source it for you.</p>
        <?= contact_buttons(null) ?>
    </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
