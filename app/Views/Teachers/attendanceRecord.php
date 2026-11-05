<?php
// Set local time zone
date_default_timezone_set('Asia/Manila');
if (!isset($base_url)) {
    $base_url = '';
}

$currentPage = 'attendanceRecord';

$assignedGrade   = $_SESSION['assigned_grade_level'] ?? 'Grade';
$advisorySection = $_SESSION['advisory_section'] ?? 'Section';

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
    <title>BL1ES - Attendance Record</title>
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
                    <h2>CLASS ATTENDANCE - <?php echo htmlspecialchars('('. $assignedGrade . ' - ' . $advisorySection . ')'); ?></h2>
                </div>

                <form method="GET" action="" class="filter-action-group">
                    <input type="hidden" name="page" value="attendanceRecord">
                    <input type="date" name="attendance_date" class="date-filter-input" value="<?php echo htmlspecialchars($searchDate); ?>">                    
                    <button type="submit" class="sync-btn">
                        <i class="fa-solid fa-magnifying-glass"></i> Search Date
                    </button>
                </form>
            </div>

            <div class="table-section-card">
                <h3>Attendance Record Summary</h3>
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
                                <td><?php echo htmlspecialchars($searchDate); ?></td>
                                <td>0</td>
                                <td>0</td>
                                <td>
                                    <button type="button" class="view-btn" onclick="openAttendanceModal()">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Attendance Details Modal Overlay (Naka-sync na sa $searchDate) -->
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
                            <tr>
                                <td><?php echo htmlspecialchars($searchDate); ?></td>
                                <td>107989120052</td>
                                <td>James Gabriel Calajate</td>
                                <td>Male</td>
                                <td>08:00 AM</td>
                                <td>03:00 PM</td>
                                <td>Manual</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Pag-link sa hiwalay na JavaScript file -->
    <script src="/public/js/attendanceRecord.js"></script>
    
</body>
</html>