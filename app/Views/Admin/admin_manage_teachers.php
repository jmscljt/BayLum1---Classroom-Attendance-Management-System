<?php
// Initialize base URL and current page active state
if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'admin_manage_teachers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FontAwesome Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/public/css/admin_style.css">
    <title>Teacher Accounts Management - Admin</title>
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
                    <h2>TEACHER ACCOUNTS MANAGEMENT</h2>
                </div>
                <div class="teacher-accounts-actions">
                    <input type="search" class="teacher-search-input" placeholder="Search teacher name..." aria-label="Search teacher name">
                </div>
            </div>

            <div class="table-section-card">
                <h3>List of Registered Teachers</h3>
                <?php if (!empty($teacherResult)): ?>
                    <p class="section-feedback <?= !empty($teacherResult['success']) ? 'is-success' : 'is-error' ?>" role="status">
                        <?= htmlspecialchars($teacherResult['message'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Unique ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Section / Advisory</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teachers)): ?>
                                <tr>
                                    <td colspan="6" class="empty-state"><em>No teacher accounts have been registered yet.</em></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teachers as $teacher): ?>
                                    <?php
                                    $nameParts = preg_split('/\s+/', trim((string) $teacher['full_name']), 2) ?: [];
                                    $firstName = trim((string) ($teacher['first_name'] ?? $nameParts[0] ?? ''));
                                    $lastName = trim((string) ($teacher['last_name'] ?? $nameParts[1] ?? ''));
                                    ?>
                                    <tr class="teacher-row" data-search="<?= htmlspecialchars(strtolower($teacher['full_name'] . ' ' . $teacher['email'] . ' ' . $teacher['user_id']), ENT_QUOTES, 'UTF-8') ?>">
                                        <td><?= htmlspecialchars($teacher['user_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($teacher['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($teacher['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?= empty($teacher['section_name'])
                                                ? '<em>Not assigned</em>'
                                                : htmlspecialchars($teacher['grade_level'] . ' - ' . $teacher['section_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <span class="teacher-status-badge <?= (int) $teacher['is_active'] === 1 ? 'is-active' : 'is-inactive' ?>">
                                                <?= (int) $teacher['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="section-row-actions">
                                                <button
                                                    class="section-action-btn edit-teacher-btn"
                                                    type="button"
                                                    data-teacher-id="<?= (int) $teacher['id'] ?>"
                                                    data-first-name="<?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-last-name="<?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-email="<?= htmlspecialchars($teacher['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                >Edit</button>
                                                <form method="POST" action="index.php?page=admin_manage_teachers" class="teacher-status-form">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="set_teacher_status">
                                                    <input type="hidden" name="teacher_id" value="<?= (int) $teacher['id'] ?>">
                                                    <input type="hidden" name="is_active" value="<?= (int) $teacher['is_active'] === 1 ? '0' : '1' ?>">
                                                    <button
                                                        class="teacher-status-toggle-btn <?= (int) $teacher['is_active'] === 1 ? 'deactivate' : 'activate' ?>"
                                                        type="submit"
                                                        data-teacher-name="<?= htmlspecialchars($teacher['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-next-status="<?= (int) $teacher['is_active'] === 1 ? 'inactive' : 'active' ?>"
                                                    ><?= (int) $teacher['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                                                </form>
                                                <form method="POST" action="index.php?page=admin_manage_teachers" class="remove-teacher-form">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="remove_teacher">
                                                    <input type="hidden" name="teacher_id" value="<?= (int) $teacher['id'] ?>">
                                                    <button class="section-cancel-btn teacher-remove-btn" type="submit" data-teacher-name="<?= htmlspecialchars($teacher['full_name'], ENT_QUOTES, 'UTF-8') ?>">Delete Account</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <div id="teacherModal" class="section-modal-overlay" aria-hidden="true">
        <div class="section-modal-card" role="dialog" aria-modal="true" aria-labelledby="teacherModalTitle">
            <div class="section-modal-header">
                <h3 id="teacherModalTitle">Edit Teacher Account</h3>
                <button id="closeTeacherModal" class="section-modal-close" type="button" aria-label="Close">&times;</button>
            </div>
            <form action="index.php?page=admin_manage_teachers" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="update_teacher">
                <input type="hidden" id="teacher_id" name="teacher_id" value="">
                <div class="section-form-group">
                    <label for="teacher_first_name">First Name</label>
                    <input type="text" id="teacher_first_name" name="teacher_first_name" class="section-form-control" placeholder="Enter first name" maxlength="50" required>
                </div>
                <div class="section-form-group">
                    <label for="teacher_last_name">Last Name</label>
                    <input type="text" id="teacher_last_name" name="teacher_last_name" class="section-form-control" placeholder="Enter last name" maxlength="50" required>
                </div>
                <div class="section-form-group">
                    <label for="teacher_email">Email</label>
                    <input type="email" id="teacher_email" name="teacher_email" class="section-form-control" placeholder="Enter teacher email" required>
                </div>
                <div class="section-form-group section-form-group-last">
                    <label for="teacher_password">New Password (Optional)</label>
                    <input type="password" id="teacher_password" name="teacher_password" class="section-form-control" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep current password">
                    <small>Enter at least 8 characters to change the teacher's password.</small>
                </div>
                <div class="section-modal-actions">
                    <button id="cancelTeacherModal" type="button" class="section-cancel-btn">Cancel</button>
                    <button id="saveTeacherButton" type="submit" class="section-action-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/public/js/adminManageTeachers.js"></script>

</body>
</html>