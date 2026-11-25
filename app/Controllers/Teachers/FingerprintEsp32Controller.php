<?php

require_once __DIR__ . '/../../Models/Teachers/TeachersModel.php';

class FingerprintEsp32Controller
{
    private PDO $pdo;
    private TeachersModel $teachersModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->teachersModel = new TeachersModel($pdo);
    }

    public function handleRequest(string $requestMethod, string $rawInput): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($requestMethod !== 'POST') {
            $this->respond(405, ['success' => false, 'error' => 'POST is required.']);
            return;
        }

        $payload = json_decode($rawInput, true);
        if (!is_array($payload)) {
            $this->respond(400, ['success' => false, 'error' => 'Invalid JSON body.']);
            return;
        }

        $configuredSecret = $_ENV['FINGERPRINT_DEVICE_SECRET'] ?? getenv('FINGERPRINT_DEVICE_SECRET');
        $providedSecret = $payload['device_secret'] ?? '';
        if (
            !is_string($configuredSecret)
            || $configuredSecret === ''
            || !is_string($providedSecret)
            || !hash_equals($configuredSecret, $providedSecret)
        ) {
            $this->respond(403, ['success' => false, 'error' => 'Invalid fingerprint device secret.']);
            return;
        }

        try {
            $this->ensureDeviceTable();
            $action = $payload['action'] ?? '';
            if ($action === 'poll') {
                $this->pollCommand($payload);
                return;
            }
            if ($action === 'report_match') {
                $this->reportMatch($payload);
                return;
            }
            if ($action === 'report_enrolled') {
                $this->reportEnrollment($payload);
                return;
            }
            if ($action === 'report_failed') {
                $this->reportFailed($payload);
                return;
            }
            if ($action === 'report_deleted') {
                $this->reportDeleted($payload);
                return;
            }
            $this->respond(400, ['success' => false, 'error' => 'Unknown device action.']);
        } catch (PDOException | RuntimeException $e) {
            error_log('ESP32 fingerprint device error: ' . $e->getMessage());
            $this->respond(500, ['success' => false, 'error' => 'Fingerprint device request failed.']);
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

    private function pollCommand(array $payload): void
    {
        $deviceId = 'esp32-as608';
        $now = date('Y-m-d H:i:s');
        $sensorReady = ($payload['sensor_ready'] ?? false) === true;
        $heartbeat = $this->pdo->prepare(
            'INSERT INTO fingerprint_devices
             (device_id, sensor_ready, last_seen_at, updated_at)
             VALUES (:device_id, :sensor_ready, :now, :now)
             ON DUPLICATE KEY UPDATE
               sensor_ready = VALUES(sensor_ready),
               last_seen_at = VALUES(last_seen_at),
               updated_at = VALUES(updated_at)'
        );
        $heartbeat->execute([
            ':device_id' => $deviceId,
            ':sensor_ready' => $sensorReady ? 1 : 0,
            ':now' => $now,
        ]);
        $stmt = $this->pdo->prepare(
            'SELECT id, command, fingerprint_id
             FROM fingerprint_device_commands
             WHERE device_id = :device_id AND status = "pending"
             ORDER BY id ASC
             LIMIT 1'
        );
        $stmt->execute([':device_id' => $deviceId]);
        $command = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$command) {
            $this->respond(200, ['success' => true, 'command' => null]);
            return;
        }

        $update = $this->pdo->prepare(
            'UPDATE fingerprint_device_commands
             SET status = "sent", updated_at = :updated_at
             WHERE id = :id'
        );
        $update->execute([
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $command['id'],
        ]);
        $this->respond(200, [
            'success' => true,
            'command_id' => (int) $command['id'],
            'command' => $command['command'],
            'fingerprint_id' => (int) $command['fingerprint_id'],
        ]);
    }

    private function reportEnrollment(array $payload): void
    {
        $commandId = filter_var($payload['command_id'] ?? null, FILTER_VALIDATE_INT);
        $fingerprintId = filter_var($payload['fingerprint_id'] ?? null, FILTER_VALIDATE_INT);
        if ($commandId === false || $fingerprintId === false || $fingerprintId < 1 || $fingerprintId > 127) {
            $this->respond(400, ['success' => false, 'error' => 'Invalid enrollment report.']);
            return;
        }

        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare(
            'SELECT student_lrn, fingerprint_id
             FROM fingerprint_device_commands
             WHERE id = :id AND command = "ENROLL" AND status = "sent"
             FOR UPDATE'
        );
        $stmt->execute([':id' => $commandId]);
        $command = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$command) {
            $this->pdo->rollBack();
            $this->respond(404, ['success' => false, 'error' => 'Enrollment command was not found.']);
            return;
        }
        if ((int) $command['fingerprint_id'] !== (int) $fingerprintId) {
            $this->pdo->rollBack();
            $this->respond(409, ['success' => false, 'error' => 'Reported fingerprint ID does not match the queued ID.']);
            return;
        }

        $duplicate = $this->pdo->prepare(
            'SELECT student_id
             FROM students
             WHERE fingerprint_id = :fingerprint_id
               AND student_id <> :student_lrn
             LIMIT 1'
        );
        $duplicate->execute([
            ':fingerprint_id' => $fingerprintId,
            ':student_lrn' => $command['student_lrn'],
        ]);
        if ($duplicate->fetchColumn() !== false) {
            $this->pdo->rollBack();
            $this->respond(409, ['success' => false, 'error' => 'Fingerprint ID is already assigned.']);
            return;
        }

        $update = $this->pdo->prepare(
            'UPDATE students SET fingerprint_id = :fingerprint_id
             WHERE student_id = :student_lrn AND is_active = 1'
        );
        $update->execute([
            ':fingerprint_id' => $fingerprintId,
            ':student_lrn' => $command['student_lrn'],
        ]);
        $complete = $this->pdo->prepare(
            'UPDATE fingerprint_device_commands
             SET status = "completed", updated_at = :updated_at
             WHERE id = :id'
        );
        $complete->execute([
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $commandId,
        ]);
        $this->pdo->commit();
        $this->respond(200, ['success' => true, 'message' => 'Fingerprint enrollment saved.']);
    }

    private function reportFailed(array $payload): void
    {
        $commandId = filter_var($payload['command_id'] ?? null, FILTER_VALIDATE_INT);
        if ($commandId === false) {
            $this->respond(400, ['success' => false, 'error' => 'Invalid failed-command report.']);
            return;
        }
        $message = trim((string) ($payload['error'] ?? 'Fingerprint enrollment failed.'));
        $stmt = $this->pdo->prepare(
            'UPDATE fingerprint_device_commands
             SET status = "failed", error_message = :error_message, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            ':error_message' => substr($message, 0, 255),
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $commandId,
        ]);
        $this->respond(200, ['success' => true]);
    }

    private function reportDeleted(array $payload): void
    {
        $commandId = filter_var($payload['command_id'] ?? null, FILTER_VALIDATE_INT);
        $fingerprintId = filter_var($payload['fingerprint_id'] ?? null, FILTER_VALIDATE_INT);
        if ($commandId === false || $fingerprintId === false) {
            $this->respond(400, ['success' => false, 'error' => 'Invalid deletion report.']);
            return;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE fingerprint_device_commands
             SET status = "completed", updated_at = :updated_at
             WHERE id = :id AND command = "DELETE" AND fingerprint_id = :fingerprint_id'
        );
        $stmt->execute([
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $commandId,
            ':fingerprint_id' => $fingerprintId,
        ]);
        $this->respond(200, ['success' => true]);
    }

    private function reportMatch(array $payload): void
    {
        $fingerprintId = filter_var($payload['fingerprint_id'] ?? null, FILTER_VALIDATE_INT);
        if ($fingerprintId === false || $fingerprintId < 1 || $fingerprintId > 127) {
            $this->respond(400, ['success' => false, 'error' => 'Invalid fingerprint ID.']);
            return;
        }

        $studentStmt = $this->pdo->prepare(
            'SELECT student_id, section_id
             FROM students
             WHERE fingerprint_id = :fingerprint_id AND is_active = 1
             LIMIT 1'
        );
        $studentStmt->execute([':fingerprint_id' => $fingerprintId]);
        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
        if (!$student) {
            $this->respond(200, ['success' => false, 'error' => 'Fingerprint is not registered.']);
            return;
        }

        $sessionStmt = $this->pdo->prepare(
            'SELECT id
             FROM manual_attendance_sessions
             WHERE section_id = :section_id
               AND attendance_date = :attendance_date
               AND closed_at IS NULL
             ORDER BY id DESC
             LIMIT 1'
        );
        $sessionStmt->execute([
            ':section_id' => $student['section_id'],
            ':attendance_date' => date('Y-m-d'),
        ]);
        $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);
        if (!$session) {
            $this->respond(200, ['success' => false, 'error' => 'Open an attendance session before scanning.']);
            return;
        }

        $result = $this->teachersModel->recordManualAttendance(
            (int) $session['id'],
            (string) $student['student_id'],
            'Fingerprint'
        );
        $this->respond(200, $result);
    }

    private function respond(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
