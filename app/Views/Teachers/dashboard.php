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
                                <th>Status</th>
                                <th>Student Name</th>
                                <th>Grade & Section</th>
                                <th>Subject</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            /* 
                              while($row = mysqli_fetch_assoc($scanResult)) {
                                echo "<tr>
                                        <td>{$row['time']}</td>
                                        <td><span class='badge " . ($row['status'] == 'Time - In' ? 'time-in' : 'time-out') . "'>{$row['status']}</span></td>
                                        <td>{$row['student_name']}</td>
                                        <td>{$row['lrn']}</td>
                                        <td>{$row['grade_section']}</td>
                                        <td>{$row['verification_status']}</td>
                                      </tr>";
                              }
                            */ 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

</body>
</html>