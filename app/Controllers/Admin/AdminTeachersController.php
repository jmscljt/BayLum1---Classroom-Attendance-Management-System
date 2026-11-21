<?php

require_once __DIR__ . '/../../Models/Admin/AdminModel.php';

class AdminTeachersController
{
    public function __construct(private AdminModel $adminModel)
    {
    }

    public function getPageData(array &$session, array $post, string $requestMethod): array
    {
        if (empty($session['csrf_token'])) {
            $session['csrf_token'] = bin2hex(random_bytes(32));
        }

        if ($requestMethod === 'POST') {
            $submittedToken = $post['csrf_token'] ?? '';
            $sessionToken = $session['csrf_token'] ?? '';
            if (
                !is_string($submittedToken)
                || !is_string($sessionToken)
                || $sessionToken === ''
                || !hash_equals($sessionToken, $submittedToken)
            ) {
                $session['teacher_form_result'] = [
                    'success' => false,
                    'message' => 'Your session expired. Refresh the page and try again.',
                ];
            } else {
                $action = $post['action'] ?? '';
                if ($action === 'update_teacher') {
                    $session['teacher_form_result'] = $this->updateTeacher($post);
                } elseif ($action === 'remove_teacher') {
                    $session['teacher_form_result'] = $this->removeTeacher($post);
                } elseif ($action === 'set_teacher_status') {
                    $session['teacher_form_result'] = $this->setTeacherStatus($post);
                } else {
                    $session['teacher_form_result'] = [
                        'success' => false,
                        'message' => 'Unknown teacher action.',
                    ];
                }
            }

            header('Location: index.php?page=admin_manage_teachers');
            exit();
        }

        $result = $session['teacher_form_result'] ?? null;
        unset($session['teacher_form_result']);

        return [
            'teachers' => $this->adminModel->getTeacherAccounts(),
            'csrfToken' => $session['csrf_token'],
            'result' => $result,
        ];
    }

    private function updateTeacher(array $post): array
    {
        $teacherId = filter_var($post['teacher_id'] ?? null, FILTER_VALIDATE_INT);
        if ($teacherId === false || $teacherId < 1) {
            return ['success' => false, 'message' => 'Please select a valid teacher account.'];
        }

        $validated = $this->validateTeacherInput($post);
        if (isset($validated['success'])) {
            return $validated;
        }

        $password = trim((string) ($post['teacher_password'] ?? ''));
        if ($password !== '' && strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        return $this->adminModel->updateTeacherAccount(
            (int) $teacherId,
            $validated['full_name'],
            $validated['first_name'],
            $validated['last_name'],
            $validated['email'],
            $password === '' ? null : password_hash($password, PASSWORD_DEFAULT)
        );
    }

    private function removeTeacher(array $post): array
    {
        $teacherId = filter_var($post['teacher_id'] ?? null, FILTER_VALIDATE_INT);
        if ($teacherId === false || $teacherId < 1) {
            return ['success' => false, 'message' => 'Please select a valid teacher account.'];
        }

        return $this->adminModel->removeTeacherAccount((int) $teacherId);
    }

    private function setTeacherStatus(array $post): array
    {
        $teacherId = filter_var($post['teacher_id'] ?? null, FILTER_VALIDATE_INT);
        $submittedStatus = $post['is_active'] ?? null;
        if ($teacherId === false || $teacherId < 1) {
            return ['success' => false, 'message' => 'Please select a valid teacher account.'];
        }
        if (!in_array($submittedStatus, ['0', '1', 0, 1], true)) {
            return ['success' => false, 'message' => 'Please select a valid account status.'];
        }

        return $this->adminModel->setTeacherAccountStatus(
            (int) $teacherId,
            (int) $submittedStatus === 1
        );
    }

    private function validateTeacherInput(array $post): array
    {
        $firstName = trim((string) ($post['teacher_first_name'] ?? ''));
        $lastName = trim((string) ($post['teacher_last_name'] ?? ''));
        $email = trim((string) ($post['teacher_email'] ?? ''));

        if ($firstName === '' || strlen($firstName) > 50) {
            return ['success' => false, 'message' => 'Enter a first name of up to 50 characters.'];
        }
        if ($lastName === '' || strlen($lastName) > 50) {
            return ['success' => false, 'message' => 'Enter a last name of up to 50 characters.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            return ['success' => false, 'message' => 'Enter a valid email address.'];
        }

        $fullName = trim($firstName . ' ' . $lastName);

        return [
            'full_name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ];
    }
}
