<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $op = $_POST['op'] ?? '';

    if ($op === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = $db->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            flash('err', 'This category still has products. Move or delete them first.');
        } else {
            $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('ok', 'Category deleted.');
        }
        redirect('categories.php');
    }

    if ($op === 'save') {
        $id           = (int) ($_POST['id'] ?? 0);
        $name         = trim((string) ($_POST['name'] ?? ''));
        $departmentId = (int) ($_POST['department_id'] ?? 0);
        $desc         = trim((string) ($_POST['description'] ?? ''));
        $sort         = (int) ($_POST['sort_order'] ?? 0);

        $deptExists = (bool) $db->query('SELECT COUNT(*) FROM departments WHERE id = ' . $departmentId)->fetchColumn();

        if ($name === '' || mb_strlen($name) > 100 || !$deptExists || mb_strlen($desc) > 255) {
            flash('err', 'Enter a category name (up to 100 characters), select a valid department, and keep description under 255 characters.');
            redirect('categories.php' . ($id ? '?edit=' . $id : ''));
        }

        if ($id) {
            $db->prepare('UPDATE categories SET name=?, department_id=?, description=?, sort_order=? WHERE id=?')
               ->execute([$name, $departmentId, $desc ?: null, $sort, $id]);
            flash('ok', 'Category updated.');
        } else {
            $base = slugify($name);
            $slug = $base;
            $n = 2;
            $chk = $db->prepare('SELECT COUNT(*) FROM categories WHERE slug = ?');
            while (true) {
                $chk->execute([$slug]);
                if (!$chk->fetchColumn()) { break; }
                $slug = $base . '-' . $n++;
            }
            $db->prepare('INSERT INTO categories (department_id, name, slug, description, sort_order) VALUES (?,?,?,?,?)')
               ->execute([$departmentId, $name, $slug, $desc ?: null, $sort]);
            flash('ok', 'Category added.');
        }
        redirect('categories.php');
    }
    redirect('categories.php');
}

$editing = ['id' => 0, 'name' => '', 'department_id' => 0, 'description' => '', 'sort_order' => 0];
if (isset($_GET['edit'])) {
    $st = $db->prepare('SELECT * FROM categories WHERE id = ?');
    $st->execute([(int) $_GET['edit']]);
    if ($row = $st->fetch()) { $editing = $row; }
}

$departmentsList = $db->query('SELECT * FROM departments ORDER BY sort_order, name')->fetchAll();
if (!$editing['department_id'] && !empty($departmentsList)) {
    $editing['department_id'] = $departmentsList[0]['id'];
}

$categories = $db->query('SELECT c.*, d.name AS department_name, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
                          FROM categories c 
                          JOIN departments d ON d.id = c.department_id 
                          ORDER BY d.sort_order, c.sort_order, c.name')->fetchAll();

$adminTitle  = 'Categories';
$adminActive = 'categories';
require __DIR__ . '/../includes/admin-header.php';
?>
<h1>Categories</h1>

<div class="panel">
    <h2><?= $editing['id'] ? 'Edit category' : 'Add a category' ?></h2>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="save">
        <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
        <div class="form-grid">
            <div>
                <label for="name">Name</label>
                <input type="text" id="name" name="name" maxlength="100" required value="<?= e($editing['name']) ?>">
            </div>
            <div>
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id" required>
                    <?php foreach ($departmentsList as $d): ?>
                        <option value="<?= (int) $d['id'] ?>"<?= (int) $editing['department_id'] === (int) $d['id'] ? ' selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="full">
                <label for="description">Short description <span class="hint">optional</span></label>
                <input type="text" id="description" name="description" maxlength="255" value="<?= e($editing['description']) ?>">
            </div>
            <div>
                <label for="sort_order">Sort order</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= (int) $editing['sort_order'] ?>">
            </div>
        </div>
        <p style="margin-top:1.2rem">
            <button class="btn btn-primary" type="submit"><?= $editing['id'] ? 'Save changes' : 'Add category' ?></button>
            <?php if ($editing['id']): ?><a class="btn btn-outline" href="categories.php">Cancel</a><?php endif; ?>
        </p>
    </form>
</div>

<div class="panel table-wrap">
    <table class="data">
        <thead><tr><th>Name</th><th>Department</th><th>Products</th><th>Order</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
            <tr>
                <td><strong><?= e($c['name']) ?></strong></td>
                <td><?= e($c['department_name']) ?></td>
                <td><?= (int) $c['product_count'] ?></td>
                <td><?= (int) $c['sort_order'] ?></td>
                <td>
                    <div class="row-actions">
                        <a class="btn btn-outline btn-sm" href="categories.php?edit=<?= (int) $c['id'] ?>">Edit</a>
                        <form method="post" data-confirm="Delete the category &quot;<?= e($c['name']) ?>&quot;?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="op" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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
