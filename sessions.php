<?php
declare(strict_types=1);

/**
 * StudyCircle-System
 * Browse study sessions with live filters.
 */

require __DIR__ . '/includes/app.php';

require __DIR__ . '/includes/session-service.php';

require __DIR__ . '/includes/session-search.php';

// PAGE INFORMATION
$viewer = current_user();

$pageTitle = 'Explore study sessions';

$activePage = 'browse';

$isMine = false;

$error = '';

// READ AND VALIDATE FILTERS
try {

    $filters = sc_search_filters($_GET);

} catch (DomainException $e) {

    $error = $e->getMessage();

    $filters = sc_search_filters([]);
}

// GET SUBJECTS FROM DATABASE
$subjects = $pdo->query(
    'SELECT id, code, name
     FROM subjects
     ORDER BY code, name'
)->fetchAll();

// GET UPCOMING SESSIONS
$sessions = sc_browse_sessions(
    $pdo,
    $viewer,
    $filters
);

// SHARED HEADER
require __DIR__ . '/includes/header.php';
?>

<!-- ======================================
     PAGE HEADING
====================================== -->

<section
    class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">

    <div>

        <span class="section-kicker">
            DISCOVER
        </span>

        <h1 class="fw-bold mt-2">

            Study sessions<span class="heading-dot">.</span>

        </h1>

        <p class="text-secondary mb-0">

            Find your next study circle.
            Filter by subject, date, venue, or topic.

        </p>

    </div>

    <?php if ($viewer): ?>

        <a
            href="create-session.php"
            class="btn btn-brand">

            + Host a session

        </a>

    <?php else: ?>

        <a
            href="register.php"
            class="btn btn-brand">

            Join StudyCircle

        </a>

    <?php endif; ?>

</section>


<!-- ======================================
     SEARCH AND FILTER PANEL
====================================== -->

<div class="session-card mb-4">

    <form
        id="sessionSearchForm"
        action="sessions.php"
        method="get"
        role="search"
        aria-label="Find study sessions">

        <div class="row g-3 align-items-end">

            <!-- SEARCH INPUT -->
            <div class="col-lg-4 col-md-6">

                <label
                    class="form-label fw-semibold"
                    for="filterSearch">

                    Search topic or host

                </label>

                <input
                    class="form-control"
                    type="search"
                    name="q"
                    id="filterSearch"
                    maxlength="120"
                    placeholder="e.g. Data structures"
                    value="<?= e($filters['q']) ?>">

            </div>


            <!-- SUBJECT FILTER -->
            <div class="col-lg-3 col-md-6">

                <label
                    class="form-label fw-semibold"
                    for="filterSubject">

                    Subject

                </label>

                <select
                    class="form-select"
                    name="subject"
                    id="filterSubject">

                    <option value="">
                        All subjects
                    </option>

                    <?php foreach ($subjects as $subject): ?>

                        <option
                            value="<?= (int) $subject['id'] ?>"
                            <?= $filters['subject'] === (int) $subject['id'] ? 'selected' : '' ?>>

                            <?= e($subject['code']) ?>
                            —
                            <?= e($subject['name']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- DATE FILTER -->
            <div class="col-lg-2 col-md-6">

                <label
                    class="form-label fw-semibold"
                    for="filterDate">

                    Date

                </label>

                <input
                    class="form-control"
                    type="date"
                    name="date"
                    id="filterDate"
                    value="<?= e($filters['date']) ?>">

            </div>


            <!-- VENUE FILTER -->
            <div class="col-lg-3 col-md-6">

                <label
                    class="form-label fw-semibold"
                    for="filterVenue">

                    Venue

                </label>

                <input
                    class="form-control"
                    type="search"
                    name="venue"
                    id="filterVenue"
                    maxlength="180"
                    placeholder="e.g. Library"
                    value="<?= e($filters['venue']) ?>">

            </div>


            <!-- SEARCH BUTTONS -->
            <div class="col-12 d-flex align-items-center flex-wrap gap-2">

                <button
                    class="btn btn-brand"
                    type="submit">

                    Search sessions

                </button>

                <a
                    class="btn btn-outline-brand"
                    href="sessions.php"
                    id="clearSessionFilters">

                    Clear filters

                </a>

                <span
                    class="small text-secondary"
                    id="searchActivity"
                    role="status"
                    aria-live="polite">
                </span>

            </div>

        </div>

    </form>

</div>


<!-- ======================================
     SEARCH ERRORS
====================================== -->

<div
    class="alert alert-danger"
    id="searchError"
    role="alert"
    <?= $error === '' ? 'hidden' : '' ?>>

    <?= e($error) ?>

</div>


<!-- ======================================
     RESULTS COUNTER
====================================== -->

<div
    class="d-flex justify-content-between align-items-center gap-2 mb-3">

    <p
        class="session-count mb-0"
        id="resultsCount"
        aria-live="polite">

        <?= count($sessions) ?>
        upcoming session(s)

        <?php if (count($sessions) === 100): ?>
            (first 100 shown)
        <?php endif; ?>

    </p>

    <span class="small text-secondary">
        Sessions update as you filter
    </span>

</div>


<!-- ======================================
     SESSION RESULTS
====================================== -->

<div
    id="sessionResults"
    aria-busy="false">

    <?php
    require __DIR__ . '/includes/session-card.php';
    ?>

</div>


<!-- ======================================
     LIVE SEARCH JAVASCRIPT
====================================== -->

<script
    src="assets/js/live-search.js"
    defer>
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
