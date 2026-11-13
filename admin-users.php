<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';

$admin = require_admin();

$pageTitle = 'Manage Users';
$activePage = 'admin';
$extraCss = 'assets/css/admin.css';

$users = $pdo->query(
    'SELECT
        id,
        full_name,
        email,
        role,
        created_at

     FROM users

     ORDER BY created_at DESC, id DESC

     LIMIT 200'
)->fetchAll();

$studentCount = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM users
     WHERE role = 'student'"
)->fetchColumn();

$adminCount = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM users
     WHERE role = 'admin'"
)->fetchColumn();

require __DIR__ . '/includes/header.php';
?>

<section class="admin-hero">

    <div class="section-kicker">
        ADMINISTRATOR WORKSPACE
    </div>

    <h1 class="mt-3">
        User management<span style="color:#ffe16a">.</span>
    </h1>

    <p>
        View registered accounts and manage
        access to the administration features.
    </p>

</section>

<nav class="admin-tabs" aria-label="Administrator pages">

    <a href="admin-dashboard.php">Overview</a>

    <a href="admin-subjects.php">Subjects</a>

    <a href="admin-sessions.php">
        Session moderation
    </a>

    <a href="admin-users.php" class="active">
        Users
    </a>

</nav>

<div class="row g-3 mb-4">

    <div class="col-md-6">

        <div class="admin-kpi">

            <span class="kpi-symbol">◎</span>

            <div class="kpi-label">
                Student accounts
            </div>

            <div class="kpi-value">
                <?= number_format($studentCount) ?>
            </div>

        </div>

    </div>

    <div class="col-md-6">

        <div class="admin-kpi">

            <span class="kpi-symbol">✦</span>

            <div class="kpi-label">
                Administrator accounts
            </div>

            <div class="kpi-value">
                <?= number_format($adminCount) ?>
            </div>

        </div>

    </div>

</div>

<section class="admin-panel">

    <h2>
        Registered users
    </h2>

    <p class="admin-description">
        User accounts are created through registration.
        Only an administrator can promote a student
        to administrator or change their role.
    </p>

    <?php if (!$users): ?>

        <div class="admin-blank">
            <strong>No registered accounts found.</strong>
        </div>

    <?php else: ?>

        <div class="admin-table-wrap">

            <table class="admin-table w-100">

                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Joined</th>
                        <th>Role</th>
                        <th>Manage</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($users as $account): ?>

                        <?php
                        $userId = (int) $account['id'];

                        $isSelf = $userId ===
                            (int) $admin['id'];
                        ?>

                        <tr>

                            <td>

                                <div class="table-title">
                                    <?= e($account['full_name']) ?>
                                </div>

                                <div class="table-subtitle">
                                    User #<?= $userId ?>

                                    <?php if ($isSelf): ?>
                                        · You
                                    <?php endif; ?>
                                </div>

                            </td>

                            <td>
                                <?= e($account['email']) ?>
                            </td>

                            <td>
                                <?= e(date(
                                    'M j, Y',
                                    strtotime($account['created_at'])
                                )) ?>
                            </td>

                            <td>

                                <span class="badge <?= $account['role'] === 'admin' ? 'text-bg-primary' : 'text-bg-secondary' ?>">

                                    <?= e(ucfirst($account['role'])) ?>

                                </span>

                            </td>

                            <td>

                                <?php if ($isSelf): ?>

                                    <span class="small text-secondary">
                                        Cannot change own role
                                    </span>

                                <?php else: ?>

                                    <form
                                        method="post"
                                        action="admin-action.php"
                                        class="d-flex flex-wrap gap-2">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="role_update">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $userId ?>">

                                        <select
                                            class="form-select form-select-sm"
                                            name="role"
                                            aria-label="Role for <?= e($account['full_name']) ?>"
                                            style="width:125px">

                                            <option
                                                value="student"
                                                <?= $account['role'] === 'student' ? 'selected' : '' ?>>
                                                Student
                                            </option>

                                            <option
                                                value="admin"
                                                <?= $account['role'] === 'admin' ? 'selected' : '' ?>>
                                                Admin
                                            </option>

                                        </select>

                                        <button
                                            class="btn btn-outline-brand btn-sm"
                                            type="submit">
                                            Save
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

    For account security, administrators cannot
    change their own role here, and the system
    prevents removing the last administrator.

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
