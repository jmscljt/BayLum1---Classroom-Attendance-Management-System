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
                    <div class="form-group">
                        <label for="fingerprintSlot">Unused AS608 ID (1-127)</label>
                        <input type="number" id="fingerprintSlot" name="fingerprint_id" min="1" max="127" required>
                    </div>
                    <button type="submit" class="view-btn" id="enrollFingerprintButton" disabled>Enroll Selected Student</button>
                </form>
            </section>
        </div>
    </main>
    <script src="/public/js/fingerprintAttendance.js"></script>
</body>
</html>
