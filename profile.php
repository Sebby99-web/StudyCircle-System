<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';

$user = require_login();

$pageTitle = 'My profile';
$activePage = 'profile';

$error = '';
$editName = (string) $user['full_name'];
$editEmail = (string) $user['email'];

function profile_input(string $key): string
{
    $value = $_POST[$key] ?? '';

    return is_string($value) ? trim($value) : '';
}

// HANDLE PROFILE UPDATES

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = profile_input('action');

    try {
        if ($action === 'details') {
            $editName = profile_input('full_name');
            $editEmail = strtolower(profile_input('email'));

            $currentPassword = $_POST['current_password'] ?? '';

            $nameLength = function_exists('mb_strlen')
                ? mb_strlen($editName, 'UTF-8')
                : strlen($editName);

            if ($nameLength < 2 || $nameLength > 120) {
                throw new DomainException(
                    'Full name must be 2–120 characters.'
                );
            }

            if (
                strlen($editEmail) > 190 ||
                !filter_var($editEmail, FILTER_VALIDATE_EMAIL)
            ) {
                throw new DomainException(
                    'Enter a valid email address.'
                );
            }

            // Require current password when changing email.
            if ($editEmail !== (string) $user['email']) {
                $stmt = $pdo->prepare(
                    'SELECT password_hash FROM users WHERE id = ?'
                );

                $stmt->execute([(int) $user['id']]);
                $hash = $stmt->fetchColumn();

                if (
                    !is_string($currentPassword) ||
                    !$hash ||
                    !password_verify(
                        $currentPassword,
                        (string) $hash
                    )
                ) {
                    throw new DomainException(
                        'Enter your current password to change your email.'
                    );
                }
            }

            $stmt = $pdo->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?
                 WHERE id = ?'
            );

            $stmt->execute([
                $editName,
                $editEmail,
                (int) $user['id']
            ]);

            flash('success', 'Profile details updated.');
            redirect('profile.php');

        } elseif ($action === 'password') {
            $current = $_POST['current_password'] ?? '';
            $next = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (
                !is_string($current) ||
                !is_string($next) ||
                !is_string($confirm)
            ) {
                throw new DomainException(
                    'Invalid password fields.'
                );
            }

            if (strlen($next) < 8 || strlen($next) > 72) {
                throw new DomainException(
                    'New password must be 8–72 bytes long.'
                );
            }

            if ($next !== $confirm) {
                throw new DomainException(
                    'New passwords do not match.'
                );
            }

            $stmt = $pdo->prepare(
                'SELECT password_hash FROM users WHERE id = ?'
            );

            $stmt->execute([(int) $user['id']]);
            $hash = $stmt->fetchColumn();

            if (
                !$hash ||
                !password_verify($current, (string) $hash)
            ) {
                throw new DomainException(
                    'Current password is incorrect.'
                );
            }

            $stmt = $pdo->prepare(
                'UPDATE users
                 SET password_hash = ?
                 WHERE id = ?'
            );

            $stmt->execute([
                password_hash($next, PASSWORD_DEFAULT),
                (int) $user['id']
            ]);

            session_regenerate_id(true);

            $_SESSION['csrf_token'] =
                bin2hex(random_bytes(32));

            flash(
                'success',
                'Password updated successfully.'
            );

            redirect('profile.php');

        } else {
            throw new DomainException(
                'Unknown profile action.'
            );
        }

    } catch (DomainException $exception) {
        $error = $exception->getMessage();

    } catch (PDOException $exception) {
        error_log(
            'StudyCircle profile: ' .
            $exception->getMessage()
        );

        $error = $exception->getCode() === '23000'
            ? 'That email address is already registered.'
            : 'Could not save profile changes. Please try again.';
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="mb-4">
    <span class="section-kicker">
        ACCOUNT SETTINGS
    </span>

    <h1 class="fw-bold mt-2">
        My profile<span class="heading-dot">.</span>
    </h1>

    <p class="text-secondary">
        Manage your personal details and
        keep your account secure.
    </p>
</section>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="row g-4">

    <section class="col-lg-7">

        <!-- PROFILE DETAILS -->
        <div class="session-card">
            <span class="section-kicker">
                YOUR DETAILS
            </span>

            <h2 class="h4 fw-bold mt-2 mb-3">
                Account information
            </h2>

            <form
                method="post"
                action="profile.php"
                class="row g-3">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>">

                <input
                    type="hidden"
                    name="action"
                    value="details">

                <div class="col-12">
                    <label
                        class="form-label fw-semibold"
                        for="full_name">
                        Full name
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="120"
                        required
                        autocomplete="name"
                        value="<?= e($editName) ?>">
                </div>

                <div class="col-12">
                    <label
                        class="form-label fw-semibold"
                        for="email">
                        Email address
                    </label>

                    <input
                        class="form-control"
                        type="email"
                        id="email"
                        name="email"
                        maxlength="190"
                        required
                        autocomplete="email"
                        value="<?= e($editEmail) ?>">
                </div>

                <div class="col-12">
                    <label
                        class="form-label fw-semibold"
                        for="details_password">
                        Current password
                        <span class="text-secondary fw-normal">
                            (only if changing email)
                        </span>
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        id="details_password"
                        name="current_password"
                        autocomplete="current-password">
                </div>

                <div class="col-12">
                    <button
                        class="btn btn-brand"
                        type="submit">
                        Save profile details
                    </button>
                </div>

            </form>
        </div>

        <!-- CHANGE PASSWORD -->
        <div class="session-card mt-4">
            <span class="section-kicker">
                SECURITY
            </span>

            <h2 class="h4 fw-bold mt-2 mb-3">
                Change password
            </h2>

            <form
                method="post"
                action="profile.php"
                class="row g-3">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>">

                <input
                    type="hidden"
                    name="action"
                    value="password">

                <div class="col-12">
                    <label
                        class="form-label fw-semibold"
                        for="current_password">
                        Current password
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        required>
                </div>

                <div class="col-md-6">
                    <label
                        class="form-label fw-semibold"
                        for="new_password">
                        New password
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        id="new_password"
                        name="new_password"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="72"
                        required>
                </div>

                <div class="col-md-6">
                    <label
                        class="form-label fw-semibold"
                        for="confirm_password">
                        Confirm new password
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="72"
                        required>
                </div>

                <div class="col-12">
                    <button
                        class="btn btn-outline-brand"
                        type="submit">
                        Update password
                    </button>
                </div>

            </form>
        </div>

    </section>

    <!-- ACCOUNT SUMMARY -->
    <aside class="col-lg-5">
        <div class="session-card">

            <span class="subject-pill">
                <?= e(ucfirst((string) $user['role'])) ?>
            </span>

            <h2 class="h4 fw-bold mt-3">
                <?= e($user['full_name']) ?>
            </h2>

            <p class="session-subject mb-3">
                <?= e($user['email']) ?>
            </p>

            <p class="text-secondary small mb-3">
                Only administrators can change account roles.
                Your changes here do not affect your
                study sessions.
            </p>

            <a
                href="my-sessions.php"
                class="btn btn-brand">
                Go to My Sessions
            </a>

        </div>
    </aside>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
