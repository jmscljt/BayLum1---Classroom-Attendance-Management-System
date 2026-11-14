<?php
// Initialize base URL and current page active state
if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'admin_manage_sections';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FontAwesome Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/admin_style.css">
    <title>BL1ES - Grade & Sections Management</title>
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
                    <h2>GRADE & SECTIONS MANAGEMENT</h2>
                </div>
                <div class="filter-action-group">
                    <button id="openSectionModal" class="section-action-btn" type="button"><i class="fa-solid fa-plus"></i> Add New Section</button>
                </div>
            </div>

            <div class="table-section-card">
                <h3>List of School Sections</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Grade Level</th>
                                <th>Section Name</th>
                                <th>Assigned Adviser</th>
                                <th>Student Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="empty-state"><em>empty</em></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <div id="sectionModal" class="section-modal-overlay" aria-hidden="true">
        <div class="section-modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="section-modal-header">
                <h3 id="modalTitle">Add New Section</h3>
                <button id="closeSectionModal" class="section-modal-close" type="button" aria-label="Close">&times;</button>
            </div>
            <form action="#" method="POST">
                <div class="section-form-group">
                    <label for="grade_level">Grade Level</label>
                    <select id="grade_level" name="grade_level" class="section-form-control" required>
                        <option value="" disabled selected>Select Grade Level</option>
                        <option value="Grade 4">Grade 4</option>
                        <option value="Grade 5">Grade 5</option>
                        <option value="Grade 6">Grade 6</option>
                    </select>
                </div>
                <div class="section-form-group">
                    <label for="section_name">Section Name</label>
                    <input type="text" id="section_name" name="section_name" class="section-form-control" placeholder="e.g. Masigasig" required>
                </div>
                <div class="section-form-group section-form-group-last">
                    <label for="adviser_id">Assigned Adviser (Teacher)</label>
                    <select id="adviser_id" name="adviser_id" class="section-form-control" required>
                        <option value="" disabled selected>Select Teacher / Adviser</option>
                        <option value="1">Juan Dela Cruz</option>
                        <option value="2">Maria Santos</option>
                        <option value="3">Pedro Penduko</option>
                    </select>
                </div>
                <div class="section-modal-actions">
                    <button id="cancelSectionModal" type="button" class="section-cancel-btn">Cancel</button>
                    <button type="submit" class="section-action-btn">Save Section</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/public/js/adminManageSections.js"></script>

</body>
</html>