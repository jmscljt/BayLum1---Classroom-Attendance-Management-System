<?php
if (!isset($base_url)) {
    $base_url = '';
}

$currentPage = 'accountSettings';
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$teacherName = $_SESSION['user_name'] ?? 'Teacher Name';
$teacherUniqueId = $_SESSION['user_unique_id'] ?? 'Not available';
$teacherEmail = $_SESSION['user_email'] ?? ($account['email'] ?? 'Not available');
$emailParts = explode('@', (string) $teacherEmail, 2);
$teacherEmailLocalPart = count($emailParts) === 2 ? $emailParts[0] : '';
$teacherFirstName = $account['first_name'] ?? ($_SESSION['user_first_name'] ?? '');
$teacherLastName = $account['last_name'] ?? ($_SESSION['user_last_name'] ?? '');
if ($teacherFirstName === '' && $teacherLastName === '') {
    $nameParts = preg_split('/\s+/', trim($teacherName), 2);
    $teacherFirstName = $nameParts[0] ?? '';
    $teacherLastName = $nameParts[1] ?? '';
}
$assignedGrade = $_SESSION['assigned_grade_level'] ?? 'Select Grade';
$advisorySection = $_SESSION['advisory_section'] ?? 'Select Section';
$result = $accountResult ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/images/BL1-Logo.png?v=2">
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

                <?php if (!empty($result)): ?>
                    <div class="alert alert-<?= !empty($result['success']) ? 'success' : 'error'; ?>" role="status">
                        <?= $escape($result['message'] ?? '') ?>
                    </div>
                <?php endif; ?>

            <section class="table-section-card teacher-settings-card">
                <h3>Account Information</h3>
                <form action="index.php?page=accountSettings" method="POST" id="profileForm" class="teacher-settings-form">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="teacher-settings-details">
                        <div class="settings-form-row profile-name-fields">
                            <div class="settings-field">
                                <label for="teacherUniqueId">Unique ID (View Only)</label>
                                <input type="text" id="teacherUniqueId" value="<?= $escape($teacherUniqueId) ?>" readonly>
                            </div>
                            <div class="settings-field">
                                <label for="teacherEmail">Email</label>
                                <div class="email-input-group">
                                    <input type="text" id="teacherEmail" name="email_username" value="<?= $escape($teacherEmailLocalPart) ?>" pattern="[^@\s]+" title="Enter only the part before @gmail.com." required>
                                    <span class="email-domain-suffix" aria-hidden="true">@gmail.com</span>
                                </div>
                            </div>
                        </div>
                        <div class="settings-form-row profile-name-fields">
                            <div class="settings-field">
                                <label for="teacherFirstName">First Name</label>
                                <input type="text" id="teacherFirstName" name="first_name" value="<?= $escape($teacherFirstName) ?>" required>
                            </div>
                            <div class="settings-field">
                                <label for="teacherLastName">Last Name</label>
                                <input type="text" id="teacherLastName" name="last_name" value="<?= $escape($teacherLastName) ?>" required>
                            </div>
                        </div>
                    </div>
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
                    </div>
                    <p class="teacher-settings-note"><strong>NOTE:</strong> Changing your grade or section will also change the grade and section associated with your records.</p>
                    <button type="submit" class="settings-button">Save Changes</button>
                </form>
            </section>

            <section class="table-section-card teacher-settings-card">
                <h3>Change Password</h3>
                <form action="index.php?page=accountSettings" method="POST" id="passwordForm" class="teacher-settings-form">
                    <input type="hidden" name="action" value="update_password">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="settings-form-row settings-password-fields">
                        <div class="settings-field">
                            <label for="currentPassword">Current Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="currentPassword" name="current_password" autocomplete="current-password" required>
                                <button type="button" class="password-visibility-toggle" aria-controls="currentPassword" aria-label="Show current password" aria-pressed="false" data-password-label="current password">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="settings-field">
                            <label for="newPassword">New Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="newPassword" name="new_password" minlength="8" autocomplete="new-password" required>
                                <button type="button" class="password-visibility-toggle" aria-controls="newPassword" aria-label="Show new password" aria-pressed="false" data-password-label="new password">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="settings-field">
                            <label for="confirmNewPassword">Confirm New Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="confirmNewPassword" name="confirm_password" minlength="8" autocomplete="new-password" required>
                                <button type="button" class="password-visibility-toggle" aria-controls="confirmNewPassword" aria-label="Show password confirmation" aria-pressed="false" data-password-label="password confirmation">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="settings-button">Update Password</button>
                </form>
            </section>

            <section class="table-section-card teacher-settings-card teacher-settings-danger-card">
                <h3>Deactivate Account</h3>
                <p class="teacher-settings-danger-message">
                    Deactivating your account will remove your access to the teacher portal. Account deactivation is not currently available due to not having a proper account deactivation process in place.
                </p>
                <button type="button" class="settings-button settings-danger-button" disabled>
                    Deactivate Account
                </button>
            </section>

        </div>
    </main>
    <script>
        const accountSections = <?= json_encode($sections) ?>;
        const assignedGrade = <?= json_encode($assignedGrade) ?>;
        const assignedSection = <?= json_encode($advisorySection) ?>;
    </script>
    <script src="<?= $escape($base_url) ?>/public/js/accountSettings.js"></script>
    <script src="<?= $escape($base_url) ?>/public/js/passwordVisibility.js"></script>
    <script src="/public/js/alertTimer.js"></script>
</body>
</html>
