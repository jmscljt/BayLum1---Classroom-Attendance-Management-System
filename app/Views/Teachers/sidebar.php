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
