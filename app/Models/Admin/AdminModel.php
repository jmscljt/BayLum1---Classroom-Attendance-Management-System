<?php

class AdminModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAdminByIdentifier(string $identifier): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT admin_id, full_name, password_hash
             FROM admins
             WHERE username = :username OR email = :email
             LIMIT 1'
        );
        $statement->execute([
            'username' => $identifier,
            'email' => $identifier,
        ]);
        $admin = $statement->fetch(PDO::FETCH_ASSOC);

        return $admin ?: null;
    }

    public function countAdmins(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    }

    public function createAdmin(
        string $fullName,
        string $username,
        string $email,
        string $passwordHash
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO admins (full_name, username, email, password_hash)
             VALUES (:full_name, :username, :email, :password_hash)'
        );
        $statement->execute([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getSectionOptions(): array
    {
        $statement = $this->pdo->query(
            'SELECT section_id, grade_level, section_name
             FROM sections
             ORDER BY grade_level ASC, section_name ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudents(): array
    {
        $statement = $this->pdo->query(
            'SELECT s.student_id AS lrn, s.first_name, s.middle_name, s.last_name, s.suffix,
                    s.gender, s.fingerprint_id, s.section_id,
                    sec.grade_level, sec.section_name
             FROM students s
             INNER JOIN sections sec ON sec.section_id = s.section_id
             WHERE s.is_active = 1
             ORDER BY s.last_name ASC, s.first_name ASC, s.middle_name ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addStudent(array $data): array
    {
        $lrn = trim((string) ($data['lrn'] ?? ''));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $middleName = trim((string) ($data['middle_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $suffix = trim((string) ($data['suffix'] ?? ''));
        $gender = trim((string) ($data['gender'] ?? ''));
        $sectionId = filter_var($data['section_id'] ?? null, FILTER_VALIDATE_INT);

        if (
            preg_match('/^\d{1,19}$/D', $lrn) !== 1
            || $firstName === ''
            || $lastName === ''
            || !in_array($gender, ['Male', 'Female'], true)
            || $sectionId === false
        ) {
            return ['success' => false, 'message' => 'Enter valid student details and select a section and gender.'];
        }

        try {
            $duplicate = $this->pdo->prepare('SELECT 1 FROM students WHERE student_id = :lrn LIMIT 1');
            $duplicate->execute(['lrn' => $lrn]);
            if ($duplicate->fetchColumn()) {
                return ['success' => false, 'message' => 'A student with this LRN already exists.'];
            }

            $statement = $this->pdo->prepare(
                'INSERT INTO students (student_id, section_id, first_name, middle_name, last_name, suffix, gender, is_active)
                 VALUES (:lrn, :section_id, :first_name, :middle_name, :last_name, :suffix, :gender, 1)'
            );
            $statement->execute([
                'lrn' => $lrn,
                'section_id' => (int) $sectionId,
                'first_name' => $firstName,
                'middle_name' => $middleName !== '' ? $middleName : null,
                'last_name' => $lastName,
                'suffix' => $suffix !== '' ? $suffix : null,
                'gender' => $gender,
            ]);

            return ['success' => true, 'message' => 'Student added successfully.'];
        } catch (PDOException $exception) {
            error_log('Admin add student error: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Unable to add the student.'];
        }
    }

    public function updateStudent(array $data): array
    {
        $currentLrn = trim((string) ($data['current_lrn'] ?? ''));
        $lrn = trim((string) ($data['lrn'] ?? ''));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $middleName = trim((string) ($data['middle_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $suffix = trim((string) ($data['suffix'] ?? ''));
        $gender = trim((string) ($data['gender'] ?? ''));
        $sectionId = filter_var($data['section_id'] ?? null, FILTER_VALIDATE_INT);

        if (
            preg_match('/^\d{1,19}$/D', $currentLrn) !== 1
            || preg_match('/^\d{1,19}$/D', $lrn) !== 1
            || $firstName === ''
            || $lastName === ''
            || !in_array($gender, ['Male', 'Female'], true)
            || $sectionId === false
        ) {
            return ['success' => false, 'message' => 'Enter valid student details and select a section and gender.'];
        }

        try {
            $this->pdo->beginTransaction();

            $existing = $this->pdo->prepare(
                'SELECT student_id FROM students
                 WHERE student_id = :current_lrn AND is_active = 1 FOR UPDATE'
            );
            $existing->execute(['current_lrn' => $currentLrn]);
            if (!$existing->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Student not found.'];
            }

            $duplicate = $this->pdo->prepare(
                'SELECT 1 FROM students WHERE student_id = :lrn AND student_id <> :current_lrn LIMIT 1'
            );
            $duplicate->execute(['lrn' => $lrn, 'current_lrn' => $currentLrn]);
            if ($duplicate->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'A student with this LRN already exists.'];
            }

            $update = $this->pdo->prepare(
                'UPDATE students
                 SET student_id = :lrn, section_id = :section_id,
                     first_name = :first_name, middle_name = :middle_name,
                     last_name = :last_name, suffix = :suffix, gender = :gender
                 WHERE student_id = :current_lrn AND is_active = 1'
            );
            $update->execute([
                'lrn' => $lrn,
                'section_id' => (int) $sectionId,
                'first_name' => $firstName,
                'middle_name' => $middleName !== '' ? $middleName : null,
                'last_name' => $lastName,
                'suffix' => $suffix !== '' ? $suffix : null,
                'gender' => $gender,
                'current_lrn' => $currentLrn,
            ]);

            if ($lrn !== $currentLrn) {
                foreach (['attendance_logs', 'manual_attendance_records'] as $table) {
                    $reference = $this->pdo->prepare(
                        "UPDATE {$table} SET student_id = :lrn WHERE student_id = :current_lrn"
                    );
                    $reference->execute(['lrn' => $lrn, 'current_lrn' => $currentLrn]);
                }
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Student updated successfully.'];
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Admin update student error: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Unable to update the student.'];
        }
    }

    public function deactivateStudent(string $lrn): array
    {
        $fingerprintStatement = $this->pdo->prepare(
            'SELECT fingerprint_id FROM students
             WHERE student_id = :lrn AND is_active = 1'
        );
        $fingerprintStatement->execute(['lrn' => $lrn]);
        $fingerprintId = $fingerprintStatement->fetchColumn();

        $statement = $this->pdo->prepare(
            'UPDATE students SET is_active = 0, fingerprint_id = NULL
             WHERE student_id = :lrn AND is_active = 1'
        );
        $statement->execute(['lrn' => $lrn]);

        if ($statement->rowCount() !== 1) {
            return ['success' => false, 'message' => 'Student not found.'];
        }
        $deletedFingerprintId = $fingerprintId !== false && $fingerprintId !== null
            ? (int) $fingerprintId
            : null;
        if ($deletedFingerprintId !== null) {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS fingerprint_device_commands (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    device_id VARCHAR(64) NOT NULL DEFAULT "esp32-as608",
                    command VARCHAR(16) NOT NULL,
                    fingerprint_id SMALLINT UNSIGNED NOT NULL,
                    student_lrn VARCHAR(19) NULL,
                    status VARCHAR(16) NOT NULL DEFAULT "pending",
                    error_message VARCHAR(255) NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                ) ENGINE=InnoDB'
            );
            $now = date('Y-m-d H:i:s');
            $queue = $this->pdo->prepare(
                'INSERT INTO fingerprint_device_commands
                 (device_id, command, fingerprint_id, status, created_at, updated_at)
                 VALUES ("esp32-as608", "DELETE", :fingerprint_id, "pending", :created_at, :updated_at)'
            );
            $queue->execute([
                ':fingerprint_id' => $deletedFingerprintId,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Student deactivated successfully.',
            'deleted_fingerprint_id' => $deletedFingerprintId,
        ];
    }

    public function getSections(): array
    {
        $sections = $this->getSectionOptions();
        foreach ($sections as &$section) {
            $section['advisers'] = [];
        }
        unset($section);

        $sectionIndexes = [];
        foreach ($sections as $index => $section) {
            $sectionIndexes[(int) $section['section_id']] = $index;
        }

        if ($sections === []) {
            return $sections;
        }

        $adviserStatement = $this->pdo->query(
            'SELECT id, full_name, section_id
             FROM teachers
             WHERE section_id IS NOT NULL
             ORDER BY full_name ASC'
        );
        foreach ($adviserStatement->fetchAll(PDO::FETCH_ASSOC) as $adviser) {
            $sectionId = (int) $adviser['section_id'];
            if (isset($sectionIndexes[$sectionId])) {
                $sections[$sectionIndexes[$sectionId]]['advisers'][] = $adviser;
            }
        }

        return $sections;
    }

    public function getGradeLevels(): array
    {
        $statement = $this->pdo->query(
            'SELECT DISTINCT grade_level FROM sections ORDER BY grade_level ASC'
        );
        $grades = array_column($statement->fetchAll(PDO::FETCH_ASSOC), 'grade_level');

        return array_values(array_unique(array_merge(
            array_map(static fn (int $grade): string => 'Grade ' . $grade, range(1, 6)),
            $grades
        )));
    }

    public function getTeachers(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, full_name
             FROM teachers
             ORDER BY full_name ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTeacherAccounts(): array
    {
        $statement = $this->pdo->query(
            'SELECT t.id, t.user_id, t.full_name, t.first_name, t.last_name,
                    t.email, t.section_id, t.is_active, t.created_at,
                    s.grade_level, s.section_name
             FROM teachers t
             LEFT JOIN sections s ON s.section_id = t.section_id
             ORDER BY t.full_name ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setTeacherAccountStatus(int $teacherId, bool $isActive): array
    {
        try {
            $this->pdo->beginTransaction();

            $teacherStatement = $this->pdo->prepare(
                'SELECT is_active FROM teachers WHERE id = :teacher_id FOR UPDATE'
            );
            $teacherStatement->execute(['teacher_id' => $teacherId]);
            $currentStatus = $teacherStatement->fetchColumn();
            if ($currentStatus === false) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'That teacher account no longer exists.'];
            }

            if ((bool) $currentStatus === $isActive) {
                $this->pdo->commit();
                return [
                    'success' => true,
                    'message' => 'That teacher account is already ' . ($isActive ? 'active.' : 'inactive.'),
                ];
            }

            $statement = $this->pdo->prepare(
                'UPDATE teachers SET is_active = :is_active WHERE id = :teacher_id'
            );
            $statement->execute([
                'is_active' => $isActive ? 1 : 0,
                'teacher_id' => $teacherId,
            ]);

            $this->pdo->commit();
            return [
                'success' => true,
                'message' => 'Teacher account ' . ($isActive ? 'activated' : 'deactivated') . ' successfully.',
            ];
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Admin update teacher status error: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Unable to update the teacher account status.'];
        }
    }

    public function updateTeacherAccount(
        int $teacherId,
        string $fullName,
        string $firstName,
        string $lastName,
        string $email,
        ?string $passwordHash
    ): array {
        try {
            $this->pdo->beginTransaction();

            $teacherStatement = $this->pdo->prepare(
                'SELECT id FROM teachers WHERE id = :teacher_id FOR UPDATE'
            );
            $teacherStatement->execute(['teacher_id' => $teacherId]);
            if (!$teacherStatement->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'That teacher account no longer exists.'];
            }

            $emailStatement = $this->pdo->prepare(
                'SELECT id FROM teachers
                 WHERE email = :email AND id <> :teacher_id
                 LIMIT 1'
            );
            $emailStatement->execute(['email' => $email, 'teacher_id' => $teacherId]);
            if ($emailStatement->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'That email is already registered to another account.'];
            }

            if ($passwordHash === null) {
                $statement = $this->pdo->prepare(
                    'UPDATE teachers
                     SET full_name = :full_name, first_name = :first_name, last_name = :last_name,
                         email = :email
                     WHERE id = :teacher_id'
                );
                $parameters = [
                    'full_name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'teacher_id' => $teacherId,
                ];
            } else {
                $statement = $this->pdo->prepare(
                    'UPDATE teachers
                     SET full_name = :full_name, first_name = :first_name, last_name = :last_name,
                         email = :email, password = :password
                     WHERE id = :teacher_id'
                );
                $parameters = [
                    'full_name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => $passwordHash,
                    'teacher_id' => $teacherId,
                ];
            }
            $statement->execute($parameters);

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Teacher account updated successfully.'];
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Admin update teacher error: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Unable to update the teacher account.'];
        }
    }

    public function removeTeacherAccount(int $teacherId): array
    {
        try {
            $statement = $this->pdo->prepare('DELETE FROM teachers WHERE id = :teacher_id');
            $statement->execute(['teacher_id' => $teacherId]);
            if ($statement->rowCount() !== 1) {
                return ['success' => false, 'message' => 'That teacher account no longer exists.'];
            }
            return ['success' => true, 'message' => 'Teacher account removed successfully.'];
        } catch (PDOException $exception) {
            error_log('Admin remove teacher error: ' . $exception->getMessage());
            return [
                'success' => false,
                'message' => 'This teacher account cannot be removed because it has related records.',
            ];
        }
    }

    public function addSection(string $gradeLevel, string $sectionName): array
    {
        try {
            $this->pdo->beginTransaction();

            $duplicateStatement = $this->pdo->prepare(
                'SELECT section_id
                 FROM sections
                 WHERE grade_level = :grade_level AND section_name = :section_name
                 LIMIT 1
                 FOR UPDATE'
            );
            $duplicateStatement->execute([
                'grade_level' => $gradeLevel,
                'section_name' => $sectionName,
            ]);
            if ($duplicateStatement->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'That section already exists for this grade.'];
            }

            $insertStatement = $this->pdo->prepare(
                'INSERT INTO sections (grade_level, section_name)
                 VALUES (:grade_level, :section_name)'
            );
            $insertStatement->execute([
                'grade_level' => $gradeLevel,
                'section_name' => $sectionName,
            ]);
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Section added successfully.'];
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Admin add section error: ' . $exception->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to add this section. Check the section details and try again.',
            ];
        }
    }

    public function removeSection(int $sectionId): array
    {
        try {
            $this->pdo->beginTransaction();

            $sectionStatement = $this->pdo->prepare(
                'SELECT section_id FROM sections WHERE section_id = :section_id FOR UPDATE'
            );
            $sectionStatement->execute(['section_id' => $sectionId]);
            if (!$sectionStatement->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'That section no longer exists.'];
            }

            $studentStatement = $this->pdo->prepare(
                'SELECT COUNT(*) FROM students WHERE section_id = :section_id'
            );
            $studentStatement->execute(['section_id' => $sectionId]);
            $studentCount = (int) $studentStatement->fetchColumn();

            $teacherStatement = $this->pdo->prepare(
                'SELECT COUNT(*) FROM teachers WHERE section_id = :section_id'
            );
            $teacherStatement->execute(['section_id' => $sectionId]);
            $teacherCount = (int) $teacherStatement->fetchColumn();

            if ($studentCount > 0 || $teacherCount > 0) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'This section still has students or an assigned adviser. Reassign or remove those records before deleting it.',
                ];
            }

            $deleteStatement = $this->pdo->prepare(
                'DELETE FROM sections WHERE section_id = :section_id'
            );
            $deleteStatement->execute(['section_id' => $sectionId]);
            $this->pdo->commit();

            return ['success' => true, 'message' => 'Section removed successfully.'];
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Admin remove section error: ' . $exception->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to remove this section because it is still in use.',
            ];
        }
    }

    public function updateSection(
        int $sectionId,
        string $gradeLevel,
        string $sectionName,
        ?int $teacherId
    ): array {
        try {
            $this->pdo->beginTransaction();

            $sectionStatement = $this->pdo->prepare(
                'SELECT section_id FROM sections WHERE section_id = :section_id FOR UPDATE'
            );
            $sectionStatement->execute(['section_id' => $sectionId]);
            if (!$sectionStatement->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'That section no longer exists.'];
            }

            $duplicateStatement = $this->pdo->prepare(
                'SELECT section_id
                 FROM sections
                 WHERE grade_level = :grade_level
                   AND section_name = :section_name
                   AND section_id <> :section_id
                 LIMIT 1
                 FOR UPDATE'
            );
            $duplicateStatement->execute([
                'grade_level' => $gradeLevel,
                'section_name' => $sectionName,
                'section_id' => $sectionId,
            ]);
            if ($duplicateStatement->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'That section already exists for this grade.'];
            }

            $currentAdviserStatement = $this->pdo->prepare(
                'SELECT id
                 FROM teachers
                 WHERE section_id = :section_id
                 ORDER BY id ASC
                 FOR UPDATE'
            );
            $currentAdviserStatement->execute(['section_id' => $sectionId]);
            $currentAdvisers = $currentAdviserStatement->fetchAll(PDO::FETCH_COLUMN);
            if (count($currentAdvisers) > 1) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'This section has multiple assigned teachers. Resolve those assignments before editing it.',
                ];
            }

            $selectedTeacherSectionId = null;
            if ($teacherId !== null) {
                $teacherStatement = $this->pdo->prepare(
                    'SELECT section_id FROM teachers WHERE id = :teacher_id FOR UPDATE'
                );
                $teacherStatement->execute(['teacher_id' => $teacherId]);
                $selectedTeacherSectionId = $teacherStatement->fetchColumn();
                if ($selectedTeacherSectionId === false) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'That teacher no longer exists.'];
                }
            } elseif ($currentAdvisers !== []) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Select an adviser. Teachers must remain assigned to a section.',
                ];
            }

            if (
                $teacherId !== null
                && $currentAdvisers !== []
                && (int) $selectedTeacherSectionId !== $sectionId
            ) {
                $otherAdviserStatement = $this->pdo->prepare(
                    'SELECT id
                     FROM teachers
                     WHERE section_id = :section_id AND id <> :teacher_id
                     LIMIT 1
                     FOR UPDATE'
                );
                $otherAdviserStatement->execute([
                    'section_id' => $selectedTeacherSectionId,
                    'teacher_id' => $teacherId,
                ]);
                if ($otherAdviserStatement->fetchColumn()) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'message' => 'The selected teacher\'s current section has multiple assigned teachers. Resolve those assignments before editing.',
                    ];
                }
            }

            $updateSectionStatement = $this->pdo->prepare(
                'UPDATE sections
                 SET grade_level = :grade_level, section_name = :section_name
                 WHERE section_id = :section_id'
            );
            $updateSectionStatement->execute([
                'grade_level' => $gradeLevel,
                'section_name' => $sectionName,
                'section_id' => $sectionId,
            ]);

            if ($teacherId !== null && (int) $selectedTeacherSectionId !== $sectionId) {
                $assignTeacherStatement = $this->pdo->prepare(
                    'UPDATE teachers SET section_id = :section_id WHERE id = :teacher_id'
                );
                $assignTeacherStatement->execute([
                    'section_id' => $sectionId,
                    'teacher_id' => $teacherId,
                ]);
                if ($assignTeacherStatement->rowCount() !== 1) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'The selected adviser could not be assigned.'];
                }

                if ($currentAdvisers !== []) {
                    $returnTeacherStatement = $this->pdo->prepare(
                        'UPDATE teachers SET section_id = :section_id WHERE id = :teacher_id'
                    );
                    $returnTeacherStatement->execute([
                        'section_id' => (int) $selectedTeacherSectionId,
                        'teacher_id' => (int) $currentAdvisers[0],
                    ]);
                    if ($returnTeacherStatement->rowCount() !== 1) {
                        $this->pdo->rollBack();
                        return [
                            'success' => false,
                            'message' => 'The previous adviser could not be reassigned. No changes were saved.',
                        ];
                    }
                }
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Section updated successfully.'];
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Admin update section error: ' . $exception->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to update this section. Check the grade, section name, and adviser, then try again.',
            ];
        }
    } 
}
