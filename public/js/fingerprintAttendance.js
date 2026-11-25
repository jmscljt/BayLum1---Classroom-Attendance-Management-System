document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('fingerprint-reader');
    const status = document.getElementById('fingerprintReaderStatus');
    const connectButton = document.getElementById('connectFingerprintReaderButton');
    const enrollForm = document.getElementById('fingerprintEnrollForm');
    const studentSearch = document.getElementById('fingerprintStudentSearch');
    const studentSearchStatus = document.getElementById('fingerprintStudentSearchStatus');
    const studentSelect = document.getElementById('fingerprintStudent');
    const enrollButton = document.getElementById('enrollFingerprintButton');
    if (
        !panel || !status || !connectButton
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
    let pendingSlotReplacement = null;
    let esp32Connected = false;
    let serialPort = null;
    let serialWriter = null;
    let serialReader = null;

    function showStatus(message) {
        status.textContent = message;
    }

    async function writeSerial(line) {
        if (!serialWriter) {
            throw new Error('The ESP32 reader is not connected.');
        }
        await serialWriter.write(new TextEncoder().encode(line + '\n'));
    }

    async function connectSerial() {
        if (!('serial' in navigator)) {
            showStatus('Web Serial is not supported here. Use current Chrome or Edge over localhost or HTTPS.');
            return;
        }

        try {
            serialPort = await navigator.serial.requestPort();
            await serialPort.open({ baudRate: 115200 });
            serialWriter = serialPort.writable.getWriter();
            serialReader = serialPort.readable.getReader();
            connectButton.textContent = 'ESP32 Reader Connected';
            connectButton.disabled = true;

            const decoder = new TextDecoder();
            let receiveBuffer = '';
            while (true) {
                const { value, done } = await serialReader.read();
                if (done) {
                    break;
                }
                receiveBuffer += decoder.decode(value, { stream: true });
                const lines = receiveBuffer.split(/\r?\n/);
                receiveBuffer = lines.pop() || '';
                lines.forEach(handleLine);
            }
            esp32Connected = false;
            serialWriter = null;
            serialReader = null;
            connectButton.disabled = false;
            connectButton.textContent = 'Connect ESP32 Reader';
            showStatus('The ESP32 reader disconnected. Reconnect it and try again.');
        } catch (error) {
            esp32Connected = false;
            serialWriter = null;
            serialReader = null;
            connectButton.disabled = false;
            connectButton.textContent = 'Connect ESP32 Reader';
            showStatus(error.message || 'Could not connect to the ESP32 reader.');
        }
    }

    connectButton.addEventListener('click', connectSerial);

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

        const deleted = /^DELETED:(\d{1,3})$/.exec(line);
        if (deleted) {
            const fingerprintId = Number(deleted[1]);
            if (pendingSlotReplacement === fingerprintId) {
                pendingSlotReplacement = null;
                showStatus(`Cleared old fingerprint slot ${fingerprintId}. Starting re-enrollment...`);
                writeSerial(`ENROLL:${fingerprintId}`).catch((error) => {
                    pendingEnrollment = null;
                    pendingSlotReplacement = null;
                    enrollButton.disabled = !esp32Connected || !studentSelect.value;
                    showStatus(error.message || 'Could not restart fingerprint enrollment.');
                });
            }
            return;
        }

        if (line === 'READY') {
            esp32Connected = true;
            showStatus('AS608 connected. Scan a registered finger or enroll a student below.');
            connectButton.disabled = true;
            enrollButton.disabled = !studentSelect.value || Boolean(pendingEnrollment);
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
                'ERROR:ID_IN_USE': 'That AS608 slot contains an old fingerprint. Clearing it and restarting enrollment.',
                'ERROR:FINGER_MISMATCH': 'The two scans did not match. Start enrollment again using the same finger.',
                'ERROR:AS608_NOT_FOUND': 'The ESP32 cannot communicate with the AS608. Check power and sensor wiring.',
                'ERROR:STORE_FAILED': 'The AS608 could not save the fingerprint template.',
                'ERROR:IMAGE_CAPTURE': 'The AS608 could not read the finger. Try again.',
                'ERROR:IMAGE_CONVERSION': 'The AS608 could not process the fingerprint. Try again.'
            };
            if (line === 'ERROR:ID_IN_USE' && pendingEnrollment) {
                pendingSlotReplacement = pendingEnrollment.fingerprintId;
                showStatus(messages[line]);
                writeSerial(`DELETE:${pendingEnrollment.fingerprintId}`).catch((error) => {
                    pendingEnrollment = null;
                    pendingSlotReplacement = null;
                    enrollButton.disabled = !esp32Connected || !studentSelect.value;
                    showStatus(error.message || 'Could not clear the occupied fingerprint slot.');
                });
            } else if (pendingEnrollment) {
                pendingEnrollment = null;
                enrollButton.disabled = !esp32Connected || !studentSelect.value;
            }
            showStatus(messages[line] || line);
        }
    }

    enrollForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!esp32Connected) {
            showStatus('Click Connect ESP32 Reader and select the ESP32 serial port first.');
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
                option
            };
            showStatus(result.message + ` Assigned AS608 ID ${result.fingerprint_id}. Follow the instructions and scan the same finger twice.`);
            await writeSerial(`ENROLL:${result.fingerprint_id}`);
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
