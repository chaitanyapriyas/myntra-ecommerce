<?php
$cardPrice = product_price($product);
?>
<article class="product-card" data-product-card data-category="<?= e($product['category']) ?>" data-search="<?= e(strtolower($product['name'] . ' ' . $product['brand'])) ?>">
    <a href="product-details.php?id=<?= (int) $product['product_id'] ?>" class="product-image-wrap">
        <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        <?php if ((int) $product['discount'] > 0): ?><span class="product-tag"><?= (int) $product['discount'] ?>% OFF</span><?php endif; ?>
        <span class="rating">★ 4.8</span>
    </a>
    <div class="product-info">
        <div class="product-brand"><?= e($product['brand']) ?></div>
        <div class="product-name"><?= e($product['name']) ?></div>
        <div class="price-line"><strong>₹<?= number_format($cardPrice, 0) ?></strong><span class="old-price">₹<?= number_format((float) $product['price'], 0) ?></span><span class="discount">(<?= (int) $product['discount'] ?>% off)</span></div>
        <div class="card-actions">
            <form method="post" action="cart.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
                <button class="button button-small" type="submit" <?= (int) $product['stock'] < 1 ? 'disabled' : '' ?>>Add to bag</button>
            </form>
            <form method="post" action="wishlist.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
                <button class="button-outline button-small" type="submit" aria-label="Add <?= e($product['name']) ?> to wishlist">♡</button>
            </form>
        </div>
    </div>
</article>
