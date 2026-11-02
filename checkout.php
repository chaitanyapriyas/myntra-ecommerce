<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Simulated checkout';
$userId = (int) current_user()['user_id'];
$orderId = (int) ($_GET['order'] ?? 0);
$confirmation = null;
if ($orderId > 0) {
    $statement = db()->prepare('SELECT * FROM orders WHERE order_id=? AND user_id=?');
    $statement->execute([$orderId, $userId]);
    $confirmation = $statement->fetch();
}
$items = cart_rows($userId);
$totals = cart_totals($items, (string) ($_SESSION['coupon'] ?? ''));
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $address = trim((string) ($_POST['address'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $pin = trim((string) ($_POST['pin'] ?? ''));
    if (mb_strlen($address) < 8 || mb_strlen($address) > 250) $errors['address'] = 'Enter a complete address (8–250 characters).';
    if (!preg_match('/^[\p{L} .\'-]{2,80}$/u', $city)) $errors['city'] = 'Enter a valid city name.';
    if (!valid_pin($pin)) $errors['pin'] = 'Enter a six-digit PIN code.';
    if (!$items) $errors['cart'] = 'Your bag is empty.';
    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $lockedItems = cart_rows($userId);
            foreach ($lockedItems as $row) {
                $lock = $pdo->prepare('SELECT stock FROM products WHERE product_id=? FOR UPDATE');
                $lock->execute([$row['product_id']]);
                $stock = $lock->fetchColumn();
                if ($stock === false || (int) $stock < (int) $row['quantity']) {
                    throw new RuntimeException(e($row['name']) . ' does not have enough stock. Please update your bag.');
                }
            }
            $lockedTotals = cart_totals($lockedItems, (string) ($_SESSION['coupon'] ?? ''));
            $order = $pdo->prepare('INSERT INTO orders (user_id,total_amount) VALUES (?,?)');
            $order->execute([$userId, $lockedTotals['total']]);
            $newOrderId = (int) $pdo->lastInsertId();
            $addItem = $pdo->prepare('INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)');
            $reduceStock = $pdo->prepare('UPDATE products SET stock=stock-? WHERE product_id=? AND stock>=?');
            foreach ($lockedItems as $row) {
                $price = product_price($row);
                $addItem->execute([$newOrderId, $row['product_id'], $row['quantity'], $price]);
                $reduceStock->execute([$row['quantity'], $row['product_id'], $row['quantity']]);
                if ($reduceStock->rowCount() !== 1) throw new RuntimeException('Stock changed during checkout. Please try again.');
            }
            $pdo->prepare('DELETE FROM cart WHERE user_id=?')->execute([$userId]);
            $pdo->commit();
            unset($_SESSION['coupon']);
            redirect('checkout.php?order=' . $newOrderId);
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors['cart'] = $exception->getMessage();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="container"><div class="page-intro"><div class="eyebrow">One last step</div><h1>Checkout</h1><p>This is a simulated order for this educational project. No payment details are collected.</p></div>
<?php if ($confirmation): ?><div class="confirmation"><div class="check">✓</div><h2>Order placed. Nicely done.</h2><p>Order <strong>#<?= (int) $confirmation['order_id'] ?></strong> is confirmed as a simulated transaction.</p><p>Total: <strong>₹<?= number_format((float) $confirmation['total_amount'],2) ?></strong></p><a class="button" href="products.php">Keep exploring</a></div>
<?php elseif (!$items): ?><div class="empty-state"><?php if (isset($errors['cart'])): ?><p class="flash-error"><?= e($errors['cart']) ?></p><?php endif; ?><h2>Your bag is empty</h2><a class="button" href="products.php">Browse styles</a></div>
<?php else: ?><div class="checkout-grid"><section class="panel"><h2>Delivery details</h2>
    <?php if (isset($errors['cart'])): ?><div class="flash flash-error"><?= e($errors['cart']) ?></div><?php endif; ?>
    <form method="post" data-validate-form novalidate><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-field"><label for="address">Street address</label><textarea id="address" name="address" rows="3" required><?= e($_POST['address'] ?? '') ?></textarea><div class="field-error"><?= e($errors['address'] ?? '') ?></div></div>
        <div class="form-field"><label for="city">City</label><input id="city" name="city" required value="<?= e($_POST['city'] ?? '') ?>"><div class="field-error"><?= e($errors['city'] ?? '') ?></div></div>
        <div class="form-field"><label for="pin">PIN code</label><input id="pin" name="pin" inputmode="numeric" maxlength="6" required value="<?= e($_POST['pin'] ?? '') ?>"><div class="field-error" data-error="pin"><?= e($errors['pin'] ?? '') ?></div></div>
        <div class="help-box">Simulated checkout only — never enter card or bank information.</div>
        <button class="button" type="submit" style="margin-top:18px">Place simulated order · ₹<?= number_format($totals['total'],2) ?></button>
    </form>
</section><aside class="panel"><h2>Order summary</h2><?php foreach ($items as $item): ?><div class="summary-row"><span><?= e($item['name']) ?> × <?= (int) $item['quantity'] ?></span><span>₹<?= number_format(product_price($item)*(int)$item['quantity'],2) ?></span></div><?php endforeach; ?>
    <div class="summary-row"><span>Subtotal</span><span>₹<?= number_format($totals['subtotal'],2) ?></span></div><?php if ($totals['discount']): ?><div class="summary-row"><span>FASHION10</span><span>− ₹<?= number_format($totals['discount'],2) ?></span></div><?php endif; ?><div class="summary-row total"><span>Total</span><span>₹<?= number_format($totals['total'],2) ?></span></div>
</aside></div><?php endif; ?></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
