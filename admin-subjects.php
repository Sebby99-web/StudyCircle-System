<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';

$admin = require_admin();

$pageTitle = 'Manage Subjects';
$activePage = 'admin';
$extraCss = 'assets/css/admin.css';

// Retrieve subjects with their session totals.
$subjects = $pdo->query(
    "SELECT
        sub.id,
        sub.code,
        sub.name,
        COUNT(s.id) AS session_count

     FROM subjects sub

     LEFT JOIN study_sessions s
       ON s.subject_id = sub.id

     GROUP BY
        sub.id,
        sub.code,
        sub.name

     ORDER BY sub.code"
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="admin-hero">

    <div class="section-kicker">
        ADMINISTRATOR WORKSPACE
    </div>

    <h1 class="mt-3">
        Subject management<span style="color:#ffe16a">.</span>
    </h1>

    <p>
        Organize the subjects that students can
        choose when creating study sessions.
    </p>

</section>

<nav class="admin-tabs" aria-label="Administrator pages">

    <a href="admin-dashboard.php">Overview</a>

    <a href="admin-subjects.php" class="active">
        Subjects
    </a>

    <a href="admin-sessions.php">
        Session moderation
    </a>

    <a href="admin-users.php">Users</a>

</nav>

<!-- ADD NEW SUBJECT -->
<section class="admin-panel">

    <span class="section-kicker">
        ADD SUBJECT
    </span>

    <h2 class="mt-2">
        Create a new subject
    </h2>

    <p class="admin-description">
        Students will see this subject in the
        Host a Session form.
    </p>

    <form action="admin-action.php" method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>">

        <input
            type="hidden"
            name="action"
            value="subject_create">

        <div class="row g-3 align-items-end">

            <div class="col-md-3">

                <label
                    for="newCode"
                    class="admin-label">
                    Subject code
                </label>

                <input
                    id="newCode"
                    class="form-control"
                    type="text"
                    name="code"
                    maxlength="20"
                    placeholder="e.g. CS201"
                    pattern="[A-Za-z0-9_-]{2,20}"
                    required>

            </div>

            <div class="col-md-6">

                <label
                    for="newName"
                    class="admin-label">
                    Subject name
                </label>

                <input
                    id="newName"
                    class="form-control"
                    type="text"
                    name="name"
                    maxlength="120"
                    placeholder="e.g. Data Structures and Algorithms"
                    required>

            </div>

            <div class="col-md-3">

                <button
                    class="btn btn-brand w-100"
                    type="submit">
                    + Add Subject
                </button>

            </div>

        </div>

    </form>

</section>

<!-- EXISTING SUBJECTS -->
<section>

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <span class="section-kicker">
                SUBJECT DIRECTORY
            </span>

            <h2 class="h4 fw-bold mt-2">
                Existing subjects
            </h2>

        </div>

        <span class="session-count">
            <?= count($subjects) ?> subjects
        </span>

    </div>

    <?php if (!$subjects): ?>

        <div class="admin-panel admin-blank">

            <strong>
                No subjects available
            </strong>

            Add your first subject above.

        </div>

    <?php endif; ?>

    <?php foreach ($subjects as $subject): ?>

        <?php
        $subjectId = (int) $subject['id'];
        $sessionCount = (int) $subject['session_count'];
        ?>

        <article class="admin-subject-item">

            <form
                action="admin-action.php"
                method="post">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>">

                <input
                    type="hidden"
                    name="action"
                    value="subject_update">

                <input
                    type="hidden"
                    name="id"
                    value="<?= $subjectId ?>">

                <div class="row g-3 align-items-end">

                    <div class="col-lg-3">

                        <label
                            for="code<?= $subjectId ?>"
                            class="admin-label">
                            Subject code
                        </label>

                        <input
                            id="code<?= $subjectId ?>"
                            name="code"
                            class="form-control"
                            type="text"
                            maxlength="20"
                            pattern="[A-Za-z0-9_-]{2,20}"
                            value="<?= e($subject['code']) ?>"
                            required>

                    </div>

                    <div class="col-lg-6">

                        <label
                            for="name<?= $subjectId ?>"
                            class="admin-label">
                            Subject name
                        </label>

                        <input
                            id="name<?= $subjectId ?>"
                            name="name"
                            class="form-control"
                            type="text"
                            maxlength="120"
                            value="<?= e($subject['name']) ?>"
                            required>

                    </div>

                    <div class="col-lg-3">

                        <button
                            class="btn btn-outline-brand w-100"
                            type="submit">
                            Save changes
                        </button>

                    </div>

                </div>

            </form>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-3 border-top">

                <span class="admin-description mb-0">
                    <?= $sessionCount ?>
                    study session(s) reference this subject
                </span>

                <form
                    action="admin-action.php"
                    method="post"
                    class="m-0"
                    onsubmit="return confirm('Delete this subject?');">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>">

                    <input
                        type="hidden"
                        name="action"
                        value="subject_delete">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= $subjectId ?>">

                    <button
                        class="btn btn-outline-danger btn-sm"
                        type="submit"
                        <?= $sessionCount > 0 ? 'disabled' : '' ?>>

                        Delete subject

                    </button>

                </form>

            </div>

        </article>

    <?php endforeach; ?>

</section>

<div class="admin-note mt-3">

    Subjects used by existing sessions cannot be
    deleted, even when those sessions are cancelled.
    This protects the database relationships.

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
