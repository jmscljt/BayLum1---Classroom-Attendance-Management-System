const absencesModal = document.getElementById('absencesModal');
const absenceHistoryRows = document.getElementById('absenceHistoryRows');
const absencesModalTitle = document.getElementById('absencesModalTitle');
let absenceRequest;

function setAbsenceMessage(message) {
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 3;
    cell.textContent = message;
    row.appendChild(cell);
    absenceHistoryRows.replaceChildren(row);
}

function closeAbsencesModal() {
    absencesModal.style.display = 'none';
    absencesModal.setAttribute('aria-hidden', 'true');
    if (absenceRequest) {
        absenceRequest.abort();
        absenceRequest = null;
    }
}

async function openAbsencesModal(studentId, studentName) {
    if (absenceRequest) {
        absenceRequest.abort();
    }

    const request = new AbortController();
    absenceRequest = request;
    absencesModalTitle.textContent = `Absence History - ${studentName}`;
    setAbsenceMessage('Loading absence records...');
    absencesModal.style.display = 'flex';
    absencesModal.setAttribute('aria-hidden', 'false');

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
            absenceHistoryRows.appendChild(row);
        });
    } catch (error) {
        if (error.name !== 'AbortError') {
            setAbsenceMessage(error.message || 'Unable to load absence records.');
        }
    } finally {
        if (absenceRequest === request) {
            absenceRequest = null;
        }
    }
}

document.querySelectorAll('.btn-view').forEach((button) => {
    button.addEventListener('click', () => {
        openAbsencesModal(button.dataset.studentId, button.dataset.studentName);
    });
});

absencesModal.addEventListener('click', (event) => {
    if (event.target === absencesModal) {
        closeAbsencesModal();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && absencesModal.style.display === 'flex') {
        closeAbsencesModal();
    }
});