const absencesModal = document.getElementById('absencesModal');
const absenceHistoryRows = document.getElementById('absenceHistoryRows');
const absencesModalTitle = document.getElementById('absencesModalTitle');
const remarksOptionModal = document.getElementById('remarksOptionModal');
const excusedReasonForm = document.getElementById('excusedReasonForm');
const excusedReason = document.getElementById('excusedReason');
const otherRemarksGroup = document.getElementById('otherRemarksGroup');
const otherRemarks = document.getElementById('otherRemarks');
const absenceSaveError = document.getElementById('absenceSaveError');
const absenceStatusMessage = document.getElementById('absenceStatusMessage');
let absenceRequest;
let currentStudentId;
let currentStudentName;
let currentStudentRow;
let pendingAbsence;
let savingStatus = false;

function setAbsenceMessage(message) {
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 4;
    cell.textContent = message;
    row.appendChild(cell);
    absenceHistoryRows.replaceChildren(row);
}

function closeRemarksModal() {
    remarksOptionModal.style.display = 'none';
    remarksOptionModal.setAttribute('aria-hidden', 'true');
    pendingAbsence = null;
    absenceSaveError.hidden = true;
}

function closeAbsencesModal() {
    closeRemarksModal();
    absencesModal.style.display = 'none';
    absencesModal.setAttribute('aria-hidden', 'true');
    if (absenceRequest) {
        absenceRequest.abort();
        absenceRequest = null;
    }
}

function updateMasterlistCounts(previousStatus, status) {
    if (!currentStudentRow || previousStatus === status) {
        return;
    }

    const absentCell = currentStudentRow.cells[5];
    const excusedCell = currentStudentRow.cells[6];
    let absentCount = Number.parseInt(absentCell.textContent, 10) || 0;
    let excusedCount = Number.parseInt(excusedCell.textContent, 10) || 0;

    if (previousStatus === 'Absent') {
        absentCount = Math.max(0, absentCount - 1);
    } else if (previousStatus === 'Excused') {
        excusedCount = Math.max(0, excusedCount - 1);
    }

    if (status === 'Absent') {
        absentCount += 1;
    } else if (status === 'Excused') {
        excusedCount += 1;
    }

    absentCell.textContent = String(absentCount);
    excusedCell.textContent = String(excusedCount);
}

function createActionButton(absence, status) {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = status;
    button.dataset.status = status;
    button.className = 'absence-status-button';
    button.setAttribute('aria-pressed', absence.attendance_status === status ? 'true' : 'false');
    if (absence.attendance_status === status) {
        button.classList.add('is-current');
    }

    button.addEventListener('click', () => {
        if (status === 'Excused') {
            pendingAbsence = absence;
            excusedReasonForm.reset();
            otherRemarksGroup.hidden = true;
            otherRemarks.required = false;
            absenceSaveError.hidden = true;
            remarksOptionModal.style.display = 'flex';
            remarksOptionModal.setAttribute('aria-hidden', 'false');
            excusedReason.focus();
            return;
        }

        saveAbsenceStatus(absence, 'Absent').catch((error) => {
            absenceStatusMessage.textContent = error.message || 'Unable to update attendance.';
            absenceStatusMessage.hidden = false;
        });
    });

    return button;
}

async function loadAbsences(studentId) {
    if (absenceRequest) {
        absenceRequest.abort();
    }

    const request = new AbortController();
    absenceRequest = request;
    const url = new URL('index.php', window.location.href);
    url.searchParams.set('page', 'studentAbsences');
    url.searchParams.set('student_id', studentId);

    try {
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            signal: request.signal
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || 'Unable to load absence records.');
        }
        if (!Array.isArray(result.absences)) {
            throw new Error('The server returned an invalid absence history.');
        }

        absenceHistoryRows.replaceChildren();
        if (result.absences.length === 0) {
            setAbsenceMessage('No absence records found for this student.');
            return;
        }

        result.absences.forEach((absence) => {
            const row = document.createElement('tr');
            [absence.log_date, absence.attendance_status, absence.remarks].forEach((value) => {
                const cell = document.createElement('td');
                cell.textContent = value || '--';
                row.appendChild(cell);
            });

            const actionCell = document.createElement('td');
            const actionButtons = document.createElement('div');
            actionButtons.className = 'absence-action-buttons';
            actionButtons.append(
                createActionButton(absence, 'Absent'),
                createActionButton(absence, 'Excused')
            );
            actionCell.appendChild(actionButtons);
            row.appendChild(actionCell);
            absenceHistoryRows.appendChild(row);
        });
    } finally {
        if (absenceRequest === request) {
            absenceRequest = null;
        }
    }
}

