<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $op = $_POST['op'] ?? '';

    if ($op === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = $db->prepare('SELECT COUNT(*) FROM categories WHERE department_id = ?');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            flash('err', 'This department still has categories. Move or delete its categories first.');
        } else {
            $stImg = $db->prepare('SELECT image FROM departments WHERE id = ?');
            $stImg->execute([$id]);
            $rowImg = $stImg->fetch();
            $db->prepare('DELETE FROM departments WHERE id = ?')->execute([$id]);
            if (!empty($rowImg['image'])) {
                delete_image_file($rowImg['image']);
            }
            flash('ok', 'Department deleted.');
        }
        redirect('departments.php');
    }

    if ($op === 'save') {
        $id     = (int) ($_POST['id'] ?? 0);
        $name   = trim((string) ($_POST['name'] ?? ''));
        $desc   = trim((string) ($_POST['description'] ?? ''));
        $sort   = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;

        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Enter a department name (up to 100 characters).';
        }
        if (mb_strlen($desc) > 255) {
            $errors[] = 'Description must be under 255 characters.';
        }

        $existing = null;
        if ($id) {
            $st = $db->prepare('SELECT * FROM departments WHERE id = ?');
            $st->execute([$id]);
            $existing = $st->fetch();
            if (!$existing) { redirect('departments.php'); }
        }

        $newImage = null;
        if (!$errors) {
            try {
                $newImage = handle_image_upload($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
            }
        }

        if ($errors) {
            foreach ($errors as $err) { flash('err', $err); }
            redirect('departments.php' . ($id ? '?edit=' . $id : ''));
        }

        if ($existing) {
            $image = $newImage ?: $existing['image'];
            $db->prepare('UPDATE departments SET name=?, description=?, image=?, sort_order=?, is_active=? WHERE id=?')
               ->execute([$name, $desc ?: null, $image, $sort, $active, $id]);
            if ($newImage && !empty($existing['image'])) {
                delete_image_file($existing['image']);
            }
            flash('ok', 'Department updated.');
        } else {
            $base = slugify($name);
            $slug = $base;
            $n = 2;
            $chk = $db->prepare('SELECT COUNT(*) FROM departments WHERE slug = ?');
            while (true) {
                $chk->execute([$slug]);
                if (!$chk->fetchColumn()) { break; }
                $slug = $base . '-' . $n++;
            }
            $db->prepare('INSERT INTO departments (name, slug, description, image, sort_order, is_active) VALUES (?,?,?,?,?,?)')
               ->execute([$name, $slug, $desc ?: null, $newImage, $sort, $active]);
            flash('ok', 'Department added.');
        }
        redirect('departments.php');
    }
    redirect('departments.php');
}

$editing = ['id' => 0, 'name' => '', 'description' => '', 'image' => null, 'sort_order' => 0, 'is_active' => 1];
if (isset($_GET['edit'])) {
    $st = $db->prepare('SELECT * FROM departments WHERE id = ?');
    $st->execute([(int) $_GET['edit']]);
    if ($row = $st->fetch()) { $editing = $row; }
}

$departments = $db->query('SELECT d.*, 
                           (SELECT COUNT(*) FROM categories c WHERE c.department_id = d.id) AS category_count,
                           (SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE c.department_id = d.id) AS product_count
                           FROM departments d 
                           ORDER BY d.sort_order, d.name')->fetchAll();

$adminTitle  = 'Departments';
$adminActive = 'departments';
require __DIR__ . '/../includes/admin-header.php';
?>
<h1>Departments</h1>

<div class="panel">
    <h2><?= $editing['id'] ? 'Edit department' : 'Add a new department' ?></h2>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="save">
        <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
        <div class="form-grid">
            <div>
                <label for="name">Department name</label>
                <input type="text" id="name" name="name" maxlength="100" required value="<?= e($editing['name']) ?>">
            </div>
            <div>
                <label for="sort_order">Sort order <span class="hint">(lower numbers come first)</span></label>
                <input type="number" id="sort_order" name="sort_order" value="<?= (int) $editing['sort_order'] ?>">
            </div>
            <div class="full">
                <label for="description">Short description / subtitle <span class="hint">shown on homepage cards &amp; banner</span></label>
                <input type="text" id="description" name="description" maxlength="255" value="<?= e($editing['description']) ?>">
            </div>
            <div>
                <label for="image-input">Department cover photo <span class="hint">used on homepage card</span></label>
                <input type="file" id="image-input" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
                <?php if (!empty($editing['image'])): ?>
                    <img id="image-preview" src="<?= e(department_image_url($editing, '../')) ?>" alt="" style="width:140px;margin-top:.6rem;border-radius:10px;border:1px solid var(--line)">
                <?php else: ?>
                    <img id="image-preview" src="" alt="" style="width:140px;margin-top:.6rem;border-radius:10px;border:1px solid var(--line)" hidden>
                <?php endif; ?>
            </div>
            <div style="display:grid;align-content:end">
                <label class="check"><input type="checkbox" name="is_active" value="1"<?= $editing['is_active'] ? ' checked' : '' ?>> Visible on the website</label>
            </div>
        </div>
        <p style="margin-top:1.2rem">
            <button class="btn btn-primary" type="submit"><?= $editing['id'] ? 'Save changes' : 'Add department' ?></button>
            <?php if ($editing['id']): ?><a class="btn btn-outline" href="departments.php">Cancel</a><?php endif; ?>
        </p>
    </form>
</div>

<div class="panel table-wrap">
    <table class="data">
        <thead><tr><th>Cover</th><th>Department name</th><th>Categories</th><th>Products</th><th>Status</th><th>Order</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($departments as $d): ?>
            <tr>
                <td><img src="<?= e(department_image_url($d, '../')) ?>" alt="" style="width:52px;height:52px;object-fit:cover;border-radius:8px"></td>
                <td><strong><?= e($d['name']) ?></strong></td>
                <td><?= (int) $d['category_count'] ?></td>
                <td><?= (int) $d['product_count'] ?></td>
                <td><span class="pill <?= $d['is_active'] ? 'on' : 'off' ?>"><?= $d['is_active'] ? 'Visible' : 'Hidden' ?></span></td>
                <td><?= (int) $d['sort_order'] ?></td>
                <td>
                    <div class="row-actions">
                        <a class="btn btn-outline btn-sm" href="departments.php?edit=<?= (int) $d['id'] ?>">Edit</a>
                        <a class="btn btn-outline btn-sm" href="../<?= e(department_url($d)) ?>" target="_blank" rel="noopener">View</a>
                        <form method="post" data-confirm="Delete department &quot;<?= e($d['name']) ?>&quot;?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="op" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
