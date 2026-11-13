<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/session-service.php';

$user = require_login();

$id = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false) {
    http_response_code(404);
    exit('Study session not found.');
}

$find = $pdo->prepare(
    'SELECT * FROM study_sessions WHERE id = ?'
);

$find->execute([$id]);
$session = $find->fetch();

if (!$session) {
    http_response_code(404);
    exit('Study session not found.');
}

if ((int) $session['host_id'] !== (int) $user['id']) {
    http_response_code(403);
    exit('Only the host can edit this session.');
}

if (
    $session['status'] === 'cancelled' ||
    sc_session_started($session)
) {
    flash(
        'error',
        'Cancelled or started sessions cannot be edited.'
    );

    redirect('my-sessions.php');
}

$values = sc_form_values($session);

$subjects = $pdo->query(
    'SELECT id, code, name FROM subjects ORDER BY code'
)->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $values = sc_form_values($_POST);

    try {
        $data = sc_validate_session($pdo, $_POST);

        $pdo->beginTransaction();

        // Recheck permissions under a database lock.
        $lock = $pdo->prepare(
            'SELECT host_id, status, session_date, start_time
             FROM study_sessions
             WHERE id = ?
             FOR UPDATE'
        );

        $lock->execute([$id]);
        $locked = $lock->fetch();

        if (
            !$locked ||
            (int) $locked['host_id'] !== (int) $user['id']
        ) {
            throw new DomainException(
                'You cannot edit this session.'
            );
        }

        if (
            $locked['status'] === 'cancelled' ||
            sc_session_started($locked)
        ) {
            throw new DomainException(
                'This session can no longer be edited.'
            );
        }

        $countStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM session_members
             WHERE session_id = ?'
        );

        $countStmt->execute([$id]);

        $memberCount =
            (int) $countStmt->fetchColumn();

        if ($memberCount > $data['capacity']) {
            throw new DomainException(
                'Capacity cannot be lower than the number of joined students.'
            );
        }

        $status = $memberCount >= $data['capacity']
            ? 'full'
            : 'open';

        $update = $pdo->prepare(
            'UPDATE study_sessions
             SET
                subject_id = ?,
                topic = ?,
                venue = ?,
                session_date = ?,
                start_time = ?,
                end_time = ?,
                capacity = ?,
                status = ?
             WHERE id = ?'
        );

        $update->execute([
            $data['subject_id'],
            $data['topic'],
            $data['venue'],
            $data['session_date'],
            $data['start_time'],
            $data['end_time'],
            $data['capacity'],
            $status,
            $id
        ]);

        $pdo->commit();

        flash(
            'success',
            'Study session updated successfully.'
        );

        redirect('my-sessions.php');

    } catch (DomainException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'StudyCircle edit-session: ' .
            $e->getMessage()
        );

        $error =
            'Could not update the session. Please try again.';
    }
}

$pageTitle = 'Edit study session';
$activePage = 'mine';

$formAction = 'edit-session.php?id=' . $id;
$submitLabel = 'Save changes';

require __DIR__ . '/includes/header.php';
?>

<section class="mb-4">

    <span class="section-kicker">
        YOUR HOSTED SESSION
    </span>

    <h1 class="fw-bold mt-2">
        Edit study session<span class="heading-dot">.</span>
    </h1>

    <p class="text-secondary">
        Update the details. The new capacity cannot
        be less than students already joined.
    </p>

</section>

<div class="row g-4">

    <div class="col-lg-8">

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php
        require __DIR__ . '/includes/session-form.php';
        ?>

    </div>

    <aside class="col-lg-4">

        <div class="session-card">

            <h2 class="h5 fw-bold">
                Changes affect everyone
            </h2>

            <p class="session-subject mb-0">
                Students who have joined will see the
                updated date, time, venue, and topic
                in My Sessions.
            </p>

        </div>

    </aside>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
