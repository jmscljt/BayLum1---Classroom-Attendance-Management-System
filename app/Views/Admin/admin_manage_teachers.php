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
    <link rel="stylesheet" href="/public/css/admin_style.css">
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
                    <button id="openTeacherModal" class="teacher-add-btn" type="button"><i class="fa-solid fa-user-plus"></i> Add Teacher Account</button>
                </div>
            </div>

            <div class="table-section-card">
                <h3>List of Registered Teachers</h3>
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
                            <tr>
                                <td colspan="6" class="empty-state"><em>empty</em></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <div id="teacherModal" class="section-modal-overlay" aria-hidden="true">
        <div class="section-modal-card" role="dialog" aria-modal="true" aria-labelledby="teacherModalTitle">
            <div class="section-modal-header">
                <h3 id="teacherModalTitle">Add Teacher Account</h3>
                <button id="closeTeacherModal" class="section-modal-close" type="button" aria-label="Close">&times;</button>
            </div>
            <form action="#" method="POST">
                <div class="section-form-group">
                    <label for="teacher_name">Full Name</label>
                    <input type="text" id="teacher_name" name="teacher_name" class="section-form-control" placeholder="Enter teacher name" required>
                </div>
                <div class="section-form-group">
                    <label for="teacher_email">Email</label>
                    <input type="email" id="teacher_email" name="teacher_email" class="section-form-control" placeholder="Enter teacher email" required>
                </div>
                <div class="section-form-group section-form-group-last">
                    <label for="teacher_section">Section / Advisory</label>
                    <input type="text" id="teacher_section" name="teacher_section" class="section-form-control" placeholder="Optional">
                </div>
                <div class="section-modal-actions">
                    <button id="cancelTeacherModal" type="button" class="section-cancel-btn">Cancel</button>
                    <button type="submit" class="section-action-btn">Save Teacher</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/public/js/adminManageTeachers.js"></script>

</body>
</html>