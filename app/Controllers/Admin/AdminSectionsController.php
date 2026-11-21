<?php

require_once __DIR__ . '/../../Models/Admin/AdminModel.php';

class AdminSectionsController
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
                $session['section_form_result'] = [
                    'success' => false,
                    'message' => 'Your session expired. Refresh the page and try again.',
                ];
            } else {
                $action = $post['action'] ?? '';
                if ($action === 'add_section') {
                    $session['section_form_result'] = $this->addSection($post);
                } elseif ($action === 'update_section') {
                    $session['section_form_result'] = $this->updateSection($post);
                } elseif ($action === 'remove_section') {
                    $session['section_form_result'] = $this->removeSection($post);
                } else {
                    $session['section_form_result'] = [
                        'success' => false,
                        'message' => 'Unknown section action.',
                    ];
                }
            }

            header('Location: index.php?page=admin_manage_sections');
            exit();
        }

        $result = $session['section_form_result'] ?? null;
        unset($session['section_form_result']);

        return [
            'sections' => $this->adminModel->getSections(),
            'grades' => $this->adminModel->getGradeLevels(),
            'teachers' => $this->adminModel->getTeachers(),
            'csrfToken' => $session['csrf_token'],
            'result' => $result,
        ];
    }

    private function addSection(array $post): array
    {
        $submittedGrade = $post['grade_level'] ?? '';
        $submittedSectionName = $post['section_name'] ?? '';
        if (!is_string($submittedGrade) || !is_string($submittedSectionName)) {
            return ['success' => false, 'message' => 'Enter a valid grade level and section name.'];
        }

        $gradeLevel = trim($submittedGrade);
        $sectionName = trim($submittedSectionName);
        if (!in_array($gradeLevel, $this->adminModel->getGradeLevels(), true)) {
            return ['success' => false, 'message' => 'Please select a valid grade level.'];
        }
        if ($sectionName === '' || strlen($sectionName) > 50) {
            return ['success' => false, 'message' => 'Enter a section name of up to 50 characters.'];
        }

        return $this->adminModel->addSection($gradeLevel, $sectionName);
    }

    private function removeSection(array $post): array
    {
        $sectionId = filter_var($post['section_id'] ?? null, FILTER_VALIDATE_INT);
        if ($sectionId === false || $sectionId < 1) {
            return ['success' => false, 'message' => 'Please select a valid section to remove.'];
        }

        return $this->adminModel->removeSection((int) $sectionId);
    }

    private function updateSection(array $post): array
    {
        $sectionId = filter_var($post['section_id'] ?? null, FILTER_VALIDATE_INT);
        $submittedGrade = $post['grade_level'] ?? '';
        $submittedSectionName = $post['section_name'] ?? '';

        if ($sectionId === false || $sectionId < 1) {
            return ['success' => false, 'message' => 'Please select a valid section to edit.'];
        }
        if (!is_string($submittedGrade) || !is_string($submittedSectionName)) {
            return ['success' => false, 'message' => 'Enter a valid grade level and section name.'];
        }

        $gradeLevel = trim($submittedGrade);
        $sectionName = trim($submittedSectionName);
        if (!in_array($gradeLevel, $this->adminModel->getGradeLevels(), true)) {
            return ['success' => false, 'message' => 'Please select a valid grade level.'];
        }
        if ($sectionName === '' || strlen($sectionName) > 50) {
            return ['success' => false, 'message' => 'Enter a section name of up to 50 characters.'];
        }

        $teacherId = $this->validateTeacherId($post['adviser_id'] ?? '');
        if ($teacherId === false) {
            return ['success' => false, 'message' => 'Please select a valid adviser.'];
        }

        return $this->adminModel->updateSection(
            (int) $sectionId,
            $gradeLevel,
            $sectionName,
            $teacherId
        );
    }

    private function validateTeacherId(mixed $submittedTeacherId): int|false|null
    {
        if ($submittedTeacherId === '') {
            return null;
        }

        $teacherId = filter_var($submittedTeacherId, FILTER_VALIDATE_INT);
        if ($teacherId === false || $teacherId < 1) {
            return false;
        }

        return (int) $teacherId;
    }
}
