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
    <title>Dashboard - Bayan Luma 1 Elementary School</title>
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

            <!-- Metric Summary Cards with Colored Borders & Shadows -->
            <div class="metrics-grid">
                <div class="metric-card metric-card-blue">
                    <span class="metric-title">Total Advisory Students</span>
                    <span class="metric-value"><?php echo isset($totalStudents) ? $totalStudents : ''; ?></span>
                </div>
                <div class="metric-card metric-card-green">
                    <span class="metric-title">Present Students</span>
                    <span class="metric-value text-green"><?php echo isset($presentStudents) ? $presentStudents : ''; ?></span>
                </div>
                <div class="metric-card metric-card-red">
                    <span class="metric-title">Absent Students</span>
                    <span class="metric-value text-red"><?php echo isset($absentStudents) ? $absentStudents : ''; ?></span>
                </div>
                <div class="metric-card metric-card-blue">
                    <span class="metric-title">Assigned Subjects</span>
                    <span class="metric-value"><?php echo isset($assignedSubjects) ? $assignedSubjects : ''; ?></span>
                </div>
            </div>

            <div class="table-section-card">
                <h3>Recent Fingerprint Scans</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Student Name</th>
                                <th>LRN</th>
                                <th>Grade & Section</th>
                                <th>Verification Status</th>
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