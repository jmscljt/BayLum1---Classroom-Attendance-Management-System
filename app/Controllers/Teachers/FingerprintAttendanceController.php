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
        array &$session,
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
            || !in_array($action, ['attendance', 'check_enrollment', 'save_enrollment'], true)
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
            if ($action === 'attendance') {
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

            if (!$result['success']) {
                $this->respond(422, ['error' => $result['message']]);
                return;
            }

            if ($action === 'attendance') {
                foreach (array_keys($session) as $key) {
                    if (
                        str_starts_with($key, 'attendance_summaries_')
                        || str_starts_with($key, 'attendance_logs_')
                        || str_starts_with($key, 'attendance_section_student_count_')
                    ) {
                        unset($session[$key]);
                    }
                }
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

    private function respond(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
