<?php
// Set local time zone
date_default_timezone_set('Asia/Manila');

if (!isset($base_url)) {
    $base_url = '';
}

$currentPage = 'attendanceLogs';

$assignedGrade   = $_SESSION['assigned_grade_level'] ?? 'Grade';
$advisorySection = $_SESSION['advisory_section'] ?? 'Section';

$searchDate = $searchDate ?? ($_GET['attendance_date'] ?? date('Y-m-d'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/teachers_style.css">
    <title>BL1ES - Attendance Logs</title>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    <header>
        <div class="dashboard-header">
            <h1>Bayan Luma 1 Elementary School</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="attendance-container">           
            <div class="overview-header-row">
                <div class="overview-titles">
                    <h2>CLASS ATTENDANCE</h2>
                </div>

                <form method="GET" action="" class="filter-action-group">
                    <input type="hidden" name="page" value="attendanceLogs">
                    <input type="date" name="attendance_date" class="date-filter-input" value="<?php echo htmlspecialchars($searchDate, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="sync-btn">Search Date
                    </button>
                </form>
            </div>

            <div class="table-section-card">
                <h4>Attendance Record Summary of <?php echo htmlspecialchars($assignedGrade . ' ' . $advisorySection); ?></h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Present Students</th>
                                <th>Absent Students</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars($searchDate, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) $presentCount; ?></td>
                                <td><?php echo (int) $absentCount; ?></td>
                                <td>
                                    <button type="button" class="view-btn" onclick="openAttendanceModal()">
                                        View Logs
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="attendanceModal" class="attendance-modal-overlay">
            <div class="attendance-modal-content">
                <div class="modal-header-row">
                    <h3>Attendance for <?php echo htmlspecialchars($searchDate); ?></h3>
                    <button type="button" class="close-modal-btn" onclick="closeAttendanceModal()">&times;</button>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>LRN</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attendanceLogs)): ?>
                                <tr>
                                    <td colspan="7">No manual attendance records for this date.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($attendanceLogs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($searchDate, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($log['lrn'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(trim($log['first_name'] . ' ' . ($log['middle_name'] ?? '') . ' ' . $log['last_name']), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($log['gender'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo $log['time_in'] ? htmlspecialchars(date('h:i A', strtotime($log['time_in'])), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                        <td><?php echo $log['time_out'] ? htmlspecialchars(date('h:i A', strtotime($log['time_out'])), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                        <td>Manual</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="/public/js/attendanceRecord.js"></script>
    
</body>
</html>