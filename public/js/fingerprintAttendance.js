document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('fingerprint-reader');
    const connectButton = document.getElementById('connectFingerprintReader');
    const disconnectButton = document.getElementById('disconnectFingerprintReader');
    const status = document.getElementById('fingerprintReaderStatus');
    const enrollForm = document.getElementById('fingerprintEnrollForm');
    const studentSearch = document.getElementById('fingerprintStudentSearch');
    const studentSearchStatus = document.getElementById('fingerprintStudentSearchStatus');
    const studentSelect = document.getElementById('fingerprintStudent');
    const slotInput = document.getElementById('fingerprintSlot');
    const enrollButton = document.getElementById('enrollFingerprintButton');
    if (
        !panel || !connectButton || !disconnectButton || !status
        || !enrollForm || !studentSearch || !studentSearchStatus
        || !studentSelect || !slotInput || !enrollButton
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

    let port;
    let reader;
    let reading = false;
    let buffer = '';
    let lastFingerprintId = null;
    let lastScanAt = 0;
    let requestInProgress = false;
    let pendingEnrollment = null;

    function showStatus(message) {
        status.textContent = message;
    }

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
            slotInput.value = '';
            showStatus(result.message);
        } catch (error) {
            showStatus(
                `The sensor stored slot ${fingerprintId}, but the student mapping was not saved: `
                + (error.message || 'unknown server error')
                + ' Do not reuse this slot; contact the administrator to resolve the mapping.'
            );
        } finally {
            requestInProgress = false;
            enrollButton.disabled = !port || !studentSelect.value || !slotInput.value;
            disconnectButton.disabled = false;
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
                enrollButton.disabled = !port || !studentSelect.value || !slotInput.value;
                disconnectButton.disabled = false;
            }
            showStatus(messages[line] || line);
        }
    }

    async function sendCommand(command) {
        if (!port || !port.writable) {
            throw new Error('The serial port is not connected.');
        }
        const writer = port.writable.getWriter();
        try {
            await writer.write(new TextEncoder().encode(`${command}\n`));
        } finally {
            writer.releaseLock();
        }
    }

    async function disconnect() {
        reading = false;
        pendingEnrollment = null;
        if (reader) {
            await reader.cancel();
        }
    }

    connectButton.addEventListener('click', async () => {
        if (!('serial' in navigator)) {
            showStatus('Web Serial is unavailable. Use a current Chrome or Edge browser.');
            return;
        }

        connectButton.disabled = true;
        try {
            port = await navigator.serial.requestPort();
            await port.open({ baudRate: 57600 });
            reader = port.readable.pipeThrough(new TextDecoderStream()).getReader();
            reading = true;
            connectButton.hidden = true;
            disconnectButton.hidden = false;
            enrollButton.disabled = false;
            showStatus('Reader connected. Initializing AS608...');

            while (reading) {
                const { value, done } = await reader.read();
                if (done) {
                    break;
                }
                buffer += value;
                const lines = buffer.split(/\r?\n/);
                buffer = lines.pop() || '';
                lines.forEach(handleLine);
            }
        } catch (error) {
            showStatus(error.message || 'Could not connect to the fingerprint reader.');
        } finally {
            if (reader) {
                const activeReader = reader;
                reader = null;
                activeReader.releaseLock();
            }
            if (port) {
                const activePort = port;
                port = null;
                if (activePort.readable !== null || activePort.writable !== null) {
                    try {
                        await activePort.close();
                    } catch (error) {
                        if (error.name !== 'InvalidStateError') {
                            showStatus(error.message || 'The reader connection could not be closed.');
                        }
                    }
                }
            }
            reading = false;
            connectButton.disabled = false;
            connectButton.hidden = false;
            disconnectButton.hidden = true;
            disconnectButton.disabled = false;
            enrollButton.disabled = true;
            if (pendingEnrollment) {
                pendingEnrollment = null;
                showStatus('Reader disconnected before enrollment finished. Check whether the AS608 stored the selected slot before retrying.');
            }
        }
    });

    disconnectButton.addEventListener('click', () => {
        disconnect().catch((error) => {
            showStatus(error.message || 'Could not disconnect the reader.');
        });
    });

    enrollForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!port || !reading) {
            showStatus('Connect the AS608 reader before enrolling a student.');
            return;
        }
        if (pendingEnrollment || requestInProgress) {
            return;
        }

        const studentLrn = studentSelect.value;
        const fingerprintId = Number(slotInput.value);
        const option = studentSelect.options[studentSelect.selectedIndex];
        const studentName = option ? (option.dataset.studentName || option.textContent) : '';
        if (!studentLrn || !Number.isInteger(fingerprintId) || fingerprintId < 1 || fingerprintId > 127) {
            showStatus('Select an active student and an unused AS608 ID from 1 to 127.');
            return;
        }

        requestInProgress = true;
        enrollButton.disabled = true;
        showStatus('Checking student and fingerprint ID...');
        try {
            const result = await postRequest({
                action: 'check_enrollment',
                student_lrn: studentLrn,
                fingerprint_id: fingerprintId
            });
            pendingEnrollment = {
                studentLrn,
                studentName,
                fingerprintId,
                option
            };
            await sendCommand(`ENROLL:${fingerprintId}`);
            disconnectButton.disabled = true;
            showStatus(result.message + ' Follow the instructions and scan the same finger twice.');
        } catch (error) {
            pendingEnrollment = null;
            enrollButton.disabled = false;
            showStatus(error.message || 'Could not start fingerprint enrollment.');
        } finally {
            requestInProgress = false;
        }
    });

    studentSelect.addEventListener('change', () => {
        enrollButton.disabled = !port || !studentSelect.value || !slotInput.value || Boolean(pendingEnrollment);
    });
    studentSearch.addEventListener('input', filterStudents);
    slotInput.addEventListener('input', () => {
        enrollButton.disabled = !port || !studentSelect.value || !slotInput.value || Boolean(pendingEnrollment);
    });
});
