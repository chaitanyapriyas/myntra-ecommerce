<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Inventory dashboard';
$errors = [];
$editing = null;
$categories = ['Men','Women','Kids','Home','Beauty'];
$blank = ['name'=>'','brand'=>'','category'=>'Women','description'=>'','price'=>'','discount'=>'0','stock'=>'0','image'=>''];
$form = $blank;

if (isset($_GET['edit'])) {
    $editing = get_product((int) $_GET['edit']);
    if ($editing) $form = $editing;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                db()->prepare('DELETE FROM products WHERE product_id=?')->execute([$id]);
                flash('Product deleted from inventory.', 'info');
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') throw $exception;
                flash('This product is part of an order and cannot be deleted.', 'error');
            }
        } else {
            flash('Choose a valid product to delete.', 'error');
        }
        redirect('dashboard.php');
    }
    $form = [
        'name'=>trim((string) ($_POST['name'] ?? '')),
        'brand'=>trim((string) ($_POST['brand'] ?? '')),
        'category'=>(string) ($_POST['category'] ?? ''),
        'description'=>trim((string) ($_POST['description'] ?? '')),
        'price'=>$_POST['price'] ?? '',
        'discount'=>$_POST['discount'] ?? '',
        'stock'=>$_POST['stock'] ?? '',
        'image'=>trim((string) ($_POST['image'] ?? '')),
    ];
    $id = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT);
    $price = filter_var($form['price'], FILTER_VALIDATE_FLOAT);
    $discount = filter_var($form['discount'], FILTER_VALIDATE_INT);
    $stock = filter_var($form['stock'], FILTER_VALIDATE_INT);
    if (mb_strlen($form['name']) < 2 || mb_strlen($form['name']) > 180) $errors['name'] = 'Product name must be 2–180 characters.';
    if (mb_strlen($form['brand']) < 2 || mb_strlen($form['brand']) > 100) $errors['brand'] = 'Brand must be 2–100 characters.';
    if (!in_array($form['category'], $categories, true)) $errors['category'] = 'Choose a valid category.';
    if (mb_strlen($form['description']) < 8) $errors['description'] = 'Add a short product description.';
    if ($price === false || $price <= 0) $errors['price'] = 'Enter a price greater than zero.';
    if ($discount === false || $discount < 0 || $discount > 90) $errors['discount'] = 'Discount must be from 0 to 90.';
    if ($stock === false || $stock < 0) $errors['stock'] = 'Stock must be zero or more.';
    if (!filter_var($form['image'], FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $form['image'])) $errors['image'] = 'Use a valid HTTPS or HTTP image URL.';
    if (!$errors) {
        $values = [$form['name'],$form['brand'],$form['category'],$form['description'],$price,$discount,$stock,$form['image']];
        if ($action === 'update' && $id) {
            $values[] = $id;
            $statement = db()->prepare('UPDATE products SET name=?,brand=?,category=?,description=?,price=?,discount=?,stock=?,image=? WHERE product_id=?');
            $statement->execute($values);
            flash('Product details and inventory updated.');
        } elseif ($action === 'create') {
            $statement = db()->prepare('INSERT INTO products (name,brand,category,description,price,discount,stock,image) VALUES (?,?,?,?,?,?,?,?)');
            $statement->execute($values);
            flash('Product added to the catalogue.');
        } else {
            $errors['form'] = 'Could not identify the product action.';
        }
        if (!$errors) redirect('dashboard.php');
    }
}
$search = trim((string) ($_GET['q'] ?? ''));
if ($search !== '') {
    $statement = db()->prepare('SELECT * FROM products WHERE name LIKE ? OR brand LIKE ? ORDER BY name');
    $statement->execute(['%' . $search . '%','%' . $search . '%']);
    $inventory = $statement->fetchAll();
} else {
    $inventory = db()->query('SELECT * FROM products ORDER BY name')->fetchAll();
}
$lowStock = db()->query('SELECT COUNT(*) FROM products WHERE stock <= 5')->fetchColumn();
require __DIR__ . '/../includes/header.php';
?>
<div class="container"><div class="page-intro"><div class="eyebrow">Back of house</div><h1>Inventory, in good shape.</h1><p>Welcome, <?= e(current_user()['name']) ?>. Keep every detail up to date.</p></div>
<div class="admin-nav"><span class="stock-badge"><?= (int) $lowStock ?> low-stock alert<?= (int) $lowStock === 1 ? '' : 's' ?> (5 or fewer)</span><a class="text-link" href="../products.php">View storefront →</a><a class="text-link" href="../logout.php">Sign out</a></div>
<section class="panel" style="margin-bottom:28px"><h2><?= $editing ? 'Edit product' : 'Add a product' ?></h2><?php if (isset($errors['form'])): ?><div class="flash flash-error"><?= e($errors['form']) ?></div><?php endif; ?>
    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>"><input type="hidden" name="product_id" value="<?= (int) ($form['product_id'] ?? 0) ?>">
        <div class="admin-form-grid">
            <div class="form-field"><label>Product name</label><input name="name" required value="<?= e($form['name']) ?>"><div class="field-error"><?= e($errors['name'] ?? '') ?></div></div>
            <div class="form-field"><label>Brand</label><input name="brand" required value="<?= e($form['brand']) ?>"><div class="field-error"><?= e($errors['brand'] ?? '') ?></div></div>
            <div class="form-field"><label>Category</label><select name="category"><?php foreach ($categories as $category): ?><option <?= $form['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select><div class="field-error"><?= e($errors['category'] ?? '') ?></div></div>
            <div class="form-field"><label>Image URL</label><input name="image" type="url" required value="<?= e($form['image']) ?>"><div class="field-error"><?= e($errors['image'] ?? '') ?></div></div>
            <div class="form-field"><label>Price (₹)</label><input name="price" type="number" step="0.01" min="0.01" required value="<?= e($form['price']) ?>"><div class="field-error"><?= e($errors['price'] ?? '') ?></div></div>
            <div class="form-field"><label>Discount (%)</label><input name="discount" type="number" min="0" max="90" required value="<?= e($form['discount']) ?>"><div class="field-error"><?= e($errors['discount'] ?? '') ?></div></div>
            <div class="form-field"><label>Available stock</label><input name="stock" type="number" min="0" required value="<?= e($form['stock']) ?>"><div class="field-error"><?= e($errors['stock'] ?? '') ?></div></div>
            <div class="form-field wide"><label>Description</label><textarea name="description" rows="3" required><?= e($form['description']) ?></textarea><div class="field-error"><?= e($errors['description'] ?? '') ?></div></div>
        </div><button class="button" type="submit"><?= $editing ? 'Save product changes' : 'Add product' ?></button><?php if ($editing): ?> <a class="button-outline" href="dashboard.php">Cancel</a><?php endif; ?>
    </form>
</section>
<section class="panel"><div class="section-heading"><div><h2>Product inventory</h2><p><?= count($inventory) ?> product<?= count($inventory) === 1 ? '' : 's' ?></p></div><form method="get" style="display:flex;gap:7px"><input name="q" value="<?= e($search) ?>" placeholder="Search products" style="padding:9px;border:1px solid var(--line)"><button class="button button-small">Search</button></form></div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Product</th><th>Brand / category</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($inventory as $product): ?><tr><td><?= e($product['name']) ?></td><td><?= e($product['brand']) ?> · <?= e($product['category']) ?></td><td>₹<?= number_format((float) $product['price'],0) ?></td><td><?php if ((int) $product['stock'] <= 5): ?><span class="stock-badge">Low: <?= (int) $product['stock'] ?></span><?php else: ?><?= (int) $product['stock'] ?><?php endif; ?></td><td><a class="text-link" href="dashboard.php?edit=<?= (int) $product['product_id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete this product?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>"><button class="text-link" style="border:0;background:none;margin-left:10px">Delete</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
