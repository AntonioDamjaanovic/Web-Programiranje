<?php
// index.php — application entry point.
// Starts the session (via auth.php) and forwards based on login state.
require __DIR__ . '/includes/auth.php';

$destination = is_logged_in() ? 'films.php' : 'login.php';
header('Location: ' . $destination);

// Fallback body in case the redirect header is not followed.
$pageTitle = 'Welcome';
require __DIR__ . '/includes/header.php';
?>
        <section class="content-section">
            <p>Redirecting&hellip; If nothing happens, <a href="<?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>">click here to continue</a>.</p>
        </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
