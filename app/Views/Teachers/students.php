<?php
// Set local time zone
date_default_timezone_set('Asia/Manila');

if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'students';
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
                <h3>Count</h3>
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
                                <td>20</td>
                                <td>5</td>
                                <td>2</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-view">View Absences</button>
                                        <button class="btn-edit">Edit</button>
                                        <button class="btn-remove">Remove</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>