<?php
// login.php — user login form and handler.
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    header('Location: films.php');
    exit;
}

$errors = [];
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $errors[] = 'Please enter your username/email and password.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, username, email, password_hash, role
                 FROM users
                 WHERE username = ? OR email = ?
                 LIMIT 1'
            );
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $upd->execute([$newHash, $user['id']]);
                }
                login_user($user);
                header('Location: films.php');
                exit;
            }

            $errors[] = 'Invalid credentials.';
        } catch (PDOException $e) {
            error_log('Login query failed: ' . $e->getMessage());
            $errors[] = 'Server error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — Movie Database</title>
    <link rel="stylesheet" href="public/styles/style.css">
    <link rel="stylesheet" href="public/styles/auth.css">
</head>
<body>
    <header>
        <h1 class="header-title">Movie Database</h1>
    </header>

    <main>
        <section class="content-section auth-card">
            <h2>Sign in</h2>

            <?php if ($errors): ?>
                <ul class="auth-errors" role="alert">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="post" action="login.php" novalidate>
                <div class="form-row">
                    <label for="identifier">Username or email</label>
                    <input type="text" id="identifier" name="identifier"
                           value="<?= htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8') ?>"
                           required autocomplete="username">
                </div>

                <div class="form-row">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                           required autocomplete="current-password">
                </div>

                <button type="submit" class="btn-primary">Sign in</button>
            </form>

            <p class="auth-alt">No account yet? <a href="register.php">Create one</a></p>
        </section>
    </main>
</body>
</html>
