<?php
if (!isset($base_url)) {
    $base_url = '';
}
if (!isset($currentPage)) {
    $currentPage = '';
}
?>
<div class="sidebar">
    <img src="/images/BL1-Logo.png" alt="Bayan Luma 1 Logo" class="sidebar-logo">
    
    <div class="admin-badge">
        <i class="fa-solid fa-user-shield"></i> Administrator
    </div>

    <div class="nav-links">
        <a href="<?php echo $base_url; ?>/index.php?page=admin_dashboard" class="<?php echo ($currentPage == 'admin_dashboard') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>

        <a href="<?php echo $base_url; ?>/index.php?page=admin_manage_sections" class="<?php echo ($currentPage == 'admin_manage_sections') ? 'active' : ''; ?>">
            <i class="fa-solid fa-layer-group"></i> Grade & Sections
        </a>

        <a href="<?php echo $base_url; ?>/index.php?page=admin_teachers" class="<?php echo ($currentPage == 'admin_teachers') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chalkboard-user"></i> Teacher Accounts
        </a>

        <a href="<?php echo $base_url; ?>/index.php?page=admin_students" class="<?php echo ($currentPage == 'admin_students') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Master Students
        </a>

        <a href="<?php echo $base_url; ?>/index.php?page=admin_devices" class="<?php echo ($currentPage == 'admin_devices') ? 'active' : ''; ?>">
            <i class="fa-solid fa-fingerprint"></i> Biometric Devices
        </a>

        <a href="<?php echo $base_url; ?>/index.php?page=admin_activity_logs" class="<?php echo ($currentPage == 'admin_activity_logs') ? 'active' : ''; ?>">
            <i class="fa-solid fa-shield-halved"></i> Admin Logs
        </a>

        <a href="<?php echo $base_url; ?>/index.php?page=admin_teacher_logs" class="<?php echo ($currentPage == 'admin_teacher_logs') ? 'active' : ''; ?>">
            <i class="fa-solid fa-list-check"></i> Teacher Logs
        </a>

        <a href="<?php echo $base_url; ?>/index.php?page=admin_settings" class="<?php echo ($currentPage == 'admin_settings') ? 'active' : ''; ?>">
            <i class="fa-solid fa-cog"></i> Settings
        </a>
        
        <a href="<?php echo $base_url; ?>/index.php?page=admin_logout" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>