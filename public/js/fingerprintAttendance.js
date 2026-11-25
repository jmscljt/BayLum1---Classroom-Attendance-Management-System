document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('fingerprint-reader');
    const status = document.getElementById('fingerprintReaderStatus');
    const enrollForm = document.getElementById('fingerprintEnrollForm');
    const studentSearch = document.getElementById('fingerprintStudentSearch');
    const studentSearchStatus = document.getElementById('fingerprintStudentSearchStatus');
    const studentSelect = document.getElementById('fingerprintStudent');
    const enrollButton = document.getElementById('enrollFingerprintButton');
    if (
        !panel || !status
        || !enrollForm || !studentSearch || !studentSearchStatus
        || !studentSelect || !enrollButton
    ) {
        return;
    }

    const studentOptions = Array.from(studentSelect.options).slice(1);

    function filterStudents() {
        const query = studentSearch.value.trim().toLocaleLowerCase();
        const matches = query
            ? studentOptions.filter((option) => option.textContent.toLocaleLowerCase().includes(query))
            : [];
        const placeholder = studentSelect.options[0];
        studentSelect.replaceChildren(placeholder, ...matches);
        studentSelect.value = '';
        studentSelect.disabled = matches.length === 0;
        enrollButton.disabled = true;

        if (!query) {
            studentSearchStatus.textContent = studentOptions.length
                ? 'Type a name or LRN to find a student.'
                : 'No active students are available to enroll.';
        } else if (matches.length === 0) {
            studentSearchStatus.textContent = 'No students found. Check the spelling or LRN and try again.';
        } else {
            studentSearchStatus.textContent = `${matches.length} student${matches.length === 1 ? '' : 's'} found. Select the correct student below.`;
        }
    }

    filterStudents();

    let buffer = '';
    let lastFingerprintId = null;
    let lastScanAt = 0;
    let requestInProgress = false;
    let pendingEnrollment = null;
    let esp32Connected = false;

    function showStatus(message) {
        status.textContent = message;
    }

    async function checkDeviceStatus() {
        try {
            const result = await postRequest({ action: 'device_status' });
            esp32Connected = Boolean(result.connected && result.sensor_ready);
            if (!pendingEnrollment) {
                showStatus(result.message);
            }
            enrollButton.disabled = !esp32Connected || !studentSelect.value || Boolean(pendingEnrollment);
        } catch (error) {
            esp32Connected = false;
            showStatus(error.message || 'Could not check the ESP32 fingerprint reader.');
            enrollButton.disabled = true;
        }
    }

    checkDeviceStatus();
    window.setInterval(checkDeviceStatus, 3000);

    async function postRequest(payload) {
        const response = await fetch(panel.dataset.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': panel.dataset.csrfToken,
                Accept: 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || 'The server could not process the request.');
        }
        return result;
    }

    async function recordFingerprint(fingerprintId) {
        const now = Date.now();
        if (
            requestInProgress
            || pendingEnrollment
            || (lastFingerprintId === fingerprintId && now - lastScanAt < 3000)
        ) {
            return;
        }
        requestInProgress = true;
        lastFingerprintId = fingerprintId;
        lastScanAt = now;
        showStatus(`AS608 matched slot ${fingerprintId}. Sending attendance...`);

        try {
            const result = await postRequest({
                action: 'attendance',
                fingerprint_id: fingerprintId
            });
            showStatus(result.message);
        } catch (error) {
            showStatus(error.message || `Unable to record scanned slot ${fingerprintId}.`);
        } finally {
            requestInProgress = false;
        }
    }

    async function finishEnrollment(fingerprintId) {
        if (!pendingEnrollment || pendingEnrollment.fingerprintId !== fingerprintId) {
            return;
        }

        const enrollment = pendingEnrollment;
        pendingEnrollment = null;
        requestInProgress = true;
        try {
            const result = await postRequest({
                action: 'save_enrollment',
                student_lrn: enrollment.studentLrn,
                fingerprint_id: fingerprintId
            });
            enrollment.option.value = enrollment.studentLrn;
            enrollment.option.disabled = false;
            enrollment.option.textContent = `${enrollment.studentName} (registered: ID ${fingerprintId})`;
            studentSelect.value = enrollment.studentLrn;
            showStatus(result.message);
        } catch (error) {
            showStatus(
                `The sensor stored slot ${fingerprintId}, but the student mapping was not saved: `
                + (error.message || 'unknown server error')
                + ' Do not reuse this slot; contact the administrator to resolve the mapping.'
            );
        } finally {
            requestInProgress = false;
            enrollButton.disabled = !esp32Connected || !studentSelect.value;
        }
    }

    function handleLine(rawLine) {
        const line = rawLine.trim();
        const match = /^MATCH:(\d{1,3})$/.exec(line);
        if (match) {
            const fingerprintId = Number(match[1]);
            if (fingerprintId >= 1 && fingerprintId <= 127) {
                recordFingerprint(fingerprintId);
            }
            return;
        }

        const enrolled = /^ENROLLED:(\d{1,3})$/.exec(line);
        if (enrolled) {
            finishEnrollment(Number(enrolled[1]));
            return;
        }

        if (line === 'READY') {
            esp32Connected = true;
            showStatus('AS608 connected. Scan a registered finger or enroll a student below.');
        } else if (line === 'NO_MATCH') {
            showStatus('Fingerprint not recognized. Enroll this finger to a student before taking attendance.');
        } else if (line === 'ENROLL:PLACE_FINGER') {
            showStatus('Enrollment started. Place the selected student’s finger on the reader.');
        } else if (line === 'ENROLL:REMOVE_FINGER') {
            showStatus('Remove the finger, then place the same finger again when prompted.');
        } else if (line === 'ENROLL:PLACE_AGAIN') {
            showStatus('Place the same finger on the reader a second time.');
        } else if (line.startsWith('ERROR:')) {
            const messages = {
                'ERROR:ID_IN_USE': 'That AS608 slot already contains a fingerprint. Choose an unused slot.',
                'ERROR:FINGER_MISMATCH': 'The two scans did not match. Start enrollment again using the same finger.',
                'ERROR:AS608_NOT_FOUND': 'The Uno cannot communicate with the AS608. Check power and sensor wiring.',
                'ERROR:STORE_FAILED': 'The AS608 could not save the fingerprint template.',
                'ERROR:IMAGE_CAPTURE': 'The AS608 could not read the finger. Try again.',
                'ERROR:IMAGE_CONVERSION': 'The AS608 could not process the fingerprint. Try again.'
            };
            if (pendingEnrollment) {
                pendingEnrollment = null;
                enrollButton.disabled = !esp32Connected || !studentSelect.value;
            }
            showStatus(messages[line] || line);
        }
    }

    enrollForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!esp32Connected) {
            showStatus('Waiting for the ESP32 fingerprint reader. The ESP32 connects automatically when powered.');
            return;
        }
        if (pendingEnrollment || requestInProgress) {
            return;
        }

        const studentLrn = studentSelect.value;
        const option = studentSelect.options[studentSelect.selectedIndex];
        const studentName = option ? (option.dataset.studentName || option.textContent) : '';
        if (!studentLrn) {
            showStatus('Select an active student.');
            return;
        }

        requestInProgress = true;
        enrollButton.disabled = true;
        showStatus('Checking student and fingerprint ID...');
        try {
            const result = await postRequest({
                action: 'check_enrollment',
                student_lrn: studentLrn
            });
            pendingEnrollment = {
                studentLrn,
                studentName,
                fingerprintId: result.fingerprint_id,
                commandId: result.command_id,
                option
            };
            showStatus(result.message + ` Assigned AS608 ID ${result.fingerprint_id}. Follow the instructions and scan the same finger twice.`);
            const statusInterval = window.setInterval(async () => {
                if (!pendingEnrollment || pendingEnrollment.commandId !== result.command_id) {
                    window.clearInterval(statusInterval);
                    return;
                }
                try {
                    const statusResult = await postRequest({
                        action: 'enrollment_status',
                        student_lrn: studentLrn,
                        command_id: result.command_id
                    });
                    if (statusResult.message === 'Fingerprint enrollment completed.') {
                        window.clearInterval(statusInterval);
                        pendingEnrollment = null;
                        enrollButton.disabled = false;
                        showStatus(statusResult.message);
                    } else {
                        showStatus(statusResult.message);
                    }
                } catch (error) {
                    showStatus(error.message || 'Could not check fingerprint enrollment status.');
                }
            }, 1000);
        } catch (error) {
            pendingEnrollment = null;
            enrollButton.disabled = false;
            showStatus(error.message || 'Could not start fingerprint enrollment.');
        } finally {
            requestInProgress = false;
        }
    });

    studentSelect.addEventListener('change', () => {
        enrollButton.disabled = !esp32Connected || !studentSelect.value || Boolean(pendingEnrollment);
    });
    studentSearch.addEventListener('input', filterStudents);
});
