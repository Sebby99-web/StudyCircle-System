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

if (session_status() !== PHP_SESSION_ACTIVE) {

    session_name('SC_SYSTEM_SESSION');

    $https = !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off';

    // Local XAMPP uses a subfolder.
    // The live website uses the domain root.
    $scriptPath = (string) (
        $_SERVER['SCRIPT_NAME'] ?? ''
    );

    $cookiePath = str_starts_with(
        $scriptPath,
        '/StudyCircle-System/'
    )
        ? '/StudyCircle-System/'
        : '/';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
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

    /*
     * Supports both configurations:
     *
     * Local XAMPP:
     * database.php returns an array.
     *
     * InfinityFree:
     * database.php may create $pdo.
     */

    $dbSettings = require __DIR__
        . '/../config/database.php';

    if (is_array($dbSettings)) {

        // Local XAMPP configuration.
        $pdo = new PDO(
            'mysql:host=' . $dbSettings['host'] .
            ';dbname=' . $dbSettings['name'] .
            ';charset=utf8mb4',

            $dbSettings['user'],
            $dbSettings['password'],

            [
                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES =>
                    false,
            ]
        );

    } elseif ($dbSettings instanceof PDO) {

        // Configuration directly returned a PDO.
        $pdo = $dbSettings;
    }

    // Also supports database.php setting $pdo.
    if (
        !isset($pdo) ||
        !($pdo instanceof PDO)
    ) {
        throw new RuntimeException(
            'Database connection was not initialized.'
        );
    }

    // Philippine timezone for MySQL.
    $pdo->exec("SET time_zone = '+08:00'");

} catch (Throwable $exception) {

    error_log(
        'StudyCircle database error: ' .
        $exception->getMessage()
    );

    http_response_code(500);

    exit(
        'Database initialization failed. ' .
        'Please check the server error log.'
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

    $id = (int) (
        $_SESSION['user_id'] ?? 0
    );

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
        !hash_equals(
            csrf_token(),
            $submitted
        )
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

function redirect(
    string $destination
): never {

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


// ==========================================
// STRICT ROLE SEPARATION
// ==========================================

function enforce_study_role_boundary(): void
{
    $script = basename(
        (string) (
            $_SERVER['SCRIPT_NAME'] ?? ''
        )
    );

    // Student-only pages and actions.
    $studentOnlyPages = [
        'create-session.php',
        'edit-session.php',
        'my-sessions.php',
        'session-action.php',
        'download-calendar.php',
    ];

    if (
        $script !== 'index.php' &&
        !in_array(
            $script,
            $studentOnlyPages,
            true
        )
    ) {
        return;
    }

    $account = current_user();

    if (
        !$account ||
        $account['role'] !== 'admin'
    ) {
        return;
    }

    // Admins go to their own dashboard.
    if ($script === 'index.php') {

        redirect('admin-dashboard.php');
    }

    // Admins cannot perform student actions.
    http_response_code(403);

    exit(
        'Administrators cannot perform ' .
        'student session actions.'
    );
}


// Apply role restrictions.
enforce_study_role_boundary();
