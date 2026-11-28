<?php
// Set local time zone
date_default_timezone_set('Asia/Manila');

if (!isset($base_url)) {
    $base_url = '';
}
$currentPage = 'students';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/images/BL1-Logo.png?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/teachers_style.css">
    <title>BL1ES - Students</title>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <header>
        <div class="dashboard-header">
            <h1>Bayan Luma 1 Elementary School</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="students-container">

            <div class="overview-header-row">
                <div class="overview-titles">
                    <h2>STUDENTS MASTERLIST</h2>
                </div>
            </div>

            <div class="student-buttons-section">
                <div class="student-actions-right">
                    <h4 class="student_count">Total Students: <?php echo (int)($studentCount ?? 0); ?></h4>
                    <button class="import_excel_btn">Import Excel</button>
                    <button class="export_excel_btn">Export Excel</button>
                    <button type="button" class="add-student-btn">Add Student</button>
                    <button type="button" class="view-inactive-btn" id="viewInactiveStudentsBtn">View Inactive Students</button>
                </div>
            </div>



            <!-- Inactivate Students Modal -->
            <div id="inactiveStudentsModal" class="modal-overlay" aria-hidden="true">
                <div class="modal-content inactive-students-modal-content" role="dialog" aria-modal="true" aria-labelledby="inactiveStudentsModalTitle">
                    <div class="modal-header">
                        <h3 id="inactiveStudentsModalTitle">Inactive Students</h3>
                        <button type="button" class="close-modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table inactive-students-table">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Last Name</th>
                                    <th>Suffix</th>
                                    <th>Gender</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inactiveStudents)): ?>
                                    <?php foreach ($inactiveStudents as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string) $student['lrn'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($student['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($student['middle_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($student['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($student['suffix'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($student['gender'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <form action="index.php?page=students" method="POST">
                                                    <input type="hidden" name="action" value="reactivate_student">
                                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars((string) $student['lrn'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <button type="submit" class="reactivate-btn">Reactivate</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="no-data-cell">No inactive students found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (isset($result)): ?>
                <div class="alert <?= $result['success'] ? 'alert-success' : 'alert-error' ?>" style="padding: 12px; margin-bottom: 6px; border-radius: 6px; background-color: <?= $result['success'] ? '#d4edda' : '#f8d7da' ?>; color: <?= $result['success'] ? '#155724' : '#721c24' ?>; border: 1px solid <?= $result['success'] ? '#c3e6cb' : '#f5c6cb' ?>;">
                    <?= htmlspecialchars($result['message']) ?>
                </div>
            <?php endif; ?>

            <!-- students.php View -->
            <div class="table-section-card">
                <div class="table-responsive">
                    <table class="data-table student-masterlist-table">
                        <thead>
                            <tr>
                                <th>AS608 Fingerprint ID</th>
                                <th>LRN</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Last Name</th>
                                <th>Suffix</th>
                                <th>Gender</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Excused</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($masterlist)): ?>
                                <?php foreach ($masterlist as $student): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $fingerprintId = trim((string) ($student['fingerprint_id'] ?? ''));
                                            if ($fingerprintId !== '') {
                                                echo '<span class="as608-id" data-fingerprint-student-lrn="' . htmlspecialchars((string) $student['lrn'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($fingerprintId, ENT_QUOTES, 'UTF-8') . '</span>';
                                            } else {
                                                echo '<span class="as608-unregistered" data-fingerprint-student-lrn="' . htmlspecialchars((string) $student['lrn'], ENT_QUOTES, 'UTF-8') . '">Unregistered</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['lrn'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($student['middle_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($student['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($student['suffix'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($student['gender'] ?? 'N'); ?></td>
                                        <td><?php echo (int) ($student['present_count'] ?? 0); ?></td>
                                        <td><?php echo (int) ($student['absent_count'] ?? 0); ?></td>
                                        <td><?php echo (int) ($student['excused_count'] ?? 0); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button
                                                    type="button"
                                                    class="btn-view"
                                                    data-student-id="<?php echo htmlspecialchars((string) $student['lrn'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-student-name="<?php echo htmlspecialchars(trim($student['last_name'] . ', ' . $student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['suffix'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                                                >View Absences</button>
                                                <button
                                                    type="button"
                                                    class="btn-edit"
                                                    data-student-id="<?php echo htmlspecialchars((string) $student['lrn'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-first-name="<?php echo htmlspecialchars($student['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-middle-name="<?php echo htmlspecialchars($student['middle_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-last-name="<?php echo htmlspecialchars($student['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-suffix="<?php echo htmlspecialchars($student['suffix'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-fingerprint-id="<?php echo htmlspecialchars((string) ($student['fingerprint_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-gender="<?php echo htmlspecialchars($student['gender'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                >Edit</button>
                                                <button
                                                    type="button"
                                                    class="btn-remove"
                                                    data-student-id="<?php echo htmlspecialchars((string) $student['lrn'], ENT_QUOTES, 'UTF-8'); ?>"
                                                >Deactivate</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="no-data-cell">No students record found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>



            <!-- Edit Student Modal -->
            <div id="editStudentModal" class="modal-overlay" aria-hidden="true">
                <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="editStudentModalTitle">
                    <div class="modal-header">
                        <h3 id="editStudentModalTitle">Edit Student</h3>
                        <button type="button" class="close-modal" aria-label="Close">&times;</button>
                    </div>
                    <form id="editStudentForm" action="index.php?page=students" method="POST">
                        <input type="hidden" name="action" value="edit_student">
                        <input type="hidden" name="current_student_id" id="current_student_id">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="form-group">
                            <label for="edit_first_name">First Name</label>
                            <input type="text" id="edit_first_name" name="first_name" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_middle_name">Middle Name</label>
                            <input type="text" id="edit_middle_name" name="middle_name">
                        </div>

                        <div class="form-group">
                            <label for="edit_last_name">Last Name</label>
                            <input type="text" id="edit_last_name" name="last_name" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_suffix">Suffix</label>
                            <input type="text" id="edit_suffix" name="suffix" placeholder="e.g. Jr., Sr., III">
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="clear_fingerprint_id" name="clear_fingerprint_id" value="1">
                                Remove the registered fingerprint ID
                            </label>
                            <p id="edit_fingerprint_status">No fingerprint is currently registered.</p>
                        </div>

                        <div class="form-group">
                            <label for="edit_lrn">LRN</label>
                            <input type="text" id="edit_lrn" name="lrn" inputmode="numeric" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_gender">Gender</label>
                            <select id="edit_gender" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="cancel-btn">Cancel</button>
                            <button type="submit" class="submit-btn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>



            <!-- Remove Student Modal -->
            <div id="removeStudentModal" class="modal-overlay" aria-hidden="true">
                <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="removeStudentModalTitle">
                    <div class="modal-header">
                        <h3 id="removeStudentModalTitle">Confirm Deactivation</h3>
                        <!-- <button type="button" class="close-modal" aria-label="Close">&times;</button> -->
                    </div>
                    <p>Are you sure you want to deactivate this student from the list?</p>
                    <form id="removeStudentForm" action="index.php?page=students" method="POST">
                        <input type="hidden" name="action" value="remove_student">
                        <input type="hidden" name="student_id" id="remove_student_id">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="modal-actions">
                            <button type="button" class="cancel-btn">Cancel</button>
                            <button type="submit" class="delete-btn">Yes, deactivate</button>
                        </div>
                    </form>
                </div>
            </div>



            <!-- Add Student Modal -->
            <div id="addStudentModal" class="modal-overlay">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Add New Student</h3>
                        <span class="close-modal">&times;</span>
                    </div>
                    <form id="addStudentForm" action="index.php?page=students" method="POST">
                        <input type="hidden" name="action" value="add_student">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-group">
                            <label for="lrn">LRN</label>
                            <input type="text" id="lrn" name="lrn" placeholder="Enter LRN" required>
                        </div>

                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" placeholder="Enter First Name" required>
                        </div>

                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" placeholder="Enter Middle Name">
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" placeholder="Enter Last Name" required>
                        </div>

                        <div class="form-group">
                            <label for="suffix">Suffix</label>
                            <input type="text" id="suffix" name="suffix" placeholder="e.g. Jr., Sr., III">
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <label class="fingerprint-enrollment-option" for="register_fingerprint">
                            <input type="checkbox" id="register_fingerprint" name="register_fingerprint" value="1" checked>
                            <span>Register fingerprint after saving this student (optional)</span>
                        </label>

                        <div class="modal-actions">
                            <button type="button" class="cancel-btn">Cancel</button>
                            <button type="submit" class="submit-btn">Save Student</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($fingerprintEnrollmentStudent)): ?>
                <?php
                $pendingFingerprintLrn = (string) $fingerprintEnrollmentStudent['lrn'];
                $pendingFingerprintName = trim(
                    ($fingerprintEnrollmentStudent['last_name'] ?? '') . ', ' .
                    ($fingerprintEnrollmentStudent['first_name'] ?? '') . ' ' .
                    ($fingerprintEnrollmentStudent['middle_name'] ?? '') . ' ' .
                    ($fingerprintEnrollmentStudent['suffix'] ?? '')
                );
                $pendingFingerprintLabel = $pendingFingerprintName . ' (LRN ' . $pendingFingerprintLrn . ')';
                ?>
                <div id="directFingerprintEnrollmentModal" class="modal-overlay" aria-hidden="true" data-open="1">
                    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="directFingerprintEnrollmentTitle">
                        <div class="modal-header">
                            <h3 id="directFingerprintEnrollmentTitle">Register Fingerprint</h3>
                            <button type="button" class="close-modal" aria-label="Close">&times;</button>
                        </div>
                        <p>Student saved: <strong><?= htmlspecialchars($pendingFingerprintLabel, ENT_QUOTES, 'UTF-8') ?></strong></p>
                        <p>Connect the ESP32 reader using the button in the sidebar, then follow the prompts to scan the same finger twice.</p>
                        <p id="directFingerprintEnrollmentStatus" role="status" aria-live="polite">Select the student and enroll a fingerprint, or close this dialog to do it later.</p>
                        <form id="fingerprintEnrollForm">
                            <div class="form-group">
                                <label for="fingerprintStudentSearch">Student being enrolled</label>
                                <input
                                    type="search"
                                    id="fingerprintStudentSearch"
                                    value="<?= htmlspecialchars($pendingFingerprintLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    autocomplete="off"
                                    readonly
                                    aria-describedby="fingerprintStudentSearchStatus"
                                >
                                <p id="fingerprintStudentSearchStatus" role="status" aria-live="polite">New student selected.</p>
                            </div>
                            <div class="form-group">
                                <label for="fingerprintStudent">Student</label>
                                <select id="fingerprintStudent" name="student_lrn" data-initial-lrn="<?= htmlspecialchars($pendingFingerprintLrn, ENT_QUOTES, 'UTF-8') ?>" required disabled>
                                    <option value="">Select the new student</option>
                                    <option
                                        value="<?= htmlspecialchars($pendingFingerprintLrn, ENT_QUOTES, 'UTF-8') ?>"
                                        data-student-name="<?= htmlspecialchars($pendingFingerprintLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    ><?= htmlspecialchars($pendingFingerprintLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                </select>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="cancel-btn">Skip registration</button>
                                <button type="submit" class="submit-btn" id="enrollFingerprintButton" disabled>Enroll Fingerprint</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>



            <!-- Absence History Modal -->
            <div id="absencesModal" class="absences-modal-overlay" aria-hidden="true">
                <div class="absences-modal-content" role="dialog" aria-modal="true" aria-labelledby="absencesModalTitle">
                    <input type="hidden" id="absenceCsrfToken" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="modal-header-row">
                        <h3 id="absencesModalTitle">Absence History</h3>
                        <button type="button" class="close-modal-btn" onclick="closeAbsencesModal()">&times;</button>
                    </div>

                    <div class="absence-save-error" id="absenceStatusMessage" role="alert" hidden></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="absenceHistoryRows">
                                    <tr><td colspan="4">Select a student to load absence records.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>



                <!-- Remarks Option Modal -->
                <div id="remarksOptionModal" class="remarks-modal-overlay" aria-hidden="true">
                    <div class="absences-modal-content" role="dialog" aria-modal="true" aria-labelledby="remarksOptionTitle">
                        <div class="modal-header-row">
                            <h3 id="remarksOptionTitle">Remarks Option</h3>
                            <button type="button" class="close-modal-btn" id="closeRemarksModal" aria-label="Close">&times;</button>
                        </div>
                        <form id="excusedReasonForm">
                            <div class="form-group">
                                <label for="excusedReason">Reason for being excused</label>
                                <select id="excusedReason" name="reason" required>
                                    <option value="">Select a reason</option>
                                    <option value="Illness">Illness</option>
                                    <option value="Medical appointment">Medical appointment</option>
                                    <option value="Family emergency">Family emergency</option>
                                    <option value="Bereavement">Bereavement</option>
                                    <option value="School activity">School activity</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group" id="otherRemarksGroup" hidden>
                                <label for="otherRemarks">Specific remark</label>
                                <textarea id="otherRemarks" name="other_remarks" maxlength="255"></textarea>
                            </div>
                            <div class="absence-save-error" id="absenceSaveError" role="alert" hidden></div>
                            <div class="absences-modal-actions">
                                <button type="button" class="cancel-btn" id="cancelRemarksModal">Cancel</button>
                                <button type="submit" class="submit-btn" id="saveExcusedStatus">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>



        </div>
    </main>

    <script src="/public/js/studentAbsences.js"></script>
    <script src="/public/js/addStudentModal.js"></script>
    <script src="/public/js/alertTimer.js"></script>
</body>
</html>