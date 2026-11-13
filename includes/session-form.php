<?php
/** @var array $values */
/** @var array $subjects */
/** @var string $formAction */
/** @var string $submitLabel */
/** @var array $user */

$isCreating = $formAction === 'create-session.php';
?>

<form
    method="post"
    action="<?= e($formAction) ?>"
    class="session-card"
    id="sessionForm"
    data-draft-mode="<?= $isCreating ? 'create' : 'edit' ?>"
    data-user-id="<?= (int) $user['id'] ?>">

    <input
        type="hidden"
        name="csrf_token"
        value="<?= e(csrf_token()) ?>">

    <div class="row g-3">

        <!-- SUBJECT -->
        <div class="col-md-7">

            <label
                for="subject_id"
                class="form-label fw-semibold">
                Subject
            </label>

            <select
                name="subject_id"
                id="subject_id"
                class="form-select"
                required>

                <option value="">
                    Choose a subject
                </option>

                <?php foreach ($subjects as $subject): ?>

                    <option
                        value="<?= (int) $subject['id'] ?>"
                        <?= $values['subject_id'] === (string) $subject['id'] ? 'selected' : '' ?>>

                        <?= e($subject['code']) ?>
                        —
                        <?= e($subject['name']) ?>

                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <!-- CAPACITY -->
        <div class="col-md-5">

            <label
                for="capacity"
                class="form-label fw-semibold">
                Available student seats
            </label>

            <input
                type="number"
                name="capacity"
                id="capacity"
                min="1"
                max="50"
                step="1"
                class="form-control"
                value="<?= e($values['capacity']) ?>"
                required>

            <div class="form-text">
                The host is not counted as a joined member.
            </div>
        </div>

        <!-- TOPIC -->
        <div class="col-12">

            <label
                for="topic"
                class="form-label fw-semibold">
                Study topic
            </label>

            <input
                name="topic"
                id="topic"
                type="text"
                minlength="3"
                maxlength="160"
                class="form-control"
                value="<?= e($values['topic']) ?>"
                placeholder="e.g. Database normalization review"
                required>
        </div>

        <!-- VENUE -->
        <div class="col-12">

            <label
                for="venue"
                class="form-label fw-semibold">
                Venue / meeting location
            </label>

            <input
                name="venue"
                id="venue"
                type="text"
                minlength="2"
                maxlength="180"
                class="form-control"
                value="<?= e($values['venue']) ?>"
                placeholder="e.g. Library study area"
                required>
        </div>

        <!-- DATE -->
        <div class="col-md-4">

            <label
                for="session_date"
                class="form-label fw-semibold">
                Session date
            </label>

            <input
                name="session_date"
                id="session_date"
                type="date"
                min="<?= date('Y-m-d') ?>"
                class="form-control"
                value="<?= e($values['session_date']) ?>"
                required>
        </div>

        <!-- START TIME -->
        <div class="col-md-4">

            <label
                for="start_time"
                class="form-label fw-semibold">
                Start time
            </label>

            <input
                name="start_time"
                id="start_time"
                type="time"
                class="form-control"
                value="<?= e($values['start_time']) ?>"
                required>
        </div>

        <!-- END TIME -->
        <div class="col-md-4">

            <label
                for="end_time"
                class="form-label fw-semibold">
                End time
            </label>

            <input
                name="end_time"
                id="end_time"
                type="time"
                class="form-control"
                value="<?= e($values['end_time']) ?>"
                required>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="col-12 d-flex flex-wrap align-items-center gap-2 mt-4">

            <button
                type="submit"
                class="btn btn-brand">
                <?= e($submitLabel) ?>
            </button>

            <a
                href="my-sessions.php"
                class="btn btn-outline-brand">
                Cancel and go back
            </a>

            <?php if ($isCreating): ?>

                <button
                    type="button"
                    id="clearHostDraft"
                    class="btn btn-outline-secondary">
                    Clear draft
                </button>

            <?php endif; ?>

        </div>

        <!-- DRAFT STATUS -->
        <?php if ($isCreating): ?>

            <div class="col-12">

                <p
                    class="small text-secondary mb-0"
                    id="draftStatus"
                    role="status">

                    Your unfinished form can be saved on this device.

                </p>

            </div>

        <?php endif; ?>

    </div>
</form>
