<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Your form session expired. Go back, refresh the page, and try again.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        flash('Please sign in to continue.', 'info');
        redirect('login.php');
    }
}

function require_admin(): void
{
    if (!current_user() || (current_user()['role'] ?? '') !== 'admin') {
        flash('Administrator access is required.', 'error');
        redirect('admin/login.php');
    }
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $stored = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $stored;
}

function product_price(array $product): float
{
    return round((float) $product['price'] * (100 - (int) $product['discount']) / 100, 2);
}

function get_product(int $id): ?array
{
    $statement = db()->prepare('SELECT * FROM products WHERE product_id = ?');
    $statement->execute([$id]);
    $product = $statement->fetch();
    return $product ?: null;
}

function cart_count(): int
{
    if (!current_user()) {
        return 0;
    }
    $statement = db()->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?');
    $statement->execute([current_user()['user_id']]);
    return (int) $statement->fetchColumn();
}

function set_last_visit_cookie(): ?string
{
    $previous = $_COOKIE['last_visit'] ?? null;
    setcookie('last_visit', date(DATE_ATOM), [
        'expires' => time() + 31536000,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    return $previous ? date('M j, Y g:i A', strtotime($previous)) : null;
}

function valid_name(string $name): bool
{
    return (bool) preg_match("/^[\p{L} ]{2,80}$/u", trim($name));
}

function valid_password(string $password): bool
{
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

function valid_pin(string $pin): bool
{
    return (bool) preg_match('/^\d{6}$/', $pin);
}

function cart_rows(int $userId): array
{
    $statement = db()->prepare(
        'SELECT c.cart_id, c.quantity, c.size, p.* FROM cart c
         JOIN products p ON p.product_id = c.product_id WHERE c.user_id = ? ORDER BY c.cart_id DESC'
    );
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

function cart_totals(array $rows, string $coupon = ''): array
{
    $subtotal = 0.0;
    $quantity = 0;
    foreach ($rows as $row) {
        $subtotal += product_price($row) * (int) $row['quantity'];
        $quantity += (int) $row['quantity'];
    }
    $discount = strtoupper(trim($coupon)) === 'FASHION10' ? round($subtotal * 0.10, 2) : 0.0;
    return [
        'subtotal' => round($subtotal, 2),
        'quantity' => $quantity,
        'discount' => $discount,
        'total' => round($subtotal - $discount, 2),
    ];
}
