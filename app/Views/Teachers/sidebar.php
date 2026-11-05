<?php
if (!isset($base_url)) {
    $base_url = '';
}
if (!isset($currentPage)) {
    $currentPage = '';
}

$assignedGrade   = $_SESSION['assigned_grade_level'] ?? 'Grade';
$advisorySection = $_SESSION['advisory_section'] ?? 'Section';
?>
<div class="sidebar">
    <img src="/images/BL1-Logo.png" alt="Logo" class="sidebar-logo">
    <div class="nav-links">
        <a href="index.php?page=dashboard" class="<?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i>Dashboard</a>
        <a href="index.php?page=attendanceRecord" class="<?php echo ($currentPage == 'attendanceRecord') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-check"></i>Attendance Record</a>
        <a href="<?php echo $base_url; ?>/index.php?page=students" class="<?php echo ($currentPage == 'students') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i><?php echo htmlspecialchars($assignedGrade . ' - ' . $advisorySection . ' Students'); ?></a>
        <a href="<?php echo $base_url; ?>/index.php?page=teachers_section" class="<?php echo ($currentPage == 'section') ? 'active' : ''; ?>">
            <i class="fa-solid fa-building"></i>Other Sections Handled</a>
        <a href="<?php echo $base_url; ?>/index.php?page=teachers_schedule" class="<?php echo ($currentPage == 'schedule') ? 'active' : ''; ?>">
            <i class="fa-solid fa-calendar-alt"></i>Schedule</a>
        <a href="<?php echo $base_url; ?>/index.php?page=teachers_settings" class="<?php echo ($currentPage == 'settings') ? 'active' : ''; ?>">
            <i class="fa-solid fa-cog"></i>Settings</a>
        <a href="<?php echo $base_url; ?>/index.php?page=logout" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>

