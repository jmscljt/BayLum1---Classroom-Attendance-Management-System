<?php

require_once __DIR__ . '/TeachersController.php';

class DashboardController extends TeachersController
{
    public function getPageData(array &$session): array
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

        $analyticsCacheKey = 'dashboard_analytics_' . $sectionId . '_' . date('Y-m-d');
        $analyticsCache = $session[$analyticsCacheKey] ?? null;
        $analyticsCacheTime = (int) ($session[$analyticsCacheKey . '_time'] ?? 0);
        if (
            !is_array($analyticsCache)
            || time() - $analyticsCacheTime >= 60
        ) {
            $analytics = $this->getMonthlyAttendanceAnalytics($sectionId);
            $session[$analyticsCacheKey] = $analytics;
            $session[$analyticsCacheKey . '_time'] = time();
        } else {
            $analytics = $analyticsCache;
        }

        $recentAttendanceActivity = $this->getRecentAttendanceActivity($teacherId, $sectionId);

        return [
            'totalStudents' => $dashboardMetrics['totalStudents'],
            'presentStudents' => $dashboardMetrics['presentStudents'],
            'absentStudents' => $dashboardMetrics['absentStudents'],
            'analytics' => $analytics,
            'recentAttendanceActivity' => $recentAttendanceActivity,
        ];
    }
}