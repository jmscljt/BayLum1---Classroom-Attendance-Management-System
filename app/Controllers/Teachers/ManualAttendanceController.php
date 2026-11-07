<?php

require_once __DIR__ . '/../../Models/Teachers/TeachersModel.php';

class ManualAttendanceController
{
    private TeachersModel $teachersModel;

    public function __construct(PDO $pdo)
    {
        $this->teachersModel = new TeachersModel($pdo);
    }

    public function process(string $input): array
    {
        $input = trim($input);
        if (preg_match('/^\d{4}$/D', $input) === 1) {
            $currentSessionId = (int) ($_SESSION['manual_attendance_session_id'] ?? 0);
            $result = $this->teachersModel->processAttendanceCode(
                $input,
                $currentSessionId > 0 ? $currentSessionId : null
            );
            if ($result['success']) {
                if ($result['action'] === 'opened') {
                    $_SESSION['manual_attendance_session_id'] = $result['session_id'];
                } elseif (
                    (int) ($_SESSION['manual_attendance_session_id'] ?? 0) === $result['session_id']
                ) {
                    unset($_SESSION['manual_attendance_session_id']);
                }
            }

            return $result;
        }

        $sessionId = (int) ($_SESSION['manual_attendance_session_id'] ?? 0);
        if ($sessionId < 1) {
            return [
                'success' => false,
                'message' => 'Enter the adviser’s 4-digit code to open an attendance session first.',
            ];
        }

        $result = $this->teachersModel->recordManualAttendance($sessionId, $input);
        if (!empty($result['clear_session'])) {
            unset($_SESSION['manual_attendance_session_id']);
        }

        return $result;
    }
}
