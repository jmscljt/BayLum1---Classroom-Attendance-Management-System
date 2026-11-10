<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once 'config/database.php';

// Teachers Side
require_once 'app/Controllers/Teachers/AuthController.php';
require_once 'app/Controllers/Teachers/LogoutController.php';
require_once 'app/Controllers/Teachers/TeachersController.php';
require_once 'app/Controllers/Teachers/DashboardController.php';
require_once 'app/Controllers/Teachers/ManualAttendanceController.php';
require_once 'app/Controllers/Teachers/AttendanceLogsController.php';

// Students Info
require_once 'app/Controllers/Teachers/StudentsController.php';

$base_url = '';

$page = $_GET['page'] ?? 'login';
$currentPage = $page;

switch ($page) {

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
        $controller = new DashboardController($pdo);
        $pageData = $controller->getPageData($_SESSION);
        $attendanceCodes = $pageData['attendanceCodes'];
        $totalAdvisoryStudents = $pageData['totalAdvisoryStudents'];
        $presentStudentsAdvisory = $pageData['presentStudentsAdvisory'];
        $absentStudentsAdvisory = $pageData['absentStudentsAdvisory'];
        $totalAssignedStudents = $pageData['totalAssignedStudents'];
        $view = 'app/Views/Teachers/dashboard.php';
        break;

    case 'attendanceLogs':
        $controller = new AttendanceLogsController($pdo);
        $pageData = $controller->getPageData($_SESSION, $_GET);
        $searchDate = $pageData['searchDate'];
        $attendanceLogs = $pageData['attendanceLogs'];
        $presentCount = $pageData['presentCount'];
        $absentCount = $pageData['absentCount'];
        $view = 'app/Views/Teachers/attendanceLogs.php';
        break;

    case 'students':
        $controller = new StudentsController($pdo);
        $pageData = $controller->getPageData($_SESSION, $_POST, $_SERVER['REQUEST_METHOD']);
        $result = $pageData['result'];
        $masterlist = $pageData['masterlist'];
        $studentCount = $pageData['studentCount'];
        $view = 'app/Views/Teachers/students.php';
        break;

    case 'studentAbsences':
        $controller = new StudentsController($pdo);
        $controller->handleStudentAbsencesRequest($_SESSION, $_GET);
        exit();

    case 'manualAttendance':
        $controller = new ManualAttendanceController($pdo);
        $result = $controller->handleRequest($_SESSION, $_POST, $_SERVER['REQUEST_METHOD']);
        if (!empty($result['redirect'])) {
            header('Location: ' . $result['redirect']);
            exit();
        }
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