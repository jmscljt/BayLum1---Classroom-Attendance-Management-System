<?php

require_once __DIR__ . '/TeachersController.php';

class DashboardController extends TeachersController
{
    public function getPageData(array $session): array
    {
        $authenticationRedirect = $this->requireAuthentication($session);
        if ($authenticationRedirect !== null) {
            header('Location: ' . $authenticationRedirect['redirect']);
            exit();
        }

        $teacherId = (int) $session['user_id'];
        $sectionId = (int) ($session['section_id'] ?? 0);
        $dashboardMetrics = $this->getDashboardAttendanceMetrics(
            $teacherId,
            $sectionId,
            date('Y-m-d')
        );
        $recentAttendanceActivity = $this->getRecentAttendanceActivity($teacherId, $sectionId);

        return [
            'attendanceCodes' => $this->getOrCreateAttendanceCodes($teacherId),
            'totalAdvisoryStudents' => $dashboardMetrics['totalAdvisoryStudents'],
            'presentStudentsAdvisory' => $dashboardMetrics['presentStudentsAdvisory'],
            'absentStudentsAdvisory' => $dashboardMetrics['absentStudentsAdvisory'],
            'totalAssignedStudents' => $dashboardMetrics['totalAssignedStudents'],
            'recentAttendanceActivity' => $recentAttendanceActivity,
        ];
    }
}