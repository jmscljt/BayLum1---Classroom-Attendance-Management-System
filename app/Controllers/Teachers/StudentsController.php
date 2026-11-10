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

        $masterlist = $this->getStudentsMasterlist((int) ($session['section_id'] ?? 0));

        return [
            'result' => $result,
            'masterlist' => $masterlist,
            'studentCount' => count($masterlist),
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

}
?>