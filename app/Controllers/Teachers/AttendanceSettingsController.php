<?php

require_once __DIR__ . '/TeachersController.php';

class AttendanceSettingsController extends TeachersController
{
    public function getPageData(array &$session): array
    {
        $authenticationRedirect = $this->requireAuthentication($session);
        if ($authenticationRedirect !== null) {
            header('Location: ' . $authenticationRedirect['redirect']);
            exit();
        }

        if (empty($session['csrf_token'])) {
            $session['csrf_token'] = bin2hex(random_bytes(32));
        }

        return [
            'attendanceCodes' => $this->getOrCreateAttendanceCodes((int) $session['user_id']),
            'csrfToken' => $session['csrf_token'],
            'fingerprintStudents' => $this->getFingerprintStudents((int) ($session['section_id'] ?? 0)),
        ];
    }
}
