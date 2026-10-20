<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$db = db();

/* --------------------------------------------------------------- POST actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $op = $_POST['op'] ?? '';

    // ---- Delete a whole product
    if ($op === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = $db->prepare('SELECT image FROM products WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if ($row) {
            $st = $db->prepare('SELECT image FROM product_images WHERE product_id = ?');
            $st->execute([$id]);
            $files = array_column($st->fetchAll(), 'image');
            $db->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            delete_image_file($row['image']);
            foreach ($files as $f) { delete_image_file($f); }
            flash('ok', 'Product deleted.');
        }
        redirect('products.php');
    }

    // ---- Remove one gallery image
    if ($op === 'delete_gallery') {
        $imgId     = (int) ($_POST['image_id'] ?? 0);
        $productId = (int) ($_POST['id'] ?? 0);
        $st = $db->prepare('SELECT image FROM product_images WHERE id = ? AND product_id = ?');
        $st->execute([$imgId, $productId]);
        if ($file = $st->fetchColumn()) {
            $db->prepare('DELETE FROM product_images WHERE id = ?')->execute([$imgId]);
            delete_image_file($file);
            flash('ok', 'Image removed.');
        }
        redirect('products.php?action=edit&id=' . $productId);
    }

    // ---- Create / update a product
    if ($op === 'save') {
        $id          = (int) ($_POST['id'] ?? 0);
        $name        = trim((string) ($_POST['name'] ?? ''));
        $categoryId  = (int) ($_POST['category_id'] ?? 0);
        $short       = trim((string) ($_POST['short_description'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $priceRaw    = trim((string) ($_POST['price'] ?? ''));
        $origPriceRaw= trim((string) ($_POST['original_price'] ?? ''));
        $sort        = (int) ($_POST['sort_order'] ?? 0);
        $featured    = isset($_POST['featured']) ? 1 : 0;
        $active      = isset($_POST['is_active']) ? 1 : 0;

        $errors = [];
        if ($name === '' || mb_strlen($name) > 150) { $errors[] = 'Enter a product name (up to 150 characters).'; }
        if (mb_strlen($short) > 255)                { $errors[] = 'The short description must be 255 characters or fewer.'; }

        $st = $db->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
        $st->execute([$categoryId]);
        if (!$st->fetchColumn()) { $errors[] = 'Choose a category.'; }

        $price = null;
        if ($priceRaw !== '') {
            $clean = str_replace([',', ' '], '', $priceRaw);
            if (!is_numeric($clean) || (float) $clean < 0 || (float) $clean > 99999999) {
                $errors[] = 'Enter a valid price, or leave it empty to show "Call or WhatsApp for price".';
            } else {
                $price = (float) $clean;
            }
        }

        $originalPrice = null;
        if ($origPriceRaw !== '') {
            $cleanOrig = str_replace([',', ' '], '', $origPriceRaw);
            if (!is_numeric($cleanOrig) || (float) $cleanOrig < 0 || (float) $cleanOrig > 99999999) {
                $errors[] = 'Enter a valid original price or leave it blank.';
            } else {
                $originalPrice = (float) $cleanOrig;
            }
        }

        $existing = null;
        if ($id) {
            $st = $db->prepare('SELECT * FROM products WHERE id = ?');
            $st->execute([$id]);
            $existing = $st->fetch();
            if (!$existing) { redirect('products.php'); }
        }

        // Uploads (validated before anything is written to the database)
        $newMain = null;
        $newGallery = [];
        if (!$errors) {
            try {
                $newMain = handle_image_upload($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
                foreach (normalize_files($_FILES['gallery'] ?? []) as $f) {
                    if ($saved = handle_image_upload($f)) { $newGallery[] = $saved; }
                }
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
                delete_image_file($newMain);
                foreach ($newGallery as $g) { delete_image_file($g); }
                $newMain = null; $newGallery = [];
            }
        }

        if ($errors) {
            foreach ($errors as $err) { flash('err', $err); }
            $_SESSION['old'] = $_POST;
            redirect('products.php?action=' . ($id ? 'edit&id=' . $id : 'new'));
        }

        if ($existing) {
            $slug = $existing['slug'];
            $image = $newMain ?: $existing['image'];
            $db->prepare('UPDATE products SET category_id=?, name=?, short_description=?, description=?, price=?, original_price=?, image=?, featured=?, is_active=?, sort_order=? WHERE id=?')
               ->execute([$categoryId, $name, $short ?: null, $description ?: null, $price, $originalPrice, $image, $featured, $active, $sort, $id]);
            if ($newMain) { delete_image_file($existing['image']); }
        } else {
            $base = slugify($name);
            $slug = $base;
            $n = 2;
            $chk = $db->prepare('SELECT COUNT(*) FROM products WHERE slug = ?');
            while (true) {
                $chk->execute([$slug]);
                if (!$chk->fetchColumn()) { break; }
                $slug = $base . '-' . $n++;
            }
            $db->prepare('INSERT INTO products (category_id, name, slug, short_description, description, price, original_price, image, featured, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
               ->execute([$categoryId, $name, $slug, $short ?: null, $description ?: null, $price, $originalPrice, $newMain, $featured, $active, $sort]);
            $id = (int) $db->lastInsertId();
        }

        foreach ($newGallery as $g) {
            $db->prepare('INSERT INTO product_images (product_id, image) VALUES (?, ?)')->execute([$id, $g]);
        }
        unset($_SESSION['old']);
        flash('ok', $existing ? 'Product updated.' : 'Product added.');
        redirect('products.php');
    }

    redirect('products.php');
}

/* ------------------------------------------------------------------ Form view */
$action = $_GET['action'] ?? 'list';
if ($action === 'new' || $action === 'edit') {
    $product = [
        'id' => 0, 'category_id' => 0, 'name' => '', 'short_description' => '', 'description' => '',
        'price' => '', 'original_price' => '', 'image' => null, 'featured' => 0, 'is_active' => 1, 'sort_order' => 0,
    ];
    $gallery = [];
    if ($action === 'edit') {
        $st = $db->prepare('SELECT * FROM products WHERE id = ?');
        $st->execute([(int) ($_GET['id'] ?? 0)]);
        $product = $st->fetch();
        if (!$product) { flash('err', 'Product not found.'); redirect('products.php'); }
        $gallery = get_product_gallery((int) $product['id']);
    }
    if (!empty($_SESSION['old'])) { // re-show what was typed after a validation error
        $o = $_SESSION['old'];
        $product = array_merge($product, [
            'category_id' => (int) ($o['category_id'] ?? 0), 'name' => $o['name'] ?? '',
            'short_description' => $o['short_description'] ?? '', 'description' => $o['description'] ?? '',
            'price' => $o['price'] ?? '', 'original_price' => $o['original_price'] ?? '', 'featured' => isset($o['featured']) ? 1 : 0,
            'is_active' => isset($o['is_active']) ? 1 : 0, 'sort_order' => (int) ($o['sort_order'] ?? 0),
        ]);
        unset($_SESSION['old']);
    }
    $categories = $db->query('SELECT c.*, d.name AS department_name 
                              FROM categories c 
                              JOIN departments d ON d.id = c.department_id 
                              ORDER BY d.sort_order, c.sort_order, c.name')->fetchAll();

    $adminTitle  = $product['id'] ? 'Edit product' : 'Add product';
    $adminActive = 'products';
    require __DIR__ . '/../includes/admin-header.php';
    ?>
    <p><a href="products.php">&larr; Back to products</a></p>
    <div class="panel">
        <h1><?= e($adminTitle) ?></h1>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="op" value="save">
            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
            <div class="form-grid">
                <div class="full">
                    <label for="name">Product name</label>
                    <input type="text" id="name" name="name" maxlength="150" required value="<?= e($product['name']) ?>">
                </div>
                <div>
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Choose&hellip;</option>
                        <?php $lastDept = ''; foreach ($categories as $c):
                            if ($c['department_name'] !== $lastDept) {
                                if ($lastDept !== '') { echo '</optgroup>'; }
                                echo '<optgroup label="' . e($c['department_name']) . '">';
                                $lastDept = $c['department_name'];
                            } ?>
                            <option value="<?= (int) $c['id'] ?>"<?= (int) $product['category_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; if ($lastDept !== '') { echo '</optgroup>'; } ?>
                    </select>
                </div>
                <div>
                    <label for="price">Current Selling Price (<?= e(CURRENCY) ?>) <span class="hint">leave empty to show &ldquo;Call or WhatsApp for price&rdquo;</span></label>
                    <input type="text" id="price" name="price" inputmode="decimal" value="<?= e($product['price'] !== null && $product['price'] !== '' ? rtrim(rtrim((string) $product['price'], '0'), '.') : '') ?>">
                </div>
                <div>
                    <label for="original_price">Original / Before-Discount Price (<?= e(CURRENCY) ?>) <span class="hint">optional, triggers &ldquo;-X% OFF&rdquo; and strikethrough</span></label>
                    <input type="text" id="original_price" name="original_price" inputmode="decimal" value="<?= e($product['original_price'] !== null && $product['original_price'] !== '' ? rtrim(rtrim((string) $product['original_price'], '0'), '.') : '') ?>">
                </div>
                <div class="full">
                    <label for="short_description">Short description <span class="hint">shown on product cards</span></label>
                    <input type="text" id="short_description" name="short_description" maxlength="255" value="<?= e($product['short_description']) ?>">
                </div>
                <div class="full">
                    <label for="description">Full description</label>
                    <textarea id="description" name="description"><?= e($product['description']) ?></textarea>
                </div>
                <div>
                    <label for="image-input">Main image <span class="hint">JPG, PNG, WebP or GIF, max 5&nbsp;MB</span></label>
                    <input type="file" id="image-input" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
                    <?php $cur = $product['id'] ? product_image_url($product, '../') : ''; ?>
                    <img id="image-preview" src="<?= e($cur) ?>" alt="" style="width:140px;margin-top:.6rem;border-radius:10px;border:1px solid var(--line)" <?= $cur ? '' : 'hidden' ?>>
                </div>
                <div>
                    <label for="gallery">More images <span class="hint">optional, select several</span></label>
                    <input type="file" id="gallery" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp,image/gif">
                </div>
                <div>
                    <label for="sort_order">Sort order <span class="hint">lower numbers appear first</span></label>
                    <input type="number" id="sort_order" name="sort_order" value="<?= (int) $product['sort_order'] ?>">
                </div>
                <div style="display:grid;gap:.6rem;align-content:end">
                    <label class="check"><input type="checkbox" name="featured" value="1"<?= $product['featured'] ? ' checked' : '' ?>> Featured on the homepage</label>
                    <label class="check"><input type="checkbox" name="is_active" value="1"<?= $product['is_active'] ? ' checked' : '' ?>> Visible on the website</label>
                </div>
            </div>
            <p style="margin-top:1.4rem"><button class="btn btn-primary" type="submit">Save product</button>
               <a class="btn btn-outline" href="products.php">Cancel</a></p>
        </form>

        <?php if ($gallery): ?>
            <h2 style="margin-top:2rem">Extra images</h2>
            <div class="gallery-admin">
                <?php foreach ($gallery as $g): ?>
                    <figure>
                        <img src="<?= e(gallery_image_url($g['image'], '../')) ?>" alt="">
                        <form method="post" data-confirm="Remove this image?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="op" value="delete_gallery">
                            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                            <input type="hidden" name="image_id" value="<?= (int) $g['id'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                        </form>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    require __DIR__ . '/../includes/admin-footer.php';
    exit;
}

/* ------------------------------------------------------------------- List view */
$products = $db->query('SELECT p.*, c.name AS category_name, d.name AS department_name
                        FROM products p 
                        JOIN categories c ON c.id = p.category_id
                        JOIN departments d ON d.id = c.department_id
                        ORDER BY d.sort_order, c.sort_order, p.sort_order, p.name')->fetchAll();

$adminTitle  = 'Products';
$adminActive = 'products';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="section-head-row">
    <h1 style="margin:0">Products</h1>
    <a class="btn btn-primary" href="products.php?action=new">+ Add product</a>
</div>
<div class="panel table-wrap">
    <?php if (!$products): ?>
        <p>No products yet. <a href="products.php?action=new">Add your first product</a>.</p>
    <?php else: ?>
    <table class="data">
        <thead><tr><th></th><th>Name</th><th>Department / category</th><th>Price</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><img src="<?= e(product_image_url($p, '../')) ?>" alt=""></td>
                <td><strong><?= e($p['name']) ?></strong><?= $p['featured'] ? ' <span class="pill on">Featured</span>' : '' ?></td>
                <td><?= e($p['department_name']) ?> &rsaquo; <?= e($p['category_name']) ?></td>
                <td>
                    <?php if ($p['price'] !== null): ?>
                        <div style="font-weight:700"><?= e(format_price($p['price'])) ?></div>
                        <?php if ($p['original_price'] !== null && (float)$p['original_price'] > (float)$p['price']): 
                            $pct = calculate_discount_percent($p['price'], $p['original_price']); ?>
                            <div style="font-size:.8rem;color:var(--muted);text-decoration:line-through"><?= e(format_price($p['original_price'])) ?></div>
                            <?php if ($pct > 0): ?>
                                <span class="badge-discount" style="font-size:.72rem;padding:1px 6px;margin-top:2px">-<?= $pct ?>%</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:var(--muted)">Ask</span>
                    <?php endif; ?>
                </td>
                <td><span class="pill <?= $p['is_active'] ? 'on' : 'off' ?>"><?= $p['is_active'] ? 'Visible' : 'Hidden' ?></span></td>
                <td>
                    <div class="row-actions">
                        <a class="btn btn-outline btn-sm" href="products.php?action=edit&amp;id=<?= (int) $p['id'] ?>">Edit</a>
                        <a class="btn btn-outline btn-sm" href="../product.php?slug=<?= e(rawurlencode($p['slug'])) ?>" target="_blank" rel="noopener">View</a>
                        <form method="post" data-confirm="Delete &quot;<?= e($p['name']) ?>&quot;? This cannot be undone.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="op" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
