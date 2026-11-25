<?php

require_once __DIR__ . '/../../Models/Teachers/TeachersModel.php';

class FingerprintAttendanceController
{
    private TeachersModel $teachersModel;
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->teachersModel = new TeachersModel($pdo);
    }

    public function handleRequest(
        array $session,
        string $requestMethod,
        string $rawInput,
        string $csrfToken
    ): void {
        header('Content-Type: application/json; charset=utf-8');

        if ($requestMethod !== 'POST') {
            $this->respond(405, ['error' => 'POST is required.']);
            return;
        }

        if (empty($session['user_logged_in']) || empty($session['user_id'])) {
            $this->respond(401, ['error' => 'Please sign in as a teacher to use fingerprint attendance.']);
            return;
        }

        $sessionToken = $session['csrf_token'] ?? '';
        if (
            !is_string($csrfToken)
            || !is_string($sessionToken)
            || $sessionToken === ''
            || !hash_equals($sessionToken, $csrfToken)
        ) {
            $this->respond(403, ['error' => 'Your session expired. Refresh the page and try again.']);
            return;
        }

        $payload = json_decode($rawInput, true);
        if (!is_array($payload)) {
            $this->respond(400, ['error' => 'A valid JSON request body is required.']);
            return;
        }

        $action = $payload['action'] ?? 'attendance';
        $fingerprintId = $payload['fingerprint_id'] ?? null;
        if (
            !is_string($action)
            || !in_array($action, ['attendance', 'check_enrollment', 'save_enrollment', 'enrollment_status', 'device_status'], true)
        ) {
            $this->respond(400, ['error' => 'Choose a valid fingerprint action.']);
            return;
        }
        $hasFingerprintId = is_int($fingerprintId)
            || (is_string($fingerprintId) && ctype_digit($fingerprintId));
        if ($action === 'attendance') {
            if (!$hasFingerprintId || (int) $fingerprintId < 1 || (int) $fingerprintId > 127) {
                $this->respond(400, ['error' => 'Choose a valid fingerprint ID from 1 to 127.']);
                return;
            }
        }

        try {
            if ($action === 'device_status') {
                $result = $this->getDeviceStatus();
            } elseif ($action === 'attendance') {
                $result = $this->teachersModel->recordFingerprintAttendance(
                    (int) $session['user_id'],
                    (int) $fingerprintId
                );
            } else {
                $studentLrn = $payload['student_lrn'] ?? '';
                if (!is_string($studentLrn) || preg_match('/^\d{1,19}$/D', $studentLrn) !== 1) {
                    $this->respond(400, ['error' => 'Select a valid student LRN.']);
                    return;
                }
                if ($action === 'enrollment_status') {
                    $result = $this->getEnrollmentStatus((int) ($payload['command_id'] ?? 0), $studentLrn);
                } else {
                    $result = $action === 'check_enrollment'
                        ? $this->teachersModel->checkFingerprintEnrollment(
                            (int) $session['user_id'],
                            $studentLrn,
                            $hasFingerprintId ? (int) $fingerprintId : null
                        )
                        : $this->teachersModel->saveFingerprintEnrollment(
                            (int) $session['user_id'],
                            $studentLrn,
                            $hasFingerprintId ? (int) $fingerprintId : null
                        );
                }
                if ($action === 'check_enrollment' && $result['success']) {
                    $commandId = $this->queueEnrollment(
                        $studentLrn,
                        (int) $result['fingerprint_id']
                    );
                    $result['command_id'] = $commandId;
                }
            }

            if (!$result['success']) {
                $this->respond(422, ['error' => $result['message']]);
                return;
            }

            $this->respond(200, [
                'success' => true,
                'message' => $result['message'],
                'fingerprint_id' => $result['fingerprint_id'] ?? null,
                'command_id' => $result['command_id'] ?? null,
                'connected' => $result['connected'] ?? null,
                'sensor_ready' => $result['sensor_ready'] ?? null,
            ]);
        } catch (PDOException | RuntimeException $e) {
            error_log('Fingerprint attendance error: ' . $e->getMessage());
            $this->respond(500, ['error' => 'Unable to process fingerprint attendance right now.']);
        }
    }

    private function ensureDeviceTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS fingerprint_device_commands (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                device_id VARCHAR(64) NOT NULL DEFAULT "esp32-as608",
                command VARCHAR(16) NOT NULL,
                fingerprint_id SMALLINT UNSIGNED NOT NULL,
                student_lrn VARCHAR(19) NULL,
                status VARCHAR(16) NOT NULL DEFAULT "pending",
                error_message VARCHAR(255) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS fingerprint_devices (
                device_id VARCHAR(64) PRIMARY KEY,
                sensor_ready TINYINT(1) NOT NULL DEFAULT 0,
                last_seen_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB'
        );
    }

    private function queueEnrollment(string $studentLrn, int $fingerprintId): int
    {
        $this->ensureDeviceTable();
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO fingerprint_device_commands
             (device_id, command, fingerprint_id, student_lrn, status, created_at, updated_at)
             VALUES ("esp32-as608", "ENROLL", :fingerprint_id, :student_lrn, "pending", :created_at, :updated_at)'
        );
        $stmt->execute([
            ':fingerprint_id' => $fingerprintId,
            ':student_lrn' => $studentLrn,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    private function getDeviceStatus(): array
    {
        $this->ensureDeviceTable();
        $stmt = $this->pdo->prepare(
            'SELECT sensor_ready, last_seen_at
             FROM fingerprint_devices
             WHERE device_id = "esp32-as608"
             LIMIT 1'
        );
        $stmt->execute();
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$device) {
            return [
                'success' => true,
                'connected' => false,
                'sensor_ready' => false,
                'message' => 'ESP32 fingerprint reader is offline.',
            ];
        }

        $lastSeen = strtotime((string) $device['last_seen_at']);
        $connected = $lastSeen !== false && (time() - $lastSeen) <= 15;
        if (!$connected) {
            return [
                'success' => true,
                'connected' => false,
                'sensor_ready' => false,
                'message' => 'ESP32 fingerprint reader is offline.',
            ];
        }
        if (!(int) $device['sensor_ready']) {
            return [
                'success' => true,
                'connected' => true,
                'sensor_ready' => false,
                'message' => 'ESP32 is connected, but the AS608 sensor was not found.',
            ];
        }
        return [
            'success' => true,
            'connected' => true,
            'sensor_ready' => true,
            'message' => 'ESP32 and AS608 fingerprint reader are connected.',
        ];
    }

    private function getEnrollmentStatus(int $commandId, string $studentLrn): array
    {
        $this->ensureDeviceTable();
        $stmt = $this->pdo->prepare(
            'SELECT status, fingerprint_id, error_message
             FROM fingerprint_device_commands
             WHERE id = :id AND student_lrn = :student_lrn
             LIMIT 1'
        );
        $stmt->execute([
            ':id' => $commandId,
            ':student_lrn' => $studentLrn,
        ]);
        $command = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$command) {
            return ['success' => false, 'message' => 'Enrollment status was not found.'];
        }
        if ($command['status'] === 'completed') {
            return [
                'success' => true,
                'fingerprint_id' => (int) $command['fingerprint_id'],
                'message' => 'Fingerprint enrollment completed.',
            ];
        }
        if ($command['status'] === 'failed') {
            return [
                'success' => false,
                'message' => $command['error_message'] ?: 'Fingerprint enrollment failed.',
            ];
        }
        return [
            'success' => true,
            'fingerprint_id' => (int) $command['fingerprint_id'],
            'message' => 'Waiting for the ESP32 fingerprint reader.',
        ];
    }

    private function respond(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
