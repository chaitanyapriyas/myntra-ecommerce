<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Your wishlist';
$userId = (int) current_user()['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $productId = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT);
    if ($action === 'add' && $productId) {
        if (!get_product((int) $productId)) { flash('That product is no longer available.', 'error'); redirect('products.php'); }
        $statement = db()->prepare('INSERT IGNORE INTO wishlist (user_id,product_id) VALUES (?,?)');
        $statement->execute([$userId, $productId]);
        flash('Saved to your wishlist.');
    } elseif ($action === 'remove' && filter_var($_POST['wishlist_id'] ?? '', FILTER_VALIDATE_INT)) {
        $statement = db()->prepare('DELETE FROM wishlist WHERE wishlist_id = ? AND user_id = ?');
        $statement->execute([(int) $_POST['wishlist_id'], $userId]);
        flash('Removed from your wishlist.', 'info');
    } elseif ($action === 'move' && filter_var($_POST['wishlist_id'] ?? '', FILTER_VALIDATE_INT)) {
        $statement = db()->prepare('SELECT w.product_id,p.stock FROM wishlist w JOIN products p ON p.product_id=w.product_id WHERE w.wishlist_id=? AND w.user_id=?');
        $statement->execute([(int) $_POST['wishlist_id'], $userId]);
        $item = $statement->fetch();
        if (!$item || (int) $item['stock'] < 1) {
            flash('This style is currently out of stock.', 'error');
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $lock = $pdo->prepare('SELECT stock FROM products WHERE product_id=? FOR UPDATE');
                $lock->execute([$item['product_id']]);
                $stock = (int) $lock->fetchColumn();
                $count = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=? AND product_id=?');
                $count->execute([$userId, $item['product_id']]);
                if ((int) $count->fetchColumn() >= $stock) {
                    $pdo->rollBack();
                    flash('Your bag already contains all available stock for this style.', 'error');
                } else {
                    $add = $pdo->prepare('INSERT INTO cart (user_id,product_id,quantity,size) VALUES (?,?,1,"M") ON DUPLICATE KEY UPDATE quantity=quantity+1');
                    $add->execute([$userId, $item['product_id']]);
                    $pdo->prepare('DELETE FROM wishlist WHERE wishlist_id=? AND user_id=?')->execute([(int) $_POST['wishlist_id'], $userId]);
                    $pdo->commit();
                    flash('Moved to your bag.');
                }
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $exception;
            }
        }
    } else {
        flash('The wishlist action could not be completed.', 'error');
    }
    redirect('wishlist.php');
}
$statement = db()->prepare('SELECT w.wishlist_id,p.* FROM wishlist w JOIN products p ON p.product_id=w.product_id WHERE w.user_id=? ORDER BY w.created_at DESC');
$statement->execute([$userId]);
$items = $statement->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="container"><div class="page-intro"><div class="eyebrow">Saved for later</div><h1>Your wishlist</h1><p>A little collection of things that feel like you.</p></div>
<?php if (!$items): ?><div class="empty-state"><div class="empty-icon">♡</div><h2>Your wishlist is waiting for a little love</h2><p>Save the styles you love and find them here later.</p><a class="button" href="products.php">Explore styles</a></div>
<?php else: ?><div class="product-grid"><?php foreach ($items as $item): ?><article class="product-card">
    <a class="product-image-wrap" href="product-details.php?id=<?= (int) $item['product_id'] ?>"><img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>"><span class="product-tag"><?= (int) $item['discount'] ?>% OFF</span></a>
    <div class="product-info"><div class="product-brand"><?= e($item['brand']) ?></div><div class="product-name"><?= e($item['name']) ?></div><div class="price-line"><strong>₹<?= number_format(product_price($item),0) ?></strong></div>
        <div class="card-actions">
            <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="wishlist_id" value="<?= (int) $item['wishlist_id'] ?>"><input type="hidden" name="action" value="move"><button class="button button-small" type="submit" <?= (int) $item['stock'] < 1 ? 'disabled' : '' ?>>Move to bag</button></form>
            <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="wishlist_id" value="<?= (int) $item['wishlist_id'] ?>"><input type="hidden" name="action" value="remove"><button class="button-outline button-small" type="submit">Remove</button></form>
        </div>
    </div>
</article><?php endforeach; ?></div><?php endif; ?></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
