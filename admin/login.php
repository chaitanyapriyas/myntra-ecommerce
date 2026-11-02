<?php
require_once __DIR__ . '/../includes/functions.php';
if (current_user() && current_user()['role'] === 'admin') redirect('dashboard.php');
$pageTitle = 'Admin sign in';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $statement = db()->prepare("SELECT user_id,name,email,password,role FROM users WHERE email=? AND role='admin'");
    $statement->execute([$email]);
    $user = $statement->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
        redirect('dashboard.php');
    }
    $error = 'Administrator credentials were not recognised.';
}
require __DIR__ . '/../includes/header.php';
?>
<section class="auth-wrap"><div class="auth-card"><div class="eyebrow">Studio tools</div><h1>Administrator sign in</h1><p>Manage products and stock for the demo shop.</p>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-field"><label for="email">Admin email</label><input id="email" name="email" type="email" required autocomplete="username"></div>
        <div class="form-field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
        <button class="button" type="submit" style="width:100%">Sign in to dashboard</button>
    </form><p class="form-note">Demo credentials are in the README. This admin panel is for the local educational project.</p>
</div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
