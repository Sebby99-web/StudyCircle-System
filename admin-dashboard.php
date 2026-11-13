<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';

// Only administrators may access this page.
$admin = require_admin();

$pageTitle = 'Admin Dashboard';
$activePage = 'admin';
$extraCss = 'assets/css/admin.css';

// DASHBOARD STATISTICS
$stats = $pdo->query(
    "SELECT
        (
            SELECT COUNT(*)
            FROM users
            WHERE role = 'student'
        ) AS students,

        (
            SELECT COUNT(*)
            FROM study_sessions
            WHERE status <> 'cancelled'
        ) AS sessions,

        (
            SELECT COUNT(*)
            FROM session_members m
            JOIN study_sessions s
              ON s.id = m.session_id
            WHERE s.status <> 'cancelled'
        ) AS joins,

        (
            SELECT COUNT(*)
            FROM study_sessions
            WHERE status = 'full'
        ) AS full_sessions"
)->fetch();

// SESSIONS PER SUBJECT
$subjectRows = $pdo->query(
    "SELECT
        sub.code,
        sub.name,
        COUNT(s.id) AS total

     FROM subjects sub

     LEFT JOIN study_sessions s
       ON s.subject_id = sub.id
       AND s.status <> 'cancelled'

     GROUP BY
        sub.id,
        sub.code,
        sub.name

     ORDER BY total DESC, sub.code"
)->fetchAll();

$subjectLabels = [];
$subjectValues = [];

foreach ($subjectRows as $row) {
    $subjectLabels[] = $row['code'];
    $subjectValues[] = (int) $row['total'];
}

// MEMBERSHIPS BY JOIN WEEK
// Show eight weeks, including weeks with zero joins.
$zone = new DateTimeZone('Asia/Manila');

$monday = (new DateTimeImmutable('now', $zone))
    ->modify('monday this week')
    ->setTime(0, 0);

$firstWeek = $monday->modify('-7 weeks');

$weekLabels = [];
$weekKeys = [];
$weekCounts = [];

for ($i = 0; $i < 8; $i++) {
    $week = $firstWeek->modify("+{$i} weeks");

    $key = $week->format('Y-m-d');

    $weekKeys[] = $key;
    $weekLabels[] = $week->format('M j');
    $weekCounts[$key] = 0;
}

$weeklyStatement = $pdo->prepare(
    "SELECT
        DATE_FORMAT(
            DATE_SUB(
                DATE(m.joined_at),
                INTERVAL WEEKDAY(m.joined_at) DAY
            ),
            '%Y-%m-%d'
        ) AS week_start,

        COUNT(*) AS total

     FROM session_members m

     JOIN study_sessions s
       ON s.id = m.session_id

     WHERE m.joined_at >= ?
       AND s.status <> 'cancelled'

     GROUP BY week_start
     ORDER BY week_start"
);

$weeklyStatement->execute([
    $firstWeek->format('Y-m-d H:i:s')
]);

foreach ($weeklyStatement->fetchAll() as $row) {
    $key = $row['week_start'];

    if (array_key_exists($key, $weekCounts)) {
        $weekCounts[$key] = (int) $row['total'];
    }
}

$weekValues = array_values($weekCounts);

$jsonFlags =
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_THROW_ON_ERROR;

require __DIR__ . '/includes/header.php';
?>

<!-- ADMIN HEADING -->
<section class="admin-hero">

    <div class="section-kicker">
        ADMINISTRATOR WORKSPACE
    </div>

    <h1 class="mt-3">
        Campus overview<span style="color:#ffe16a">.</span>
    </h1>

    <p>
        Monitor study group activity, understand subject
        demand, and manage your campus community.
    </p>

</section>

<!-- ADMIN NAVIGATION -->
<nav class="admin-tabs" aria-label="Administrator pages">

    <a href="admin-dashboard.php" class="active">
        Overview
    </a>

    <a href="admin-subjects.php">
        Subjects
    </a>

    <a href="admin-sessions.php">
        Session moderation
    </a>

    <a href="admin-users.php">
        Users
    </a>

</nav>

