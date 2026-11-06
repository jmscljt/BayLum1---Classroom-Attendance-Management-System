<?php
session_start();
require_once 'config/database.php';

// Teachers Side
require_once 'app/Controllers/Teachers/AuthController.php';
require_once 'app/Controllers/Teachers/LogoutController.php';
require_once 'app/Controllers/Teachers/TeachersController.php';

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
        $view = 'app/Views/Teachers/dashboard.php';
        break;

    case 'attendanceLogs':
        $view = 'app/Views/Teachers/attendanceLogs.php';
        break;

    case 'students':
        $controller = new StudentsController($pdo);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Automatically attach the section_id stored in the teacher's login session
            $_POST['section_id'] = $_SESSION['section_id'] ?? null;
            
            $result = $controller->addStudent($_POST);
        }
        
        $masterlist = $controller->getStudentsMasterlist();
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