<?php

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    header('Location: films.php');
    exit;
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 30) {
        $errors[] = 'Username must be 3–30 characters.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
        $errors[] = 'Username may only contain letters, numbers, dot, dash, underscore.';
    }

    $emailValid = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$emailValid) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 50) {
        $errors[] = 'Email is too long.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'A user with that username or email already exists.';
            }
        } catch (PDOException $e) {
            error_log('Register lookup failed: ' . $e->getMessage());
            $errors[] = 'Server error. Please try again later.';
        }
    }

    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$username, $email, $hash, 'user']);

            $user = [
                'id'       => (int) $pdo->lastInsertId(),
                'username' => $username,
                'email'    => $email,
                'role'     => 'user',
            ];
            login_user($user);
            header('Location: films.php');
            exit;
        } catch (PDOException $e) {
            error_log('Register insert failed: ' . $e->getMessage());
            $errors[] = 'Could not create account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Movie Database</title>
    <link rel="stylesheet" href="public/styles/style.css">
    <link rel="stylesheet" href="public/styles/auth.css">
</head>
<body>
    <header>
        <h1 class="header-title">Movie Database</h1>
    </header>

    <main>
        <section class="content-section auth-card">
            <h2>Create an account</h2>

            <?php if ($errors): ?>
                <ul class="auth-errors" role="alert">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="post" action="register.php" novalidate>
                <div class="form-row">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                           required minlength="3" maxlength="30"
                           autocomplete="username">
                </div>

                <div class="form-row">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                           required maxlength="50" autocomplete="email">
                </div>

                <div class="form-row">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                           required minlength="8" autocomplete="new-password">
                </div>

                <div class="form-row">
                    <label for="password_confirm">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm"
                           required minlength="8" autocomplete="new-password">
                </div>

                <button type="submit" class="btn-primary">Register</button>
            </form>

            <p class="auth-alt">Already have an account? <a href="login.php">Sign in</a></p>
        </section>
    </main>
</body>
</html>
