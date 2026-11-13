<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/session-service.php';

$viewer = require_login();

$statement = $pdo->prepare(
    "SELECT
        s.*,
        sub.code AS subject_code,
        sub.name AS subject_name,
        host.full_name AS host_name,

        (
            SELECT COUNT(*)
            FROM session_members m
            WHERE m.session_id = s.id
        ) AS member_count,

        EXISTS(
            SELECT 1
            FROM session_members m
            WHERE m.session_id = s.id
              AND m.user_id = ?
        ) AS joined

     FROM study_sessions s

     JOIN subjects sub
       ON sub.id = s.subject_id

     JOIN users host
       ON host.id = s.host_id

     WHERE s.host_id = ?

        OR EXISTS(
            SELECT 1
            FROM session_members own
            WHERE own.session_id = s.id
              AND own.user_id = ?
        )

     ORDER BY
         s.session_date DESC,
         s.start_time DESC,
         s.id DESC

     LIMIT 200"
);

$statement->execute([
    (int) $viewer['id'],
    (int) $viewer['id'],
    (int) $viewer['id']
]);

$sessions = $statement->fetchAll();

$isMine = true;

$pageTitle = 'My study sessions';
$activePage = 'mine';

require __DIR__ . '/includes/header.php';
?>

<section class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">

    <div>

        <span class="section-kicker">
            YOUR STUDY CIRCLE
        </span>

        <h1 class="fw-bold mt-2">
            My sessions<span class="heading-dot">.</span>
        </h1>

        <p class="text-secondary mb-0">
            Your hosted and joined sessions,
            including past and cancelled sessions.
        </p>

    </div>

    <a
        href="create-session.php"
        class="btn btn-brand">
        + Host a session
    </a>

</section>

<p class="session-count d-inline-block mb-4">
    <?= count($sessions) ?> session(s) shown
</p>

<?php
require __DIR__ . '/includes/session-card.php';
?>

<?php require __DIR__ . '/includes/footer.php'; ?>
