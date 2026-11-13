<?php
declare(strict_types=1);

/**
 * StudyCircle System
 * Shared application initialization
 */

date_default_timezone_set('Asia/Manila');

ini_set('session.use_strict_mode', '1');

// ==========================================
// SESSION CONFIGURATION
// ==========================================

// Separate session from the original project.
if (session_status() !== PHP_SESSION_ACTIVE) {

    session_name('SC_SYSTEM_SESSION');

    $https = !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/StudyCircle-System/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}


// ==========================================
// DATABASE CONNECTION
// ==========================================

try {

    $db = require __DIR__ . '/../config/database.php';

    $pdo = new PDO(
        'mysql:host=' . $db['host'] .
        ';dbname=' . $db['name'] .
        ';charset=utf8mb4',

        $db['user'],
        $db['password'],

        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Keep database time consistent with Manila.
    $pdo->exec("SET time_zone = '+08:00'");

} catch (Throwable $exception) {

    error_log(
        'StudyCircle System database error: ' .
        $exception->getMessage()
    );

    http_response_code(500);

    exit(
        'Database connection failed. ' .
        'Check MySQL and config/database.php.'
    );
}


// ==========================================
// HTML ESCAPING
// ==========================================

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


// ==========================================
// CURRENT USER
// ==========================================

function current_user(): ?array
{
    global $pdo;

    $id = (int) ($_SESSION['user_id'] ?? 0);

    if ($id < 1) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT
            id,
            full_name,
            email,
            role
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    $statement->execute([$id]);

    $account = $statement->fetch();

    if (!$account) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $account;
}


// ==========================================
// LOGIN PROTECTION
// ==========================================

function require_login(): array
{
    $user = current_user();

    if (!$user) {
        redirect('login.php');
    }

    return $user;
}


// ==========================================
// ADMIN PROTECTION
// ==========================================

function require_admin(): array
{
    $user = require_login();

    if ($user['role'] !== 'admin') {
        http_response_code(403);

        exit('Administrator access required.');
    }

    return $user;
}


// ==========================================
// CSRF PROTECTION
// ==========================================

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {

        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submitted) ||
        !hash_equals(csrf_token(), $submitted)
    ) {
        http_response_code(419);

        exit(
            'Security token expired. ' .
            'Refresh the page and try again.'
        );
    }
}


// ==========================================
// REDIRECT HELPER
// ==========================================

function redirect(string $destination): never
{
    header(
        'Location: ' . $destination,
        true,
        303
    );

    exit;
}


// ==========================================
// FLASH MESSAGES
// ==========================================

function flash(
    string $type,
    ?string $message = null
): ?string {

    if ($message !== null) {

        $_SESSION['flash'][$type] = $message;

        return null;
    }

    $value = $_SESSION['flash'][$type] ?? null;

    unset($_SESSION['flash'][$type]);

    return is_string($value)
        ? $value
        : null;
}
