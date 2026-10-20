<?php
require_once __DIR__ . '/includes/functions.php';

$query = trim((string) ($_GET['q'] ?? ''));
$deptSlug = trim((string) ($_GET['dept'] ?? ''));

$selectedDept = null;
$departmentId = null;
if ($deptSlug !== '') {
    $selectedDept = get_department_by_slug($deptSlug);
    if ($selectedDept) {
        $departmentId = (int) $selectedDept['id'];
    }
}

$allDepartments = get_departments(true);
$results = $query !== '' ? search_products($query, $departmentId) : [];

$pageTitle = $query !== '' ? 'Search: "' . $query . '"' : 'Search Products';
$pageDesc  = 'Search for products, household appliances, kitchenware, and bags at ' . SITE_NAME . '.';
$active    = 'search';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Home</a><span>/</span>Search
        </nav>
        <h1>Search Products</h1>
        <p>Find household items, kitchenware, bags and accessories across our entire catalogue.</p>
    </div>
</section>

<section class="section" style="padding-top:2.5rem">
    <div class="container">
        <div class="search-page-box" style="margin-bottom:2.5rem;background:var(--card);padding:1.6rem;border-radius:var(--radius);border:1px solid var(--line);box-shadow:var(--shadow-sm)">
            <form method="get" action="search.php" role="search" style="display:flex;gap:.8rem;flex-wrap:wrap">
                <input type="search" name="q" value="<?= e($query) ?>" placeholder="Type a product name, brand, or feature..." aria-label="Search" style="flex:2;min-width:240px;padding:.75rem 1.1rem;border-radius:999px;border:1px solid var(--line);font:inherit" autofocus>
                
                <select name="dept" aria-label="Filter by department" style="flex:1;min-width:180px;padding:.75rem 1rem;border-radius:999px;border:1px solid var(--line);font:inherit;background:#fff">
                    <option value="">All Departments</option>
                    <?php foreach ($allDepartments as $d): ?>
                        <option value="<?= e($d['slug']) ?>"<?= $deptSlug === $d['slug'] ? ' selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <button class="btn btn-primary" type="submit" style="padding:.75rem 1.6rem">
                    <?= icon('search', 18) ?><span>Search</span>
                </button>
            </form>
        </div>

        <?php if ($query !== ''): ?>
            <div class="section-head-row" style="margin-bottom:1.4rem">
                <div>
                    <h2 style="font-size:1.4rem;margin:0">
                        Results for &ldquo;<?= e($query) ?>&rdquo; 
                        <?php if ($selectedDept): ?><span style="font-size:1rem;color:var(--muted);font-weight:400">in <?= e($selectedDept['name']) ?></span><?php endif; ?>
                    </h2>
                    <p style="color:var(--muted);margin:.3rem 0 0;font-size:.95rem">
                        Found <?= count($results) ?> product<?= count($results) === 1 ? '' : 's' ?>
                    </p>
                </div>
            </div>

            <?php if ($results): ?>
                <div class="grid">
                    <?php foreach ($results as $p) { echo render_product_card($p); } ?>
                </div>
            <?php else: ?>
                <div class="empty">
                    <p style="font-size:1.15rem;margin-bottom:.5rem"><strong>No products matched &ldquo;<?= e($query) ?>&rdquo;</strong></p>
                    <p style="max-width:540px;margin:0 auto 1.5rem">We might still have it in our warehouse or can source it directly for you. Send us a message on WhatsApp or call!</p>
                    <?= contact_buttons(null) ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="text-align:center;padding:3rem 1rem;color:var(--muted)">
                <p style="font-size:1.1rem;margin-bottom:.6rem">Enter a keyword above to search our products.</p>
                <p style="font-size:.95rem">Popular searches: <em>fridge, microwave, non-stick, cookware, backpack, tote bag</em></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
