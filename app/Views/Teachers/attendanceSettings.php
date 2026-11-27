<?php
date_default_timezone_set('Asia/Manila');

if (!isset($base_url)) {
    $base_url = '';
}

$currentPage = 'attendanceSettings';
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/images/BL1-Logo.png?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/teachers_style.css">
    <title>BL1ES - Attendance Settings</title>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <header>
        <div class="dashboard-header">
            <h1>Bayan Luma 1 Elementary School</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="dashboard-container teacher-settings-container">
            <div class="overview-header-row">
                <div class="overview-titles">
                    <h2>ATTENDANCE SETTINGS</h2>
                    <p class="overview-date">Manage manual attendance codes and fingerprint enrollment.</p>
                </div>
            </div>

            <section class="table-section-card attendance-codes-section">
                <h3 class="attendance-codes-title">Your Manual Attendance Codes</h3>
                <p class="attendance-codes-description">Enter the code once to open a session and again to close it. Students can enter their LRN while the session is open.</p>
                <div class="attendance-code-grid">
                    <div class="metric-card metric-card-blue time-in-code-card">
                        <span class="metric-title time-in-code-title">Time In Code</span>
                        <span class="metric-value time-in-code-value"><?= $escape($attendanceCodes['time_in']) ?></span>
                    </div>
                    <div class="metric-card metric-card-blue time-out-code-card">
                        <span class="metric-title time-out-code-title">Time Out Code</span>
                        <span class="metric-value time-out-code-value"><?= $escape($attendanceCodes['time_out']) ?></span>
                    </div>
                </div>
            </section>

            <section class="table-section-card" id="fingerprint-reader"
                data-endpoint="index.php?page=fingerprintAttendance"
                data-csrf-token="<?= $escape($csrfToken) ?>">
                <h3>AS608 Fingerprint Attendance</h3>
                <p>Choose an active student. Plug the ESP32 into this laptop and connect it below. The next available sensor ID from 1 to 127 is assigned automatically.</p>
                <p><strong>AS608 capacity:</strong> <?= (int) $fingerprintCapacity['used'] ?> of <?= (int) $fingerprintCapacity['total'] ?> slots used; <?= (int) $fingerprintCapacity['available'] ?> available.</p>
                <p id="fingerprintReaderStatus" role="status" aria-live="polite">ESP32 is not connected. Connect it through this browser.</p>
                <button type="button" class="view-btn" id="connectFingerprintReaderButton">Connect ESP32 Reader</button>

                <form id="fingerprintEnrollForm">
                    <div class="form-group">
                        <label for="fingerprintStudentSearch">Find a student by name or LRN</label>
                        <input
                            type="search"
                            id="fingerprintStudentSearch"
                            placeholder="Start typing a student name or LRN"
                            autocomplete="off"
                            aria-describedby="fingerprintStudentSearchStatus"
                        >
                        <p id="fingerprintStudentSearchStatus" role="status" aria-live="polite">
                            Type a name or LRN to find a student.
                        </p>
                    </div>
                    <div class="form-group">
                        <label for="fingerprintStudent">Select the student to enroll or re-enroll</label>
                        <select id="fingerprintStudent" name="student_lrn" required disabled>
                            <option value="">Select an active student</option>
                            <?php foreach ($fingerprintStudents as $student): ?>
                                <?php
                                $studentName = trim(
                                    $student['last_name'] . ', ' . $student['first_name'] . ' ' .
                                ($student['middle_name'] ?? '') . ' ' .
                                ($student['suffix'] ?? '')
                                );
                                ?>
                                <option
                                    value="<?= $escape($student['lrn']) ?>"
                                    data-student-name="<?= $escape($studentName . ' (LRN ' . $student['lrn'] . ')') ?>"
                                >
                                    <?php
                                    $mappingLabel = $student['fingerprint_id'] === null
                                        ? 'not registered'
                                        : 'currently ID ' . $student['fingerprint_id'];
                                    echo $escape($studentName . ' (LRN ' . $student['lrn'] . ', ' . $mappingLabel . ')');
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="view-btn" id="enrollFingerprintButton" disabled>Enroll Selected Student</button>
                </form>
            </section>
        </div>
    </main>
    <script src="/public/js/fingerprintAttendance.js"></script>
</body>
</html>
