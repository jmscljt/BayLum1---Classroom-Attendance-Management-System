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
                <label class="student-filter-field" for="studentGradeFilter">
                    <span>Grade Level</span>
                    <select id="studentGradeFilter" class="section-form-control">
                        <option value="">All Grades</option>
                        <?php
                        $filterGrades = array_values(array_unique(array_column($sections, 'grade_level')));
                        sort($filterGrades, SORT_NATURAL);
                        ?>
                        <?php foreach ($filterGrades as $gradeLevel): ?>
                            <option value="<?= htmlspecialchars($gradeLevel, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($gradeLevel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="student-filter-field" for="studentSectionFilter">
                    <span>Section Name</span>
                    <select id="studentSectionFilter" class="section-form-control">
                        <option value="">All Sections</option>
                        <?php foreach ($sections as $section): ?>
                            <option
                                value="<?= (int) $section['section_id'] ?>"
                                data-grade="<?= htmlspecialchars($section['grade_level'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <?= htmlspecialchars($section['section_name'], ENT_QUOTES, 'UTF-8') ?>
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
                    <span id="studentRecordCount" class="student-record-count" aria-live="polite">
                        <?= (int) count($students ?? []) ?> <?= count($students ?? []) === 1 ? 'student' : 'students' ?>
                    </span>
                </div>
                <?php if (!empty($studentResult)): ?>
                    <p class="section-feedback <?= !empty($studentResult['success']) ? 'is-success' : 'is-error' ?>" role="status">
                        <?= htmlspecialchars($studentResult['message'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
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
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $middleName = trim((string) ($student['middle_name'] ?? ''));
                                    $middleInitial = $middleName !== ''
                                        ? strtoupper(substr($middleName, 0, 1)) . '.'
                                        : '';
                                    $studentName = trim(
                                        ($student['last_name'] ?? '') . ', ' .
                                        ($student['first_name'] ?? '') .
                                        ($middleInitial !== '' ? ' ' . $middleInitial : '')
                                    );
                                    $studentSearchText = strtolower(
                                        (string) $student['lrn'] . ' ' .
                                        $studentName . ' ' .
                                        ($student['first_name'] ?? '') . ' ' .
                                        ($student['middle_name'] ?? '') . ' ' .
                                        ($student['last_name'] ?? '')
                                    );
                                    ?>
                                    <tr
                                        class="student-record-row"
                                        data-section-id="<?= (int) $student['section_id'] ?>"
                                        data-grade="<?= htmlspecialchars($student['grade_level'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-search="<?= htmlspecialchars($studentSearchText, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <td><?= htmlspecialchars((string) $student['lrn'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(
                                            ($student['grade_level'] ?? '') . ' - ' . ($student['section_name'] ?? ''),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?></td>
                                        <td><?= htmlspecialchars((string) ($student['gender'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= empty($student['fingerprint_id']) ? 'Unregistered' : 'Registered (ID ' . (int) $student['fingerprint_id'] . ')' ?></td>
                                        <td>
                                            <div class="student-row-actions">
                                                <button
                                                    class="action-btn edit-student-btn"
                                                    type="button"
                                                    title="Edit student"
                                                    aria-label="Edit <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-lrn="<?= htmlspecialchars((string) $student['lrn'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-first-name="<?= htmlspecialchars((string) ($student['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-middle-name="<?= htmlspecialchars((string) ($student['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-last-name="<?= htmlspecialchars((string) ($student['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-section-id="<?= (int) $student['section_id'] ?>"
                                                    data-gender="<?= htmlspecialchars((string) ($student['gender'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                ><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
                                                <form method="POST" action="index.php?page=admin_manage_students" class="deactivate-student-form">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="deactivate_student">
                                                    <input type="hidden" name="lrn" value="<?= htmlspecialchars((string) $student['lrn'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <button
                                                        class="action-btn delete-btn"
                                                        type="submit"
                                                        title="Deactivate student"
                                                        aria-label="Deactivate <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-student-name="<?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?>"
                                                    ><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="studentEmptyState" class="empty-state-row" hidden>
                                    <td colspan="6" class="empty-state"><em>No matching student records found.</em></td>
                                </tr>
                            <?php else: ?>
                                <tr id="studentEmptyState">
                                    <td colspan="6" class="empty-state"><em>No student records have been added yet.</em></td>
                                </tr>
                            <?php endif; ?>
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
            <form action="index.php?page=admin_manage_students" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="studentAction" name="action" value="add_student">
                <input type="hidden" id="currentStudentLrn" name="current_lrn" value="">
                <div class="section-form-group">
                    <label for="studentLrn">LRN (Learner Reference Number)</label>
                    <input id="studentLrn" name="lrn" type="text" class="section-form-control" placeholder="12-digit LRN" inputmode="numeric" maxlength="12" required>
                </div>
                <div class="section-form-group">
                    <label for="studentFirstName">First Name</label>
                    <input id="studentFirstName" name="first_name" type="text" class="section-form-control" placeholder="Enter first name" required>
                </div>
                <div class="section-form-group">
                    <label for="studentMiddleName">Middle Name</label>
                    <input id="studentMiddleName" name="middle_name" type="text" class="section-form-control" placeholder="Enter middle name">
                </div>
                <div class="section-form-group">
                    <label for="studentLastName">Last Name</label>
                    <input id="studentLastName" name="last_name" type="text" class="section-form-control" placeholder="Enter last name" required>
                </div>
                <div class="section-form-group">
                    <label for="studentGradeLevel">Grade Level</label>
                    <select id="studentGradeLevel" class="section-form-control" required>
                        <option value="" disabled selected>Select Grade Level</option>
                        <?php
                        $studentGrades = array_values(array_unique(array_column($sections, 'grade_level')));
                        sort($studentGrades, SORT_NATURAL);
                        ?>
                        <?php foreach ($studentGrades as $gradeLevel): ?>
                            <option value="<?= htmlspecialchars($gradeLevel, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($gradeLevel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="section-form-group">
                    <label for="studentSection">Section Name</label>
                    <select id="studentSection" name="section_id" class="section-form-control" required disabled>
                        <option value="" disabled selected>Select Section</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= (int) $section['section_id'] ?>" data-grade="<?= htmlspecialchars($section['grade_level'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($section['section_name'], ENT_QUOTES, 'UTF-8') ?>
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
                    <button id="saveStudentButton" type="submit" class="section-action-btn">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/public/js/adminManageStudents.js"></script>
</body>
</html>
