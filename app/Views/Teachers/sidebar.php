<?php
if (!isset($base_url)) { 
    $base_url = ''; 
}


$assignedGrade = $_SESSION['assigned_grade_level'] ?? '';
$advisorySection = $_SESSION['advisory_section'] ?? '';
$displayGrade = preg_replace('/^Grade\s*/i', '', trim($assignedGrade));
?>
<div class="sidebar">
    <img src="/images/BL1-Logo.png" alt="Logo" class="sidebar-logo">
    <div class="nav-links">

        <?php if ($currentPage !== 'attendanceSettings'): ?>
            <div class="fingerprint-reader-status" id="fingerprint-reader"
                data-endpoint="index.php?page=fingerprintAttendance"
                data-csrf-token="<?php echo htmlspecialchars($fingerprintCsrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                data-auto-connect="1">
                <span id="fingerprintReaderStatus" role="status" aria-live="polite">Checking fingerprint reader...</span>
            </div>
            <script src="/public/js/fingerprintAttendance.js"></script>
        <?php endif; ?>

        <?php if (!empty($_SESSION['manual_attendance_session_id'])): ?>
            <div id="attendance-idle-timer" hidden
                data-endpoint="index.php?page=closeIdleAttendance"
                data-csrf-token="<?php echo htmlspecialchars($fingerprintCsrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                data-session-id="<?php echo (int) $_SESSION['manual_attendance_session_id']; ?>"></div>
            <script src="/public/js/attendanceIdleTimer.js" defer></script>
        <?php endif; ?>

        <a href="<?php echo $base_url; ?>/index.php?page=dashboard" class="<?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i>Dashboard</a>
        
        <a href="<?php echo $base_url; ?>/index.php?page=attendanceLogs" class="<?php echo ($currentPage == 'attendanceLogs') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-check"></i>Attendance Records</a>

        <a href="<?php echo $base_url; ?>/index.php?page=students" class="<?php echo ($currentPage == 'students') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i><?php echo htmlspecialchars($displayGrade . ' - ' . trim($advisorySection) . ' Students'); ?></a>

        <a href="<?php echo $base_url; ?>/index.php?page=attendanceSettings" class="<?php echo ($currentPage == 'attendanceSettings') ? 'active' : ''; ?>">
            <i class="fa-solid fa-clock"></i>Attendance Settings</a>

        <a href="<?php echo $base_url; ?>/index.php?page=accountSettings" class="<?php echo ($currentPage == 'accountSettings') ? 'active' : ''; ?>">
            <i class="fa-solid fa-cog"></i>Account Settings</a>

        <a href="<?php echo $base_url; ?>/index.php?page=logout" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout</a>

    </div>
</div>
