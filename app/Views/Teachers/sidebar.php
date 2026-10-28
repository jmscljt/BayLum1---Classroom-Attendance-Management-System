<?php
if (!isset($base_url)) {
    $base_url = '';
}
if (!isset($currentPage)) {
    $currentPage = '';
}
?>
<div class="sidebar">
    <img src="/images/BL1-Logo.png" alt="Logo" class="logo">
    <div class="nav-links">
        <a href="<?php echo $base_url; ?>/index.php?page=teachers_dashboard" class="<?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i>Dashboard</a>
        <a href="<?php echo $base_url; ?>/index.php?page=teachers_attendacerecord" class="<?php echo ($currentPage == 'attendacerecord') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-check"></i>Attendance Record</a>
        <a href="<?php echo $base_url; ?>/index.php?page=teachers_students" class="<?php echo ($currentPage == 'students') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i>Students</a>
        <a href="<?php echo $base_url; ?>/index.php?page=teachers_section" class="<?php echo ($currentPage == 'section') ? 'active' : ''; ?>">
            <i class="fa-solid fa-building"></i>Section</a>
        <a href="<?php echo $base_url; ?>/index.php?page=teachers_classadvisory" class="<?php echo ($currentPage == 'classadvisory') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chalkboard-teacher"></i>Class Advisory</a>
        <a href="<?php echo $base_url; ?>/index.php?page=teachers_schedule" class="<?php echo ($currentPage == 'schedule') ? 'active' : ''; ?>">
            <i class="fa-solid fa-calendar-alt"></i>Schedule</a>
        <a href="<?php echo $base_url; ?>/index.php?page=teachers_settings" class="<?php echo ($currentPage == 'settings') ? 'active' : ''; ?>">
            <i class="fa-solid fa-cog"></i>Settings</a>
    </div>



</div>