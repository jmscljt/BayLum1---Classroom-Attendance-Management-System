<?php

class OtherSectionHandledModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAvailableSections(int $teacherId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sec.section_id, sec.grade_level, sec.section_name,
                    GROUP_CONCAT(DISTINCT adviser.full_name ORDER BY adviser.full_name SEPARATOR ", ") AS class_adviser
             FROM sections sec
             INNER JOIN teachers adviser ON adviser.section_id = sec.section_id
             WHERE sec.section_id <> (
                 SELECT own.section_id FROM teachers own WHERE own.id = :teacher_id
             )
             GROUP BY sec.section_id, sec.grade_level, sec.section_name
             ORDER BY sec.grade_level, sec.section_name'
        );
        $stmt->execute([':teacher_id' => $teacherId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAssignments(int $teacherId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT assignment.assignment_id, assignment.section_id, assignment.subject,
                    sec.grade_level, sec.section_name,
                    GROUP_CONCAT(DISTINCT adviser.full_name ORDER BY adviser.full_name SEPARATOR ", ") AS class_adviser
             FROM teacher_other_section_assignments assignment
             INNER JOIN sections sec ON sec.section_id = assignment.section_id
             INNER JOIN teachers adviser ON adviser.section_id = sec.section_id
             WHERE assignment.teacher_id = :teacher_id
             GROUP BY assignment.assignment_id, assignment.section_id, assignment.subject,
                      sec.grade_level, sec.section_name
             ORDER BY sec.grade_level, sec.section_name, assignment.subject'
        );
        $stmt->execute([':teacher_id' => $teacherId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveAssignment(int $teacherId, int $sectionId, string $subject, ?int $assignmentId = null): void
    {
        if (!in_array($subject, self::subjects(), true)) {
            throw new InvalidArgumentException('Choose a valid subject.');
        }

        $sectionStmt = $this->pdo->prepare(
            'SELECT 1
             FROM sections sec
             INNER JOIN teachers adviser ON adviser.section_id = sec.section_id
             WHERE sec.section_id = :section_id
               AND sec.section_id <> (
                   SELECT own.section_id FROM teachers own WHERE own.id = :teacher_id
               )
             LIMIT 1'
        );
        $sectionStmt->execute([
            ':section_id' => $sectionId,
            ':teacher_id' => $teacherId,
        ]);
        if (!$sectionStmt->fetchColumn()) {
            throw new InvalidArgumentException('Choose a section assigned to another class adviser.');
        }

        if ($assignmentId === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO teacher_other_section_assignments (teacher_id, section_id, subject)
                 VALUES (:teacher_id, :section_id, :subject)'
            );
            $stmt->execute([
                ':teacher_id' => $teacherId,
                ':section_id' => $sectionId,
                ':subject' => $subject,
            ]);
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE teacher_other_section_assignments
             SET section_id = :section_id, subject = :subject
             WHERE assignment_id = :assignment_id AND teacher_id = :teacher_id'
        );
        $stmt->execute([
            ':section_id' => $sectionId,
            ':subject' => $subject,
            ':assignment_id' => $assignmentId,
            ':teacher_id' => $teacherId,
        ]);
        if ($stmt->rowCount() === 0 && !$this->assignmentExists($teacherId, $assignmentId)) {
            throw new InvalidArgumentException('This assigned section could not be found.');
        }
    }

    public function removeAssignment(int $teacherId, int $assignmentId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM teacher_other_section_assignments
             WHERE assignment_id = :assignment_id AND teacher_id = :teacher_id'
        );
        $stmt->execute([
            ':assignment_id' => $assignmentId,
            ':teacher_id' => $teacherId,
        ]);
        if ($stmt->rowCount() === 0) {
            throw new InvalidArgumentException('This assigned section could not be found.');
        }
    }

    public function getStudentsForAssignment(int $teacherId, int $assignmentId): array
    {
        $assignmentStmt = $this->pdo->prepare(
            'SELECT assignment.section_id, sec.grade_level, sec.section_name,
                    GROUP_CONCAT(DISTINCT adviser.full_name ORDER BY adviser.full_name SEPARATOR ", ") AS class_adviser
             FROM teacher_other_section_assignments assignment
             INNER JOIN sections sec ON sec.section_id = assignment.section_id
             INNER JOIN teachers adviser ON adviser.section_id = sec.section_id
             WHERE assignment.assignment_id = :assignment_id
               AND assignment.teacher_id = :teacher_id
             GROUP BY assignment.section_id, sec.grade_level, sec.section_name'
        );
        $assignmentStmt->execute([
            ':assignment_id' => $assignmentId,
            ':teacher_id' => $teacherId,
        ]);
        $section = $assignmentStmt->fetch(PDO::FETCH_ASSOC);
        if (!$section) {
            return [];
        }

        $studentsStmt = $this->pdo->prepare(
            'SELECT student_id AS lrn, first_name, middle_name, last_name, gender
             FROM students
             WHERE section_id = :section_id AND is_active = 1
             ORDER BY last_name, first_name, middle_name'
        );
        $studentsStmt->execute([':section_id' => (int) $section['section_id']]);

        return [
            'section' => $section,
            'students' => $studentsStmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public static function subjects(): array
    {
        return ['Math', 'English', 'Science', 'Filipino', 'Araling Panlipunan'];
    }

    private function assignmentExists(int $teacherId, int $assignmentId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM teacher_other_section_assignments
             WHERE assignment_id = :assignment_id AND teacher_id = :teacher_id'
        );
        $stmt->execute([
            ':assignment_id' => $assignmentId,
            ':teacher_id' => $teacherId,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
