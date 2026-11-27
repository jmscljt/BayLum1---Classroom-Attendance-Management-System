<?php
if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'admin_settings';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/images/BL1-Logo.png?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/admin_style.css">
    <title>Admin Settings - Bayan Luma 1 Elementary School</title>
</head>
<body>
    <?php require_once __DIR__ . '/admin_sidebar.php'; ?>

    <header>
        <div class="dashboard-header">
            <h1>Bayan Luma 1 Elementary School</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="dashboard-container">
            <div class="overview-header-row">
                <div class="overview-titles">
                    <h2>SETTINGS</h2>
                    <p class="admin-settings-description">Manage your administrator profile and attendance rules.</p>
                </div>
            </div>

            <div class="admin-settings-grid">
                <section class="admin-settings-card admin-settings-card-wide">
                    <div class="admin-settings-card-heading">
                        <span class="admin-settings-icon"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></span>
                        <div>
                            <h3>Admin Profile</h3>
                            <p>Update your administrator profile and password.</p>
                        </div>
                    </div>
                    <form class="admin-settings-form" action="#" method="post">
                        <div class="admin-profile-columns">
                            <div class="admin-profile-group">
                                <h4>Profile Information</h4>
                                <div class="admin-settings-field">
                                    <label for="adminProfileName">Full name</label>
                                    <input id="adminProfileName" name="full_name" class="section-form-control" type="text" value="<?php echo $adminName; ?>" autocomplete="name">
                                </div>
                                <div class="admin-settings-field">
                                    <label for="adminProfileEmail">Email address</label>
                                    <input id="adminProfileEmail" name="email" class="section-form-control" type="email" placeholder="Enter email address" autocomplete="email">
                                </div>
                            </div>
                            <div class="admin-profile-group">
                                <h4>Change Password</h4>
                                <div class="admin-settings-field">
                                    <label for="currentPassword">Current password</label>
                                    <input id="currentPassword" name="current_password" class="section-form-control" type="password" placeholder="Enter current password" autocomplete="current-password">
                                </div>
                                <div class="admin-settings-field">
                                    <label for="newPassword">New password</label>
                                    <input id="newPassword" name="new_password" class="section-form-control" type="password" placeholder="At least 8 characters" autocomplete="new-password">
                                </div>
                                <div class="admin-settings-field">
                                    <label for="confirmPassword">Confirm new password</label>
                                    <input id="confirmPassword" name="confirm_password" class="section-form-control" type="password" placeholder="Re-enter new password" autocomplete="new-password">
                                </div>
                            </div>
                        </div>
                        <button class="section-action-btn" type="button">Save profile &amp; password</button>
                    </form>
                </section>

                <section class="admin-settings-card admin-settings-card-wide">
                    <div class="admin-settings-card-heading">
                        <span class="admin-settings-icon"><i class="fa-solid fa-clock" aria-hidden="true"></i></span>
                        <div>
                            <h3>Attendance Settings</h3>
                            <p>Configure manual attendance and daily time rules.</p>
                        </div>
                    </div>
                    <form class="admin-settings-form" action="#" method="post">
                        <label class="admin-settings-toggle">
                            <input type="checkbox" name="manual_attendance_enabled">
                            <span class="admin-settings-switch" aria-hidden="true"></span>
                            <span>
                                <strong>Manual attendance</strong>
                                <small>Allow teachers to record attendance manually.</small>
                            </span>
                        </label>
                        <div class="admin-settings-attendance-fields">
                            <div class="admin-settings-field">
                                <label for="timeIn">Time-in</label>
                                <input id="timeIn" name="time_in" class="section-form-control" type="time">
                            </div>
                            <div class="admin-settings-field">
                                <label for="timeOut">Time-out</label>
                                <input id="timeOut" name="time_out" class="section-form-control" type="time">
                            </div>
                            <div class="admin-settings-field">
                                <label for="gracePeriod">Grace period (minutes)</label>
                                <input id="gracePeriod" name="grace_period" class="section-form-control" type="number" min="0" placeholder="e.g. 10">
                            </div>
                        </div>
                        <button class="section-action-btn" type="button">Save attendance settings</button>
                    </form>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
