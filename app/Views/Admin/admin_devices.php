<?php
if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'admin_devices';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/admin_style.css">
    <title>Biometric Devices - Bayan Luma 1 Elementary School</title>
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
            <div class="overview-header-row device-page-header">
                <div class="overview-titles">
                    <h2>BIOMETRIC DEVICES</h2>
                    <p class="device-page-description">Manage attendance terminals and student fingerprint enrollment.</p>
                </div>
                <button id="openDeviceModal" class="section-action-btn" type="button">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Register Device
                </button>
            </div>

            <section class="table-section-card device-table-card">
                <div class="device-table-heading">
                    <div>
                        <h3>Registered Devices</h3>
                        <p>Monitor device locations and connection status.</p>
                    </div>
                    <span class="device-record-count">0 devices</span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Device Name / ID</th>
                                <th scope="col">Location / Room</th>
                                <th scope="col">Status</th>
                                <th scope="col">Last Seen</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="empty-state">No biometric devices have been registered yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="table-section-card">
                <div class="device-table-heading">
                    <div>
                        <h3>Student Fingerprint Enrollment</h3>
                        <p>Enroll or manage fingerprints for registered students.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">LRN</th>
                                <th scope="col">Student Name</th>
                                <th scope="col">Grade &amp; Section</th>
                                <th scope="col">Fingerprint Status</th>
                                <th scope="col">Enrollment Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="empty-state">No student records are available for fingerprint enrollment.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <div id="deviceModal" class="section-modal-overlay" aria-hidden="true">
        <div class="section-modal-card" role="dialog" aria-modal="true" aria-labelledby="deviceModalTitle">
            <div class="section-modal-header">
                <h3 id="deviceModalTitle">Register Biometric Device</h3>
                <button id="closeDeviceModal" class="section-modal-close" type="button" aria-label="Close">&times;</button>
            </div>
            <form action="#" method="post">
                <div class="section-form-group">
                    <label for="deviceName">Device Name / ID</label>
                    <input id="deviceName" name="device_name" class="section-form-control" type="text" placeholder="e.g. BIO-GATE-02" required>
                </div>
                <div class="section-form-group section-form-group-last">
                    <label for="deviceLocation">Location / Room</label>
                    <input id="deviceLocation" name="device_location" class="section-form-control" type="text" placeholder="e.g. Gate 2 / Grade 5 Building" required>
                </div>
                <div class="section-modal-actions">
                    <button id="cancelDeviceModal" class="section-cancel-btn" type="button">Cancel</button>
                    <button class="section-action-btn" type="submit">Save Device</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/public/js/adminDevices.js"></script>
</body>
</html>
