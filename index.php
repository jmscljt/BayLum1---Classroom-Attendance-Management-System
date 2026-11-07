<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once 'config/database.php';

// Teachers Side
require_once 'app/Controllers/Teachers/AuthController.php';
require_once 'app/Controllers/Teachers/LogoutController.php';
require_once 'app/Controllers/Teachers/TeachersController.php';
require_once 'app/Controllers/Teachers/ManualAttendanceController.php';

// Students Info
require_once 'app/Controllers/Teachers/StudentsController.php';

$base_url = '';

$page = $_GET['page'] ?? 'login';
$currentPage = $page;

switch ($page) {

    case 'manualAttendance':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=login');
            exit();
        }

        try {
            $controller = new ManualAttendanceController($pdo);
            $result = $controller->process($_POST['manual_attendance'] ?? '');
        } catch (PDOException | RuntimeException $e) {
            error_log('Manual attendance error: ' . $e->getMessage());
            $result = [
                'success' => false,
                'message' => 'Attendance could not be processed. Check that the manual-attendance database setup has been applied.',
            ];
        }

        $_SESSION['auth_message'] = $result['message'];
        $_SESSION['auth_message_type'] = $result['success'] ? 'success' : 'error';
        header('Location: index.php?page=login');
        exit();

    case 'signup':
        $controller = new AuthController($pdo);
        $data = $controller->handleSignupRequest();
        $view = 'app/Views/Teachers/signup.php';
        break;

    case 'login':
        $controller = new AuthController($pdo);
        $data = $controller->handleLoginRequest();
        $view = 'app/Views/Teachers/login.php';
        break;

    case 'logout':
        $controller = new LogoutController();
        $controller->logoutAndRedirect();
        exit();

    case 'dashboard':
        if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit();
        }
        $teachersModel = new TeachersModel($pdo);
        $teacherId = (int) $_SESSION['user_id'];
        $sectionId = (int) ($_SESSION['section_id'] ?? 0);
        $attendanceCodes = $teachersModel->getOrCreateAttendanceCodes($teacherId);
        $dashboardMetrics = $teachersModel->getDashboardAttendanceMetrics(
            $teacherId,
            $sectionId,
            date('Y-m-d')
        );
        $totalAdvisoryStudents = $dashboardMetrics['totalAdvisoryStudents'];
        $presentStudentsAdvisory = $dashboardMetrics['presentStudentsAdvisory'];
        $absentStudentsAdvisory = $dashboardMetrics['absentStudentsAdvisory'];
        $totalAssignedStudents = $dashboardMetrics['totalAssignedStudents'];
        $view = 'app/Views/Teachers/dashboard.php';
        break;

    case 'attendanceLogs':
        if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit();
        }
        $searchDate = $_GET['attendance_date'] ?? date('Y-m-d');
        $parsedDate = is_string($searchDate)
            ? DateTime::createFromFormat('!Y-m-d', $searchDate)
            : false;
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $searchDate) {
            $searchDate = date('Y-m-d');
        }
        $teachersModel = new TeachersModel($pdo);
        $sectionId = (int) ($_SESSION['section_id'] ?? 0);
        $attendanceLogs = $teachersModel->getManualAttendanceLogs(
            (int) $_SESSION['user_id'],
            $sectionId,
            $searchDate
        );
        $presentCount = count(array_filter(
            $attendanceLogs,
            static fn(array $log): bool => !empty($log['time_in'])
        ));
        $studentCount = $teachersModel->getSectionStudentCount($sectionId);
        $absentCount = max(0, $studentCount - $presentCount);
        $view = 'app/Views/Teachers/attendanceLogs.php';
        break;

    case 'students':
        $controller = new StudentsController($pdo);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Automatically attach the section_id stored in the teacher's login session
            $_POST['section_id'] = $_SESSION['section_id'] ?? null;
            
            $_SESSION['student_form_result'] = $controller->addStudent($_POST);
            header('Location: index.php?page=students');
            exit();
        }

        $result = $_SESSION['student_form_result'] ?? null;
        unset($_SESSION['student_form_result']);
        
        $sectionId = (int)($_SESSION['section_id'] ?? 0);
        $masterlist = $controller->getStudentsMasterlist($sectionId);
        $studentCount = count($masterlist);
        $view = 'app/Views/Teachers/students.php';
        break;

    case 'otherSectionHandled':
        $view = 'app/Views/Teachers/otherSectionHandled.php';
        break;
        
    default:
        $view = 'app/Views/Teachers/login.php';
        break;
}

include $view;
?>