async function openAbsencesModal(studentId, studentName, studentRow) {
    currentStudentId = studentId;
    currentStudentName = studentName;
    currentStudentRow = studentRow;
    absencesModalTitle.textContent = `Absence History - ${studentName}`;
    absenceStatusMessage.hidden = true;
    setAbsenceMessage('Loading absence records...');
    absencesModal.style.display = 'flex';
    absencesModal.setAttribute('aria-hidden', 'false');

    try {
        await loadAbsences(studentId);
    } catch (error) {
        if (error.name !== 'AbortError') {
            setAbsenceMessage(error.message || 'Unable to load absence records.');
        }
    }
}

async function saveAbsenceStatus(absence, status, reason = '', otherRemark = '') {
    if (savingStatus) {
        return;
    }
    savingStatus = true;
    document.querySelectorAll('.absence-status-button').forEach((button) => {
        button.disabled = true;
    });

    try {
        const formData = new URLSearchParams({
            csrf_token: document.getElementById('absenceCsrfToken').value,
            student_id: currentStudentId,
            date: absence.log_date,
            status,
            reason,
            other_remarks: otherRemark
        });
        const response = await fetch('index.php?page=studentAbsenceStatus', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || 'Unable to update attendance.');
        }

        updateMasterlistCounts(absence.attendance_status, status);
        absenceStatusMessage.hidden = true;
        closeRemarksModal();
        await loadAbsences(currentStudentId);
    } finally {
        savingStatus = false;
        document.querySelectorAll('.absence-status-button').forEach((button) => {
            button.disabled = false;
        });
    }
}

document.querySelectorAll('.btn-view').forEach((button) => {
    button.addEventListener('click', () => {
        openAbsencesModal(
            button.dataset.studentId,
            button.dataset.studentName,
            button.closest('tr')
        );
    });
});

excusedReason.addEventListener('change', () => {
    const isOther = excusedReason.value === 'Other';
    otherRemarksGroup.hidden = !isOther;
    otherRemarks.required = isOther;
    if (!isOther) {
        otherRemarks.value = '';
    }
});

excusedReasonForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!pendingAbsence) {
        return;
    }

    const saveButton = document.getElementById('saveExcusedStatus');
    saveButton.disabled = true;
    absenceSaveError.hidden = true;
    try {
        await saveAbsenceStatus(
            pendingAbsence,
            'Excused',
            excusedReason.value,
            otherRemarks.value
        );
    } catch (error) {
        absenceSaveError.textContent = error.message || 'Unable to update attendance.';
        absenceSaveError.hidden = false;
    } finally {
        saveButton.disabled = false;
    }
});

document.getElementById('closeRemarksModal').addEventListener('click', closeRemarksModal);
document.getElementById('cancelRemarksModal').addEventListener('click', closeRemarksModal);
remarksOptionModal.addEventListener('click', (event) => {
    if (event.target === remarksOptionModal) {
        closeRemarksModal();
    }
});

absencesModal.addEventListener('click', (event) => {
    if (event.target === absencesModal) {
        closeAbsencesModal();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && remarksOptionModal.style.display === 'flex') {
        closeRemarksModal();
    } else if (event.key === 'Escape' && absencesModal.style.display === 'flex') {
        closeAbsencesModal();
    }
});
