<?php
if (!isset($base_url)) {
    $base_url = '';
}

$currentPage = 'teachers_settings';
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$teacherName = $_SESSION['user_name'] ?? 'Teacher Name';
$teacherUniqueId = $_SESSION['user_unique_id'] ?? 'Not available';
$assignedGrade = $_SESSION['assigned_grade_level'] ?? 'Select Grade';
$advisorySection = $_SESSION['advisory_section'] ?? 'Select Section';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/teachers_style.css">
    <title>BL1ES - Teacher Settings</title>
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
                <h3>Teacher Account</h3>
                <div class="teacher-settings-details">
                    <div class="settings-detail">
                        <span class="settings-label">Unique ID (View Only)</span>
                        <span class="settings-value"><?= $escape($teacherUniqueId) ?></span>
                    </div>
                    <div class="settings-detail">
                        <span class="settings-label">Name</span>
                        <span class="settings-value"><?= $escape($teacherName) ?></span>
                    </div>

                    <div class="settings-detail settings-section-detail">
                        <div>
                            <span class="settings-label">Section (Handled)</span>
                            <span class="settings-value"><?= $escape($assignedGrade . ' - ' . $advisorySection) ?></span>
                        </div>
                        <button type="button" class="settings-button settings-secondary-button">
                            Edit
                        </button>
                    </div>
                </div>

                <div class="teacher-settings-form settings-section-form">
                    <div class="settings-form-row">
                        <div class="settings-field">
                            <label for="settingsGrade">Grade</label>
                            <select id="settingsGrade">
                                <option selected><?= $escape($assignedGrade) ?></option>
                                <option>Grade 4</option>
                                <option>Grade 5</option>
                                <option>Grade 6</option>
                            </select>
                        </div>
                        <div class="settings-field">
                            <label for="settingsSection">Section</label>
                            <select id="settingsSection">
                                <option selected><?= $escape($advisorySection) ?></option>
                                <option>Mabait</option>
                                <option>Magalang</option>
                                <option>Masipag</option>
                                <option>Masayahin</option>
                                <option>Matapat</option>
                            </select>
                        </div>
                        <button type="button" class="settings-button">Save</button>
                    </div>
                </div>
            </section>

            <section class="table-section-card teacher-settings-card">
                <h3>Change Password</h3>
                <div class="teacher-settings-form">
                    <div class="settings-form-row settings-password-fields">
                        <div class="settings-field">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword" autocomplete="current-password">
                        </div>
                        <div class="settings-field">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" autocomplete="new-password">
                        </div>
                        <div class="settings-field">
                            <label for="confirmNewPassword">Confirm New Password</label>
                            <input type="password" id="confirmNewPassword" autocomplete="new-password">
                        </div>
                    </div>
                    <button type="button" class="settings-button">Update Password</button>
                </div>
            </section>

            <section class="table-section-card teacher-settings-card">
                <h3>Manual Attendance Codes</h3>
                <div class="settings-attendance-codes">
                    <div class="metric-card metric-card-blue">
                        <span class="metric-title">Time In Code</span>
                        <span class="metric-value">----</span>
                    </div>
                    <div class="metric-card metric-card-blue">
                        <span class="metric-title">Time Out Code</span>
                        <span class="metric-value">----</span>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
