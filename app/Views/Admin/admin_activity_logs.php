<?php
if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'admin_activity_logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/admin_style.css">
    <title>Admin Activity Logs - Bayan Luma 1 Elementary School</title>
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
                    <h2>ADMIN ACTIVITY LOGS</h2>
                    <p class="activity-logs-description">Review administrative actions and system activity.</p>
                </div>
            </div>

            <section class="table-section-card">
                <div class="activity-logs-table-heading">
                    <div>
                        <h3>Admin Audit Trail</h3>
                    </div>
                    <span id="activityLogsCount" class="activity-logs-count">0 records</span>
                </div>
                <div class="activity-logs-filters" aria-label="Filter admin activity logs">
                    <label class="activity-logs-filter-field">
                        <span>Search by date</span>
                        <input id="activityLogDateFilter" class="section-form-control" type="date" aria-label="Search logs by date">
                    </label>
                    <label class="activity-logs-filter-field">
                        <span>Action type</span>
                        <select id="activityLogActionFilter" class="section-form-control">
                            <option value="">All action types</option>
                            <option value="login">Login</option>
                            <option value="student edit">Student edit</option>
                            <option value="section change">Section change</option>
                            <option value="teacher edit">Teacher edit</option>
                            <option value="password reset">Password reset</option>
                        </select>
                    </label>
                    <button id="clearActivityLogFilters" class="section-cancel-btn" type="button">Clear filters</button>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Timestamp</th>
                                <th scope="col">Administrator</th>
                                <th scope="col">Action</th>
                                <th scope="col">Details</th>
                            </tr>
                        </thead>
                        <tbody id="activityLogsTableBody">
                            <tr id="activityLogsEmptyState">
                                <td colspan="4" class="empty-state">No activity records available yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
    <script src="/public/js/activityLogs.js"></script>
</body>
</html>
