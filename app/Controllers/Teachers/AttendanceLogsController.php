<?php

require_once __DIR__ . '/TeachersController.php';

class AttendanceLogsController extends TeachersController
{
    public function getPageData(array &$session, array $query): array
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
        $requestedLogDate = $query['log_date'] ?? $requestedDate;
        $parsedLogDate = is_string($requestedLogDate) && $requestedLogDate !== ''
            ? DateTime::createFromFormat('!Y-m-d', $requestedLogDate)
            : false;
        if (!$parsedLogDate || $parsedLogDate->format('Y-m-d') !== $requestedLogDate) {
            $requestedLogDate = null;
        }

        $summaryCacheKey = 'attendance_summaries_'
            . $teacherId . '_'
            . $sectionId . '_'
            . ($requestedDate ?? 'all');
        $summaryCacheTimeKey = $summaryCacheKey . '_time';
        $attendanceSummaries = $session[$summaryCacheKey] ?? null;
        $attendanceSummariesAge = time() - (int) ($session[$summaryCacheTimeKey] ?? 0);

        if (!is_array($attendanceSummaries) || $attendanceSummariesAge >= 30) {
            $attendanceSummaries = $this->getManualAttendanceSummaries(
                $teacherId,
                $sectionId,
                $requestedDate
            );
            $session[$summaryCacheKey] = $attendanceSummaries;
            $session[$summaryCacheTimeKey] = time();
        }

        $today = date('Y-m-d');
        if (
            ($requestedDate === null || $requestedDate === $today)
            && !array_filter(
                $attendanceSummaries,
                static fn(array $summary): bool => $summary['attendance_date'] === $today
            )
        ) {
            array_unshift($attendanceSummaries, [
                'attendance_date' => $today,
                'present_count' => 0,
            ]);
        }

        $studentCountKey = 'attendance_section_student_count_' . $sectionId;
        $studentCountTimeKey = $studentCountKey . '_time';
        $totalStudents = (int) ($session[$studentCountKey] ?? 0);
        $studentCountAge = time() - (int) ($session[$studentCountTimeKey] ?? 0);
        if ($totalStudents < 1 || $studentCountAge >= 60) {
            $totalStudents = $this->getSectionStudentCount($sectionId);
            $session[$studentCountKey] = $totalStudents;
            $session[$studentCountTimeKey] = time();
        }

        foreach ($attendanceSummaries as &$summary) {
            $summary['present_count'] = (int) $summary['present_count'];
            $summary['absent_count'] = max(0, $totalStudents - $summary['present_count']);
        }
        unset($summary);

        $attendanceLogs = !$showLogs && $requestedDate === null
            ? []
            : ($requestedLogDate === null
                ? []
                : $this->getCachedAttendanceLogs(
                    $session,
                    $teacherId,
                    $sectionId,
                    $requestedLogDate
                ));

        return [
            'searchDate' => $requestedDate,
            'logDate' => $requestedLogDate,
            'attendanceSummaries' => $attendanceSummaries,
            'attendanceLogs' => $attendanceLogs,
            'showLogs' => $showLogs,
        ];
    }

    private function getCachedAttendanceLogs(
        array &$session,
        int $teacherId,
        int $sectionId,
        string $date
    ): array {
        $cacheKey = 'attendance_logs_' . $teacherId . '_' . $sectionId . '_' . $date;
        $timeKey = $cacheKey . '_time';
        $logs = $session[$cacheKey] ?? null;

        if (!is_array($logs) || time() - (int) ($session[$timeKey] ?? 0) >= 30) {
            $logs = $this->getManualAttendanceLogs($teacherId, $sectionId, $date);
            $session[$cacheKey] = $logs;
            $session[$timeKey] = time();
        }

        return $logs;
    }
}
