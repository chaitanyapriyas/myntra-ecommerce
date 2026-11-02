<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'JavaScript interaction lab';
$products = db()->query('SELECT * FROM products ORDER BY name LIMIT 10')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="container"><div class="page-intro"><div class="eyebrow">Hands-on web technology</div><h1>Interaction lab.</h1><p>A small playground for the JavaScript events used across the site.</p></div>
<section class="demo-section"><div class="demo-box"><h2>Click, hover & coordinates</h2><p>Move your mouse across the box to see client and screen coordinates. Click to toggle its highlight.</p><div class="coordinates" data-demo-coordinates>Move your mouse here — then click.</div></div>
<div class="demo-box"><h2>Keyboard events</h2><p>Press any key while on this page.</p><div class="coordinates" data-key-output>Waiting for a key…</div></div>
<div class="demo-box"><h2>Contextual help</h2><label for="demo-email">Email field</label><input id="demo-email" data-help-trigger placeholder="Focus here for a tip"><div class="help-box" data-help-box hidden>Tip: use an email address in the format name@example.com.</div></div>
<div class="demo-box"><h2>Hover to bring forward</h2><p>Hover either panel to bring it to the front.</p><div class="overlap-stage"><div class="overlap-panel overlap-one" data-overlap>Panel A · hover me</div><div class="overlap-panel overlap-two" data-overlap>Panel B · or me</div></div></div>
<div class="demo-box"><h2>Dynamic DOM filtering</h2><div class="form-field"><label for="demo-filter">Filter products as you type</label><input id="demo-filter" type="search" data-demo-filter placeholder="Try a brand or product name"></div><ul><?php foreach ($products as $product): ?><li data-demo-product data-text="<?= e(strtolower($product['name'] . ' ' . $product['brand'])) ?>"><?= e($product['brand'] . ' — ' . $product['name']) ?></li><?php endforeach; ?></ul></div>
</section></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
