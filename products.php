<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Shop the collection';
$categories = ['Men','Women','Kids','Home','Beauty'];
$brands = db()->query('SELECT DISTINCT brand FROM products ORDER BY brand')->fetchAll(PDO::FETCH_COLUMN);
$where = [];
$params = [];
$query = trim((string) ($_GET['q'] ?? ''));
$category = (string) ($_GET['category'] ?? '');
$brand = (string) ($_GET['brand'] ?? '');
$min = filter_var($_GET['min'] ?? '', FILTER_VALIDATE_FLOAT);
$max = filter_var($_GET['max'] ?? '', FILTER_VALIDATE_FLOAT);
if ($query !== '') { $where[] = '(name LIKE ? OR brand LIKE ?)'; $params[] = '%' . $query . '%'; $params[] = '%' . $query . '%'; }
if (in_array($category, $categories, true)) { $where[] = 'category = ?'; $params[] = $category; }
if (in_array($brand, $brands, true)) { $where[] = 'brand = ?'; $params[] = $brand; }
if ($min !== false && $min !== null) { $where[] = 'price * (100 - discount) / 100 >= ?'; $params[] = $min; }
if ($max !== false && $max !== null) { $where[] = 'price * (100 - discount) / 100 <= ?'; $params[] = $max; }
$sorts = ['price-low' => 'price * (100 - discount) ASC', 'price-high' => 'price * (100 - discount) DESC', 'discount' => 'discount DESC', 'newest' => 'created_at DESC'];
$sort = (string) ($_GET['sort'] ?? 'newest');
$order = $sorts[$sort] ?? $sorts['newest'];
$sql = 'SELECT * FROM products' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY {$order}";
$statement = db()->prepare($sql);
$statement->execute($params);
$products = $statement->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="page-intro"><div class="eyebrow">The collection</div><h1>Find your next favourite.</h1><p>Thoughtful pieces for every version of you.</p></div>
    <div class="catalog-layout">
        <aside class="filters">
            <form method="get" data-live-filter>
                <div class="filter-group"><h3>Search</h3><input type="search" name="q" placeholder="Product or brand" value="<?= e($query) ?>"></div>
                <div class="filter-group"><h3>Category</h3>
                    <select name="category"><option value="">All categories</option><?php foreach ($categories as $item): ?><option value="<?= e($item) ?>" <?= $category === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select>
                </div>
                <div class="filter-group"><h3>Brand</h3>
                    <select name="brand"><option value="">All brands</option><?php foreach ($brands as $item): ?><option value="<?= e($item) ?>" <?= $brand === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select>
                </div>
                <div class="filter-group"><h3>Price range (₹)</h3><input type="number" name="min" min="0" placeholder="Min price" value="<?= e($_GET['min'] ?? '') ?>"><input type="number" name="max" min="0" placeholder="Max price" value="<?= e($_GET['max'] ?? '') ?>" style="margin-top:8px"></div>
                <div class="filter-group"><h3>Sort by</h3><select name="sort"><option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Recommended</option><option value="price-low" <?= $sort === 'price-low' ? 'selected' : '' ?>>Price: low to high</option><option value="price-high" <?= $sort === 'price-high' ? 'selected' : '' ?>>Price: high to low</option><option value="discount" <?= $sort === 'discount' ? 'selected' : '' ?>>Best discount</option></select></div>
                <button class="button button-small" type="submit">Apply filters</button>
            </form>
        </aside>
        <section>
            <div class="catalog-toolbar"><p><strong><?= count($products) ?></strong> styles to explore</p><span style="font-size:12px;color:#777">All prices in INR</span></div>
            <?php if ($products): ?><div class="product-grid"><?php foreach ($products as $product): require __DIR__ . '/includes/product-card.php'; endforeach; ?></div>
            <?php else: ?><div class="empty-state"><div class="empty-icon">⌕</div><h2>No styles found</h2><p>Try changing a filter or search term.</p><a class="button" href="products.php">Clear filters</a></div><?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
