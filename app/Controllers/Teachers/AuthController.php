<?php
// app/Controllers/Teachers/AuthController.php
require_once __DIR__ . '/TeachersController.php';

    class AuthController extends TeachersController{
        public function handleSignupRequest(): array
        {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $role               = $_POST['role'] ?? '';
                $uniqueId           = $_POST['unique_id'] ?? '';
                $firstName          = $_POST['first_name'] ?? '';
                $lastName           = $_POST['last_name'] ?? '';
                $email              = $_POST['email'] ?? '';
                $password           = $_POST['password'] ?? '';
                $confirmPassword    = $_POST['confirm_password'] ?? '';
                $assignedGradeLevel = $_POST['assigned_grade_level'] ?? $_POST['grade_level'] ?? '';
                $advisorySection    = $_POST['advisory_section'] ?? $_POST['section'] ?? '';

                // Support both first_name/last_name (new) and combined name (old) fields
                $firstName = $_POST['first_name'] ?? '';
                $lastName  = $_POST['last_name'] ?? '';

                // Fallback: if first_name is empty but 'full_name' is provided, try to split it
                if (empty($firstName) && !empty($_POST['full_name'])) {
                    $nameParts = explode(' ', trim($_POST['full_name']), 2);
                    $firstName = $nameParts[0] ?? '';
                    $lastName  = $nameParts[1] ?? '';
                }

                $result = $this->handleSignup(
                    $uniqueId,
                    $role,
                    $firstName,
                    $lastName,
                    $email,
                    $password,
                    $confirmPassword,
                    $assignedGradeLevel,
                    $advisorySection
                );

                // Checking if success for auto login after signup
                if ($result['success']) {
                    $teacherStmt = $this->pdo->prepare("SELECT * FROM teachers WHERE id = ?");
                    $teacherStmt->execute([$result['insert_id']]);
                    $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);

                    if ($teacher) {
                        $_SESSION['user_logged_in'] = true;
                        $_SESSION['user_id'] = $teacher['id'];
                        $_SESSION['user_name'] = $teacher['full_name'];
                        $_SESSION['temp_user_id_for_display'] = $result['user_id'];

                        $_SESSION['auth_message'] = "Account successfully created! Please log in.";
                        $_SESSION['auth_message_type'] = "success";

                        header('Location: index.php?page=login');
                        exit();
                    }
                }

                // If it fails, save error and redirect back to signup form (not login!)
                $_SESSION['auth_message'] = $result['message'] ?? 'Unable to create account.';
                $_SESSION['auth_message_type'] = $result['success'] ? 'success' : 'error';
                header('Location: index.php?page=signup');
                exit();
            }

            $message = $_SESSION['auth_message'] ?? '';
            $message_type = $_SESSION['auth_message_type'] ?? '';
            unset($_SESSION['auth_message']);
            unset($_SESSION['auth_message_type']);

            return [
                'message' => $message,
                'message_type' => $message_type,
            ];
        }



        public function handleLoginRequest(): array
        {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $this->handleLogin($_POST['user_id'] ?? '', $_POST['user_password'] ?? '');

                if ($result['success']) {
                    $user = $result['user'];
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];

                    header('Location: index.php?page=dashboard');
                    exit();
                }

                $_SESSION['auth_message'] = $result['message'] ?? 'Invalid Unique ID/Username or Password.';
                $_SESSION['auth_message_type'] = 'error';
                header('Location: index.php?page=login');
                exit();
            }

            $message = $_SESSION['auth_message'] ?? '';
            $message_type = $_SESSION['auth_message_type'] ?? '';
            unset($_SESSION['auth_message']);
            unset($_SESSION['auth_message_type']);

            return [
                'message' => $message,
                'message_type' => $message_type,
            ];
        }
    }
?>