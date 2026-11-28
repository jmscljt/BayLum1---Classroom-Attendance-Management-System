<?php

require_once __DIR__ . '/../../Models/Teachers/TeachersModel.php';

class ManualAttendanceController
{
    private TeachersModel $teachersModel;

    public function __construct(PDO $pdo)
    {
        $this->teachersModel = new TeachersModel($pdo);
    }

    public function handleRequest(array &$session, array $post, string $requestMethod): array
    {
        $redirectPage = !empty($session['user_logged_in']) && !empty($session['user_id'])
            ? 'dashboard'
            : 'login';

        if ($requestMethod !== 'POST') {
            return ['redirect' => 'index.php?page=' . $redirectPage];
        }

        try {
            $result = $this->process($post['manual_attendance'] ?? '', $session);
        } catch (PDOException | RuntimeException $e) {
            $sqlState = $e instanceof PDOException ? (string) $e->getCode() : 'runtime';
            error_log(
                'Manual attendance error: '
                . $e->getMessage()
                . ' (SQLSTATE/class: ' . $sqlState . ')'
            );
            $result = [
                'success' => false,
                'message' => 'Attendance could not be processed due to a database error. Please try again.',
            ];
        }

        $session['auth_message'] = $result['message'];
        $session['auth_message_type'] = $result['success'] ? 'success' : 'error';

        return ['redirect' => 'index.php?page=' . $redirectPage];
    }

    public function handleIdleCloseRequest(
        array &$session,
        string $requestMethod,
        string $csrfToken
    ): void {
        header('Content-Type: application/json; charset=utf-8');

        if ($requestMethod !== 'POST') {
            $this->respond(405, ['error' => 'POST is required.']);
            return;
        }

        if (empty($session['user_logged_in']) || empty($session['user_id'])) {
            $this->respond(401, ['error' => 'Please sign in as a teacher.']);
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

        $attendanceSessionId = (int) ($session['manual_attendance_session_id'] ?? 0);
        if ($attendanceSessionId < 1) {
            $this->respond(409, ['error' => 'There is no active attendance session to close.']);
            return;
        }

        try {
            $closed = $this->teachersModel->closeManualAttendanceSession(
                $attendanceSessionId,
                (int) $session['user_id']
            );
            unset($session['manual_attendance_session_id']);
            $this->clearAttendanceCaches($session);
            $this->respond(200, ['success' => true, 'closed' => $closed]);
        } catch (PDOException | RuntimeException $e) {
            error_log('Idle attendance close error: ' . $e->getMessage());
            $this->respond(500, ['error' => 'Unable to close attendance right now.']);
        }
    }

    public function process(string $input, array &$session): array
    {
        $input = trim($input);
        if (preg_match('/^\d{4}$/D', $input) === 1) {
            $currentSessionId = (int) ($session['manual_attendance_session_id'] ?? 0);
            $result = $this->teachersModel->processAttendanceCode(
                $input,
                $currentSessionId > 0 ? $currentSessionId : null
            );
            if ($result['success']) {
                if ($result['action'] === 'opened') {
                    $session['manual_attendance_session_id'] = $result['session_id'];
                } elseif (
                    (int) ($session['manual_attendance_session_id'] ?? 0) === $result['session_id']
                ) {
                    unset($session['manual_attendance_session_id']);
                }
                $this->clearAttendanceCaches($session);
            }

            return $result;
        }

        $sessionId = (int) ($session['manual_attendance_session_id'] ?? 0);
        if ($sessionId < 1) {
            return [
                'success' => false,
                'message' => 'Enter the adviser’s 4-digit code to open an attendance session first.',
            ];
        }

        $result = $this->teachersModel->recordManualAttendance($sessionId, $input);
        if (!empty($result['clear_session'])) {
            unset($session['manual_attendance_session_id']);
        }
        if (!empty($result['success'])) {
            $this->clearAttendanceCaches($session);
        }

        return $result;
    }

    private function clearAttendanceCaches(array &$session): void
    {
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

    private function respond(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
