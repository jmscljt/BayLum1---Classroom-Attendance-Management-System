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

        <a href="<?php echo $base_url; ?>/index.php?page=dashboard" class="<?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i>Dashboard</a>
        
        <a href="<?php echo $base_url; ?>/index.php?page=attendanceLogs" class="<?php echo ($currentPage == 'attendanceLogs') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-check"></i>Attendance Logs</a>

        <a href="<?php echo $base_url; ?>/index.php?page=students" class="<?php echo ($currentPage == 'students') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i><?php echo htmlspecialchars($displayGrade . ' - ' . trim($advisorySection) . ' Students'); ?></a>

        <a href="<?php echo $base_url; ?>/index.php?page=otherSectionHandled" class="<?php echo ($currentPage == 'otherSectionHandled') ? 'active' : ''; ?>">
            <i class="fa-solid fa-building"></i>Other Sections Handled</a>

        <a href="<?php echo $base_url; ?>/index.php?page=teachers_settings" class="<?php echo ($currentPage == 'teachers_settings') ? 'active' : ''; ?>">
            <i class="fa-solid fa-cog"></i>Settings</a>

        <a href="<?php echo $base_url; ?>/index.php?page=logout" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout</a>

    </div>
</div>
