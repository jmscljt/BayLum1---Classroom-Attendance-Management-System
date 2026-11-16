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

    public function getAttendanceRecordsForAssignment(
        int $teacherId,
        int $assignmentId,
        ?string $date = null,
        ?string $logDate = null
    ): array
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

        $sectionId = (int) $section['section_id'];
        $studentCountStmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM students WHERE section_id = :section_id AND is_active = 1'
        );
        $studentCountStmt->execute([':section_id' => $sectionId]);
        $studentCount = (int) $studentCountStmt->fetchColumn();

        $dateCondition = $date === null ? '' : ' AND attendance_date = :attendance_date';
        $summaryStmt = $this->pdo->prepare(
            "SELECT attendance_date,
                    COUNT(DISTINCT CASE WHEN attendance_type = 'time_in' THEN student_id END) AS present_count
             FROM manual_attendance_records
             WHERE section_id = :section_id{$dateCondition}
             GROUP BY attendance_date
             ORDER BY attendance_date DESC"
        );
        $summaryParameters = [':section_id' => $sectionId];
        if ($date !== null) {
            $summaryParameters[':attendance_date'] = $date;
        }
        $summaryStmt->execute($summaryParameters);
        $summaries = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

        $today = date('Y-m-d');
        if (
            ($date === null || $date === $today)
            && !array_filter(
                $summaries,
                static fn(array $summary): bool => $summary['attendance_date'] === $today
            )
        ) {
            array_unshift($summaries, [
                'attendance_date' => $today,
                'present_count' => 0,
            ]);
        }
        foreach ($summaries as &$summary) {
            $summary['present_count'] = (int) $summary['present_count'];
            $summary['absent_count'] = max(0, $studentCount - $summary['present_count']);
        }
        unset($summary);

        $attendanceLogs = [];
        if ($logDate !== null) {
            $logsStmt = $this->pdo->prepare(
                "SELECT s.student_id AS lrn,
                        s.first_name,
                        s.middle_name,
                        s.last_name,
                        s.gender,
                        GROUP_CONCAT(DISTINCT r.attendance_method ORDER BY r.attendance_method SEPARATOR ', ') AS methods,
                        MAX(CASE WHEN r.attendance_type = 'time_in' THEN r.recorded_at END) AS time_in,
                        MAX(CASE WHEN r.attendance_type = 'time_out' THEN r.recorded_at END) AS time_out
                 FROM manual_attendance_records r
                 INNER JOIN students s
                    ON s.student_id = r.student_id AND s.section_id = r.section_id
                 WHERE r.section_id = :section_id
                    AND r.attendance_date = :attendance_date
                 GROUP BY s.student_id, s.first_name, s.middle_name, s.last_name, s.gender
                 ORDER BY s.last_name, s.first_name"
            );
            $logsStmt->execute([
                ':section_id' => $sectionId,
                ':attendance_date' => $logDate,
            ]);
            $attendanceLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [
            'section' => $section,
            'summaries' => $summaries,
            'attendanceLogs' => $attendanceLogs,
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
