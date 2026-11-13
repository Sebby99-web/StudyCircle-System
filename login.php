<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';

if (current_user()) {
    redirect('index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(
        trim((string) ($_POST['email'] ?? ''))
    );

    $password = (string) ($_POST['password'] ?? '');

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 190 ||
        $password === ''
    ) {
        $error = 'Enter a valid email address and password.';

    } else {
        $statement = $pdo->prepare(
            'SELECT id, password_hash
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $statement->execute([$email]);
        $account = $statement->fetch();

        if (
            !$account ||
            !password_verify(
                $password,
                $account['password_hash']
            )
        ) {
            $error = 'Incorrect email or password.';

        } else {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $account['id'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            flash('success', 'You are now logged in.');
            redirect('index.php');
        }
    }
}

$pageTitle = 'Log in';

require __DIR__ . '/includes/header.php';
?>

<div class="auth-layout">

    <aside class="auth-aside">

        <span class="eyebrow">
            <span class="eyebrow-dot"></span>
            WELCOME BACK
        </span>

        <h1>
            Pick up where
            <span>you left off.</span>
        </h1>

        <p>
            Your classmates, study goals, and next
            breakthrough are one login away.
        </p>

        <div class="quote-tile">
            <span>✳</span>
            Show up. Study smart. Grow together.
        </div>

    </aside>

    <section
        class="auth-panel"
        aria-labelledby="loginTitle">

        <div class="auth-eyebrow">
            STUDENT & ADMIN ACCESS
        </div>

        <h2 id="loginTitle">
            Log in to StudyCircle
        </h2>

        <p class="auth-subtitle">
            Welcome back! Enter your account details.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logged_out'])): ?>
            <div class="alert alert-success" role="status">
                You have been logged out.
            </div>
        <?php endif; ?>

        <form
            action="login.php"
            method="post"
            class="auth-form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>">

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
                    autocomplete="email"
                    maxlength="190"
                    placeholder="you@example.com"
                    value="<?= e($email) ?>"
                    required>
            </div>

            <!-- PASSWORD WITH SHOW/HIDE BUTTON -->
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
                        autocomplete="current-password"
                        placeholder="Enter your password"
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
            </div>

            <!-- LOGIN BUTTON -->
            <button
                type="submit"
                class="btn btn-brand w-100">
                Log in
                <span aria-hidden="true">↗</span>
            </button>

        </form>

        <p class="auth-switch">
            New to StudyCircle?
            <a href="register.php">
                Create an account
            </a>.
        </p>

    </section>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
