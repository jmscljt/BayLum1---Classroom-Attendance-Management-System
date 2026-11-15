<?php
// Set local time zone
date_default_timezone_set('Asia/Manila');

if (!isset($base_url)) {
    $base_url = '';
}

$currentPage = 'dashboard';
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
            
            <div class="overview-header-row">
                <div class="overview-titles">
                    <h2>ATTENDANCE OVERVIEW</h2>
                    <p class="overview-date"><?php echo date('F d, Y'); ?></p>
                </div>
            </div>

            <div class="table-section-card attendance-codes-section">
                <h3 class="attendance-codes-title">Your Manual Attendance Codes</h3>
                <p class="attendance-codes-description">Enter the code once to open a session and again to close it. Students can enter their LRN while the session is open.</p>
                <div class="attendance-code-grid">
                    <div class="metric-card metric-card-blue time-in-code-card">
                        <span class="metric-title time-in-code-title">Time In Code</span>
                        <span class="metric-value time-in-code-value"><?php echo htmlspecialchars($attendanceCodes['time_in'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="metric-card metric-card-blue time-out-code-card">
                        <span class="metric-title time-out-code-title">Time Out Code</span>
                        <span class="metric-value time-out-code-value"><?php echo htmlspecialchars($attendanceCodes['time_out'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>

            <section class="table-section-card" id="fingerprint-reader"
                data-endpoint="index.php?page=fingerprintAttendance"
                data-csrf-token="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <h3>AS608 Fingerprint Attendance</h3>
                <p>Connect the Uno here. Choose an active student and an unused sensor ID from 1 to 127. This can register a new student or replace an existing mapping. Keep the same finger on the reader when prompted.</p>
                <button type="button" class="view-btn" id="connectFingerprintReader">Connect AS608 Reader</button>
                <button type="button" class="view-btn" id="disconnectFingerprintReader" hidden>Disconnect</button>
                <p id="fingerprintReaderStatus" role="status" aria-live="polite">Not connected. Open this site in Chrome or Edge on localhost or HTTPS.</p>

                <form id="fingerprintEnrollForm">
                    <div class="form-group">
                        <label for="fingerprintStudent">Student to enroll or re-enroll</label>
                        <select id="fingerprintStudent" name="student_lrn" required>
                            <option value="">Select an active student</option>
                            <?php foreach ($fingerprintStudents as $student): ?>
                                <?php
                                $studentName = trim(
                                    $student['last_name'] . ', ' . $student['first_name'] . ' ' .
                                    ($student['middle_name'] ?? '')
                                );
                                ?>
                                <option
                                    value="<?php echo htmlspecialchars((string) $student['lrn'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-student-name="<?php echo htmlspecialchars($studentName . ' (LRN ' . $student['lrn'] . ')', ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <?php
                                    $mappingLabel = $student['fingerprint_id'] === null
                                        ? 'not registered'
                                        : 'currently ID ' . $student['fingerprint_id'];
                                    echo htmlspecialchars($studentName . ' (LRN ' . $student['lrn'] . ', ' . $mappingLabel . ')', ENT_QUOTES, 'UTF-8');
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fingerprintSlot">Unused AS608 ID (1-127)</label>
                        <input type="number" id="fingerprintSlot" name="fingerprint_id" min="1" max="127" required>
                    </div>
                    <button type="submit" class="view-btn" id="enrollFingerprintButton" disabled>Enroll Selected Student</button>
                </form>
            </section>

            <!-- Metric Summary Cards with Colored Borders & Shadows -->
            <div class="metrics-grid">
                <div class="metric-card metric-card-blue">
                    <span class="metric-title">Total Advisory Students</span>
                    <span class="metric-value"><?php echo isset($totalAdvisoryStudents) ? $totalAdvisoryStudents : '0'; ?></span>
                </div>
                <div class="metric-card metric-card-blue">
                    <span class="metric-title">Other Handled Students</span>
                    <span class="metric-value"><?php echo isset($totalOtherHandledStudents) ? $totalOtherHandledStudents : '0'; ?></span>
                </div>
                <div class="metric-card metric-card-blue">
                    <span class="metric-title">Total Assigned Students</span>
                    <span class="metric-value"><?php echo isset($totalAssignedStudents) ? $totalAssignedStudents : '0'; ?></span>
                </div>
            </div>

            <div class="metrics-grid">
                <div class="metric-card metric-card-blue">
                    <span class="metric-title">Present Students (Advisory)</span>
                    <span class="metric-value"><?php echo isset($presentStudentsAdvisory) ? $presentStudentsAdvisory : '0'; ?></span>
                </div>
                <div class="metric-card metric-card-blue">
                    <span class="metric-title">Absent Students (Advisory)</span>
                    <span class="metric-value"><?php echo isset($absentStudentsAdvisory) ? $absentStudentsAdvisory : '0'; ?></span>
                </div>
                <div class="metric-card metric-card-blue">
                    <span class="metric-title">Present Students (Other Handled)</span>
                    <span class="metric-value"><?php echo isset($presentStudentsOtherHandled) ? $presentStudentsOtherHandled : '0'; ?></span>
                </div>
                <div class="metric-card metric-card-blue">
                    <span class="metric-title">Absent Subjects (Other Handled)</span>
                    <span class="metric-value"><?php echo isset($absentSubjectsOtherHandled) ? $absentSubjectsOtherHandled : '0'; ?></span>
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
                                <th>Grade & Section</th>
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
                                        $activity['first_name'] . ' ' .
                                        ($activity['middle_name'] ?? '') . ' ' .
                                        $activity['last_name']
                                    );
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('h:i A', strtotime($activity['recorded_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($activity['lrn'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($activity['grade_level'] . ' ' . $activity['section_name'], ENT_QUOTES, 'UTF-8'); ?></td>
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
    </main>

    <script src="/public/js/fingerprintAttendance.js"></script>
</body>
</html>