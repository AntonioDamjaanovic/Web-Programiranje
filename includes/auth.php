<?php

if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = [
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['user']['id']);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user']['role'] ?? null) === 'admin';
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Forbidden: admin access required.');
    }
}

function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'       => (int) $user['id'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'role'     => $user['role'],
    ];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
