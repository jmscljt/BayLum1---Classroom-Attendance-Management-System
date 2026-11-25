<?php
if (!isset($base_url)) {
    $base_url = '';
}

$currentPage = 'accountSettings';
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$teacherName = $_SESSION['user_name'] ?? 'Teacher Name';
$teacherUniqueId = $_SESSION['user_unique_id'] ?? 'Not available';
$assignedGrade = $_SESSION['assigned_grade_level'] ?? 'Select Grade';
$advisorySection = $_SESSION['advisory_section'] ?? 'Select Section';
$result = $accountResult ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $escape($base_url) ?>/public/css/teachers_style.css">
    <title>BL1ES - Account Settings</title>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <header>
        <div class="dashboard-header">
            <h1>Bayan Luma 1 Elementary School</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="dashboard-container teacher-settings-container">
            <div class="overview-header-row">
                <div class="overview-titles">
                    <h2>ACCOUNT SETTINGS</h2>
                    <p class="overview-date">Teacher account preferences.</p>
                </div>
            </div>

            <section class="table-section-card teacher-settings-card">
                <h3>Account Information</h3>
                <?php if (!empty($result)): ?>
                    <div class="alert alert-<?= !empty($result['success']) ? 'success' : 'error'; ?>" role="status">
                        <?= $escape($result['message'] ?? '') ?>
                    </div>
                <?php endif; ?>
                <div class="teacher-settings-details">
                    <div class="settings-detail">
                        <span class="settings-label">Unique ID (View Only)</span>
                        <span class="settings-value"><?= $escape($teacherUniqueId) ?></span>
                    </div>
                    <div class="settings-detail">
                        <span class="settings-label">Username</span>
                        <span class="settings-value"><?= $escape($teacherName) ?></span>
                    </div>

                    <div class="settings-detail settings-section-detail">
                        <div class="settings-handled-values">
                            <span class="settings-label">Section (Handled)</span>
                            <span class="settings-value"><?= $escape($assignedGrade) ?> - <?= $escape($advisorySection) ?></span>
                        </div>
                        <button type="button" id="editAssignedSection" class="settings-button settings-secondary-button">
                            Edit
                        </button>
                    </div>
                </div>

                <form action="index.php?page=teachers_settings" method="POST" id="settingsSectionForm" class="teacher-settings-form settings-section-form" hidden>
                    <input type="hidden" name="action" value="update_section">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="settings-form-row">
                        <div class="settings-field">
                            <label for="settingsGrade">Grade</label>
                            <select id="settingsGrade" required>
                                <option value="" disabled>Select Grade</option>
                            </select>
                        </div>
                        <div class="settings-field">
                            <label for="settingsSection">Section</label>
                            <select id="settingsSection" name="section_id" required>
                                <option value="" disabled>Select Section</option>
                            </select>
                        </div>
                        <button type="submit" class="settings-button">Save</button>
                    </div>
                </form>
            </section>

            <section class="table-section-card teacher-settings-card">
                <h3>Change Password</h3>
                <form action="index.php?page=accountSettings" method="POST" class="teacher-settings-form">
                    <input type="hidden" name="action" value="update_password">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="settings-form-row settings-password-fields">
                        <div class="settings-field">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword" name="current_password" autocomplete="current-password" required>
                        </div>
                        <div class="settings-field">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="new_password" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div class="settings-field">
                            <label for="confirmNewPassword">Confirm New Password</label>
                            <input type="password" id="confirmNewPassword" name="confirm_password" minlength="8" autocomplete="new-password" required>
                        </div>
                    </div>
                    <button type="submit" class="settings-button">Update Password</button>
                </form>
            </section>

        </div>
    </main>
    <script>
        const accountSections = <?= json_encode($sections) ?>;
        const assignedGrade = <?= json_encode($assignedGrade) ?>;
        const assignedSection = <?= json_encode($advisorySection) ?>;
    </script>
    <script src="<?= $escape($base_url) ?>/public/js/accountSettings.js"></script>
    <script src="/public/js/alertTimer.js"></script>
</body>
</html>
