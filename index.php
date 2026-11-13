<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';

$pageTitle = 'Study groups that actually connect';
$activePage = 'home';

$user = current_user();


// ==========================================
// GET UPCOMING STUDY SESSIONS
// ==========================================

$sessions = $pdo->query(
    "SELECT
        s.id,
        s.topic,
        s.venue,
        s.session_date,
        s.start_time,
        s.end_time,
        s.capacity,
        s.status,

        sub.code AS subject_code,
        sub.name AS subject_name,

        u.full_name AS host_name,

        (
            SELECT COUNT(*)
            FROM session_members sm
            WHERE sm.session_id = s.id
        ) AS member_count

     FROM study_sessions s

     JOIN subjects sub
        ON sub.id = s.subject_id

     JOIN users u
        ON u.id = s.host_id

     WHERE s.status <> 'cancelled'

       AND TIMESTAMP(
           s.session_date,
           s.start_time
       ) > NOW()

     ORDER BY
        s.session_date,
        s.start_time,
        s.id

     LIMIT 6"
)->fetchAll();


// ==========================================
// LOAD PAGE
// ==========================================

require __DIR__ . '/includes/header.php';
?>


<!-- ======================================
     HERO SECTION
====================================== -->

<section class="hero-shell">

    <div class="hero-copy">

        <div class="eyebrow">

            <span class="eyebrow-dot"></span>

            YOUR CAMPUS STUDY SPACE

        </div>


        <h1>
            Great minds
            <span class="yellow-underline">study</span>
            together.
        </h1>


        <p class="hero-lead">

            Find classmates, share what you know,
            and make your next study session count.

            A better way to learn starts here.

        </p>


        <div class="hero-actions">

            <?php if ($user): ?>

                <a
                    class="btn btn-bright"
                    href="#sessions">

                    Explore sessions

                    <span aria-hidden="true">↗</span>

                </a>

            <?php else: ?>

                <a
                    class="btn btn-bright"
                    href="register.php">

                    Find your study circle

                    <span aria-hidden="true">↗</span>

                </a>

                <a
                    class="btn btn-hero-ghost"
                    href="login.php">

                    I already have an account

                </a>

            <?php endif; ?>

        </div>


        <div class="hero-micro">

            ✦ Built for students, powered by teamwork

        </div>

    </div>


    <!-- HERO GRAPHIC -->
    <div class="hero-art" aria-hidden="true">

        <div class="orbit orbit-one"></div>
        <div class="orbit orbit-two"></div>


        <div class="floating-card floating-top">

            <span class="floating-emoji">
                ✦
            </span>

            <span>

                Better together

                <small>
                    Always learning
                </small>

            </span>

        </div>


        <div class="hero-circle">

            <span class="hero-circle-inner">

                SC<span>.</span>

            </span>

        </div>


        <div class="floating-card floating-bottom">

            <span class="floating-emoji">
                ✓
            </span>

            <span>

                Your study crew

                <small>
                    Starts here
                </small>

            </span>

        </div>


        <span class="spark spark-a">✳</span>
        <span class="spark spark-b">✳</span>

    </div>

</section>


<!-- ======================================
     UPCOMING SESSIONS
====================================== -->

