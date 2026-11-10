<?php

require_once __DIR__ . '/TeachersController.php';

class AttendanceLogsController extends TeachersController
{
    public function getPageData(array $session, array $query): array
    {
        $authenticationRedirect = $this->requireAuthentication($session);
        if ($authenticationRedirect !== null) {
            header('Location: ' . $authenticationRedirect['redirect']);
            exit();
        }

        $requestedDate = $query['attendance_date'] ?? date('Y-m-d');
        $parsedDate = is_string($requestedDate)
            ? DateTime::createFromFormat('!Y-m-d', $requestedDate)
            : false;
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $requestedDate) {
            $requestedDate = date('Y-m-d');
        }

        $sectionId = (int) ($session['section_id'] ?? 0);
        $attendanceLogs = $this->getManualAttendanceLogs(
            (int) $session['user_id'],
            $sectionId,
            $requestedDate
        );
        $presentCount = count(array_filter(
            $attendanceLogs,
            static fn(array $log): bool => !empty($log['time_in'])
        ));

        return [
            'searchDate' => $requestedDate,
            'attendanceLogs' => $attendanceLogs,
            'presentCount' => $presentCount,
            'absentCount' => max(0, $this->getSectionStudentCount($sectionId) - $presentCount),
        ];
    }
}
