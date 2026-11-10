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
        if ($requestMethod !== 'POST') {
            return ['redirect' => 'index.php?page=login'];
        }

        try {
            $result = $this->process($post['manual_attendance'] ?? '', $session);
        } catch (PDOException | RuntimeException $e) {
            error_log('Manual attendance error: ' . $e->getMessage());
            $result = [
                'success' => false,
                'message' => 'Attendance could not be processed. Check that the manual-attendance database setup has been applied.',
            ];
        }

        $session['auth_message'] = $result['message'];
        $session['auth_message_type'] = $result['success'] ? 'success' : 'error';

        return ['redirect' => 'index.php?page=login'];
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

        return $result;
    }
}
