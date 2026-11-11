<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once 'config/database.php';

// Teachers/Users Controllers
require_once 'app/Controllers/Teachers/AuthController.php';
require_once 'app/Controllers/Teachers/LogoutController.php';
require_once 'app/Controllers/Teachers/TeachersController.php';
require_once 'app/Controllers/Teachers/DashboardController.php';
require_once 'app/Controllers/Teachers/ManualAttendanceController.php';
require_once 'app/Controllers/Teachers/StudentsController.php';
require_once 'app/Controllers/Teachers/AttendanceLogsController.php';

//Admin Controllers
require_once 'app/Controllers/AdminAuthController.php';



$base_url = '';

$page = $_GET['page'] ?? 'login';
$currentPage = $page; // Set the current page for navigation highlighting


// Admin routing through index.php (page=admin_{resource})
if (strpos($page, 'admin_') === 0) {
    $adminPage = substr($page, 6);

    // Public admin pages
    if ($adminPage === 'login') {
        $view = 'app/Views/Admin/admin_login.php';
        include $view;
        exit();
    }
    if ($adminPage === 'signup') {
        $view = 'app/Views/Admin/admin_signup.php';
        include $view;
        exit();
    }
    if ($adminPage === 'authenticate') {
        $controller = new AdminAuthController($pdo);
        $controller->handleLogin($_POST);
        exit();
    }
    if ($adminPage === 'register') {
        $controller = new AdminAuthController($pdo);
        $controller->handleSignup($_POST);
        exit();
    }

    // Protected admin pages - require authentication
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: index.php?page=admin_login');
        exit();
    }

    switch ($adminPage) {
        case 'logout':
            $controller = new AdminAuthController($pdo);
            $controller->handleLogout();
            exit();

        case 'dashboard':
            $view = 'app/Views/Admin/admin_dashboard.php';
            break;

        case 'users':
            $view = 'app/Views/Admin/admin_users.php';
            break;

        case 'activity_log':
            $view = 'app/Views/Admin/admin_activity_log.php';
            break;

        case 'settings':
            $view = 'app/Views/Admin/admin_settings.php';
            break;

        default:
            header('Location: index.php?page=admin_dashboard');
            exit();
    }

    if (isset($view)) {
        include $view;
    }
    exit();
}



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
        $recentAttendanceActivity = $pageData['recentAttendanceActivity'];
        $view = 'app/Views/Teachers/dashboard.php';
        break;

    case 'attendanceLogs':
        $controller = new AttendanceLogsController($pdo);
        $pageData = $controller->getPageData($_SESSION, $_GET);
        $searchDate = $pageData['searchDate'];
        $logDate = $pageData['logDate'];
        $attendanceSummaries = $pageData['attendanceSummaries'];
        $attendanceLogs = $pageData['attendanceLogs'];
        $showLogs = $pageData['showLogs'];
        $view = 'app/Views/Teachers/attendanceLogs.php';
        break;

    case 'students':
        $controller = new StudentsController($pdo);
        $pageData = $controller->getPageData($_SESSION, $_POST, $_SERVER['REQUEST_METHOD']);
        $result = $pageData['result'];
        $masterlist = $pageData['masterlist'];
        $studentCount = $pageData['studentCount'];
        $csrfToken = $pageData['csrfToken'];
        $view = 'app/Views/Teachers/students.php';
        break;

    case 'studentAbsences':
        $controller = new StudentsController($pdo);
        $controller->handleStudentAbsencesRequest($_SESSION, $_GET);
        exit();

    case 'studentAbsenceStatus':
        $controller = new StudentsController($pdo);
        $controller->handleStudentAbsenceStatusRequest($_SESSION, $_POST);
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
        $view = 'app/Views/Teachers/dashboard.php';
        break;
}

include $view;
?>