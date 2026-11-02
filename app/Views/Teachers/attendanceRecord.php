<?php
if (!isset($base_url)) {
    $base_url = '';
}

$currentPage = 'attendanceRecord';

$assignedGrade   = $_SESSION['assigned_grade_level'] ?? 'Grade';
$advisorySection = $_SESSION['advisory_section'] ?? 'Section';
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
        <div class="dashboard-container">
            
            <div class="overview-header-row">
                <div class="overview-titles">
                    <h2>CLASS ATTENDANCE - <?php echo htmlspecialchars($assignedGrade . ' - ' . $advisorySection); ?></h2>
                </div>
            </div>
        </div>
    </main>
    
</body>
</html>