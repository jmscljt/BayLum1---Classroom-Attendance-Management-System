<?php
// Set local time zone
date_default_timezone_set('Asia/Manila');

if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'students';

$searchDate = $_GET['attendance_date'] ?? date('Y-m-d');
if (!DateTime::createFromFormat('Y-m-d', $searchDate)) {
    $searchDate = date('Y-m-d');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/teachers_style.css">
    <title>BL1ES - Students</title>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <header>
        <div class="dashboard-header">
            <h1>Bayan Luma 1 Elementary School</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="students-container">

            <div class="overview-header-row">
                <div class="overview-titles">
                    <h2>STUDENTS MASTERLIST</h2>
                </div>
            </div>

            <div class="student-buttons-section">
                <h4>Count - 100</h4>
                <div class="student-actions-right">
                    <button class="import_excel_btn">Import Excel</button>
                    <button class="export_excel_btn">Export Excel</button>
                    <button class="add-student-btn">Add Student</button>
                </div>
            </div>

            <div class="table-section-card">
                <h3>Attendance Record Summary</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>LRN</th>
                                <th>Student Name</th>
                                <th>Gender</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Excused</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Registered</td>
                                <td>107989120052</td>
                                <td>James Gabriel Calajate</td>
                                <td>Male</td>
                                <td>20</td>
                                <td>5</td>
                                <td>2</td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn-view" onclick="openAbsencesModal()">View Absences</button>
                                        <button class="btn-edit">Edit</button>
                                        <button class="btn-remove">Remove</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="absencesModal" class="absences-modal-overlay">
                <div class="absences-modal-content">
                    <div class="modal-header-row">
                        <h3>Mark Attendance Status - <?php echo htmlspecialchars($searchDate); ?></h3>
                        <button type="button" class="close-modal-btn" onclick="closeAbsencesModal()">&times;</button>
                    </div>
                    
                    <form method="POST" action="<?php echo $base_url; ?>/teachers/saveAbsenceStatus">
                        <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($searchDate); ?>">
                        
                        <div class="input-group" style="margin-bottom: 15px;">
                            <label>Date</label>
                            <input type="text" value="<?php echo htmlspecialchars($searchDate); ?>" readonly class="input-group input[readonly]">
                        </div>

                        <div class="input-group" style="margin-bottom: 15px;">
                            <label>Marked As</label>
                            <select name="attendance_status" id="attendanceStatusSelect" class="input-group select" onchange="toggleExcuseReason(this)">
                                <option value="ABSENT" selected>ABSENT</option>
                                <option value="EXCUSE">EXCUSE</option>
                            </select>
                        </div>

                        <div class="input-group" id="excuseReasonGroup" style="margin-bottom: 15px; display: none;">
                            <label>Reason for Excuse</label>
                            <select name="excuse_reason_category" class="input-group select" style="margin-bottom: 8px;">
                                <option value="" disabled selected>Select reason...</option>
                                <option value="Medical / Sick">Medical / Sick</option>
                                <option value="Family Emergency">Family Emergency</option>
                                <option value="School Activity / Representing School">School Activity / Representing School</option>
                                <option value="Other">Other</option>
                            </select>
                            <textarea name="excuse_remarks" placeholder="Enter specific details or remarks for being excuse..." class="input-group input" style="height: 70px; resize: vertical; padding: 8px;"></textarea>
                        </div>

                        <div class="absences-modal-actions">
                            <button type="button" class="sync-btn" style="background-color: #6c757d;" onclick="closeAbsencesModal()">Cancel</button>
                            <button type="submit" class="sync-btn" style="background-color: #0056b3;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="/public/js/studentsAbbsences.js"></script>
</body>
</html>