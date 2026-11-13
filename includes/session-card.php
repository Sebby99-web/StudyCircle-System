<?php
/** @var array $sessions */
/** @var array|null $viewer */
/** @var bool $isMine */

$returnTo = $isMine
    ? 'my-sessions.php'
    : 'sessions.php';
?>

<?php if (!$sessions): ?>

    <div class="empty-state">

        <div class="empty-icon" aria-hidden="true">
            ◎
        </div>

        <h3>
            <?= $isMine
                ? 'No sessions here yet.'
                : 'No upcoming sessions right now.' ?>
        </h3>

        <p>
            <?= $isMine
                ? 'Join a study session or create one of your own.'
                : 'Be the first to organize a study session.' ?>
        </p>

        <a
            href="<?= $isMine ? 'sessions.php' : 'create-session.php' ?>"
            class="btn btn-brand">

            <?= $isMine
                ? 'Explore sessions'
                : 'Host a session' ?>

        </a>
    </div>

<?php else: ?>

    <div class="session-grid">

        <?php foreach ($sessions as $session): ?>

            <?php
            $id = (int) $session['id'];

            $count = (int) $session['member_count'];

            $capacity = max(
                1,
                (int) $session['capacity']
            );

            $remaining = max(
                0,
                $capacity - $count
            );

            $percent = min(
                100,
                (int) round(
                    100 * $count / $capacity
                )
            );

            $hosting = $viewer !== null &&
                (int) $viewer['id'] ===
                (int) $session['host_id'];

            $joined = (int) $session['joined'] === 1;

            $isAdmin = $viewer !== null &&
                $viewer['role'] === 'admin';

            $cancelled =
                $session['status'] === 'cancelled';

            $started = sc_session_started($session);

            $full = $remaining === 0 ||
                $session['status'] === 'full';

            $label = $cancelled
                ? 'Cancelled'
                : ($started
                    ? 'Started'
                    : ($full ? 'Full' : 'Open'));
            ?>

            <article class="session-card">

                <!-- SUBJECT AND STATUS -->
                <div class="session-card-top">

                    <span class="subject-pill">
                        <?= e($session['subject_code']) ?>
                    </span>

                    <span class="status-pill <?= $cancelled || $started ? 'text-bg-secondary' : ($full ? 'status-full' : 'status-open') ?>">

                        <?= e($label) ?>

                    </span>

                </div>

                <!-- SESSION DETAILS -->
                <h3>
                    <?= e($session['topic']) ?>
                </h3>

                <p class="session-subject">
                    <?= e($session['subject_name']) ?>
                </p>

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

                        –

                        <?= e(date(
                            'g:i A',
                            strtotime($session['end_time'])
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

                <!-- SEAT AVAILABILITY -->
                <div class="seat-row">

                    <span>
                        Available seats
                    </span>

                    <strong>
                        <?= $remaining ?> / <?= $capacity ?>
                    </strong>

                </div>

                <div
                    class="seat-track"
                    role="progressbar"
                    aria-valuenow="<?= $count ?>"
                    aria-valuemin="0"
                    aria-valuemax="<?= $capacity ?>"
                    aria-label="Seats taken">

                    <div style="width: <?= $percent ?>%"></div>

                </div>

                <!-- SESSION BUTTONS -->
                <div class="session-footer d-flex flex-wrap align-items-center gap-2">

                    <?php if ($cancelled || $started): ?>

                        <span class="soon-note">

                            <?= $cancelled
                                ? 'This session was cancelled.'
                                : 'This session has already started.' ?>

                        </span>

                    <?php elseif ($hosting): ?>

                        <!-- HOST: EDIT -->
                        <a
                            href="edit-session.php?id=<?= $id ?>"
                            class="btn btn-outline-brand btn-sm">
                            Edit
                        </a>

                        <!-- HOST: CANCEL -->
                        <form
                            action="session-action.php"
                            method="post"
                            class="m-0"
                            onsubmit="return confirm('Cancel this study session?');">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>">

                            <input
                                type="hidden"
                                name="session_id"
                                value="<?= $id ?>">

                            <input
                                type="hidden"
                                name="action"
                                value="cancel">

                            <input
                                type="hidden"
                                name="return_to"
                                value="<?= e($returnTo) ?>">

                            <button
                                class="btn btn-outline-danger btn-sm"
                                type="submit">
                                Cancel session
                            </button>

                        </form>

                    <?php elseif ($joined): ?>

                        <!-- MEMBER: LEAVE -->
                        <form
                            action="session-action.php"
                            method="post"
                            class="m-0">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>">

                            <input
                                type="hidden"
                                name="session_id"
                                value="<?= $id ?>">

                            <input
                                type="hidden"
                                name="action"
                                value="leave">

                            <input
                                type="hidden"
                                name="return_to"
                                value="<?= e($returnTo) ?>">

                            <button
                                class="btn btn-outline-brand btn-sm"
                                type="submit">
                                Leave session
                            </button>

                        </form>

                    <?php elseif ($isAdmin): ?>

                        <!-- ADMIN: MODERATION -->
                        <form
                            action="session-action.php"
                            method="post"
                            class="m-0"
                            onsubmit="return confirm('Remove this inappropriate session?');">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>">

                            <input
                                type="hidden"
                                name="session_id"
                                value="<?= $id ?>">

                            <input
                                type="hidden"
                                name="action"
                                value="cancel">

                            <input
                                type="hidden"
                                name="return_to"
                                value="<?= e($returnTo) ?>">

                            <button
                                class="btn btn-outline-danger btn-sm"
                                type="submit">
                                Remove session
                            </button>

                        </form>

                    <?php elseif (!$viewer): ?>

                        <!-- VISITOR: LOGIN -->
                        <a
                            href="login.php"
                            class="btn btn-brand btn-sm">
                            Log in to join
                        </a>

                    <?php elseif ($full): ?>

                        <!-- FULL SESSION -->
                        <button
                            class="btn btn-outline-brand btn-sm"
                            disabled>
                            Session full
                        </button>

                    <?php else: ?>

                        <!-- STUDENT: JOIN -->
                        <form
                            action="session-action.php"
                            method="post"
                            class="m-0">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>">

                            <input
                                type="hidden"
                                name="session_id"
                                value="<?= $id ?>">

                            <input
                                type="hidden"
                                name="action"
                                value="join">

                            <input
                                type="hidden"
                                name="return_to"
                                value="<?= e($returnTo) ?>">

                            <button
                                class="btn btn-brand btn-sm"
                                type="submit">
                                Join session
                            </button>

                        </form>

                    <?php endif; ?>

                    <!-- CALENDAR EXPORT -->
                    <?php if (
                        ($joined || $hosting) &&
                        !$cancelled
                    ): ?>

                        <a
                            href="download-calendar.php?id=<?= $id ?>"
                            class="btn btn-outline-brand btn-sm">

                            Add to calendar (.ics)

                        </a>

                    <?php endif; ?>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>
