<?php
// Set local time zone
date_default_timezone_set('Asia/Manila');

if (!isset($base_url)) {
    $base_url = '';
}

$currentPage = 'attendanceLogs';

$assignedGrade   = $_SESSION['assigned_grade_level'] ?? 'Grade';
$advisorySection = $_SESSION['advisory_section'] ?? 'Section';

$searchDate = $searchDate ?? null;
$attendanceSummaries = $attendanceSummaries ?? [];
$showLogs = $showLogs ?? false;
$displaySearchDate = $searchDate !== null ? date('M j, Y', strtotime($searchDate)) : '';
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
                    <input type="date" name="attendance_date" class="date-filter-input" value="<?php echo htmlspecialchars($searchDate ?? '', ENT_QUOTES, 'UTF-8'); ?>">
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
                            <?php if (empty($attendanceSummaries)): ?>
                                <tr>
                                    <td colspan="4"><?php echo $searchDate === null ? 'No attendance records have been recorded yet.' : 'No attendance record for this day.'; ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($attendanceSummaries as $summary): ?>
                                    <?php
                                    $summaryDate = $summary['attendance_date'];
                                    $summaryDisplayDate = date('M j, Y', strtotime($summaryDate));
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($summaryDisplayDate, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo (int) $summary['present_count']; ?></td>
                                        <td><?php echo (int) $summary['absent_count']; ?></td>
                                        <td>
                                            <?php
                                            $logsUrl = 'index.php?' . http_build_query([
                                                    'page' => 'attendanceLogs',
                                                    'attendance_date' => $summaryDate,
                                                    'show_logs' => '1',
                                            ]);
                                            ?>
                                            <button type="button" class="view-btn" onclick="<?php echo $searchDate !== null && !$showLogs ? 'openAttendanceModal()' : 'window.location.href=\'' . htmlspecialchars($logsUrl, ENT_QUOTES, 'UTF-8') . '\''; ?>">
                                                View Logs
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($searchDate !== null): ?>
        <div id="attendanceModal" class="attendance-modal-overlay">
            <div class="attendance-modal-content">
                <div class="modal-header-row">
                    <h3>Attendance for <?php echo htmlspecialchars($displaySearchDate, ENT_QUOTES, 'UTF-8'); ?></h3>
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
                                    <td colspan="7">No attendance record for this day.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($attendanceLogs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('M j, Y', strtotime($searchDate)), ENT_QUOTES, 'UTF-8'); ?></td>
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
        <?php endif; ?>
    </main>

    <script src="/public/js/attendanceRecord.js"></script>
    <?php if ($searchDate !== null && $showLogs): ?>
        <script>openAttendanceModal();</script>
    <?php endif; ?>
    
</body>
</html>