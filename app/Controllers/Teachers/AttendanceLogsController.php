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

        $requestedDate = $query['attendance_date'] ?? null;
        $parsedDate = is_string($requestedDate) && $requestedDate !== ''
            ? DateTime::createFromFormat('!Y-m-d', $requestedDate)
            : false;
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $requestedDate) {
            $requestedDate = null;
        }

        $sectionId = (int) ($session['section_id'] ?? 0);
        $teacherId = (int) $session['user_id'];
        $showLogs = ($query['show_logs'] ?? '') === '1';
        $attendanceSummaries = $this->getManualAttendanceSummaries(
            $teacherId,
            $sectionId,
            $showLogs ? null : $requestedDate
        );
        $totalStudents = $this->getSectionStudentCount($sectionId);
        foreach ($attendanceSummaries as &$summary) {
            $summary['present_count'] = (int) $summary['present_count'];
            $summary['absent_count'] = max(0, $totalStudents - $summary['present_count']);
        }
        unset($summary);

        $attendanceLogs = $requestedDate === null
            ? []
            : $this->getManualAttendanceLogs($teacherId, $sectionId, $requestedDate);

        return [
            'searchDate' => $requestedDate,
            'attendanceSummaries' => $attendanceSummaries,
            'attendanceLogs' => $attendanceLogs,
            'showLogs' => $showLogs,
        ];
    }
}