<!-- SUMMARY CARDS -->
<section class="row g-3 mb-4">

    <?php
    $cards = [
        [
            'symbol' => '◎',
            'label' => 'Registered students',
            'value' => (int) $stats['students']
        ],
        [
            'symbol' => '▦',
            'label' => 'Non-cancelled sessions',
            'value' => (int) $stats['sessions']
        ],
        [
            'symbol' => '↗',
            'label' => 'Current memberships',
            'value' => (int) $stats['joins']
        ],
        [
            'symbol' => '✓',
            'label' => 'Full sessions',
            'value' => (int) $stats['full_sessions']
        ]
    ];
    ?>

    <?php foreach ($cards as $card): ?>

        <div class="col-6 col-lg-3">

            <div class="admin-kpi">

                <span class="kpi-symbol">
                    <?= e($card['symbol']) ?>
                </span>

                <div class="kpi-label">
                    <?= e($card['label']) ?>
                </div>

                <div class="kpi-value">
                    <?= number_format($card['value']) ?>
                </div>

            </div>

        </div>

    <?php endforeach; ?>

</section>

<!-- CHARTS -->
<section class="row g-4">

    <div class="col-lg-6">

        <div class="admin-panel h-100">

            <span class="section-kicker">
                SUBJECT DEMAND
            </span>

            <h2 class="mt-2">
                Sessions per subject
            </h2>

            <p class="admin-description">
                Number of non-cancelled study sessions
                organized for each subject.
            </p>

            <div class="admin-chart-box">

                <canvas
                    id="subjectChart"
                    role="img"
                    aria-label="Bar chart of sessions per subject">
                </canvas>

            </div>

        </div>

    </div>

    <div class="col-lg-6">

        <div class="admin-panel h-100">

            <span class="section-kicker">
                WEEKLY ENGAGEMENT
            </span>

            <h2 class="mt-2">
                Members joining per week
            </h2>

            <p class="admin-description">
                Current memberships grouped by the
                week each student joined.
            </p>

            <div class="admin-chart-box">

                <canvas
                    id="weeklyChart"
                    role="img"
                    aria-label="Line chart of current memberships by joining week">
                </canvas>

            </div>

        </div>

    </div>

</section>

<div
    id="chartStatus"
    class="small text-secondary mt-2"
    role="status">
</div>

<!-- MANAGEMENT SHORTCUTS -->
<section class="admin-panel mt-4">

    <span class="section-kicker">
        MANAGEMENT
    </span>

    <h2 class="mt-2">
        Quick actions
    </h2>

    <p class="admin-description">
        Manage subjects, review sessions,
        and organize user access.
    </p>

    <div class="admin-inline-actions">

        <a href="admin-subjects.php" class="btn btn-brand">
            Manage subjects
        </a>

        <a href="admin-sessions.php" class="btn btn-outline-brand">
            Review sessions
        </a>

        <a href="admin-users.php" class="btn btn-outline-brand">
            Manage users
        </a>

    </div>

</section>

<div class="admin-note mt-3">
    Analytics show session creation and membership records,
    not confirmed attendance. Leaving a session removes
    its membership record from the weekly chart.
</div>

<!-- CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
(() => {
    if (typeof Chart === 'undefined') {
        document.getElementById('chartStatus').textContent =
            'Chart.js could not load. Check your internet connection.';
        return;
    }

    const subjectLabels = <?= json_encode(
        $subjectLabels,
        $jsonFlags
    ) ?>;

    const subjectValues = <?= json_encode(
        $subjectValues,
        $jsonFlags
    ) ?>;

    const weekLabels = <?= json_encode(
        $weekLabels,
        $jsonFlags
    ) ?>;

    const weekValues = <?= json_encode(
        $weekValues,
        $jsonFlags
    ) ?>;

    const subjectCanvas =
        document.getElementById('subjectChart');

    const weeklyCanvas =
        document.getElementById('weeklyChart');

    new Chart(subjectCanvas, {
        type: 'bar',

        data: {
            labels: subjectLabels,
            datasets: [{
                label: 'Sessions',
                data: subjectValues,
                backgroundColor: '#5364ed',
                borderRadius: 8,
                maxBarThickness: 45
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },

            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    new Chart(weeklyCanvas, {
        type: 'line',

        data: {
            labels: weekLabels,
            datasets: [{
                label: 'Current memberships',
                data: weekValues,
                borderColor: '#2738d6',
                backgroundColor: 'rgba(39,56,214,.12)',
                tension: .35,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#2738d6'
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },

            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
