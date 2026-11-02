<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'Myntra Studio';
$activePage = basename($_SERVER['PHP_SELF'] ?? '');
$rootPrefix = str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/') ? '../' : '';
$notice = flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Myntra Studio — an educational fashion shopping replica.">
    <title><?= e($pageTitle) ?> | Myntra Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $rootPrefix ?>css/style.css">
</head>
<body>
<div class="top-note">A fashion-forward classroom project <span>·</span> Free shipping on orders over ₹1,499</div>
<header class="site-header">
    <a class="brand-mark" href="<?= $rootPrefix ?>index.php" aria-label="Myntra Studio home"><span class="brand-glyph">M</span><span>MYNTRA<span class="brand-light">STUDIO</span></span></a>
    <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false">☰</button>
    <nav class="main-nav" aria-label="Main navigation">
        <a href="<?= $rootPrefix ?>products.php?category=Men">Men</a><a href="<?= $rootPrefix ?>products.php?category=Women">Women</a>
        <a href="<?= $rootPrefix ?>products.php?category=Kids">Kids</a><a href="<?= $rootPrefix ?>products.php?category=Home">Home</a>
        <a href="<?= $rootPrefix ?>products.php?category=Beauty">Beauty</a><a class="nav-sale" href="<?= $rootPrefix ?>products.php?sort=discount">Studio Edit</a>
    </nav>
    <form class="header-search" action="<?= $rootPrefix ?>products.php" method="get" role="search">
        <button type="submit" aria-label="Search">⌕</button><input name="q" type="search" placeholder="Search for products, brands and more" value="<?= e($_GET['q'] ?? '') ?>">
    </form>
    <div class="header-actions">
        <?php if (current_user()): ?>
            <a href="<?= $rootPrefix ?>wishlist.php"><span>♡</span><small>Wishlist</small></a>
            <a href="<?= $rootPrefix ?>cart.php" class="bag-link"><span>♧<i><?= cart_count() ?></i></span><small>Bag</small></a>
            <a href="<?= $rootPrefix ?>logout.php"><span>↪</span><small>Logout</small></a>
        <?php else: ?>
            <a href="<?= $rootPrefix ?>login.php"><span>♙</span><small>Sign in</small></a>
            <a href="<?= $rootPrefix ?>cart.php"><span>♧</span><small>Bag</small></a>
        <?php endif; ?>
    </div>
</header>
<?php if ($notice): ?><div class="flash flash-<?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endif; ?>
<main>
