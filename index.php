<?php
session_start();
require_once 'config/database.php';

// Teachers Side
require_once 'app/Controllers/Teachers/AuthController.php';
require_once 'app/Controllers/Teachers/TeachersController.php';

$base_url = '';

$page = $_GET['page'] ?? 'signup';

switch ($page) {
    case 'signup':
        $controller = new AuthController($pdo);
        $data = $controller->handleSignupRequest();
        $view = 'app/Views/Teachers/signup.php';
        break;

    case 'login':
    default:
        $view = 'app/Views/Teachers/login.php';
        break;
}

include $view;
?>