<section class="browse-section" id="sessions">

    <div class="section-title-row">

        <div>

            <div class="section-kicker">
                DISCOVER
            </div>

            <h2>

                Upcoming study sessions<span class="heading-dot">.</span>

            </h2>

            <p>

                Browse sessions created by students
                in your campus community.

            </p>

        </div>


        <span class="session-count">

            <?= count($sessions) ?> shown

        </span>

    </div>


    <!-- EMPTY STATE -->
    <?php if (!$sessions): ?>

        <div class="empty-state">

            <div
                class="empty-icon"
                aria-hidden="true">

                ◎

            </div>


            <h3>
                Nothing on the board yet.
            </h3>


            <p>

                You're seeing a brand-new database.

                Once someone hosts a session,
                it will appear here automatically.

            </p>


            <?php if (!$user): ?>

                <a
                    class="btn btn-brand"
                    href="register.php">

                    Create your student account

                </a>

            <?php else: ?>


            <a href="create-session.php" class="btn btn-brand">
                Host your first session
            </a>


            <?php endif; ?>

        </div>


    <?php else: ?>

        <!-- SESSION CARDS -->
        <div class="session-grid">

            <?php foreach ($sessions as $session): ?>

                <?php
                $joinedCount =
                    (int) $session['member_count'];

                $capacity =
                    (int) $session['capacity'];

                $remaining = max(
                    0,
                    $capacity - $joinedCount
                );

                $full =
                    $remaining === 0 ||
                    $session['status'] === 'full';

                $percent = min(
                    100,
                    (int) round(
                        100 * $joinedCount /
                        max(1, $capacity)
                    )
                );
                ?>


                <article class="session-card">

                    <!-- SUBJECT AND STATUS -->
                    <div class="session-card-top">

                        <span class="subject-pill">

                            <?= e($session['subject_code']) ?>

                        </span>


                        <span class="status-pill <?= $full ? 'status-full' : 'status-open' ?>">

                            <?= $full ? 'Full' : 'Open' ?>

                        </span>

                    </div>


                    <!-- SESSION TOPIC -->
                    <h3>

                        <?= e($session['topic']) ?>

                    </h3>


                    <!-- SUBJECT NAME -->
                    <p class="session-subject">

                        <?= e($session['subject_name']) ?>

                    </p>


                    <!-- DATE, TIME, VENUE -->
                    <div class="session-details">

                        <span>

                            ◷

                            <?= e(date(
                                'M j, Y',
                                strtotime($session['session_date'])
                            )) ?>

                            ·

                            <?= e(date(
                                'g:i A',
                                strtotime($session['start_time'])
                            )) ?>

                        </span>


                        <span>

                            ⌖ <?= e($session['venue']) ?>

                        </span>


                        <span>

                            Hosted by
                            <?= e($session['host_name']) ?>

                        </span>

                    </div>


                    <!-- AVAILABLE SEATS -->
                    <div class="seat-row">

                        <span>
                            Available seats
                        </span>

                        <strong>

                            <?= $remaining ?>
                            /
                            <?= $capacity ?>

                        </strong>

                    </div>


                    <!-- SEAT PROGRESS BAR -->
                    <div
                        class="seat-track"
                        role="progressbar"
                        aria-valuenow="<?= $joinedCount ?>"
                        aria-valuemin="0"
                        aria-valuemax="<?= $capacity ?>"
                        aria-label="Seats taken">

                        <div
                            style="width: <?= $percent ?>%">
                        </div>

                    </div>


                    <!-- SESSION ACTION -->
                    <div class="session-footer">

                        <?php if (!$user): ?>

                            <a
                                href="login.php"
                                class="btn btn-brand btn-sm">

                                Log in to join

                            </a>

                        <?php else: ?>


                        <a href="sessions.php" class="btn btn-brand btn-sm">
                            View &amp; join session
                        </a>


                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<!-- ======================================
     HOW IT WORKS
====================================== -->

<section class="how-section">

    <div>

        <span class="section-kicker">
            HOW IT WORKS
        </span>

        <h2>

            A little collaboration
            goes a long way.

        </h2>

    </div>


    <div class="how-grid">

        <article>

            <span class="how-number">
                01
            </span>

            <h3>
                Explore
            </h3>

            <p>

                Find a subject and a study group
                that fits your schedule.

            </p>

        </article>


        <article>

            <span class="how-number">
                02
            </span>

            <h3>
                Join
            </h3>

            <p>

                Save a seat and let the host know
                you're coming.

            </p>

        </article>


        <article>

            <span class="how-number">
                03
            </span>

            <h3>
                Grow
            </h3>

            <p>

                Work through concepts together
                and build confidence.

            </p>

        </article>

    </div>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
