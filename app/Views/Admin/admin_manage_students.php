<?php
// Initialize base URL and current page active state
if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'admin_manage_students';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/public/css/admin_style.css">
    <title>Student Management - Bayan Luma 1 Elementary School</title>
</head>
<body>
    <?php require_once __DIR__ . '/admin_sidebar.php'; ?>

    <header>
        <div class="dashboard-header">
            <h1>Bayan Luma 1 Elementary School</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="dashboard-container student-management">
            <div class="overview-header-row student-page-header">
                <div class="overview-titles">
                    <h2>STUDENT MANAGEMENT</h2>
                </div>
                <div class="student-page-actions">
                    <button class="student-secondary-btn" type="button">
                        <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i> Import CSV
                    </button>
                    <button class="student-secondary-btn student-export-btn" type="button">
                        <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Export CSV
                    </button>
                    <button id="openStudentModal" class="section-action-btn" type="button">
                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Add Student
                    </button>
                </div>
            </div>

            <section class="student-filter-card" aria-label="Filter student records">
                <label class="student-search-field">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="studentSearch" type="search" placeholder="Search by LRN or student name..." aria-label="Search by LRN or student name">
                </label>
                <label class="student-section-field" for="studentSectionFilter">
                    <span>Grade &amp; Section</span>
                    <select id="studentSectionFilter" class="section-form-control">
                        <option value="">All Sections</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= (int) $section['section_id'] ?>">
                                <?= htmlspecialchars($section['grade_level'] . ' - ' . $section['section_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </section>

            <section class="table-section-card">
                <div class="student-table-heading">
                    <div>
                        <h3>Student Registry</h3>
                        <p>Student records and fingerprint enrollment status</p>
                    </div>
                    <span id="studentRecordCount" class="student-record-count" aria-live="polite">0 students</span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">LRN</th>
                                <th scope="col">Student Name</th>
                                <th scope="col">Grade &amp; Section</th>
                                <th scope="col">Gender</th>
                                <th scope="col">Fingerprint Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            <tr id="studentEmptyState">
                                <td colspan="6" class="empty-state"><em>No student records have been added yet.</em></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <div id="studentModal" class="section-modal-overlay" aria-hidden="true">
        <div class="section-modal-card" role="dialog" aria-modal="true" aria-labelledby="studentModalTitle">
            <div class="section-modal-header">
                <h3 id="studentModalTitle">Add New Student</h3>
                <button id="closeStudentModal" class="section-modal-close" type="button" aria-label="Close">&times;</button>
            </div>
            <form action="#" method="POST">
                <div class="section-form-group">
                    <label for="studentLrn">LRN (Learner Reference Number)</label>
                    <input id="studentLrn" name="lrn" type="text" class="section-form-control" placeholder="12-digit LRN" inputmode="numeric" maxlength="12" required>
                </div>
                <div class="section-form-group">
                    <label for="studentName">Student Full Name</label>
                    <input id="studentName" name="student_name" type="text" class="section-form-control" placeholder="Last Name, First Name M.I." required>
                </div>
                <div class="section-form-group">
                    <label for="studentGradeSection">Grade &amp; Section</label>
                    <select id="studentGradeSection" name="section" class="section-form-control" required>
                        <option value="" disabled selected>Select Section</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= (int) $section['section_id'] ?>">
                                <?= htmlspecialchars($section['grade_level'] . ' - ' . $section['section_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="section-form-group section-form-group-last">
                    <label for="studentGender">Gender</label>
                    <select id="studentGender" name="gender" class="section-form-control" required>
                        <option value="" disabled selected>Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="section-modal-actions">
                    <button id="cancelStudentModal" type="button" class="section-cancel-btn">Cancel</button>
                    <button type="submit" class="section-action-btn">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/public/js/adminManageStudents.js"></script>
</body>
</html>
