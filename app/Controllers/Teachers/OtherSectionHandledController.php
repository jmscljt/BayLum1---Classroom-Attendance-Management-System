<?php

require_once __DIR__ . '/../../Models/Teachers/OtherSectionHandledModel.php';

class OtherSectionHandledController
{
    private OtherSectionHandledModel $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new OtherSectionHandledModel($pdo);
    }

    public function getPageData(array &$session, array $query, array $post, string $requestMethod): array
    {
        if (empty($session['user_logged_in']) || empty($session['user_id'])) {
            header('Location: index.php?page=login');
            exit();
        }

        $teacherId = (int) $session['user_id'];
        if ($requestMethod === 'POST') {
            $result = $this->handleFormSubmission($session, $teacherId, $post);
            $session['other_section_message'] = $result;
            header('Location: index.php?page=otherSectionHandled');
            exit();
        }

        if (isset($query['record'])) {
            $this->sendStudentRecord($teacherId, $query['record']);
        }

        if (empty($session['csrf_token'])) {
            $session['csrf_token'] = bin2hex(random_bytes(32));
        }

        $message = $session['other_section_message'] ?? null;
        unset($session['other_section_message']);

        try {
            return [
                'assignedSections' => $this->model->getAssignments($teacherId),
                'availableSections' => $this->model->getAvailableSections($teacherId),
                'subjects' => OtherSectionHandledModel::subjects(),
                'csrfToken' => $session['csrf_token'],
                'assignedMessage' => $message,
            ];
        } catch (PDOException $e) {
            error_log('Other section page load error: ' . $e->getMessage());
            return [
                'assignedSections' => [],
                'availableSections' => [],
                'subjects' => OtherSectionHandledModel::subjects(),
                'csrfToken' => $session['csrf_token'],
                'assignedMessage' => [
                    'success' => false,
                    'message' => 'Unable to load assigned sections right now. Please try again.',
                ],
            ];
        }
    }

    private function sendStudentRecord(int $teacherId, $requestedAssignmentId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $assignmentId = $this->positiveInteger($requestedAssignmentId);
            $record = $this->model->getStudentsForAssignment($teacherId, $assignmentId);
            if (!$record) {
                http_response_code(404);
                echo json_encode(['error' => 'Assigned section not found.']);
                exit();
            }

            echo json_encode($record, JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (PDOException $e) {
            error_log('Other section student record error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Unable to load this student record right now.']);
        }
        exit();
    }

    private function handleFormSubmission(array $session, int $teacherId, array $post): array
    {
        $csrfToken = $post['csrf_token'] ?? '';
        $sessionToken = $session['csrf_token'] ?? '';
        if (
            !is_string($csrfToken)
            || !is_string($sessionToken)
            || $sessionToken === ''
            || !hash_equals($sessionToken, $csrfToken)
        ) {
            return ['success' => false, 'message' => 'Your session expired. Refresh the page and try again.'];
        }

        $action = $post['action'] ?? '';
        try {
            if ($action === 'remove_assignment') {
                $assignmentId = $this->positiveInteger($post['assignment_id'] ?? null);
                $this->model->removeAssignment($teacherId, $assignmentId);
                return ['success' => true, 'message' => 'Assigned section removed.'];
            }

            if ($action === 'add_assignment' || $action === 'edit_assignment') {
                $sectionId = $this->positiveInteger($post['section_id'] ?? null);
                $subject = $post['subject'] ?? '';
                if (!is_string($subject)) {
                    throw new InvalidArgumentException('Choose a valid subject.');
                }

                $assignmentId = $action === 'edit_assignment'
                    ? $this->positiveInteger($post['assignment_id'] ?? null)
                    : null;
                $this->model->saveAssignment($teacherId, $sectionId, $subject, $assignmentId);
                return [
                    'success' => true,
                    'message' => $action === 'edit_assignment'
                        ? 'Assigned section updated.'
                        : 'Section added successfully.',
                ];
            }

            return ['success' => false, 'message' => 'Choose a valid action.'];
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (PDOException $e) {
            error_log('Other section assignment error: ' . $e->getMessage());
            $message = (int) ($e->errorInfo[1] ?? 0) === 1062
                ? 'That subject is already assigned to this grade and section.'
                : 'Unable to save the assigned section right now. Please try again.';
            return ['success' => false, 'message' => $message];
        }
    }

    private function positiveInteger($value): int
    {
        if (!is_string($value) && !is_int($value)) {
            throw new InvalidArgumentException('A valid section assignment is required.');
        }
        $validated = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($validated === false) {
            throw new InvalidArgumentException('A valid section assignment is required.');
        }

        return (int) $validated;
    }
}
