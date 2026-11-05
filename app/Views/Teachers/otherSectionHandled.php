<?php
// Set local time zone
date_default_timezone_set('Asia/Manila');

if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'otherSectionHandled';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/teachers_style.css">
    <title>BL1ES - Other Section Handled</title>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <header>
        <div class="dashboard-header">
            <h1>Bayan Luma 1 Elementary School</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="assigned-section-container">

            <div class="overview-header-row">
                <div class="overview-titles">
                    <h2>Assigned Students</h2>
                </div>
            </div>

            <div class="student-buttons-section">
                <h4>Add Assigned Section</h4>
                <div class="add-section-form-group">
                    
                    <select class="grade-btn" name="grade_level">
                        <option value="" disabled selected>Grade</option>
                        <option value="Grade 4">Grade 4</option>
                        <option value="Grade 5">Grade 5</option>
                        <option value="Grade 6">Grade 6</option>
                    </select>

                    <select class="section-btn" name="section">
                        <option value="" disabled selected>Section</option>
                        <option value="Mabait">Mabait</option>
                        <option value="Magalang">Magalang</option>
                        <option value="Masipag">Masipag</option>
                        <option value="Masayahin">Masayahin</option>
                        <option value="Matapat">Matapat</option>
                    </select>

                    <select class="subject-btn" name="subject">
                        <option value="" disabled selected>Subject</option>
                        <option value="Math">Math</option>
                        <option value="English">English</option>
                        <option value="Science">Science</option>
                        <option value="Filipino">Filipino</option>
                        <option value="Araling Panlipunan">Araling Panlipunan</option>
                    </select>

                    <button type="submit" class="add-section-btn">Add</button>
                </div>
            </div>
        </div>
    </main>

</body>
</html>