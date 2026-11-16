document.addEventListener('DOMContentLoaded', function () {
    const recordModal = document.getElementById('sectionRecordsModal');
    const editModal = document.getElementById('editAssignmentModal');
    const recordRows = document.getElementById('sectionLogRows');
    const summaryRows = document.getElementById('sectionSummaryRows');
    const recordTitle = document.getElementById('sectionRecordsTitle');
    const recordMessage = document.getElementById('sectionRecordMessage');
    const adviserLabel = document.getElementById('sectionAdviser');
    const attendanceDate = document.getElementById('sectionAttendanceDate');
    const summaryView = document.getElementById('sectionAttendanceSummary');
    const detailsView = document.getElementById('sectionAttendanceDetails');
    const detailsTitle = document.getElementById('sectionAttendanceDetailsTitle');
    let activeAssignmentId = null;

    function showModal(modal) {
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal(modal) {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }

    function formatDate(date) {
        return new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    function formatTime(time) {
        if (!time) {
            return '-';
        }
        return new Date(time.replace(' ', 'T')).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function addCell(row, value) {
        const cell = document.createElement('td');
        cell.textContent = value == null ? '' : value;
        row.appendChild(cell);
    }

    async function loadAttendanceRecord(assignmentId, date, logDate) {
        const params = new URLSearchParams({
            page: 'otherSectionHandled',
            record: assignmentId
        });
        if (date) {
            params.set('attendance_date', date);
        }
        if (logDate) {
            params.set('log_date', logDate);
        }

        const response = await fetch('index.php?' + params.toString(), {
            headers: { Accept: 'application/json' }
        });
        const record = await response.json();
        if (!response.ok) {
            throw new Error(record.error || 'Unable to load attendance records.');
        }
        return record;
    }

    function showAttendanceSummaries(summaries, assignmentId) {
        summaryRows.replaceChildren();
        summaries.forEach(function (summary) {
            const row = document.createElement('tr');
            addCell(row, formatDate(summary.attendance_date));
            addCell(row, summary.present_count);
            addCell(row, summary.absent_count);
            const actionCell = document.createElement('td');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'view-btn';
            button.textContent = 'View Records';
            button.addEventListener('click', function () {
                showAttendanceDetails(assignmentId, summary.attendance_date);
            });
            actionCell.appendChild(button);
            row.appendChild(actionCell);
            summaryRows.appendChild(row);
        });
        if (!summaries.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.textContent = 'No attendance records for this date.';
            row.appendChild(cell);
            summaryRows.appendChild(row);
        }
    }

    async function showAttendanceDetails(assignmentId, date) {
        summaryView.hidden = true;
        detailsView.hidden = false;
        detailsTitle.textContent = 'Attendance for ' + formatDate(date);
        recordMessage.textContent = 'Loading attendance details...';
        recordRows.replaceChildren();

        try {
            const record = await loadAttendanceRecord(assignmentId, null, date);
            record.attendanceLogs.forEach(function (log) {
                const row = document.createElement('tr');
                const name = [log.first_name, log.middle_name, log.last_name]
                    .filter(Boolean)
                    .join(' ');
                [
                    formatDate(date),
                    log.lrn,
                    name,
                    log.gender,
                    formatTime(log.time_in),
                    formatTime(log.time_out),
                    log.methods || 'Manual'
                ].forEach(function (value) {
                    addCell(row, value);
                });
                recordRows.appendChild(row);
            });
            if (!record.attendanceLogs.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 7;
                cell.textContent = 'No attendance record for this day.';
                row.appendChild(cell);
                recordRows.appendChild(row);
            }
            recordMessage.textContent = '';
        } catch (error) {
            recordMessage.textContent = error.message;
        }
    }

    function filterSections(gradeSelect, sectionSelect) {
        const selectedGrade = gradeSelect.value;
        sectionSelect.disabled = selectedGrade === '';
        Array.from(sectionSelect.options).forEach(function (option, index) {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            option.hidden = selectedGrade !== '' && option.dataset.grade !== selectedGrade;
        });

        const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
        if (selectedOption && selectedOption.hidden) {
            sectionSelect.value = '';
        }
    }

    document.querySelectorAll('[data-grade-select]').forEach(function (gradeSelect) {
        const sectionSelect = gradeSelect.parentElement.querySelector('[data-section-select]');
        if (!sectionSelect) {
            return;
        }
        gradeSelect.addEventListener('change', function () {
            filterSections(gradeSelect, sectionSelect);
        });
        filterSections(gradeSelect, sectionSelect);
    });

    document.querySelectorAll('.btn-view-record').forEach(function (button) {
        button.addEventListener('click', async function () {
            activeAssignmentId = button.dataset.assignmentId;
            recordTitle.textContent = 'Attendance Records of ' + button.dataset.label;
            adviserLabel.textContent = '';
            attendanceDate.value = '';
            recordMessage.textContent = 'Loading attendance records...';
            summaryRows.replaceChildren();
            summaryView.hidden = false;
            detailsView.hidden = true;
            showModal(recordModal);

            try {
                const record = await loadAttendanceRecord(activeAssignmentId, null, null);
                adviserLabel.textContent = 'Class Adviser: ' + record.section.class_adviser;
                recordMessage.textContent = '';
                showAttendanceSummaries(record.summaries, activeAssignmentId);
            } catch (error) {
                recordMessage.textContent = error.message;
            }
        });
    });

    document.getElementById('sectionAttendanceFilter').addEventListener('submit', async function (event) {
        event.preventDefault();
        recordMessage.textContent = 'Loading attendance records...';
        detailsView.hidden = true;
        summaryView.hidden = false;
        summaryRows.replaceChildren();
        if (!activeAssignmentId) {
            return;
        }

        try {
            const record = await loadAttendanceRecord(activeAssignmentId, attendanceDate.value, null);
            recordMessage.textContent = '';
            showAttendanceSummaries(record.summaries, activeAssignmentId);
        } catch (error) {
            recordMessage.textContent = error.message;
        }
    });

    document.getElementById('backToSectionSummary').addEventListener('click', function () {
        detailsView.hidden = true;
        summaryView.hidden = false;
        recordMessage.textContent = '';
    });

    document.querySelectorAll('.btn-edit-assignment').forEach(function (button) {
        button.addEventListener('click', function () {
            const gradeSelect = document.getElementById('editGradeSelect');
            const sectionSelect = document.getElementById('editSectionSelect');
            gradeSelect.value = button.dataset.grade;
            filterSections(gradeSelect, sectionSelect);
            sectionSelect.value = button.dataset.sectionId;
            document.getElementById('editAssignmentId').value = button.dataset.assignmentId;
            document.getElementById('editSubjectSelect').value = button.dataset.subject;
            showModal(editModal);
        });
    });

    document.querySelectorAll('.remove-assignment-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm('Remove this assigned section?')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(button.closest('.attendance-modal-overlay'));
        });
    });

    [recordModal, editModal].forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });
});
