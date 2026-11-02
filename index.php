<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'New season, new stories';
$previousVisit = set_last_visit_cookie();
$products = db()->query('SELECT * FROM products ORDER BY created_at DESC LIMIT 8')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-panel"><div class="hero-copy">
        <div class="eyebrow">The new season edit · 2026</div>
        <h1>Wear your<br>own story.</h1>
        <p>Fresh silhouettes, considered details and the little things that make an outfit feel like you.</p>
        <a class="button" href="products.php">Explore the edit <span>→</span></a>
    </div></div>
</section>
<div class="banner-strip">
    <a class="mini-banner" href="products.php?category=Women"><div><strong>Find your new favourite</strong><span>Up to 50% off · Women's edit</span></div><em>↗</em></a>
    <a class="mini-banner" href="products.php?category=Men"><div><strong>Everyday, elevated</strong><span>Fresh fits for him</span></div><em>↗</em></a>
    <a class="mini-banner" href="products.php?category=Home"><div><strong>Make space for calm</strong><span>Little comforts, at home</span></div><em>↗</em></a>
</div>
<section class="section"><div class="container">
    <?php if ($previousVisit): ?><p class="last-visit">Welcome back — your last visit was <?= e($previousVisit) ?>.</p><?php endif; ?>
    <div class="section-heading"><div><div class="eyebrow">Loved right now</div><h2>Trending this week</h2><p>Pieces with a little something extra.</p></div><a class="text-link" href="products.php">View all products →</a></div>
    <div class="product-grid"><?php foreach ($products as $product): require __DIR__ . '/includes/product-card.php'; endforeach; ?></div>
</div></section>
<section class="section" style="background:#fbf9f8"><div class="container">
    <div class="section-heading"><div><div class="eyebrow">A little inspiration</div><h2>Shop by mood</h2></div></div>
    <div class="category-grid">
        <a class="category-card" style="background-image:url('https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=700&q=80')" href="products.php?category=Women"><strong>Women</strong><span>Find your expression →</span></a>
        <a class="category-card" style="background-image:url('https://images.unsplash.com/photo-1516257984-b1b4d707412e?auto=format&fit=crop&w=700&q=80')" href="products.php?category=Men"><strong>Men</strong><span>Made for every day →</span></a>
        <a class="category-card" style="background-image:url('https://images.unsplash.com/photo-1519457431-44ccd64a579b?auto=format&fit=crop&w=700&q=80')" href="products.php?category=Kids"><strong>Kids</strong><span>Small styles, big spirit →</span></a>
        <a class="category-card" style="background-image:url('https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=700&q=80')" href="products.php?category=Home"><strong>Home</strong><span>Make room for good →</span></a>
        <a class="category-card" style="background-image:url('https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?auto=format&fit=crop&w=700&q=80')" href="products.php?category=Beauty"><strong>Beauty</strong><span>Your daily ritual →</span></a>
    </div>
</div></section>
<section class="section"><div class="container">
    <div class="section-heading"><div><div class="eyebrow">Names to know</div><h2>In good company</h2></div><a class="text-link" href="products.php">Discover all brands →</a></div>
    <div class="brand-row"><div class="brand-tile">ROADSTER</div><div class="brand-tile">ONLY</div><div class="brand-tile">ADIDAS</div><div class="brand-tile">HOME CENTRE</div><div class="brand-tile">LAKMÉ</div></div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
