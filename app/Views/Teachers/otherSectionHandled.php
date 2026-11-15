<?php
// Set local time zone
date_default_timezone_set('Asia/Manila');

if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'otherSectionHandled';

// Safety net so the page never warns if the variables are missing
$assignedSections = $assignedSections ?? [];
$availableSections = $availableSections ?? [];
$subjects = $subjects ?? [];
$csrfToken = $csrfToken ?? '';
$assignedMessage  = $assignedMessage ?? null;
$gradeLevels = array_values(array_unique(array_column($availableSections, 'grade_level')));
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/teachers_style.css">
    <title>BL1ES - Other Section Handled</title>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <header>
        <div class="dashboard-header">
            <h1>Bayan Luma 1 Elementary School</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="assigned-section-container">

            <div class="overview-header-row">
                <div class="overview-titles">
                    <h2>ASSIGNED STUDENTS</h2>
                </div>
            </div>

            <?php if (is_array($assignedMessage) && !empty($assignedMessage['message'])): ?>
                <div class="alert <?php echo !empty($assignedMessage['success']) ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $escape($assignedMessage['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Add Assigned Section -->
            <form method="POST" action="index.php?page=otherSectionHandled" class="student-buttons-section">
                <h4>Add Assigned Section</h4>
                <div class="add-section-form-group">
                    <input type="hidden" name="action" value="add_assignment">
                    <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                    <select class="grade-btn" data-grade-select required>
                        <option value="">Grade</option>
                        <?php foreach ($gradeLevels as $gradeLevel): ?>
                            <option value="<?php echo $escape($gradeLevel); ?>"><?php echo $escape($gradeLevel); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select class="section-btn" name="section_id" data-section-select required>
                        <option value="">Section</option>
                        <?php foreach ($availableSections as $section): ?>
                            <option value="<?php echo (int) $section['section_id']; ?>"
                                data-grade="<?php echo $escape($section['grade_level']); ?>">
                                <?php echo $escape($section['section_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select class="subject-btn" name="subject" required>
                        <option value="">Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $escape($subject); ?>"><?php echo $escape($subject); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="add-section-btn">Add</button>
                </div>
            </form>

            <!-- Assigned sections table -->
            <div class="table-section-card assigned-sections-table">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Grade and Section</th>
                                <th>Subject</th>
                                <th>Class Adviser</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignedSections as $s): ?>
                                <tr>
                                    <td><?php echo $escape($s['grade_level'] . ' - ' . $s['section_name']); ?></td>
                                    <td><?php echo $escape($s['subject']); ?></td>
                                    <td><?php echo $escape($s['class_adviser'] ?? '-'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-view btn-view-record"
                                                data-assignment-id="<?php echo (int) $s['assignment_id']; ?>"
                                                data-label="<?php echo $escape($s['grade_level'] . ' - ' . $s['section_name']); ?>">View Record</button>
                                            <button type="button" class="btn-edit btn-edit-assignment"
                                                data-assignment-id="<?php echo (int) $s['assignment_id']; ?>"
                                                data-section-id="<?php echo (int) $s['section_id']; ?>"
                                                data-grade="<?php echo $escape($s['grade_level']); ?>"
                                                data-subject="<?php echo $escape($s['subject']); ?>">Edit</button>
                                            <form method="POST" action="index.php?page=otherSectionHandled" class="remove-assignment-form">
                                                <input type="hidden" name="action" value="remove_assignment">
                                                <input type="hidden" name="assignment_id" value="<?php echo (int) $s['assignment_id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                                <button type="submit" class="btn-remove">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($assignedSections)): ?>
                                <tr>
                                    <td colspan="4" class="no-data-cell">No assigned sections found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="sectionRecordsModal" class="attendance-modal-overlay" aria-hidden="true">
            <div class="attendance-modal-content">
                <div class="modal-header-row">
                    <h3 id="sectionRecordsTitle">Students</h3>
                    <button type="button" class="close-modal-btn" data-close aria-label="Close">&times;</button>
                </div>
                <p id="sectionAdviser"></p>
                <p id="sectionRecordMessage" role="status"></p>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>LRN</th>
                                <th>Name</th>
                                <th>Gender</th>
                            </tr>
                        </thead>
                        <tbody id="sectionLogRows"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="editAssignmentModal" class="attendance-modal-overlay" aria-hidden="true">
            <div class="attendance-modal-content">
                <div class="modal-header-row">
                    <h3>Edit Assigned Section</h3>
                    <button type="button" class="close-modal-btn" data-close aria-label="Close">&times;</button>
                </div>
                <form method="POST" action="index.php?page=otherSectionHandled" class="student-buttons-section">
                    <input type="hidden" name="action" value="edit_assignment">
                    <input type="hidden" name="assignment_id" id="editAssignmentId">
                    <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                    <div class="add-section-form-group">
                        <select class="grade-btn" id="editGradeSelect" data-grade-select required>
                            <option value="">Grade</option>
                            <?php foreach ($gradeLevels as $gradeLevel): ?>
                                <option value="<?php echo $escape($gradeLevel); ?>"><?php echo $escape($gradeLevel); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="section-btn" name="section_id" id="editSectionSelect" data-section-select required>
                            <option value="">Section</option>
                            <?php foreach ($availableSections as $section): ?>
                                <option value="<?php echo (int) $section['section_id']; ?>"
                                    data-grade="<?php echo $escape($section['grade_level']); ?>">
                                    <?php echo $escape($section['section_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select class="subject-btn" name="subject" id="editSubjectSelect" required>
                            <option value="">Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $escape($subject); ?>"><?php echo $escape($subject); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="add-section-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="/public/js/otherSectionHandled.js"></script>
</body>
</html>