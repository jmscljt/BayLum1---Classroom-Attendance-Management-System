<?php

require_once __DIR__ . '/../../Models/Admin/AdminModel.php';

class AdminStudentsController
{
    private AdminModel $adminModel;

    public function __construct(PDO $pdo)
    {
        $this->adminModel = new AdminModel($pdo);
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
                $session['student_form_result'] = [
                    'success' => false,
                    'message' => 'Your session expired. Refresh the page and try again.',
                ];
            } else {
                $action = $post['action'] ?? '';
                if ($action === 'add_student') {
                    $session['student_form_result'] = $this->adminModel->addStudent($post);
                } elseif ($action === 'update_student') {
                    $session['student_form_result'] = $this->adminModel->updateStudent($post);
                } elseif ($action === 'deactivate_student') {
                    $lrn = trim((string) ($post['lrn'] ?? ''));
                    $session['student_form_result'] = preg_match('/^\d{1,19}$/D', $lrn) === 1
                        ? $this->adminModel->deactivateStudent($lrn)
                        : ['success' => false, 'message' => 'Please select a valid student.'];
                } else {
                    $session['student_form_result'] = [
                        'success' => false,
                        'message' => 'Unknown student action.',
                    ];
                }
            }

            header('Location: index.php?page=admin_manage_students');
            exit();
        }

        $result = $session['student_form_result'] ?? null;
        unset($session['student_form_result']);

        return [
            'sections' => $this->adminModel->getSectionOptions(),
            'students' => $this->adminModel->getStudents(),
            'csrfToken' => $session['csrf_token'],
            'result' => $result,
        ];
    }
}
