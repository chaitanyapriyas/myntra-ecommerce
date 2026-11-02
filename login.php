<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) redirect('index.php');
$pageTitle = 'Sign in';
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter a valid email address.';
    else {
        $statement = db()->prepare('SELECT user_id,name,email,password,role FROM users WHERE email = ?');
        $statement->execute([$email]);
        $user = $statement->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            unset($user['password']);
            $_SESSION['user'] = $user;
            flash('Welcome back, ' . $user['name'] . '.');
            redirect('index.php');
        }
        $error = 'That email and password combination was not recognised.';
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap"><div class="auth-card"><div class="eyebrow">Your style, saved</div><h1>Welcome back.</h1><p>Sign in to pick up right where you left off.</p>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" data-validate-form novalidate><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-field"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?= e($email) ?>" required autocomplete="email"><div class="field-error" data-error="email"></div></div>
        <div class="form-field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"><div class="field-error" data-error="password"></div></div>
        <button class="button" type="submit" style="width:100%">Sign in</button>
    </form><p class="form-note">New here? <a href="register.php">Create an account</a></p>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
