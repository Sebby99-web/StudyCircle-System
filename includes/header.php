<?php
$pageTitle = $pageTitle ?? 'StudyCircle';
$activePage = $activePage ?? '';
$extraCss = $extraCss ?? '';

$navUser = current_user();
?>

<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <meta
        name="description"
        content="StudyCircle campus study group finder and session scheduler.">

    <title><?= e($pageTitle) ?> · StudyCircle</title>

    <!-- GOOGLE FONTS -->
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- BOOTSTRAP -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- MAIN CSS -->
    <link
        href="assets/css/site.css"
        rel="stylesheet">

    <!-- OPTIONAL PAGE STYLESHEET -->
    <?php if ($extraCss !== ''): ?>

        <link
            href="<?= e($extraCss) ?>"
            rel="stylesheet">

    <?php endif; ?>

    <!-- SAVED THEME -->
    <script>
        try {
            document.documentElement.dataset.bsTheme =
                localStorage.getItem('sc-system-theme') === 'dark'
                    ? 'dark'
                    : 'light';

        } catch (_) {
            document.documentElement.dataset.bsTheme =
                'light';
        }
    </script>
</head>

<body>

<header class="site-header">

    <nav
        class="navbar navbar-expand-lg"
        aria-label="Primary navigation">

        <div class="container">

            <!-- BRAND -->
            <a
                class="brand"
                href="index.php"
                aria-label="StudyCircle homepage">

                <span class="brand-symbol">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>

                <span>
                    study<span class="brand-highlight">circle</span><span class="brand-dot">.</span>
                </span>

            </a>

            <!-- MOBILE TOGGLE -->
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#siteNav"
                aria-controls="siteNav"
                aria-expanded="false"
                aria-label="Toggle navigation">

                <span class="navbar-toggler-icon"></span>
            </button>

            <div
                class="collapse navbar-collapse"
                id="siteNav">

                <!-- MAIN NAVIGATION -->
                <div
                    class="navbar-nav mx-lg-auto gap-lg-1 mt-3 mt-lg-0">

                    <a
                        class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>"
                        href="index.php">
                        Home
                    </a>

                    <a
                        class="nav-link <?= $activePage === 'browse' ? 'active' : '' ?>"
                        href="sessions.php">
                        Explore
                    </a>

                    <?php if ($navUser): ?>

                        <a
                            class="nav-link <?= $activePage === 'host' ? 'active' : '' ?>"
                            href="create-session.php">
                            Host
                        </a>

                        <a
                            class="nav-link <?= $activePage === 'mine' ? 'active' : '' ?>"
                            href="my-sessions.php">
                            My Sessions
                        </a>

                        <a
                            class="nav-link <?= $activePage === 'profile' ? 'active' : '' ?>"
                            href="profile.php">
                            Profile
                        </a>

                        <?php if ($navUser['role'] === 'admin'): ?>

                            <a
                                class="nav-link <?= $activePage === 'admin' ? 'active' : '' ?>"
                                href="admin-dashboard.php">
                                Admin
                            </a>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>

                <!-- ACCOUNT CONTROLS -->
                <div
                    class="d-flex align-items-center gap-2 mt-3 mt-lg-0">

                    <!-- THEME -->
                    <button
                        id="themeToggle"
                        type="button"
                        class="theme-button"
                        aria-label="Toggle color theme">
                        ◐
                    </button>

                    <?php if ($navUser): ?>

                        <span
                            class="nav-welcome d-none d-xl-inline">

                            Hello,
                            <?= e(explode(
                                ' ',
                                trim($navUser['full_name'])
                            )[0]) ?>

                        </span>

                        <!-- LOGOUT -->
                        <form
                            action="logout.php"
                            method="post"
                            class="m-0">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>">

                            <button
                                class="btn btn-outline-brand"
                                type="submit">
                                Log out
                            </button>

                        </form>

                    <?php else: ?>

                        <a
                            class="btn btn-outline-brand"
                            href="login.php">
                            Log in
                        </a>

                        <a
                            class="btn btn-brand"
                            href="register.php">
                            Get started
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </nav>

</header>

<!-- PAGE CONTENT -->
<main class="container page-content">

    <?php if ($message = flash('success')): ?>

        <div
            class="alert alert-success"
            role="status">
            <?= e($message) ?>
        </div>

    <?php endif; ?>

    <?php if ($message = flash('error')): ?>

        <div
            class="alert alert-danger"
            role="alert">
            <?= e($message) ?>
        </div>

    <?php endif; ?>
