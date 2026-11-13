<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';

if (current_user()) {
    redirect('index.php');
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim(
        (string) ($_POST['full_name'] ?? '')
    );

    $email = strtolower(
        trim((string) ($_POST['email'] ?? ''))
    );

    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    $nameLength = function_exists('mb_strlen')
        ? mb_strlen($name, 'UTF-8')
        : strlen($name);

    if ($nameLength < 2 || $nameLength > 120) {
        $errors[] =
            'Your name must be between 2 and 120 characters.';
    }

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 190
    ) {
        $errors[] =
            'Please enter a valid email address.';
    }

    if (
        strlen($password) < 8 ||
        strlen($password) > 72
    ) {
        $errors[] =
            'Your password must be between 8 and 72 bytes.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Your passwords do not match.';
    }

    if (!$errors) {
        try {
            $statement = $pdo->prepare(
                "INSERT INTO users
                    (full_name, email, password_hash, role)
                 VALUES (?, ?, ?, 'student')"
            );

            $statement->execute([
                $name,
                $email,
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                )
            ]);

            session_regenerate_id(true);

            $_SESSION['user_id'] =
                (int) $pdo->lastInsertId();

            $_SESSION['csrf_token'] =
                bin2hex(random_bytes(32));

            flash(
                'success',
                'Your account is ready. Welcome to StudyCircle!'
            );

            redirect('index.php');

        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] =
                    'That email is already registered. Try logging in.';

            } else {
                error_log(
                    'StudyCircle registration error: ' .
                    $exception->getMessage()
                );

                $errors[] =
                    'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Create an account';

require __DIR__ . '/includes/header.php';
?>

<div class="auth-layout">

    <aside class="auth-aside">

        <span class="eyebrow">
            <span class="eyebrow-dot"></span>
            YOUR NEXT STUDY CREW
        </span>

        <h1>
            Good things start with
            <span>great people.</span>
        </h1>

        <p>
            Make studying feel less like a solo mission.
            Discover your people, one session at a time.
        </p>

        <div class="quote-tile">
            <span>✳</span>
            Small groups. Big progress.
        </div>

    </aside>

    <section
        class="auth-panel"
        aria-labelledby="registerTitle">

        <div class="auth-eyebrow">
            GET STARTED
        </div>

        <h2 id="registerTitle">
            Create your account
        </h2>

        <p class="auth-subtitle">
            Student registration is free and takes a minute.
        </p>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form
            action="register.php"
            method="post"
            class="auth-form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>">

            <!-- FULL NAME -->
            <div>
                <label for="full_name">
                    Full name
                </label>

                <input
                    class="form-control"
                    id="full_name"
                    name="full_name"
                    type="text"
                    maxlength="120"
                    autocomplete="name"
                    placeholder="e.g. Alex Rivera"
                    value="<?= e($name) ?>"
                    required>
            </div>

            <!-- EMAIL -->
            <div>
                <label for="email">
                    Email address
                </label>

                <input
                    class="form-control"
                    id="email"
                    name="email"
                    type="email"
                    maxlength="190"
                    autocomplete="email"
                    placeholder="you@example.com"
                    value="<?= e($email) ?>"
                    required>
            </div>

            <!-- PASSWORD WITH SHOW/HIDE -->
            <div>
                <label for="password">
                    Password
                </label>

                <div class="position-relative">

                    <input
                        class="form-control"
                        id="password"
                        name="password"
                        type="password"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        placeholder="At least 8 characters"
                        style="padding-right: 5rem;"
                        required>

                    <button
                        type="button"
                        class="btn btn-sm text-secondary position-absolute top-50 end-0 translate-middle-y me-2"
                        data-password-toggle="password"
                        aria-label="Show password"
                        aria-controls="password"
                        aria-pressed="false">
                        Show
                    </button>

                </div>

                <div class="form-hint">
                    Use a unique password with at least
                    8 characters.
                </div>
            </div>

            <!-- CONFIRM PASSWORD WITH SHOW/HIDE -->
            <div>
                <label for="confirm_password">
                    Confirm password
                </label>

                <div class="position-relative">

                    <input
                        class="form-control"
                        id="confirm_password"
                        name="confirm_password"
                        type="password"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        placeholder="Re-enter your password"
                        style="padding-right: 5rem;"
                        required>

                    <button
                        type="button"
                        class="btn btn-sm text-secondary position-absolute top-50 end-0 translate-middle-y me-2"
                        data-password-toggle="confirm_password"
                        aria-label="Show confirm password"
                        aria-controls="confirm_password"
                        aria-pressed="false">
                        Show
                    </button>

                </div>
            </div>

            <!-- CREATE ACCOUNT -->
            <button
                type="submit"
                class="btn btn-brand w-100">
                Create account
                <span aria-hidden="true">↗</span>
            </button>

        </form>

        <p class="auth-switch">
            Already have an account?
            <a href="login.php">
                Log in here
            </a>.
        </p>

    </section>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
