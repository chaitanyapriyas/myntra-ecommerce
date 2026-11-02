<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Your shopping bag';
$userId = (int) current_user()['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $productId = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT);
    if ($action === 'add' && $productId) {
        $product = get_product((int) $productId);
        $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);
        $quantity = $quantity && $quantity > 0 ? $quantity : 1;
        $size = trim((string) ($_POST['size'] ?? 'M'));
        if (!$product) { flash('That product is no longer available.', 'error'); redirect('products.php'); }
        if ((int) $product['stock'] < 1) { flash('This style is out of stock.', 'error'); redirect('product-details.php?id=' . (int) $productId); }
        if ($quantity > (int) $product['stock']) { flash('Only ' . (int) $product['stock'] . ' available. Please adjust the quantity.', 'error'); redirect('product-details.php?id=' . (int) $productId); }
        if (!preg_match('/^(XS|S|M|L|XL|One Size)$/', $size)) $size = 'M';
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT stock FROM products WHERE product_id=? FOR UPDATE');
            $lock->execute([$productId]);
            $stock = (int) $lock->fetchColumn();
            $count = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=? AND product_id=?');
            $count->execute([$userId, $productId]);
            if ($quantity > $stock - (int) $count->fetchColumn()) {
                $pdo->rollBack();
                flash('There is not enough stock for that quantity.', 'error');
            } else {
                $statement = $pdo->prepare('INSERT INTO cart (user_id,product_id,quantity,size) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=quantity+VALUES(quantity)');
                $statement->execute([$userId, $productId, $quantity, $size]);
                $pdo->commit();
                flash('Added to your bag.');
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    } elseif (in_array($action, ['increase','decrease','remove'], true) && filter_var($_POST['cart_id'] ?? '', FILTER_VALIDATE_INT)) {
        $cartId = (int) $_POST['cart_id'];
        $statement = db()->prepare('SELECT c.quantity,c.product_id,p.stock FROM cart c JOIN products p ON p.product_id=c.product_id WHERE c.cart_id=? AND c.user_id=?');
        $statement->execute([$cartId, $userId]);
        $row = $statement->fetch();
        $updated = false;
        if ($row) {
            if ($action === 'remove') {
                db()->prepare('DELETE FROM cart WHERE cart_id=? AND user_id=?')->execute([$cartId, $userId]);
                $updated = true;
            } elseif ($action === 'decrease') {
                db()->prepare('UPDATE cart SET quantity=GREATEST(quantity-1,1) WHERE cart_id=? AND user_id=?')->execute([$cartId, $userId]);
                $updated = true;
            } else {
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    $lock = $pdo->prepare('SELECT stock FROM products WHERE product_id=? FOR UPDATE');
                    $lock->execute([$row['product_id']]);
                    $stock = (int) $lock->fetchColumn();
                    $count = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=? AND product_id=?');
                    $count->execute([$userId, $row['product_id']]);
                    if ((int) $count->fetchColumn() < $stock) {
                        $pdo->prepare('UPDATE cart SET quantity=quantity+1 WHERE cart_id=? AND user_id=?')->execute([$cartId, $userId]);
                        $pdo->commit();
                        $updated = true;
                    } else {
                        $pdo->rollBack();
                        flash('You already have all available stock in your bag.', 'error');
                    }
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    throw $exception;
                }
            }
        }
        if ($updated) flash($action === 'remove' ? 'Item removed from your bag.' : 'Your bag was updated.', 'info');
        elseif (!$row) flash('That bag item could not be found.', 'error');
    } elseif ($action === 'coupon') {
        $coupon = strtoupper(trim((string) ($_POST['coupon'] ?? '')));
        if ($coupon === 'FASHION10') { $_SESSION['coupon'] = $coupon; flash('FASHION10 applied — enjoy 10% off.'); }
        else { unset($_SESSION['coupon']); flash('That coupon code is not valid. Try FASHION10.', 'error'); }
    } else {
        flash('The bag action could not be completed.', 'error');
    }
    redirect('cart.php');
}
$items = cart_rows($userId);
$totals = cart_totals($items, (string) ($_SESSION['coupon'] ?? ''));
require __DIR__ . '/includes/header.php';
?>
<div class="container"><div class="page-intro"><div class="eyebrow">Almost yours</div><h1>Your shopping bag</h1><p><?= $totals['quantity'] ?> item<?= $totals['quantity'] === 1 ? '' : 's' ?> in your bag.</p></div>
<?php if (!$items): ?><div class="empty-state"><div class="empty-icon">♧</div><h2>Your bag is taking a breather</h2><p>Find something that feels just right.</p><a class="button" href="products.php">Continue shopping</a></div>
<?php else: ?><div class="cart-layout"><section>
    <?php foreach ($items as $item): ?><article class="cart-item">
        <a href="product-details.php?id=<?= (int) $item['product_id'] ?>"><img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>"></a>
        <div><h3><?= e($item['brand']) ?></h3><p><?= e($item['name']) ?></p><p>Size: <?= e($item['size']) ?> · <?= (int) $item['stock'] ?> in stock</p><p><strong>₹<?= number_format(product_price($item),0) ?></strong></p>
            <div class="card-actions">
                <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="cart_id" value="<?= (int) $item['cart_id'] ?>"><input type="hidden" name="action" value="decrease"><button class="button-outline button-small" aria-label="Decrease quantity">−</button></form>
                <span style="padding:5px"><?= (int) $item['quantity'] ?></span>
                <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="cart_id" value="<?= (int) $item['cart_id'] ?>"><input type="hidden" name="action" value="increase"><button class="button-outline button-small" aria-label="Increase quantity" <?= (int) $item['quantity'] >= (int) $item['stock'] ? 'disabled' : '' ?>>+</button></form>
                <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="cart_id" value="<?= (int) $item['cart_id'] ?>"><input type="hidden" name="action" value="remove"><button class="button-outline button-small" type="submit">Remove</button></form>
            </div>
        </div>
        <strong>₹<?= number_format(product_price($item) * (int) $item['quantity'],0) ?></strong>
    </article><?php endforeach; ?>
    <p style="margin-top:20px"><a class="text-link" href="products.php">← Continue shopping</a></p>
</section>
<aside class="cart-side"><h2>Price details</h2><div class="summary-row"><span>Subtotal (<?= $totals['quantity'] ?> items)</span><span>₹<?= number_format($totals['subtotal'],2) ?></span></div>
    <form class="coupon-form" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="coupon"><input name="coupon" value="<?= e($_SESSION['coupon'] ?? '') ?>" placeholder="Coupon code"><button class="button button-small">Apply</button></form>
    <?php if ($totals['discount']): ?><div class="summary-row"><span>FASHION10 discount</span><span>− ₹<?= number_format($totals['discount'],2) ?></span></div><?php endif; ?>
    <div class="summary-row"><span>Delivery</span><span style="color:var(--green)">FREE</span></div><div class="summary-row total"><span>Total</span><span>₹<?= number_format($totals['total'],2) ?></span></div>
    <p style="font-size:11px">Price includes the product discount shown above.</p><a class="button" href="checkout.php" style="width:100%">Proceed to checkout →</a>
</aside></div><?php endif; ?></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
