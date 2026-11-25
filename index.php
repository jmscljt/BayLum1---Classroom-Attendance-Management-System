<?php
session_start();
date_default_timezone_set('Asia/Manila');
$projectRoot = __DIR__;
require_once $projectRoot . '/config/database.php';

// Teachers/Users Controllers
require_once $projectRoot . '/app/Controllers/Teachers/AuthController.php';
require_once $projectRoot . '/app/Controllers/Teachers/LogoutController.php';
require_once $projectRoot . '/app/Controllers/Teachers/TeachersController.php';
require_once $projectRoot . '/app/Controllers/Teachers/DashboardController.php';
require_once $projectRoot . '/app/Controllers/Teachers/ManualAttendanceController.php';
require_once $projectRoot . '/app/Controllers/Teachers/StudentsController.php';
require_once $projectRoot . '/app/Controllers/Teachers/AttendanceLogsController.php';
require_once $projectRoot . '/app/Controllers/Teachers/FingerprintAttendanceController.php';
require_once $projectRoot . '/app/Controllers/Teachers/AttendanceSettingsController.php';
require_once $projectRoot . '/app/Controllers/Teachers/AccountSettingsController.php';

//Admin Controllers
require_once $projectRoot . '/app/Controllers/Admin/AdminAuthController.php';
require_once $projectRoot . '/app/Controllers/Admin/AdminSectionsController.php';
require_once $projectRoot . '/app/Controllers/Admin/AdminStudentsController.php';
require_once $projectRoot . '/app/Controllers/Admin/AdminTeachersController.php';



$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$base_url = $scriptDirectory === '/' || $scriptDirectory === '.'
    ? ''
    : rtrim($scriptDirectory, '/');

$page = $_GET['page'] ?? 'login';
$currentPage = $page; // Set the current page for navigation highlighting


