<?php

require_once __DIR__ . '/../../Models/Admin/AdminModel.php';

class AdminAuthController
{
    public function __construct(private AdminModel $adminModel)
    {
    }

    public function handleLogin(array $post): void
    {
        $identifier = trim($post['admin_id'] ?? '');
        $password = $post['admin_password'] ?? '';

        $admin = $this->adminModel->findAdminByIdentifier($identifier);

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $this->redirectWithMessage('admin_login', 'Invalid username/email or password.', 'error');
        }

        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = (int) $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['full_name'];

        header('Location: index.php?page=admin_dashboard');
        exit();
    }

    public function handleSignup(array $post): void
    {
        $fullName = trim($post['full_name'] ?? '');
        $username = trim($post['username'] ?? '');
        $email = trim($post['email'] ?? '');
        $password = $post['password'] ?? '';
        $confirmPassword = $post['confirm_password'] ?? '';

        if ($fullName === '' || $username === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->redirectWithMessage('admin_signup', 'Enter a name, username, and valid email address.', 'error');
        }

        if (strlen($password) < 8) {
            $this->redirectWithMessage('admin_signup', 'Password must be at least 8 characters long.', 'error');
        }

        if ($password !== $confirmPassword) {
            $this->redirectWithMessage('admin_signup', 'Passwords do not match.', 'error');
        }

        if ($this->adminModel->countAdmins() > 0) {
            $this->redirectWithMessage('admin_login', 'An administrator account already exists. Please log in.', 'error');
        }

        try {
            $adminId = $this->adminModel->createAdmin(
                $fullName,
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            );
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $this->redirectWithMessage('admin_signup', 'That username or email is already in use.', 'error');
            }

            throw $exception;
        }

        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $adminId;
        $_SESSION['admin_name'] = $fullName;

        header('Location: index.php?page=admin_dashboard');
        exit();
    }

    public function handleLogout(): void
    {
        unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_name']);
        header('Location: index.php?page=admin_login');
        exit();
    }

    private function redirectWithMessage(string $page, string $message, string $type): never
    {
        $_SESSION['auth_message'] = $message;
        $_SESSION['auth_message_type'] = $type;
        header('Location: index.php?page=' . $page);
        exit();
    }
}