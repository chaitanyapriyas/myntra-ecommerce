<?php
require_once __DIR__ . '/includes/functions.php';
$product = get_product((int) ($_GET['id'] ?? 0));
if (!$product) { http_response_code(404); $pageTitle = 'Product not found'; require __DIR__ . '/includes/header.php'; echo '<div class="container empty-state"><h2>That style could not be found</h2><a class="button" href="products.php">Browse collection</a></div>'; require __DIR__ . '/includes/footer.php'; exit; }
$pageTitle = $product['name'];
$sizes = $product['category'] === 'Home' || $product['category'] === 'Beauty' ? ['One Size'] : ['XS','S','M','L','XL'];
require __DIR__ . '/includes/header.php';
?>
<div class="container"><div class="detail-layout">
    <div class="detail-image"><img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>"></div>
    <section>
        <h1 class="detail-brand"><?= e($product['brand']) ?></h1><p class="detail-name"><?= e($product['name']) ?></p>
        <div class="detail-price">₹<?= number_format(product_price($product), 0) ?> <del>₹<?= number_format((float) $product['price'], 0) ?></del> <span class="discount">(<?= (int) $product['discount'] ?>% off)</span></div>
        <p class="stock-note <?= (int) $product['stock'] <= 5 ? 'stock-low' : '' ?>"><?= (int) $product['stock'] > 0 ? (int) $product['stock'] . ' in stock' : 'Currently out of stock' ?></p>
        <form method="post" action="cart.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
            <div class="detail-label">Select size</div><div class="size-picker">
                <?php foreach ($sizes as $index => $size): ?><label><input type="radio" name="size" value="<?= e($size) ?>" <?= $index === 0 ? 'checked' : '' ?>><span><?= e($size === 'One Size' ? 'OS' : $size) ?></span></label><?php endforeach; ?>
            </div>
            <div class="detail-label">Quantity</div><select class="qty-select" name="quantity"><?php for ($i=1; $i<=min(5, max(1, (int) $product['stock'])); $i++): ?><option><?= $i ?></option><?php endfor; ?></select>
            <div class="detail-buttons"><button class="button" type="submit" <?= (int) $product['stock'] < 1 ? 'disabled' : '' ?>>Add to bag</button></div>
        </form>
        <form method="post" action="wishlist.php" style="margin-top:10px"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>"><button class="button-outline" type="submit">♡ &nbsp; Add to wishlist</button></form>
        <div class="detail-description"><h3>Product details</h3><p><?= e($product['description']) ?></p><p><strong>Category:</strong> <?= e($product['category']) ?><br><strong>Style note:</strong> Made for real life, and all the moments in between.</p></div>
    </section>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
