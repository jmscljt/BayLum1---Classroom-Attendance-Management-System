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
                    <h2>STUDENTS</h2>
                </div>
            </div>

            <div class="student-buttons-section">
                <h3>Count</h3>
                <div>
                    <button class="import_excel_btn">Import Excel</button>
                </div>
                <div class="add-student-wrapper">
                    <button class="add-student-btn">Add Student</button>    
                </div>
            </div>
        </div>
    </main>
</body>
</html>