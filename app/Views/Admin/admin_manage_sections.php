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
    <link rel="icon" type="image/png" href="/images/BL1-Logo.png?v=2">
    <!-- FontAwesome Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/public/css/admin_style.css">
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
                <div class="table-toolbar">
                    <h3>List of School Grade & Sections</h3>
                    <input
                        id="sectionTableSearch"
                        class="section-table-search"
                        type="search"
                        placeholder="Search grade, section, or adviser..."
                        aria-label="Search grade and sections"
                    >
                </div>
                <?php if (!empty($sectionResult)): ?>
                    <p class="section-feedback <?= !empty($sectionResult['success']) ? 'is-success' : 'is-error' ?>" role="status">
                        <?= htmlspecialchars($sectionResult['message'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Grade Level</th>
                                <th>Section Name</th>
                                <th>Assigned Adviser</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sections)): ?>
                                <tr>
                                    <td colspan="4" class="empty-state"><em>No grade sections have been added yet.</em></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sections as $section): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($section['grade_level'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($section['section_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if (empty($section['advisers'])): ?>
                                                <em>---</em>
                                            <?php else: ?>
                                                <?= htmlspecialchars(implode(', ', array_column($section['advisers'], 'full_name')), ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="section-row-actions">
                                                <button
                                                    class="section-action-btn edit-section-btn"
                                                    type="button"
                                                    data-section-id="<?= (int) $section['section_id'] ?>"
                                                    data-grade-level="<?= htmlspecialchars($section['grade_level'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-section-name="<?= htmlspecialchars($section['section_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-adviser-id="<?= !empty($section['advisers']) ? (int) $section['advisers'][0]['id'] : '' ?>"
                                                    data-has-adviser="<?= !empty($section['advisers']) ? 'true' : 'false' ?>"
                                                >Edit</button>
                                                <form method="POST" action="index.php?page=admin_manage_sections" class="remove-section-form">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="remove_section">
                                                    <input type="hidden" name="section_id" value="<?= (int) $section['section_id'] ?>">
                                                    <button class="section-cancel-btn section-remove-btn" type="submit" data-section-name="<?= htmlspecialchars($section['grade_level'] . ' - ' . $section['section_name'], ENT_QUOTES, 'UTF-8') ?>">Remove Section</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
            <form action="index.php?page=admin_manage_sections" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="add_section">
                <input type="hidden" id="section_id" name="section_id" value="">
                <div class="section-form-group">
                    <label for="grade_level">Grade Level</label>
                    <select id="grade_level" name="grade_level" class="section-form-control" required>
                        <option value="" disabled selected>Select Grade Level</option>
                        <?php foreach ($grades as $grade): ?>
                            <option value="<?= htmlspecialchars($grade, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($grade, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="section-form-group">
                    <label for="section_name">Section Name</label>
                    <input type="text" id="section_name" name="section_name" class="section-form-control" placeholder="e.g. Masipag" maxlength="50" required>
                </div>
                <template id="sectionAdviserTemplate">
                    <div id="sectionAdviserGroup" class="section-form-group section-form-group-last">
                        <label for="adviser_id">Assigned Adviser (Optional)</label>
                        <select id="adviser_id" name="adviser_id" class="section-form-control">
                            <option value="" id="noAdviserOption">No adviser assigned</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= (int) $teacher['id'] ?>">
                                    <?= htmlspecialchars($teacher['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Choosing a teacher moves them from their current section. When editing a section with an adviser, that adviser moves to the teacher's previous section.</small>
                    </div>
                </template>
                <div class="section-modal-actions">
                    <button id="cancelSectionModal" type="button" class="section-cancel-btn">Cancel</button>
                    <button id="saveSectionButton" type="submit" class="section-action-btn">Save Section</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/public/js/adminManageSections.js"></script>

</body>
</html>