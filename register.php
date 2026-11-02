<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) redirect('index.php');
$pageTitle = 'Create your account';
$errors = [];
$name = $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (!valid_name($name)) $errors['name'] = 'Use letters and spaces only (2–80 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (!valid_password($password)) $errors['password'] = 'Use 8+ characters with uppercase, lowercase and a digit.';
    if (!$errors) {
        try {
            $statement = db()->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)');
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['user_id' => (int) db()->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'customer'];
            flash('Your account is ready. Welcome to Myntra Studio!');
            redirect('index.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') $errors['email'] = 'An account already exists for this email.';
            else throw $exception;
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap"><div class="auth-card"><div class="eyebrow">Join the studio</div><h1>Create an account</h1><p>Save your favourites and keep your bag close.</p>
    <form method="post" data-validate-form novalidate><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-field"><label for="name">Your name</label><input id="name" name="name" value="<?= e($name) ?>" required autocomplete="name"><div class="field-error" data-error="name"><?= e($errors['name'] ?? '') ?></div></div>
        <div class="form-field"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?= e($email) ?>" required autocomplete="email"><div class="field-error" data-error="email"><?= e($errors['email'] ?? '') ?></div></div>
        <div class="form-field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="new-password"><div class="field-error" data-error="password"><?= e($errors['password'] ?? '') ?></div></div>
        <button class="button" type="submit" style="width:100%">Create account</button>
    </form><p class="form-note">Already part of the studio? <a href="login.php">Sign in</a></p>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
