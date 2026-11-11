<?php

require_once __DIR__ . '/../../Models/Teachers/TeachersModel.php';

class StudentsController
{
    private PDO $pdo;
    private TeachersModel $teachersModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->teachersModel = new TeachersModel($pdo);
    }

    public function addStudent(array $studentData): array
    {
        return $this->teachersModel->addStudent($studentData);
    }

    public function getStudentsMasterlist(int $sectionId): array
    {
        return $this->teachersModel->getStudentsMasterlist($sectionId);
    }

    public function getStudentAbsences(string $studentId, int $sectionId): ?array
    {
        return $this->teachersModel->getStudentAbsences($studentId, $sectionId);
    }

    public function getPageData(array &$session, array $post, string $requestMethod): array
    {
        if ($requestMethod === 'POST') {
            $post['section_id'] = $session['section_id'] ?? null;
            $session['student_form_result'] = $this->addStudent($post);
            header('Location: index.php?page=students');
            exit();
        }

        $result = $session['student_form_result'] ?? null;
        unset($session['student_form_result']);

        if (empty($session['csrf_token'])) {
            $session['csrf_token'] = bin2hex(random_bytes(32));
        }

        $masterlist = $this->getStudentsMasterlist((int) ($session['section_id'] ?? 0));

        return [
            'result' => $result,
            'masterlist' => $masterlist,
            'studentCount' => count($masterlist),
            'csrfToken' => $session['csrf_token'],
        ];
    }

    public function handleStudentAbsencesRequest(array $session, array $query): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($session['user_logged_in']) || empty($session['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Please sign in to view student absences.']);
            return;
        }

        $studentId = $query['student_id'] ?? '';
        if (!is_string($studentId) || preg_match('/^\d{1,19}$/D', $studentId) !== 1) {
            http_response_code(400);
            echo json_encode(['error' => 'A valid student ID is required.']);
            return;
        }

        try {
            $absences = $this->getStudentAbsences(
                $studentId,
                (int) ($session['section_id'] ?? 0)
            );
            if ($absences === null) {
                http_response_code(404);
                echo json_encode(['error' => 'Student not found in your assigned section.']);
                return;
            }

            echo json_encode(['absences' => $absences], JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (PDOException $e) {
            error_log('Student absence lookup error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Unable to load absence records right now.']);
        }
    }

    public function handleStudentAbsenceStatusRequest(array $session, array $post): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($session['user_logged_in']) || empty($session['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Please sign in to update student attendance.']);
            return;
        }

        $csrfToken = $post['csrf_token'] ?? '';
        $sessionToken = $session['csrf_token'] ?? '';
        if (
            !is_string($csrfToken)
            || !is_string($sessionToken)
            || $sessionToken === ''
            || !hash_equals($sessionToken, $csrfToken)
        ) {
            http_response_code(403);
            echo json_encode(['error' => 'Your session expired. Refresh the page and try again.']);
            return;
        }

        $studentId = $post['student_id'] ?? '';
        $date = $post['date'] ?? '';
        $status = $post['status'] ?? '';
        if (
            !is_string($studentId)
            || preg_match('/^\d{1,19}$/D', $studentId) !== 1
            || !is_string($date)
            || !is_string($status)
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid attendance details.']);
            return;
        }

        $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            http_response_code(400);
            echo json_encode(['error' => 'A valid attendance date is required.']);
            return;
        }

        if (!in_array($status, ['Absent', 'Excused'], true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Select Absent or Excused.']);
            return;
        }

        $remarks = null;
        if ($status === 'Absent') {
            $remarks = 'No time-in was recorded before attendance closed.';
        } else {
            $reason = $post['reason'] ?? '';
            $otherRemarks = $post['other_remarks'] ?? '';
            $reasons = [
                'Illness',
                'Medical appointment',
                'Family emergency',
                'Bereavement',
                'School activity',
                'Other',
            ];
            if (!is_string($reason) || !in_array($reason, $reasons, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Select a valid reason for the excused absence.']);
                return;
            }

            if ($reason === 'Other') {
                if (!is_string($otherRemarks) || trim($otherRemarks) === '') {
                    http_response_code(400);
                    echo json_encode(['error' => 'Enter a remark for the selected Other reason.']);
                    return;
                }
                $remarks = trim($otherRemarks);
            } else {
                $remarks = $reason;
            }

            if (strlen($remarks) > 255) {
                http_response_code(400);
                echo json_encode(['error' => 'Remarks must be 255 characters or fewer.']);
                return;
            }
        }

        try {
            $updated = $this->teachersModel->updateStudentAbsenceStatus(
                $studentId,
                (int) ($session['section_id'] ?? 0),
                $date,
                $status,
                $remarks
            );
            if (!$updated) {
                http_response_code(404);
                echo json_encode(['error' => 'That absence is no longer available to update.']);
                return;
            }

            echo json_encode(['success' => true]);
        } catch (PDOException | RuntimeException $e) {
            error_log('Student absence update error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Unable to update attendance right now.']);
        }
    }

}
?>