// Admin routing through index.php (page=admin_{resource})
if (strpos($page, 'admin_') === 0) {
    $adminPage = substr($page, 6);

    // Public admin pages
    if ($adminPage === 'login') {
        $view = $projectRoot . '/app/Views/Admin/admin_login.php';
        include $view;
        exit();
    }
    if ($adminPage === 'signup') {
        $view = $projectRoot . '/app/Views/Admin/admin_signup.php';
        include $view;
        exit();
    }
    if ($adminPage === 'authenticate') {
        $controller = new AdminAuthController(new AdminModel($pdo));
        $controller->handleLogin($_POST);
        exit();
    }
    if ($adminPage === 'register') {
        $controller = new AdminAuthController(new AdminModel($pdo));
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
            $controller = new AdminAuthController(new AdminModel($pdo));
            $controller->handleLogout();
            exit();

        case 'dashboard':
            $view = $projectRoot . '/app/Views/Admin/admin_dashboard.php';
            break;

        case 'manage_sections':
            $controller = new AdminSectionsController(new AdminModel($pdo));
            $pageData = $controller->getPageData(
                $_SESSION,
                $_POST,
                $_SERVER['REQUEST_METHOD']
            );
            $sections = $pageData['sections'];
            $grades = $pageData['grades'];
            $teachers = $pageData['teachers'];
            $csrfToken = $pageData['csrfToken'];
            $sectionResult = $pageData['result'];
            $view = $projectRoot . '/app/Views/Admin/admin_manage_sections.php';
            break;

        case 'manage_teachers':
            $controller = new AdminTeachersController(new AdminModel($pdo));
            $pageData = $controller->getPageData(
                $_SESSION,
                $_POST,
                $_SERVER['REQUEST_METHOD']
            );
            $teachers = $pageData['teachers'];
            $csrfToken = $pageData['csrfToken'];
            $teacherResult = $pageData['result'];
            $view = $projectRoot . '/app/Views/Admin/admin_manage_teachers.php';
            break;

        case 'manage_students':
            $controller = new AdminStudentsController($pdo);
            $pageData = $controller->getPageData(
                $_SESSION,
                $_POST,
                $_SERVER['REQUEST_METHOD']
            );
            $sections = $pageData['sections'];
            $students = $pageData['students'];
            $csrfToken = $pageData['csrfToken'];
            $studentResult = $pageData['result'];
            $view = $projectRoot . '/app/Views/Admin/admin_manage_students.php';
            break;

        case 'activity_logs':
            $view = $projectRoot . '/app/Views/Admin/admin_activity_logs.php';
            break;

        case 'teacher_logs':
            $view = $projectRoot . '/app/Views/Admin/teacher_activity_logs.php';
            break;

        case 'settings':
            $view = $projectRoot . '/app/Views/Admin/admin_settings.php';
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



if (
    !in_array($page, ['login', 'signup', 'logout'], true)
    && !empty($_SESSION['user_logged_in'])
    && !empty($_SESSION['user_id'])
) {
    $teacherStatusStatement = $pdo->prepare(
        'SELECT is_active FROM teachers WHERE id = :teacher_id LIMIT 1'
    );
    $teacherStatusStatement->execute(['teacher_id' => (int) $_SESSION['user_id']]);
    $teacherIsActive = $teacherStatusStatement->fetchColumn();

    if ($teacherIsActive === false || !(int) $teacherIsActive) {
        unset(
            $_SESSION['user_logged_in'],
            $_SESSION['user_id'],
            $_SESSION['user_name'],
            $_SESSION['user_unique_id'],
            $_SESSION['section_id'],
            $_SESSION['assigned_grade_level'],
            $_SESSION['advisory_section']
        );
        $_SESSION['auth_message'] = 'Your teacher account is inactive. Please contact the school administrator.';
        $_SESSION['auth_message_type'] = 'error';
        header('Location: index.php?page=login');
        exit();
    }
}

switch ($page) {

    case 'signup':
        $controller = new AuthController($pdo);
        $data = $controller->handleSignupRequest();
        $view = $projectRoot . '/app/Views/Teachers/signup.php';
        break;

    case 'login':
        $controller = new AuthController($pdo);
        $data = $controller->handleLoginRequest();
        $view = $projectRoot . '/app/Views/Teachers/Login.php';
        break;

    case 'logout':
        $controller = new LogoutController();
        $controller->logoutAndRedirect();
        exit();

    case 'dashboard':
        $controller = new DashboardController($pdo);
        $pageData = $controller->getPageData($_SESSION);
        $totalStudents = $pageData['totalStudents'];
        $presentStudents = $pageData['presentStudents'];
        $absentStudents = $pageData['absentStudents'];
        $recentAttendanceActivity = $pageData['recentAttendanceActivity'];
        $view = $projectRoot . '/app/Views/Teachers/dashboard.php';
        break;

    case 'attendanceLogs':
        $controller = new AttendanceLogsController($pdo);
        $pageData = $controller->getPageData($_SESSION, $_GET);
        $searchDate = $pageData['searchDate'];
        $logDate = $pageData['logDate'];
        $attendanceSummaries = $pageData['attendanceSummaries'];
        $attendanceLogs = $pageData['attendanceLogs'];
        $showLogs = $pageData['showLogs'];
        $view = $projectRoot . '/app/Views/Teachers/attendanceRecords.php';
        break;

    case 'attendanceSettings':
        $controller = new AttendanceSettingsController($pdo);
        $pageData = $controller->getPageData($_SESSION);
        $attendanceCodes = $pageData['attendanceCodes'];
        $csrfToken = $pageData['csrfToken'];
        $fingerprintStudents = $pageData['fingerprintStudents'];
        $view = $projectRoot . '/app/Views/Teachers/attendanceSettings.php';
        break;

    case 'students':
        $controller = new StudentsController($pdo);
        $pageData = $controller->getPageData($_SESSION, $_POST, $_SERVER['REQUEST_METHOD']);
        $result = $pageData['result'];
        $masterlist = $pageData['masterlist'];
        $inactiveStudents = $pageData['inactiveStudents'];
        $studentCount = $pageData['studentCount'];
        $csrfToken = $pageData['csrfToken'];
        $view = $projectRoot . '/app/Views/Teachers/students.php';
        break;

    case 'accountSettings':
        if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit();
        }
        $controller = new AccountSettingsController($pdo);
        $pageData = $controller->getPageData(
            $_SESSION,
            $_POST,
            $_SERVER['REQUEST_METHOD']
        );
        $account = $pageData['account'];
        $sections = $pageData['sections'];
        $csrfToken = $pageData['csrfToken'];
        $accountResult = $pageData['result'];
        $view = $projectRoot . '/app/Views/Teachers/accountSettings.php';
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

    case 'fingerprintAttendance':
        $controller = new FingerprintAttendanceController($pdo);
        $controller->handleRequest(
            $_SESSION,
            $_SERVER['REQUEST_METHOD'],
            file_get_contents('php://input'),
            $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''
        );
        exit();

    default:
        $view = $projectRoot . '/app/Views/Teachers/dashboard.php';
        break;
}

include $view;
?>