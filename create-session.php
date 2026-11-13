<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/session-service.php';

$user = require_login();

$subjects = $pdo->query(
    'SELECT id, code, name FROM subjects ORDER BY code'
)->fetchAll();

$values = sc_form_values();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $values = sc_form_values($_POST);

    try {
        $data = sc_validate_session($pdo, $_POST);

        $stmt = $pdo->prepare(
            "INSERT INTO study_sessions
                (subject_id, host_id, topic, venue,
                 session_date, start_time, end_time,
                 capacity, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')"
        );

        $stmt->execute([
            $data['subject_id'],
            (int) $user['id'],
            $data['topic'],
            $data['venue'],
            $data['session_date'],
            $data['start_time'],
            $data['end_time'],
            $data['capacity']
        ]);

        flash(
            'success',
            'Your study session has been created.'
        );

        redirect('my-sessions.php');

    } catch (DomainException $e) {
        $error = $e->getMessage();

    } catch (Throwable $e) {
        error_log(
            'StudyCircle create-session: ' .
            $e->getMessage()
        );

        $error =
            'Could not save the session. Please try again.';
    }
}

$pageTitle = 'Host a study session';
$activePage = 'host';
$formAction = 'create-session.php';
$submitLabel = 'Create study session';

require __DIR__ . '/includes/header.php';
?>

<section class="mb-4">

    <span class="section-kicker">
        HOST A SESSION
    </span>

    <h1 class="fw-bold mt-2">
        Bring your study circle together<span class="heading-dot">.</span>
    </h1>

    <p class="text-secondary">
        Choose a subject, meeting place, time,
        and number of available student seats.
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

            <span class="section-kicker">
                HOST TIPS
            </span>

            <h2 class="h5 fw-bold mt-3">
                Make it easy to join
            </h2>

            <p class="session-subject">
                Use a specific topic and a clear campus
                location. Start times must be in the future.
            </p>

            <p class="session-subject mb-0">
                You will appear as the host; the available
                seats are for classmates joining you.
            </p>

        </div>

    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
