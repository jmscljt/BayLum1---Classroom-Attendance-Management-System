<?php
// Set local time zone
date_default_timezone_set('Asia/Manila');

if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'otherSectionHandled';

// Safety net so the page never warns if the variables are missing
$assignedSections = $assignedSections ?? [];
$assignedMessage  = $assignedMessage ?? null;
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
                    <h2>Assigned Students</h2>
                </div>
            </div>

            <?php if (!empty($assignedMessage)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($assignedMessage); ?></div>
            <?php endif; ?>

            <!-- Add Assigned Section -->
            <form method="POST" action="index.php?page=otherSectionHandled" class="student-buttons-section">
                <h4>Add Assigned Section</h4>
                <div class="add-section-form-group">
                    <select class="grade-btn" name="grade_level" required>
                        <option value="" disabled selected>Grade</option>
                        <option value="Grade 4">Grade 4</option>
                        <option value="Grade 5">Grade 5</option>
                        <option value="Grade 6">Grade 6</option>
                    </select>

                    <select class="section-btn" name="section" required>
                        <option value="" disabled selected>Section</option>
                        <option value="Mabait">Mabait</option>
                        <option value="Magalang">Magalang</option>
                        <option value="Masipag">Masipag</option>
                        <option value="Masayahin">Masayahin</option>
                        <option value="Matapat">Matapat</option>
                    </select>

                    <select class="subject-btn" name="subject" required>
                        <option value="" disabled selected>Subject</option>
                        <option value="Math">Math</option>
                        <option value="English">English</option>
                        <option value="Science">Science</option>
                        <option value="Filipino">Filipino</option>
                        <option value="Araling Panlipunan">Araling Panlipunan</option>
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
                                    <td><?php echo htmlspecialchars($s['grade_level'] . ' - ' . $s['section_name']); ?></td>
                                    <td><?php echo htmlspecialchars($s['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($s['class_adviser'] ?? '-'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-view btn-view-record"
                                                data-section-id="<?php echo (int) $s['section_id']; ?>"
                                                data-label="<?php echo htmlspecialchars($s['grade_level'] . ' - ' . $s['section_name'] . ' (' . $s['subject'] . ')'); ?>">View Record</button>
                                            <button type="button" class="btn-edit">Edit</button>
                                            <button type="button" class="btn-remove">Remove</button>
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

        <!-- Frame 1: dates (with calendar search) -->
        <div id="sectionRecordsModal" class="attendance-modal-overlay">
            <div class="attendance-modal-content">
                <div class="modal-header-row">
                    <h3 id="sectionRecordsTitle">Attendance Records</h3>
                    <button type="button" class="close-modal-btn" data-close>&times;</button>
                </div>

                <form id="sectionDateSearchForm" class="filter-action-group" style="margin-bottom: 15px;">
                    <input type="date" id="sectionDateSearch" class="date-filter-input">
                    <button type="submit" class="sync-btn">Search Date</button>
                </form>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="sectionRecordDates"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Frame 2: attendance for the chosen date -->
        <div id="sectionLogsModal" class="attendance-modal-overlay">
            <div class="attendance-modal-content">
                <div class="modal-header-row">
                    <h3 id="sectionLogsTitle">Attendance for</h3>
                    <button type="button" class="close-modal-btn" data-close>&times;</button>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>LRN</th>
                                <th>Name</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody id="sectionLogRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="/public/js/otherSectionRecords.js"></script>
</body>
</html>