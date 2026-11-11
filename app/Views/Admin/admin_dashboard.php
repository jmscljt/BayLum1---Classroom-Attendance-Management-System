<?php
if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'admin_dashboard';

$currentDateDisplay = date('F d, Y');
$currentDateValue   = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/admin_style.css">
    <title>BL1ES - Admin Dashboard</title>
</head>
<body>

    <?php require_once __DIR__ . '/admin_sidebar.php'; ?>

    <header>
        <div class="dashboard-header">
            <h1>Bayan Luma 1 Elementary School</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="dashboard-container"> 
            
            <div class="overview-header-row">
                <div class="overview-titles">
                    <h2>ADMIN SYSTEM OVERVIEW</h2>
                    <p class="overview-date-label"><?php echo $currentDateDisplay; ?></p>
                </div>
                <div class="filter-action-group">
                    <input type="date" class="date-filter-input" value="<?php echo $currentDateValue; ?>">
                    <button class="sync-btn"><i class="fa-solid fa-rotate"></i> System Sync</button>
                </div>
            </div>

            <div class="metrics-grid">
                <div class="metric-card">
                    <span class="metric-title">Total Enrolled Students</span>
                    <span class="metric-value">0</span>
                </div>
                <div class="metric-card">
                    <span class="metric-title">Active Teachers</span>
                    <span class="metric-value">0</span>
                </div>
                <div class="metric-card">
                    <span class="metric-title">Registered Sections</span>
                    <span class="metric-value">0</span>
                </div>
                <div class="metric-card">
                    <span class="metric-title">Biometric Devices Online</span>
                    <span class="metric-value">0/0</span>
                </div>
            </div>

            <div class="table-section-card">
                <h3>Recent System Activities</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #777; padding: 20px;">
                                    <em>empty</em>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

</body>
</html>