<?php
// Set local time zone
date_default_timezone_set('Asia/Manila');

if (!isset($base_url)) {
    $base_url = '';
}

$currentPage = 'dashboard';
$message = $_SESSION['auth_message'] ?? '';
$message_type = $_SESSION['auth_message_type'] ?? '';
unset($_SESSION['auth_message'], $_SESSION['auth_message_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/teachers_style.css">
    <title>BL1ES - Dashboard</title>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <header>
        <div class="dashboard-header">
            <h1>Bayan Luma 1 Elementary School</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="dashboard-container">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <span><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <div class="overview-header-row dashboard-overview-header-row">
                <div class="overview-titles">
                    <h2>ATTENDANCE OVERVIEW</h2>
                    <p class="overview-date"><?php echo date('F d, Y'); ?></p>
                </div>
                <div class="dashboard-manual-attendance">
                    <div class="manual-attendance-card">
                        <form method="POST" action="index.php?page=manualAttendance" class="manual-attendance-form">
                            <div class="input-group">
                                <input type="text" name="manual_attendance" placeholder="Enter adviser code or student LRN" inputmode="numeric" autocomplete="off" maxlength="19" required>
                            </div>
                            <button type="submit" class="add-attendance-btn">Enter</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="dashboard-content-grid">
                <div class="dashboard-placeholder" aria-hidden="true"></div>

                <div class="metrics-grid">
                    <div class="metric-card metric-card-blue">
                        <span class="metric-title">Present Students</span>
                        <span class="metric-value"><?php echo isset($presentStudents) ? $presentStudents : '0'; ?></span>
                    </div>
                    <div class="metric-card metric-card-blue">
                        <span class="metric-title">Absent Students</span>
                        <span class="metric-value"><?php echo isset($absentStudents) ? $absentStudents : '0'; ?></span>
                    </div>
                    <div class="metric-card metric-card-blue">
                        <span class="metric-title">Total Students</span>
                        <span class="metric-value"><?php echo isset($totalStudents) ? $totalStudents : '0'; ?></span>
                    </div>
                </div>

                <div class="table-section-card">
                    <h3>Recent Attendance Activity</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>LRN</th>
                                    <th>Student Name</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentAttendanceActivity)): ?>
                                    <tr>
                                        <td colspan="5">No attendance activity recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentAttendanceActivity as $activity): ?>
                                        <?php
                                        $attendanceType = $activity['attendance_type'];
                                        $statusLabel = $attendanceType === 'time_in' ? 'Time - In' : 'Time - Out';
                                        $studentName = trim(
                                            $activity['last_name'] . ', ' .
                                            $activity['first_name'] . ' ' .
                                            ($activity['middle_name'] ?? '') . ' ' .
                                            ($activity['suffix'] ?? '')
                                        );
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(date('h:i A', strtotime($activity['recorded_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($activity['lrn'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($activity['recorded_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><span class="badge <?php echo $attendanceType === 'time_in' ? 'time-in' : 'time-out'; ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="/public/js/alertTimer.js"></script>
</body>
</html>