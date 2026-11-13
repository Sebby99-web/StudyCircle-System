<?php
declare(strict_types=1);

/**
 * StudyCircle-System
 * Administrator Actions
 *
 * Handles:
 * - Add subject
 * - Edit subject
 * - Delete subject
 * - Cancel inappropriate session
 * - Update user role
 */

require __DIR__ . '/includes/app.php';

// Only POST requests can modify records.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

// Enforce administrator access.
$admin = require_admin();

// Enforce CSRF protection.
verify_csrf();

function admin_text(
    array $input,
    string $key
): string {
    $value = $input[$key] ?? '';

    return is_string($value)
        ? trim($value)
        : '';
}

function admin_id(array $input): int
{
    $id = filter_var(
        $input['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (!is_int($id) || $id < 1) {
        throw new DomainException(
            'Invalid record ID.'
        );
    }

    return $id;
}

function admin_validate_subject(array $input): array
{
    $code = strtoupper(
        admin_text($input, 'code')
    );

    $name = admin_text($input, 'name');

    if (
        !preg_match(
            '/^[A-Z0-9_-]{2,20}$/D',
            $code
        )
    ) {
        throw new DomainException(
            'Subject code must contain 2–20 letters, numbers, underscores, or hyphens.'
        );
    }

    $length = function_exists('mb_strlen')
        ? mb_strlen($name, 'UTF-8')
        : strlen($name);

    if ($length < 2 || $length > 120) {
        throw new DomainException(
            'Subject name must contain 2–120 characters.'
        );
    }

    return [$code, $name];
}

// Select an approved action and fixed destination.
// Never accept an arbitrary redirect URL.
$action = admin_text($_POST, 'action');

$destinations = [
    'subject_create' => 'admin-subjects.php',
    'subject_update' => 'admin-subjects.php',
    'subject_delete' => 'admin-subjects.php',
    'session_cancel' => 'admin-sessions.php',
    'role_update' => 'admin-users.php'
];

if (!isset($destinations[$action])) {
    flash('error', 'Unknown administrator action.');
    redirect('admin-dashboard.php');
}

$destination = $destinations[$action];

try {

    // ======================================
    // CREATE SUBJECT
    // ======================================

    if ($action === 'subject_create') {

        [$code, $name] = admin_validate_subject(
            $_POST
        );

        $statement = $pdo->prepare(
            'INSERT INTO subjects (code, name)
             VALUES (?, ?)'
        );

        $statement->execute([
            $code,
            $name
        ]);

        $success = 'Subject added successfully.';


    // ======================================
    // UPDATE SUBJECT
    // ======================================

    } elseif ($action === 'subject_update') {

        $id = admin_id($_POST);

        [$code, $name] = admin_validate_subject(
            $_POST
        );

        $check = $pdo->prepare(
            'SELECT 1 FROM subjects WHERE id = ?'
        );

        $check->execute([$id]);

        if (!$check->fetchColumn()) {
            throw new DomainException(
                'Subject not found.'
            );
        }

        $statement = $pdo->prepare(
            'UPDATE subjects
             SET code = ?, name = ?
             WHERE id = ?'
        );

        $statement->execute([
            $code,
            $name,
            $id
        ]);

        $success = 'Subject updated successfully.';


    // ======================================
    // DELETE SUBJECT
    // ======================================

    } elseif ($action === 'subject_delete') {

        $id = admin_id($_POST);

        // Foreign keys prevent deleting subjects
        // referenced by study sessions.
        $statement = $pdo->prepare(
            'DELETE FROM subjects
             WHERE id = ?'
        );

        $statement->execute([$id]);

        if ($statement->rowCount() === 0) {
            throw new DomainException(
                'Subject not found.'
            );
        }

        $success = 'Subject deleted successfully.';


    // ======================================
    // CANCEL SESSION
    // ======================================

    } elseif ($action === 'session_cancel') {

        $id = admin_id($_POST);

        // Keep the session record for moderation history.
        $statement = $pdo->prepare(
            "UPDATE study_sessions
             SET status = 'cancelled'
             WHERE id = ?
               AND status <> 'cancelled'"
        );

        $statement->execute([$id]);

        if ($statement->rowCount() === 0) {
            throw new DomainException(
                'Session not found or already cancelled.'
            );
        }

        $success =
            'Study session removed from public browsing.';


    // ======================================
    // UPDATE USER ROLE
    // ======================================

    } elseif ($action === 'role_update') {

        $id = admin_id($_POST);

        $newRole = admin_text(
            $_POST,
            'role'
        );

        if (!in_array(
            $newRole,
            ['student', 'admin'],
            true
        )) {
            throw new DomainException(
                'Invalid account role.'
            );
        }

        // Prevent changing your own role.
        if ($id === (int) $admin['id']) {
            throw new DomainException(
                'You cannot change your own role.'
            );
        }

        $pdo->beginTransaction();

        // Lock administrator records so two requests
        // cannot simultaneously remove the last admin.
        $adminLock = $pdo->query(
            "SELECT id
             FROM users
             WHERE role = 'admin'
             FOR UPDATE"
        );

        $adminIds = $adminLock->fetchAll(
            PDO::FETCH_COLUMN
        );

        $find = $pdo->prepare(
            'SELECT role
             FROM users
             WHERE id = ?
             FOR UPDATE'
        );

        $find->execute([$id]);

        $account = $find->fetch();

        if (!$account) {
            throw new DomainException(
                'User account not found.'
            );
        }

        if (
            $account['role'] === 'admin' &&
            $newRole === 'student' &&
            count($adminIds) <= 1
        ) {
            throw new DomainException(
                'At least one administrator must remain.'
            );
        }

        $statement = $pdo->prepare(
            'UPDATE users
             SET role = ?
             WHERE id = ?'
        );

        $statement->execute([
            $newRole,
            $id
        ]);

        $pdo->commit();

        $success = 'User role updated successfully.';
    }

    flash('success', $success);

} catch (DomainException $exception) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash(
        'error',
        $exception->getMessage()
    );

} catch (PDOException $exception) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'StudyCircle admin database error: ' .
        $exception->getMessage()
    );

    if (
        $exception->getCode() === '23000' &&
        $action === 'subject_delete'
    ) {

        flash(
            'error',
            'Cannot delete a subject that is referenced by existing sessions.'
        );

    } elseif (
        $exception->getCode() === '23000' &&
        in_array(
            $action,
            ['subject_create', 'subject_update'],
            true
        )
    ) {

        flash(
            'error',
            'That subject code already exists.'
        );

    } else {

        flash(
            'error',
            'Database operation failed. Please try again.'
        );
    }

} catch (Throwable $exception) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'StudyCircle admin error: ' .
        $exception->getMessage()
    );

    flash(
        'error',
        'Could not complete the administrator action.'
    );
}

redirect($destination);
