<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';

$admin = require_admin();

$pageTitle = 'Session Moderation';
$activePage = 'admin';
$extraCss = 'assets/css/admin.css';

// Filters.
$search = $_GET['q'] ?? '';
$search = is_string($search)
    ? substr(trim($search), 0, 120)
    : '';

$status = $_GET['status'] ?? 'all';

if (
    !is_string($status) ||
    !in_array(
        $status,
        ['all', 'open', 'full', 'cancelled'],
        true
    )
) {
    $status = 'all';
}

// Query sessions with host and member information.
$sql = "
    SELECT
        s.id,
        s.topic,
        s.venue,
        s.session_date,
        s.start_time,
        s.capacity,
        s.status,

        sub.code AS subject_code,
        sub.name AS subject_name,

        host.full_name AS host_name,

        (
            SELECT COUNT(*)
            FROM session_members m
            WHERE m.session_id = s.id
        ) AS member_count

    FROM study_sessions s

    JOIN subjects sub
      ON sub.id = s.subject_id

    JOIN users host
      ON host.id = s.host_id

    WHERE 1 = 1
";

$params = [];

if ($status !== 'all') {
    $sql .= ' AND s.status = ?';
    $params[] = $status;
}

if ($search !== '') {
    $sql .= "
        AND (
            s.topic LIKE ?
            OR host.full_name LIKE ?
            OR sub.code LIKE ?
            OR sub.name LIKE ?
        )
    ";

    $like = '%' . $search . '%';

    array_push(
        $params,
        $like,
        $like,
        $like,
        $like
    );
}

$sql .= "
    ORDER BY
        s.session_date DESC,
        s.start_time DESC,
        s.id DESC

    LIMIT 150
";

$statement = $pdo->prepare($sql);
$statement->execute($params);

$sessions = $statement->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="admin-hero">

    <div class="section-kicker">
        ADMINISTRATOR WORKSPACE
    </div>

    <h1 class="mt-3">
        Session moderation<span style="color:#ffe16a">.</span>
    </h1>

    <p>
        Review study sessions, inspect their hosts,
        and cancel sessions that violate campus rules.
    </p>

</section>

<nav class="admin-tabs" aria-label="Administrator pages">

    <a href="admin-dashboard.php">Overview</a>

    <a href="admin-subjects.php">Subjects</a>

    <a href="admin-sessions.php" class="active">
        Session moderation
    </a>

    <a href="admin-users.php">Users</a>

</nav>

<!-- SEARCH AND FILTER -->
<section class="admin-panel">

    <h2>
        Find a study session
    </h2>

    <form
        action="admin-sessions.php"
        method="get">

        <div class="row g-3 align-items-end">

            <div class="col-md-6">

                <label
                    for="searchSessions"
                    class="admin-label">
                    Search session, host, or subject
                </label>

                <input
                    id="searchSessions"
                    name="q"
                    class="form-control"
                    type="search"
                    maxlength="120"
                    value="<?= e($search) ?>"
                    placeholder="Search sessions">

            </div>

            <div class="col-md-3">

                <label
                    for="sessionStatus"
                    class="admin-label">
                    Status
                </label>

                <select
                    id="sessionStatus"
                    name="status"
                    class="form-select">

                    <?php
                    $statusOptions = [
                        'all' => 'All statuses',
                        'open' => 'Open',
                        'full' => 'Full',
                        'cancelled' => 'Cancelled'
                    ];
                    ?>

                    <?php foreach ($statusOptions as $key => $label): ?>

                        <option
                            value="<?= e($key) ?>"
                            <?= $status === $key ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="col-md-3 d-flex gap-2">

                <button
                    class="btn btn-brand flex-grow-1"
                    type="submit">
                    Search
                </button>

                <a
                    href="admin-sessions.php"
                    class="btn btn-outline-brand">
                    Clear
                </a>

            </div>

        </div>

    </form>

</section>

<!-- SESSIONS TABLE -->
<section class="admin-panel">

    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">

        <h2 class="mb-0">
            Study sessions
        </h2>

        <span class="session-count">
            <?= count($sessions) ?> shown
        </span>

    </div>

    <?php if (!$sessions): ?>

        <div class="admin-blank">

            <strong>
                No matching sessions found
            </strong>

            Try adjusting your search or status filter.

        </div>

    <?php else: ?>

        <div class="admin-table-wrap">

            <table class="admin-table w-100">

                <thead>
                    <tr>
                        <th>Session</th>
                        <th>Host</th>
                        <th>Schedule</th>
                        <th>Members</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($sessions as $session): ?>

                        <?php
                        $id = (int) $session['id'];

                        $statusColors = [
                            'open' => 'text-bg-success',
                            'full' => 'text-bg-warning',
                            'cancelled' => 'text-bg-secondary'
                        ];

                        $badge = $statusColors[
                            $session['status']
                        ] ?? 'text-bg-secondary';
                        ?>

                        <tr>

                            <td>

                                <div class="table-title">
                                    <?= e($session['topic']) ?>
                                </div>

                                <div class="table-subtitle">
                                    <?= e($session['subject_code']) ?>
                                    ·
                                    <?= e($session['venue']) ?>
                                </div>

                            </td>

                            <td>
                                <?= e($session['host_name']) ?>
                            </td>

                            <td>

                                <?= e($session['session_date']) ?>

                                <div class="table-subtitle">
                                    <?= e(substr(
                                        $session['start_time'],
                                        0,
                                        5
                                    )) ?>
                                </div>

                            </td>

                            <td>
                                <?= (int) $session['member_count'] ?>
                                /
                                <?= (int) $session['capacity'] ?>
                            </td>

                            <td>

                                <span class="badge <?= e($badge) ?>">
                                    <?= e(ucfirst($session['status'])) ?>
                                </span>

                            </td>

                            <td>

                                <?php if ($session['status'] === 'cancelled'): ?>

                                    <span class="text-secondary small">
                                        Removed
                                    </span>

                                <?php else: ?>

                                    <form
                                        action="admin-action.php"
                                        method="post"
                                        onsubmit="return confirm('Cancel this study session?');">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="session_cancel">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $id ?>">

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger">
                                            Remove
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>

<div class="admin-note">

    Removing a session marks it as cancelled.
    It is no longer displayed in public browsing,
    but its record remains for administration
    and existing participants' My Sessions history.

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
