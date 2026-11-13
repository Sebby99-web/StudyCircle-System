<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/session-service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$user = require_login();
verify_csrf();

$action = sc_field($_POST, 'action');

$id = filter_var(
    $_POST['session_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$returnTo = sc_field($_POST, 'return_to');

if (!in_array(
    $returnTo,
    ['sessions.php', 'my-sessions.php'],
    true
)) {
    $returnTo = 'sessions.php';
}

if (
    $id === false ||
    !in_array(
        $action,
        ['join', 'leave', 'cancel'],
        true
    )
) {
    flash('error', 'Invalid session request.');
    redirect($returnTo);
}

try {
    $pdo->beginTransaction();

    // Lock the session during membership changes.
    $lock = $pdo->prepare(
        'SELECT *
         FROM study_sessions
         WHERE id = ?
         FOR UPDATE'
    );

    $lock->execute([$id]);

    $session = $lock->fetch();

    if (!$session) {
        throw new DomainException(
            'Session no longer exists.'
        );
    }

    // ======================================
    // JOIN SESSION
    // ======================================

    if ($action === 'join') {

        if (
            $session['status'] === 'cancelled' ||
            sc_session_started($session)
        ) {
            throw new DomainException(
                'This session is no longer accepting members.'
            );
        }

        if (
            (int) $session['host_id'] ===
            (int) $user['id']
        ) {
            throw new DomainException(
                'You are already hosting this session.'
            );
        }

        // Prevent duplicate membership.
        $memberCheck = $pdo->prepare(
            'SELECT 1
             FROM session_members
             WHERE session_id = ?
               AND user_id = ?'
        );

        $memberCheck->execute([
            $id,
            (int) $user['id']
        ]);

        if ($memberCheck->fetchColumn()) {
            throw new DomainException(
                'You already joined this session.'
            );
        }

        // Check capacity while the session is locked.
        $countStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM session_members
             WHERE session_id = ?'
        );

        $countStmt->execute([$id]);

        $count = (int) $countStmt->fetchColumn();

        if ($count >= (int) $session['capacity']) {
            throw new DomainException(
                'This session is full.'
            );
        }

        $insert = $pdo->prepare(
            'INSERT INTO session_members
                (session_id, user_id)
             VALUES (?, ?)'
        );

        $insert->execute([
            $id,
            (int) $user['id']
        ]);

        $count++;

        $status = $count >= (int) $session['capacity']
            ? 'full'
            : 'open';

        $pdo->prepare(
            'UPDATE study_sessions
             SET status = ?
             WHERE id = ?'
        )->execute([$status, $id]);

        $success =
            'You joined the session successfully.';

    // ======================================
    // LEAVE SESSION
    // ======================================

    } elseif ($action === 'leave') {

        $delete = $pdo->prepare(
            'DELETE FROM session_members
             WHERE session_id = ?
               AND user_id = ?'
        );

        $delete->execute([
            $id,
            (int) $user['id']
        ]);

        if ($delete->rowCount() === 0) {
            throw new DomainException(
                'You are not currently a member of this session.'
            );
        }

        // Never reopen a cancelled session.
        if ($session['status'] !== 'cancelled') {

            $countStmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM session_members
                 WHERE session_id = ?'
            );

            $countStmt->execute([$id]);

            $count = (int) $countStmt->fetchColumn();

            $status = $count >= (int) $session['capacity']
                ? 'full'
                : 'open';

            $pdo->prepare(
                'UPDATE study_sessions
                 SET status = ?
                 WHERE id = ?'
            )->execute([$status, $id]);
        }

        $success = 'You left the session.';

    // ======================================
    // CANCEL SESSION
    // ======================================

    } else {

        $isHost =
            (int) $session['host_id'] ===
            (int) $user['id'];

        $isAdmin = $user['role'] === 'admin';

        if (!$isHost && !$isAdmin) {
            throw new DomainException(
                'Only the host or an administrator can cancel this session.'
            );
        }

        if ($session['status'] === 'cancelled') {
            throw new DomainException(
                'This session has already been cancelled.'
            );
        }

        if (
            !$isAdmin &&
            sc_session_started($session)
        ) {
            throw new DomainException(
                'A started session cannot be cancelled by its host.'
            );
        }

        $pdo->prepare(
            "UPDATE study_sessions
             SET status = 'cancelled'
             WHERE id = ?"
        )->execute([$id]);

        $success = 'Session cancelled.';
    }

    $pdo->commit();

    flash('success', $success);

} catch (DomainException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash('error', $e->getMessage());

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'StudyCircle session-action: ' .
        $e->getMessage()
    );

    flash(
        'error',
        'Could not complete the request. Please try again.'
    );
}

redirect($returnTo);
