<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';

// Only POST requests can log out an account.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    header('Allow: POST');

    exit('Method not allowed.');
}

// Validate security token.
verify_csrf();

// Clear session variables.
$_SESSION = [];

// Remove session cookie.
if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]
    );
}

// Destroy session.
session_destroy();

// Redirect to login.
redirect('login.php?logged_out=1');
