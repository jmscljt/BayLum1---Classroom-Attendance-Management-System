<?php
class TeachersModel{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handleSignup(
        string $uniqueId, 
        string $firstName, 
        string $lastName, 
        string $email, 
        string $password, 
        string $confirmPassword,
        int $sectionId
    ): array
    {
        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match!'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long!'];
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['success' => false, 'message' => 'Invalid email format!'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $fullName = trim($firstName . ' ' . $lastName);

        // Check if email already exists
        $emailCheck = $this->pdo->prepare("SELECT COUNT(*) FROM teachers WHERE email = ?");
        $emailCheck->execute([$email]);
        if ($emailCheck->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'This email is already registered. Please use a different email.'];
        }
        
        // Cjheck if unique ID already exists
        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM teachers WHERE user_id = ?");
        $checkStmt->execute([$uniqueId]);
        if ($checkStmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Failed to generate a Unique ID. Please try again.'];
        }

        try {
            $this->pdo->beginTransaction();

            // Lock the selected section during signup.
            $sectionCheck = $this->pdo->prepare(
                "SELECT section_id FROM sections WHERE section_id = ? FOR UPDATE"
            );
            $sectionCheck->execute([$sectionId]);
            if ($sectionCheck->fetchColumn() === false) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Invalid section selected. Please choose a valid grade and section.'];
            }

            $teacherCheck = $this->pdo->prepare(
                'SELECT 1 FROM teachers WHERE section_id = ? LIMIT 1'
            );
            $teacherCheck->execute([$sectionId]);

            if ($teacherCheck->fetchColumn()) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'This section is already taken by another teacher. If you believe this is an error, please contact the school administrator.',
                ];
            }

            $idColumnStmt = $this->pdo->query("SHOW COLUMNS FROM teachers LIKE 'id'");
            $idColumn = $idColumnStmt->fetch(PDO::FETCH_ASSOC);
            $usesAutoIncrement = $idColumn && stripos((string) ($idColumn['Extra'] ?? ''), 'auto_increment') !== false;
            $teacherId = null;

            // Auto incrementation for making teachers account
            if ($usesAutoIncrement) {
                $query = "INSERT INTO teachers (user_id, full_name, first_name, last_name, email, password, section_id) VALUES (:user_id, :full_name, :first_name, :last_name, :email, :password, :section_id)";
            } else {
                $sequenceStmt = $this->pdo->prepare("INSERT INTO teacher_id_sequence () VALUES ()");
                $sequenceStmt->execute();
                $teacherId = (int) $this->pdo->lastInsertId();

                $query = "INSERT INTO teachers (id, user_id, full_name, first_name, last_name, email, password, section_id) VALUES (:id, :user_id, :full_name, :first_name, :last_name, :email, :password, :section_id)";
            }

            $stmt = $this->pdo->prepare($query);

            if ($teacherId !== null) {
                $stmt->bindParam(':id', $teacherId, PDO::PARAM_INT);
            }
            $stmt->bindParam(':user_id', $uniqueId, PDO::PARAM_STR);
            $stmt->bindParam(':full_name', $fullName, PDO::PARAM_STR);
            $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':section_id', $sectionId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $this->pdo->commit();
                return [
                    'success' => true,
                    'user_id' => $uniqueId,
                    'insert_id' => $teacherId ?? (int) $this->pdo->lastInsertId(),
                    'full_name' => $fullName,
                ];
            }
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log(sprintf(
                'Signup error: %s (SQLSTATE %s)',
                $e->getMessage(),
                $e->getCode()
            ));
            return ['success' => false, 'message' => 'Unable to create the account. Please try again.'];
        }

        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Unable to create the account. Please try again.'];
    }



    public function handleLogin(string $loginInput, string $password): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*, s.grade_level AS assigned_grade_level,
                s.section_name AS advisory_section
            FROM teachers t
            LEFT JOIN sections s ON s.section_id = t.section_id
            WHERE t.user_id = ? OR t.full_name = ?"
        );
        $stmt->execute([$loginInput, $loginInput]);
        $teachers = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teachers && password_verify($password, $teachers['password'])) {
            if (!(int) $teachers['is_active']) {
                return [
                    'success' => false,
                    'message' => 'This account is inactive. Please contact the school administrator.',
                ];
            }

            return [
                'success' => true,
                'user' => $teachers,
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid Unique ID/Username or Password.',
        ];
    }

    public function getTeacherAccount(int $teacherId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.id, t.user_id, t.full_name, t.first_name, t.last_name, t.email, t.section_id,
                s.grade_level AS assigned_grade_level,
                s.section_name AS advisory_section
             FROM teachers t
             LEFT JOIN sections s ON s.section_id = t.section_id
             WHERE t.id = ?
             LIMIT 1'
        );
        $stmt->execute([$teacherId]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        return $teacher ?: null;
    }

    public function getAvailableSections(): array
    {
        $stmt = $this->pdo->query(
            'SELECT section_id, grade_level, section_name
             FROM sections
             ORDER BY grade_level ASC, section_name ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateTeacherProfile(
        int $teacherId,
        string $firstName,
        string $lastName,
        string $email,
        int $sectionId
    ): array {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $fullName = trim($firstName . ' ' . $lastName);
        $email = trim($email);

        if ($firstName === '' || $lastName === '') {
            return ['success' => false, 'message' => 'First name and last name cannot be empty.'];
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }

        try {
            $this->pdo->beginTransaction();

            $teacherStmt = $this->pdo->prepare(
                'SELECT id FROM teachers WHERE id = ? FOR UPDATE'
            );
            $teacherStmt->execute([$teacherId]);
            if (!$teacherStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Your account could not be found.'];
            }

            $emailStmt = $this->pdo->prepare(
                'SELECT id FROM teachers WHERE email = ? AND id <> ? LIMIT 1'
            );
            $emailStmt->execute([$email, $teacherId]);
            if ($emailStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'That email is already registered to another account.'];
            }

            $sectionStmt = $this->pdo->prepare(
                'SELECT section_id FROM sections WHERE section_id = ? FOR UPDATE'
            );
            $sectionStmt->execute([$sectionId]);
            if (!$sectionStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'The selected section does not exist.'];
            }

            $assignedStmt = $this->pdo->prepare(
                'SELECT id FROM teachers
                 WHERE section_id = ? AND id <> ?
                 LIMIT 1 FOR UPDATE'
            );
            $assignedStmt->execute([$sectionId, $teacherId]);
            if ($assignedStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'This section is already assigned to another teacher.'];
            }

            $updateStmt = $this->pdo->prepare(
                'UPDATE teachers SET first_name = ?, last_name = ?, full_name = ?, email = ?, section_id = ? WHERE id = ?'
            );
            $updateStmt->execute([$firstName, $lastName, $fullName, $email, $sectionId, $teacherId]);
            $this->pdo->commit();

            $account = $this->getTeacherAccount($teacherId);
            if (!$account) {
                return ['success' => false, 'message' => 'Unable to refresh your account information.'];
            }

            return [
                'success' => true,
                'message' => 'Profile updated successfully.',
                'account' => $account,
            ];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Teacher profile update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to update your profile. Please try again.'];
        }
    }

    public function updateTeacherSection(int $teacherId, int $sectionId): array
    {
        try {
            $this->pdo->beginTransaction();

            $sectionStmt = $this->pdo->prepare(
                'SELECT section_id
                 FROM sections
                 WHERE section_id = ?
                 FOR UPDATE'
            );
            $sectionStmt->execute([$sectionId]);
            if (!$sectionStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'The selected section does not exist.'];
            }

            $teacherStmt = $this->pdo->prepare(
                'SELECT id
                 FROM teachers
                 WHERE id = ?
                 FOR UPDATE'
            );
            $teacherStmt->execute([$teacherId]);
            if (!$teacherStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Your account could not be found.'];
            }

            $assignedStmt = $this->pdo->prepare(
                'SELECT id
                 FROM teachers
                 WHERE section_id = ? AND id <> ?
                 LIMIT 1
                 FOR UPDATE'
            );
            $assignedStmt->execute([$sectionId, $teacherId]);
            if ($assignedStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'This section is already assigned to another teacher.',
                ];
            }

            $updateStmt = $this->pdo->prepare(
                'UPDATE teachers SET section_id = ? WHERE id = ?'
            );
            $updateStmt->execute([$sectionId, $teacherId]);
            $this->pdo->commit();

            $account = $this->getTeacherAccount($teacherId);
            if (!$account) {
                return ['success' => false, 'message' => 'Unable to refresh your account information.'];
            }

            return [
                'success' => true,
                'message' => 'Assigned section updated successfully.',
                'account' => $account,
            ];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Teacher section update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to update your assigned section. Please try again.'];
        }
    }

    public function updateTeacherPassword(
        int $teacherId,
        string $currentPassword,
        string $newPassword,
        string $confirmPassword
    ): array {
        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'New passwords do not match.'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters long.'];
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT password
                 FROM teachers
                 WHERE id = ?'
            );
            $stmt->execute([$teacherId]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$teacher || !password_verify($currentPassword, $teacher['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $this->pdo->prepare(
                'UPDATE teachers SET password = ? WHERE id = ?'
            );
            $updateStmt->execute([$hashedPassword, $teacherId]);

            return ['success' => true, 'message' => 'Password updated successfully.'];
        } catch (PDOException $e) {
            error_log('Teacher password update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to update your password. Please try again.'];
        }
    }



   public function addStudent(array $studentData): array
    {
        try {
            $studentId = trim($studentData['lrn'] ?? '');
            $firstName = trim($studentData['first_name'] ?? '');
            $middleName = trim($studentData['middle_name'] ?? null);
            $lastName = trim($studentData['last_name'] ?? '');
            $suffix = trim($studentData['suffix'] ?? '');
            $gender = trim($studentData['gender'] ?? '');
            $sectionId = trim($studentData['section_id'] ?? '');

            if (empty($studentId) || empty($firstName) || empty($lastName)) {
                return ['success' => false, 'message' => 'Required fields cannot be empty.'];
            }

            if (empty($sectionId)) {
                return ['success' => false, 'message' => 'Error: No active section assigned to this session.'];
            }

            $duplicateCheck = $this->pdo->prepare(
                "SELECT 1 FROM students WHERE student_id = :student_id LIMIT 1"
            );
            $duplicateCheck->execute([':student_id' => $studentId]);
            if ($duplicateCheck->fetchColumn()) {
                return [
                    'success' => false,
                    'message' => 'A student with this LRN already exists. Check the LRN or contact the school administrator if the existing record needs correction.',
                ];
            }

            $query = "INSERT INTO students (student_id, section_id, first_name, middle_name, last_name, suffix, gender) VALUES (:student_id, :section_id, :first_name, :middle_name, :last_name, :suffix, :gender)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                ':student_id' => $studentId,
                ':section_id' => $sectionId,
                ':first_name' => $firstName,
                ':middle_name' => $middleName !== '' ? $middleName : null,
                ':last_name' => $lastName,
                ':suffix' => $suffix !== '' ? $suffix : null,
                ':gender' => $gender,
            ]);

            return ['success' => true, 'message' => 'Student added successfully.'];
        } catch (PDOException $e) {
            $errorInfo = $e->errorInfo;
            if ((int)($errorInfo[1] ?? 0) === 1062) {
                return [
                    'success' => false,
                    'message' => 'A student with this LRN already exists. Check the LRN or contact the school administrator if the existing record needs correction.',
                ];
            }

            error_log('Add student error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to add the student right now. Please try again or contact the school administrator.',
            ];
        }
    }

    public function updateStudent(string $currentStudentId, int $sectionId, array $studentData): array
    {
        foreach (['lrn', 'first_name', 'middle_name', 'last_name', 'suffix', 'gender', 'fingerprint_id', 'clear_fingerprint_id'] as $field) {
            if (isset($studentData[$field]) && !is_string($studentData[$field])) {
                return ['success' => false, 'message' => 'Enter valid student details and select a gender.'];
            }
        }

        $studentId = trim($studentData['lrn'] ?? '');
        $firstName = trim($studentData['first_name'] ?? '');
        $middleName = trim($studentData['middle_name'] ?? '');
        $lastName = trim($studentData['last_name'] ?? '');
        $suffix = trim($studentData['suffix'] ?? '');
        $gender = trim($studentData['gender'] ?? '');
        $fingerprintId = trim($studentData['fingerprint_id'] ?? '');
        $clearFingerprint = ($studentData['clear_fingerprint_id'] ?? '') === '1';

        if (!$clearFingerprint && !isset($studentData['fingerprint_id'])) {
            $currentFingerprintStmt = $this->pdo->prepare(
                'SELECT fingerprint_id
                 FROM students
                 WHERE student_id = :student_id
                   AND section_id = :section_id
                   AND is_active = 1'
            );
            $currentFingerprintStmt->execute([
                ':student_id' => $currentStudentId,
                ':section_id' => $sectionId,
            ]);
            $existingFingerprintId = $currentFingerprintStmt->fetchColumn();
            $fingerprintId = $existingFingerprintId === false || $existingFingerprintId === null
                ? ''
                : (string) $existingFingerprintId;
        }
        if ($clearFingerprint) {
            $fingerprintId = '';
        }

        if (
            preg_match('/^\d{1,19}$/D', $studentId) !== 1
            || $firstName === ''
            || $lastName === ''
            || !in_array($gender, ['Male', 'Female'], true)
        ) {
            return ['success' => false, 'message' => 'Enter valid student details and select a gender.'];
        }

        if (
            $fingerprintId !== ''
            && (
                preg_match('/^\d{1,3}$/D', $fingerprintId) !== 1
                || (int) $fingerprintId < 1
                || (int) $fingerprintId > 127
            )
        ) {
            return ['success' => false, 'message' => 'AS608 Fingerprint ID must be a number from 1 to 127.'];
        }

        $deletedFingerprintId = null;
        if ($clearFingerprint) {
            $oldFingerprintStmt = $this->pdo->prepare(
                'SELECT fingerprint_id
                 FROM students
                 WHERE student_id = :student_id
                   AND section_id = :section_id
                   AND is_active = 1'
            );
            $oldFingerprintStmt->execute([
                ':student_id' => $currentStudentId,
                ':section_id' => $sectionId,
            ]);
            $oldFingerprintId = $oldFingerprintStmt->fetchColumn();
            if ($oldFingerprintId !== false && $oldFingerprintId !== null) {
                $deletedFingerprintId = (int) $oldFingerprintId;
            }
        }

        try {
            $this->pdo->beginTransaction();

            $studentStmt = $this->pdo->prepare(
                'SELECT student_id
                 FROM students
                 WHERE student_id = :student_id
                    AND section_id = :section_id
                    AND is_active = 1
                 FOR UPDATE'
            );
            $studentStmt->execute([
                ':student_id' => $currentStudentId,
                ':section_id' => $sectionId,
            ]);
            if (!$studentStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Student not found in your assigned section.'];
            }

            $duplicateStmt = $this->pdo->prepare(
                'SELECT 1
                 FROM students
                 WHERE student_id = :student_id AND student_id <> :current_student_id
                 LIMIT 1'
            );
            $duplicateStmt->execute([
                ':student_id' => $studentId,
                ':current_student_id' => $currentStudentId,
            ]);
            if ($duplicateStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'A student with this LRN already exists.'];
            }

            if ($fingerprintId !== '') {
                $fingerprintDuplicateStmt = $this->pdo->prepare(
                    'SELECT 1
                     FROM students
                     WHERE fingerprint_id = :fingerprint_id
                        AND student_id <> :current_student_id
                     LIMIT 1'
                );
                $fingerprintDuplicateStmt->execute([
                    ':fingerprint_id' => (int) $fingerprintId,
                    ':current_student_id' => $currentStudentId,
                ]);
                if ($fingerprintDuplicateStmt->fetchColumn()) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'message' => 'This AS608 Fingerprint ID is already assigned to another student.',
                    ];
                }
            }

            $updateStmt = $this->pdo->prepare(
                'UPDATE students
                 SET student_id = :new_student_id,
                     first_name = :first_name,
                     middle_name = :middle_name,
                     last_name = :last_name,
                     suffix = :suffix,
                     fingerprint_id = :fingerprint_id,
                     gender = :gender
                 WHERE student_id = :current_student_id
                    AND section_id = :section_id
                    AND is_active = 1'
            );
            $updateStmt->execute([
                ':new_student_id' => $studentId,
                ':first_name' => $firstName,
                ':middle_name' => $middleName !== '' ? $middleName : null,
                ':last_name' => $lastName,
                ':suffix' => $suffix !== '' ? $suffix : null,
                ':fingerprint_id' => $fingerprintId !== '' ? (int) $fingerprintId : null,
                ':gender' => $gender,
                ':current_student_id' => $currentStudentId,
                ':section_id' => $sectionId,
            ]);

            if ($studentId !== $currentStudentId) {
                foreach (['attendance_logs', 'manual_attendance_records'] as $table) {
                    $referenceStmt = $this->pdo->prepare(
                        "UPDATE {$table} SET student_id = :new_student_id WHERE student_id = :current_student_id"
                    );
                    $referenceStmt->execute([
                        ':new_student_id' => $studentId,
                        ':current_student_id' => $currentStudentId,
                    ]);
                }
            }

            $this->pdo->commit();
            return [
                'success' => true,
                'message' => 'Student updated successfully.',
                'deleted_fingerprint_id' => $deletedFingerprintId,
            ];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $errorInfo = $e->errorInfo;
            if ((int) ($errorInfo[1] ?? 0) === 1062) {
                return ['success' => false, 'message' => 'This LRN or AS608 Fingerprint ID is already assigned to another student.'];
            }

            error_log('Update student error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to update the student right now. Please try again or contact the school administrator.',
            ];
        }
    }

    public function deactivateStudent(string $studentId, int $sectionId): array
    {
        try {
            $fingerprintStmt = $this->pdo->prepare(
                'SELECT fingerprint_id
                 FROM students
                 WHERE student_id = :student_id
                   AND section_id = :section_id
                   AND is_active = 1
                 FOR UPDATE'
            );
            $fingerprintStmt->execute([
                ':student_id' => $studentId,
                ':section_id' => $sectionId,
            ]);
            $fingerprintId = $fingerprintStmt->fetchColumn();

            $stmt = $this->pdo->prepare(
                'UPDATE students
                 SET is_active = 0,
                     fingerprint_id = NULL
                 WHERE student_id = :student_id
                    AND section_id = :section_id
                    AND is_active = 1'
            );
            $stmt->execute([
                ':student_id' => $studentId,
                ':section_id' => $sectionId,
            ]);

            if ($stmt->rowCount() !== 1) {
                return ['success' => false, 'message' => 'Student not found in your assigned section.'];
            }
            $deletedFingerprintId = $fingerprintId !== false && $fingerprintId !== null
                ? (int) $fingerprintId
                : null;
            return [
                'success' => true,
                'message' => 'Student removed from the list. Attendance history was preserved.',
                'deleted_fingerprint_id' => $deletedFingerprintId,
            ];
        } catch (PDOException $e) {
            error_log('Remove student error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to remove the student right now. Please try again or contact the school administrator.',
            ];
        }
    }

    public function getInactiveStudents(int $sectionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT student_id AS lrn, first_name, middle_name, last_name, suffix, gender
             FROM students
             WHERE section_id = :section_id AND is_active = 0
             ORDER BY last_name, first_name'
        );
        $stmt->execute([':section_id' => $sectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reactivateStudent(string $studentId, int $sectionId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE students
                 SET is_active = 1
                 WHERE student_id = :student_id
                    AND section_id = :section_id
                    AND is_active = 0'
            );
            $stmt->execute([
                ':student_id' => $studentId,
                ':section_id' => $sectionId,
            ]);

            if ($stmt->rowCount() !== 1) {
                return ['success' => false, 'message' => 'Inactive student not found in your assigned section.'];
            }

            return ['success' => true, 'message' => 'Student reactivated successfully.'];
        } catch (PDOException $e) {
            error_log('Reactivate student error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to reactivate the student right now. Please try again or contact the school administrator.',
            ];
        }
    }

    private function hasAutoIncrementId(string $table): bool
    {
        $columnStmt = $this->pdo->query(
            "SHOW COLUMNS FROM {$table} LIKE 'id'"
        );
        $column = $columnStmt->fetch(PDO::FETCH_ASSOC);
        return $column
            && stripos((string) ($column['Extra'] ?? ''), 'auto_increment') !== false;
    }

    private function getNextManualAttendanceId(
        string $sequenceTable,
        string $targetTable
    ): int {
        $sequenceMax = (int) $this->pdo
            ->query("SELECT COALESCE(MAX(id), 0) FROM {$sequenceTable}")
            ->fetchColumn();
        $targetMax = (int) $this->pdo
            ->query("SELECT COALESCE(MAX(id), 0) FROM {$targetTable}")
            ->fetchColumn();
        $nextId = max($sequenceMax, $targetMax) + 1;

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$sequenceTable} (id) VALUES (:id)"
        );
        $stmt->execute([':id' => $nextId]);
        return $nextId;
    }

    public function getOrCreateAttendanceCodes(int $teacherId): array
    {
        $this->pdo->beginTransaction();

        try {
            $teacherStmt = $this->pdo->prepare(
                'SELECT id FROM teachers WHERE id = :teacher_id FOR UPDATE'
            );
            $teacherStmt->execute([':teacher_id' => $teacherId]);
            if (!$teacherStmt->fetchColumn()) {
                throw new RuntimeException('Teacher account was not found.');
            }

            $codesStmt = $this->pdo->prepare(
                'SELECT attendance_type, code
                 FROM teacher_attendance_codes
                 WHERE teacher_id = :teacher_id'
            );
            $codesStmt->execute([':teacher_id' => $teacherId]);
            $codes = [];
            foreach ($codesStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $codes[$row['attendance_type']] = $row['code'];
            }

            foreach (['time_in', 'time_out'] as $attendanceType) {
                if (isset($codes[$attendanceType])) {
                    continue;
                }

                $insertStmt = $this->pdo->prepare(
                    'INSERT INTO teacher_attendance_codes (teacher_id, attendance_type, code)
                     VALUES (:teacher_id, :attendance_type, :code)'
                );
                $created = false;
                for ($attempt = 0; $attempt < 100; $attempt++) {
                    $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                    try {
                        $insertStmt->execute([
                            ':teacher_id' => $teacherId,
                            ':attendance_type' => $attendanceType,
                            ':code' => $code,
                        ]);
                        $codes[$attendanceType] = $code;
                        $created = true;
                        break;
                    } catch (PDOException $e) {
                        if ($e->getCode() !== '23000') {
                            throw $e;
                        }
                    }
                }

                if (!$created) {
                    throw new RuntimeException('Unable to generate a unique attendance code.');
                }
            }

            $this->pdo->commit();
            return $codes;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function processAttendanceCode(string $code, ?int $browserSessionId = null): array
    {
        $codeStmt = $this->pdo->prepare(
            'SELECT teacher_id, attendance_type
             FROM teacher_attendance_codes
             WHERE code = :code'
        );
        $codeStmt->execute([':code' => $code]);
        $codeRow = $codeStmt->fetch(PDO::FETCH_ASSOC);
        if (!$codeRow) {
            return ['success' => false, 'message' => 'That attendance code is not valid.'];
        }

        $teacherId = (int) $codeRow['teacher_id'];
        $attendanceType = $codeRow['attendance_type'];
        $this->pdo->beginTransaction();

        try {
            $teacherStmt = $this->pdo->prepare(
                'SELECT section_id FROM teachers WHERE id = :teacher_id FOR UPDATE'
            );
            $teacherStmt->execute([':teacher_id' => $teacherId]);
            $sectionId = $teacherStmt->fetchColumn();
            if ($sectionId === false || $sectionId === null) {
                throw new RuntimeException('Teacher account was not found.');
            }

            $activeStmt = $this->pdo->prepare(
                'SELECT id, attendance_type, attendance_date
                 FROM manual_attendance_sessions
                 WHERE teacher_id = :teacher_id AND closed_at IS NULL
                 ORDER BY id DESC
                 LIMIT 1
                 FOR UPDATE'
            );
            $activeStmt->execute([':teacher_id' => $teacherId]);
            $activeSession = $activeStmt->fetch(PDO::FETCH_ASSOC);

            if ($activeSession && $activeSession['attendance_date'] !== date('Y-m-d')) {
                $expireStmt = $this->pdo->prepare(
                    'UPDATE manual_attendance_sessions
                     SET closed_at = :closed_at
                     WHERE id = :session_id AND closed_at IS NULL'
                );
                $expireStmt->execute([
                    ':closed_at' => date('Y-m-d H:i:s'),
                    ':session_id' => $activeSession['id'],
                ]);
                $activeSession = false;
            }

            if (!$activeSession && $browserSessionId !== null) {
                $browserSessionStmt = $this->pdo->prepare(
                    'SELECT id
                     FROM manual_attendance_sessions
                     WHERE id = :session_id AND closed_at IS NULL
                     FOR UPDATE'
                );
                $browserSessionStmt->execute([':session_id' => $browserSessionId]);
                if ($browserSessionStmt->fetchColumn()) {
                    $this->pdo->commit();
                    return [
                        'success' => false,
                        'message' => 'Close your current attendance session before opening another one.',
                    ];
                }
            }

            if ($activeSession) {
                if ($activeSession['attendance_type'] !== $attendanceType) {
                    $this->pdo->commit();
                    return [
                        'success' => false,
                        'message' => 'Close the active attendance session before opening another one.',
                    ];
                }

                $closeStmt = $this->pdo->prepare(
                    'UPDATE manual_attendance_sessions
                     SET closed_at = :closed_at
                     WHERE id = :session_id AND closed_at IS NULL'
                );
                $closeStmt->execute([
                    ':closed_at' => date('Y-m-d H:i:s'),
                    ':session_id' => $activeSession['id'],
                ]);
                $this->pdo->commit();

                return [
                    'success' => true,
                    'action' => 'closed',
                    'session_id' => (int) $activeSession['id'],
                    'message' => ucfirst(str_replace('_', ' ', $attendanceType)) . ' attendance is now closed.',
                ];
            }

            $sessionId = null;
            if ($this->hasAutoIncrementId('manual_attendance_sessions')) {
                $openStmt = $this->pdo->prepare(
                    'INSERT INTO manual_attendance_sessions
                        (teacher_id, section_id, attendance_type, attendance_date, opened_at)
                     VALUES (:teacher_id, :section_id, :attendance_type, :attendance_date, :opened_at)'
                );
                $openParameters = [
                    ':teacher_id' => $teacherId,
                    ':section_id' => (int) $sectionId,
                    ':attendance_type' => $attendanceType,
                    ':attendance_date' => date('Y-m-d'),
                    ':opened_at' => date('Y-m-d H:i:s'),
                ];
                $openStmt->execute($openParameters);
            } else {
                $sessionId = $this->getNextManualAttendanceId(
                    'manual_attendance_session_sequence',
                    'manual_attendance_sessions'
                );
                $openStmt = $this->pdo->prepare(
                    'INSERT INTO manual_attendance_sessions
                        (id, teacher_id, section_id, attendance_type, attendance_date, opened_at)
                     VALUES (:id, :teacher_id, :section_id, :attendance_type, :attendance_date, :opened_at)'
                );
                $openStmt->bindValue(':id', $sessionId, PDO::PARAM_INT);
                $openStmt->bindValue(':teacher_id', $teacherId, PDO::PARAM_INT);
                $openStmt->bindValue(':section_id', (int) $sectionId, PDO::PARAM_INT);
                $openStmt->bindValue(':attendance_type', $attendanceType, PDO::PARAM_STR);
                $openStmt->bindValue(':attendance_date', date('Y-m-d'), PDO::PARAM_STR);
                $openStmt->bindValue(':opened_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
                $openStmt->execute();
            }
            if ($sessionId === null) {
                $sessionId = (int) $this->pdo->lastInsertId();
            }
            $this->pdo->commit();

            return [
                'success' => true,
                'action' => 'opened',
                'session_id' => $sessionId,
                'message' => ucfirst(str_replace('_', ' ', $attendanceType)) . ' attendance is now open. Students may enter their LRN.',
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function closeManualAttendanceSession(int $sessionId, int $teacherId): bool
    {
        $closeStmt = $this->pdo->prepare(
            'UPDATE manual_attendance_sessions
             SET closed_at = :closed_at
             WHERE id = :session_id
               AND teacher_id = :teacher_id
               AND closed_at IS NULL'
        );
        $closeStmt->execute([
            ':closed_at' => date('Y-m-d H:i:s'),
            ':session_id' => $sessionId,
            ':teacher_id' => $teacherId,
        ]);

        return $closeStmt->rowCount() > 0;
    }

    public function recordFingerprintAttendance(int $teacherId, int $fingerprintId): array
    {
        if ($fingerprintId < 1 || $fingerprintId > 127) {
            return ['success' => false, 'message' => 'The scanner reported an invalid fingerprint ID.'];
        }

        $sessionStmt = $this->pdo->prepare(
            'SELECT id, section_id, attendance_type, attendance_date
             FROM manual_attendance_sessions
             WHERE teacher_id = :teacher_id AND closed_at IS NULL
             ORDER BY id DESC
             LIMIT 1'
        );
        $sessionStmt->execute([':teacher_id' => $teacherId]);
        $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);
        if (!$session) {
            return ['success' => false, 'message' => 'Open a time-in or time-out session before scanning.'];
        }
        if ($session['attendance_date'] !== date('Y-m-d')) {
            $expireStmt = $this->pdo->prepare(
                'UPDATE manual_attendance_sessions
                 SET closed_at = :closed_at
                 WHERE id = :session_id AND closed_at IS NULL'
            );
            $expireStmt->execute([
                ':closed_at' => date('Y-m-d H:i:s'),
                ':session_id' => $session['id'],
            ]);
            return [
                'success' => false,
                'message' => 'The previous day’s attendance session has expired. Enter the attendance code to open today’s session.',
            ];
        }

        $studentStmt = $this->pdo->prepare(
            'SELECT student_id
             FROM students
             WHERE fingerprint_id = :fingerprint_id
                AND section_id = :section_id
                AND is_active = 1
             LIMIT 1'
        );
        $studentStmt->execute([
            ':fingerprint_id' => $fingerprintId,
            ':section_id' => $session['section_id'],
        ]);
        $studentLrn = $studentStmt->fetchColumn();
        if ($studentLrn === false) {
            $mappingStmt = $this->pdo->prepare(
                'SELECT section_id, is_active
                 FROM students
                 WHERE fingerprint_id = :fingerprint_id
                 LIMIT 1'
            );
            $mappingStmt->execute([':fingerprint_id' => $fingerprintId]);
            $mapping = $mappingStmt->fetch(PDO::FETCH_ASSOC);
            if ($mapping && !(int) $mapping['is_active']) {
                return [
                    'success' => false,
                    'message' => 'Scanned fingerprint ID ' . $fingerprintId . ' belongs to an inactive student. Reactivate or re-enroll the student.',
                ];
            }
            if ($mapping) {
                return [
                    'success' => false,
                    'message' => 'Scanned fingerprint ID ' . $fingerprintId . ' is assigned to a different section than this attendance session.',
                ];
            }
            return [
                'success' => false,
                'message' => 'Scanned fingerprint ID ' . $fingerprintId . ' is not registered to a student. Enroll the student from the dashboard.',
            ];
        }

        return $this->recordManualAttendance(
            (int) $session['id'],
            (string) $studentLrn,
            'Fingerprint'
        );
    }

    public function recordManualAttendance(
        int $sessionId,
        string $studentLrn,
        string $attendanceMethod = 'Manual'
    ): array
    {
        $studentLrn = trim($studentLrn);
        if (preg_match('/^\d{1,19}$/D', $studentLrn) !== 1) {
            return ['success' => false, 'message' => 'Enter a valid student LRN.'];
        }
        if (!in_array($attendanceMethod, ['Manual', 'Fingerprint'], true)) {
            throw new InvalidArgumentException('Unsupported attendance method.');
        }

        $this->pdo->beginTransaction();
        try {
            $sessionStmt = $this->pdo->prepare(
                'SELECT id, teacher_id, section_id, attendance_date, attendance_type
                 FROM manual_attendance_sessions
                 WHERE id = :session_id AND closed_at IS NULL
                 FOR UPDATE'
            );
            $sessionStmt->execute([':session_id' => $sessionId]);
            $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);
            if (!$session) {
                $this->pdo->commit();
                return [
                    'success' => false,
                    'message' => 'There is no open attendance session. Enter the teacher\'s code first.',
                    'clear_session' => true,
                ];
            }
            if ($session['attendance_date'] !== date('Y-m-d')) {
                $expireStmt = $this->pdo->prepare(
                    'UPDATE manual_attendance_sessions
                     SET closed_at = :closed_at
                     WHERE id = :session_id AND closed_at IS NULL'
                );
                $expireStmt->execute([
                    ':closed_at' => date('Y-m-d H:i:s'),
                    ':session_id' => $session['id'],
                ]);
                $this->pdo->commit();
                return [
                    'success' => false,
                    'message' => 'The previous day’s attendance session has expired. Enter the attendance code to open today’s session.',
                    'clear_session' => true,
                ];
            }

            $studentStmt = $this->pdo->prepare(
                'SELECT student_id
                 FROM students
                 WHERE student_id = :student_id
                    AND section_id = :section_id
                    AND is_active = 1
                 LIMIT 1'
            );
            $studentStmt->execute([
                ':student_id' => $studentLrn,
                ':section_id' => $session['section_id'],
            ]);
            if (!$studentStmt->fetchColumn()) {
                $this->pdo->commit();
                return [
                    'success' => false,
                    'message' => 'This LRN does not belong to the adviser\'s assigned section.',
                ];
            }

            $recordId = null;
            if ($this->hasAutoIncrementId('manual_attendance_records')) {
                $insertStmt = $this->pdo->prepare(
                    'INSERT INTO manual_attendance_records
                        (session_id, teacher_id, section_id, student_id, attendance_date, attendance_type, recorded_at, attendance_method)
                     VALUES
                        (:session_id, :teacher_id, :section_id, :student_id, :attendance_date, :attendance_type, :recorded_at, :attendance_method)'
                );
                $insertParameters = [
                    ':session_id' => $session['id'],
                    ':teacher_id' => $session['teacher_id'],
                    ':section_id' => $session['section_id'],
                    ':student_id' => $studentLrn,
                    ':attendance_date' => $session['attendance_date'],
                    ':attendance_type' => $session['attendance_type'],
                    ':recorded_at' => date('Y-m-d H:i:s'),
                    ':attendance_method' => $attendanceMethod,
                ];
            } else {
                $recordId = $this->getNextManualAttendanceId(
                    'manual_attendance_record_sequence',
                    'manual_attendance_records'
                );
                $insertStmt = $this->pdo->prepare(
                    'INSERT INTO manual_attendance_records
                        (id, session_id, teacher_id, section_id, student_id, attendance_date, attendance_type, recorded_at, attendance_method)
                     VALUES
                        (:id, :session_id, :teacher_id, :section_id, :student_id, :attendance_date, :attendance_type, :recorded_at, :attendance_method)'
                );
                $insertStmt->bindValue(':id', $recordId, PDO::PARAM_INT);
                $insertStmt->bindValue(':session_id', (int) $session['id'], PDO::PARAM_INT);
                $insertStmt->bindValue(':teacher_id', (int) $session['teacher_id'], PDO::PARAM_INT);
                $insertStmt->bindValue(':section_id', (int) $session['section_id'], PDO::PARAM_INT);
                $insertStmt->bindValue(':student_id', $studentLrn, PDO::PARAM_STR);
                $insertStmt->bindValue(':attendance_date', $session['attendance_date'], PDO::PARAM_STR);
                $insertStmt->bindValue(':attendance_type', $session['attendance_type'], PDO::PARAM_STR);
                $insertStmt->bindValue(':recorded_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
                $insertStmt->bindValue(':attendance_method', $attendanceMethod, PDO::PARAM_STR);
            }
            try {
                if ($recordId === null) {
                    $insertStmt->execute($insertParameters);
                } else {
                    $insertStmt->execute();
                }
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'message' => 'Attendance for this student has already been recorded for this session type today.',
                    ];
                }
                throw $e;
            }

            $this->pdo->commit();
            $message = ucfirst(str_replace('_', ' ', $session['attendance_type']))
                . ' recorded for LRN ' . $studentLrn . '.';
            if ($attendanceMethod === 'Fingerprint') {
                $message = rtrim($message, '.') . ' via fingerprint.';
            }

            return ['success' => true, 'message' => $message];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getManualAttendanceLogs(int $teacherId, int $sectionId, string $date): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                s.student_id AS lrn,
                s.first_name,
                s.middle_name,
                s.last_name,
                s.suffix,
                s.gender,
                GROUP_CONCAT(DISTINCT r.attendance_method ORDER BY r.attendance_method SEPARATOR ', ') AS methods,
                MAX(CASE WHEN r.attendance_type = 'time_in' THEN r.recorded_at END) AS time_in,
                MAX(CASE WHEN r.attendance_type = 'time_out' THEN r.recorded_at END) AS time_out
             FROM manual_attendance_records r
             INNER JOIN students s
                ON s.student_id = r.student_id AND s.section_id = r.section_id
             WHERE r.teacher_id = :teacher_id
                AND r.section_id = :section_id
                AND r.attendance_date = :attendance_date
             GROUP BY s.student_id, s.first_name, s.middle_name, s.last_name, s.suffix, s.gender
             ORDER BY s.last_name, s.first_name"
        );
        $stmt->execute([
            ':teacher_id' => $teacherId,
            ':section_id' => $sectionId,
            ':attendance_date' => $date,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getManualAttendanceSummaries(int $teacherId, int $sectionId, ?string $date = null): array
    {
        $dateCondition = $date === null ? '' : ' AND attendance_date = :attendance_date';
        $stmt = $this->pdo->prepare(
            "SELECT
                attendance_date,
                COUNT(DISTINCT CASE WHEN attendance_type = 'time_in' THEN student_id END) AS present_count
             FROM manual_attendance_records
             WHERE teacher_id = :teacher_id
                AND section_id = :section_id{$dateCondition}
             GROUP BY attendance_date
             ORDER BY attendance_date DESC"
        );
        $parameters = [
            ':teacher_id' => $teacherId,
            ':section_id' => $sectionId,
        ];
        if ($date !== null) {
            $parameters[':attendance_date'] = $date;
        }
        $stmt->execute($parameters);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentAttendanceActivity(int $teacherId, int $sectionId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                r.recorded_at,
                r.attendance_type,
                s.student_id AS lrn,
                s.first_name,
                s.middle_name,
                s.last_name,
                s.suffix
             FROM manual_attendance_records r
             INNER JOIN students s
                ON s.student_id = r.student_id AND s.section_id = r.section_id
             WHERE r.teacher_id = :teacher_id
                AND r.section_id = :section_id
             ORDER BY r.recorded_at DESC, r.id DESC
             LIMIT 10"
        );
        $stmt->execute([
            ':teacher_id' => $teacherId,
            ':section_id' => $sectionId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSectionStudentCount(int $sectionId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM students WHERE section_id = :section_id AND is_active = 1'
        );
        $stmt->execute([':section_id' => $sectionId]);
        return (int) $stmt->fetchColumn();
    }

    public function getFingerprintStudents(int $sectionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT student_id AS lrn, first_name, middle_name, last_name, suffix, fingerprint_id
             FROM students
             WHERE section_id = :section_id AND is_active = 1
             ORDER BY last_name, first_name'
        );
        $stmt->execute([':section_id' => $sectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkFingerprintEnrollment(
        int $teacherId,
        string $studentLrn,
        ?int $fingerprintId = null
    ): array {
        $teacherStmt = $this->pdo->prepare(
            'SELECT section_id FROM teachers WHERE id = :teacher_id'
        );
        $teacherStmt->execute([':teacher_id' => $teacherId]);
        $sectionId = $teacherStmt->fetchColumn();
        if ($sectionId === false) {
            return ['success' => false, 'message' => 'Teacher account was not found.'];
        }

        $studentStmt = $this->pdo->prepare(
            'SELECT fingerprint_id
             FROM students
             WHERE student_id = :student_id
                AND section_id = :section_id
                AND is_active = 1'
        );
        $studentStmt->execute([
            ':student_id' => $studentLrn,
            ':section_id' => $sectionId,
        ]);
        $currentFingerprintId = $studentStmt->fetchColumn();
        if ($currentFingerprintId === false) {
            return ['success' => false, 'message' => 'Select an active student in your assigned section.'];
        }
        if ($currentFingerprintId !== false && $currentFingerprintId !== null) {
            return [
                'success' => true,
                'fingerprint_id' => (int) $currentFingerprintId,
                'message' => 'This student is already registered. Re-enrollment will replace ID ' . (int) $currentFingerprintId . '.',
            ];
        }
        if ($fingerprintId === null) {
            $fingerprintId = $this->findAvailableFingerprintId();
            if ($fingerprintId === null) {
                return ['success' => false, 'message' => 'All AS608 fingerprint slots from 1 to 127 are currently assigned.'];
            }
        }
        if ($fingerprintId < 1 || $fingerprintId > 127) {
            return ['success' => false, 'message' => 'Fingerprint ID must be from 1 to 127.'];
        }
        $mappingStmt = $this->pdo->prepare(
            'SELECT student_id
             FROM students
             WHERE fingerprint_id = :fingerprint_id
             LIMIT 1'
        );
        $mappingStmt->execute([':fingerprint_id' => $fingerprintId]);
        $mappedStudentLrn = $mappingStmt->fetchColumn();
        if ($mappedStudentLrn !== false && (string) $mappedStudentLrn !== $studentLrn) {
            return [
                'success' => false,
                'message' => 'Fingerprint ID ' . $fingerprintId . ' is already assigned to another student.',
            ];
        }

        $message = $currentFingerprintId === null
            ? 'Ready to enroll.'
            : 'This will keep or replace the student’s current mapping from ID ' . $currentFingerprintId . '.';
        return ['success' => true, 'fingerprint_id' => $fingerprintId, 'message' => $message];
    }

    public function getFingerprintCapacity(): array
    {
        $stmt = $this->pdo->query(
            'SELECT COUNT(DISTINCT fingerprint_id)
             FROM students
             WHERE is_active = 1
               AND fingerprint_id BETWEEN 1 AND 127'
        );
        $used = (int) $stmt->fetchColumn();
        return [
            'used' => $used,
            'available' => 127 - $used,
            'total' => 127,
        ];
    }

    public function getTeacherFingerprintIds(int $teacherId): array
    {
        $teacherStmt = $this->pdo->prepare(
            'SELECT section_id FROM teachers WHERE id = :teacher_id'
        );
        $teacherStmt->execute([':teacher_id' => $teacherId]);
        $sectionId = $teacherStmt->fetchColumn();
        if ($sectionId === false || $sectionId === null) {
            return ['success' => false, 'message' => 'Teacher account was not found.'];
        }

        $fingerprintStmt = $this->pdo->prepare(
            'SELECT DISTINCT fingerprint_id
             FROM students
             WHERE section_id = :section_id
               AND fingerprint_id BETWEEN 1 AND 127
             ORDER BY fingerprint_id'
        );
        $fingerprintStmt->execute([':section_id' => $sectionId]);
        $fingerprintIds = array_map('intval', $fingerprintStmt->fetchAll(PDO::FETCH_COLUMN));
        if ($fingerprintIds === []) {
            return [
                'success' => true,
                'fingerprint_ids' => [],
                'message' => 'No fingerprint records are assigned to students in your section.',
            ];
        }

        $placeholders = [];
        $parameters = [':section_id' => $sectionId];
        foreach ($fingerprintIds as $index => $fingerprintId) {
            $placeholder = ':fingerprint_id_' . $index;
            $placeholders[] = $placeholder;
            $parameters[$placeholder] = $fingerprintId;
        }
        $conflictStmt = $this->pdo->prepare(
            'SELECT fingerprint_id
             FROM students
            WHERE (section_id <> :section_id OR section_id IS NULL)
               AND fingerprint_id IN (' . implode(', ', $placeholders) . ')
             ORDER BY fingerprint_id'
        );
        $conflictStmt->execute($parameters);
        $conflictingIds = array_values(array_unique(
            array_map('intval', $conflictStmt->fetchAll(PDO::FETCH_COLUMN))
        ));
        if ($conflictingIds !== []) {
            return [
                'success' => false,
                'message' => 'Cannot clear fingerprint IDs also assigned outside your section: '
                    . implode(', ', $conflictingIds) . '. Resolve the duplicate assignments first.',
            ];
        }

        return [
            'success' => true,
            'fingerprint_ids' => $fingerprintIds,
            'message' => 'Found ' . count($fingerprintIds) . ' fingerprint record(s) for your section.',
        ];
    }

    public function clearTeacherFingerprintMappings(int $teacherId, array $fingerprintIds): array
    {
        $fingerprintIds = array_values(array_unique($fingerprintIds));
        foreach ($fingerprintIds as $fingerprintId) {
            if (!is_int($fingerprintId) || $fingerprintId < 1 || $fingerprintId > 127) {
                return ['success' => false, 'message' => 'Fingerprint IDs must be from 1 to 127.'];
            }
        }
        if ($fingerprintIds === []) {
            return [
                'success' => true,
                'message' => 'No fingerprint assignments needed to be cleared.',
                'cleared_count' => 0,
            ];
        }

        $this->pdo->beginTransaction();
        try {
            $teacherStmt = $this->pdo->prepare(
                'SELECT section_id FROM teachers WHERE id = :teacher_id FOR UPDATE'
            );
            $teacherStmt->execute([':teacher_id' => $teacherId]);
            $sectionId = $teacherStmt->fetchColumn();
            if ($sectionId === false || $sectionId === null) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Teacher account was not found.'];
            }

            $placeholders = [];
            $parameters = [':section_id' => $sectionId];
            foreach ($fingerprintIds as $index => $fingerprintId) {
                $placeholder = ':fingerprint_id_' . $index;
                $placeholders[] = $placeholder;
                $parameters[$placeholder] = $fingerprintId;
            }
            $conflictStmt = $this->pdo->prepare(
                'SELECT fingerprint_id
                 FROM students
                 WHERE (section_id <> :section_id OR section_id IS NULL)
                   AND fingerprint_id IN (' . implode(', ', $placeholders) . ')
                 FOR UPDATE'
            );
            $conflictStmt->execute($parameters);
            $conflictingIds = array_values(array_unique(
                array_map('intval', $conflictStmt->fetchAll(PDO::FETCH_COLUMN))
            ));
            if ($conflictingIds !== []) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Cannot clear fingerprint IDs also assigned outside your section: '
                        . implode(', ', $conflictingIds) . '. Resolve the duplicate assignments first.',
                ];
            }

            $updateStmt = $this->pdo->prepare(
                'UPDATE students
                 SET fingerprint_id = NULL
                 WHERE section_id = :section_id
                   AND fingerprint_id IN (' . implode(', ', $placeholders) . ')'
            );
            $updateStmt->execute($parameters);
            $clearedCount = $updateStmt->rowCount();
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Cleared ' . $clearedCount . ' fingerprint assignment(s) for your section.',
                'cleared_count' => $clearedCount,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function findAvailableFingerprintId(): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT fingerprint_id
             FROM students
             WHERE fingerprint_id IS NOT NULL
               AND is_active = 1'
        );
        $stmt->execute();

        $usedIds = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $usedId) {
            $usedIds[(int) $usedId] = true;
        }
        for ($id = 1; $id <= 127; $id++) {
            if (!isset($usedIds[$id])) {
                return $id;
            }
        }
        return null;
    }

    public function saveFingerprintEnrollment(
        int $teacherId,
        string $studentLrn,
        ?int $fingerprintId = null
    ): array {
        $this->pdo->beginTransaction();
        try {
            $teacherStmt = $this->pdo->prepare(
                'SELECT section_id FROM teachers WHERE id = :teacher_id FOR UPDATE'
            );
            $teacherStmt->execute([':teacher_id' => $teacherId]);
            $sectionId = $teacherStmt->fetchColumn();
            if ($sectionId === false) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Teacher account was not found.'];
            }

            $studentStmt = $this->pdo->prepare(
                'SELECT fingerprint_id
                 FROM students
                 WHERE student_id = :student_id
                    AND section_id = :section_id
                    AND is_active = 1
                 FOR UPDATE'
            );
            $studentStmt->execute([
                ':student_id' => $studentLrn,
                ':section_id' => $sectionId,
            ]);
            $currentFingerprintId = $studentStmt->fetchColumn();
            if ($currentFingerprintId === false) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Select an active student in your assigned section.'];
            }
            if ($fingerprintId === null) {
                $fingerprintId = $currentFingerprintId !== null
                    ? (int) $currentFingerprintId
                    : $this->findAvailableFingerprintId();
                if ($fingerprintId === null) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'All AS608 fingerprint slots from 1 to 127 are currently assigned.'];
                }
            }
            if ($fingerprintId < 1 || $fingerprintId > 127) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Fingerprint ID must be from 1 to 127.'];
            }
            $mappingStmt = $this->pdo->prepare(
                'SELECT student_id
                 FROM students
                 WHERE fingerprint_id = :fingerprint_id
                   AND student_id <> :student_id
                 LIMIT 1'
            );
            $mappingStmt->execute([
                ':fingerprint_id' => $fingerprintId,
                ':student_id' => $studentLrn,
            ]);
            if ($mappingStmt->fetchColumn() !== false) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Fingerprint ID ' . $fingerprintId . ' is already assigned to another student.'];
            }
            $updateStmt = $this->pdo->prepare(
                'UPDATE students
                 SET fingerprint_id = :fingerprint_id
                 WHERE student_id = :student_id
                    AND section_id = :section_id
                    AND is_active = 1'
            );
            $updateStmt->execute([
                ':fingerprint_id' => $fingerprintId,
                ':student_id' => $studentLrn,
                ':section_id' => $sectionId,
            ]);
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Fingerprint ID ' . $fingerprintId . ' registered for LRN ' . $studentLrn . '.',
            ];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return [
                    'success' => false,
                    'message' => 'Fingerprint ID ' . $fingerprintId . ' is already assigned to another student.',
                ];
            }
            throw $e;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getDashboardAttendanceMetrics(int $teacherId, int $sectionId, string $date): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                (SELECT COUNT(*)
                 FROM students
                 WHERE section_id = :section_id AND is_active = 1) AS total_students,
                (SELECT COUNT(DISTINCT r.student_id)
                 FROM manual_attendance_records r
                 INNER JOIN students s
                    ON s.student_id = r.student_id
                    AND s.section_id = r.section_id
                    AND s.is_active = 1
                 WHERE r.teacher_id = :teacher_id
                    AND r.section_id = :attendance_section_id
                    AND r.attendance_date = :attendance_date
                    AND r.attendance_type = 'time_in') AS present_students"
        );
        $stmt->execute([
            ':section_id' => $sectionId,
            ':teacher_id' => $teacherId,
            ':attendance_section_id' => $sectionId,
            ':attendance_date' => $date,
        ]);
        $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalStudents = (int) $metrics['total_students'];
        $presentStudents = (int) $metrics['present_students'];

        return [
            'totalStudents' => $totalStudents,
            'presentStudents' => $presentStudents,
            'absentStudents' => max(0, $totalStudents - $presentStudents),
        ];
    }



    public function getStudentsMasterlist(int $sectionId): array
    {
        try {
            $query = "SELECT
                        CASE WHEN s.fingerprint_id IS NULL THEN 'Unregistered' ELSE 'Registered' END AS status,
                        s.fingerprint_id,
                        s.student_id AS lrn,
                        s.first_name,
                        s.middle_name,
                        s.last_name,
                        s.suffix,
                        s.gender,
                        (
                            COALESCE(legacy.present_count, 0)
                            + COALESCE(manual_present.present_count, 0)
                        ) AS present_count,
                        (
                            COALESCE(legacy.absent_count, 0)
                            + COALESCE(manual_absent.absent_count, 0)
                        ) AS absent_count,
                        COALESCE(legacy.excused_count, 0) AS excused_count
                    FROM students s
                    LEFT JOIN (
                        SELECT
                            attendance.student_id,
                            COUNT(DISTINCT CASE WHEN attendance.attendance_status = 'Present'
                                THEN attendance.log_date END) AS present_count,
                            COUNT(DISTINCT CASE WHEN attendance.attendance_status = 'Absent'
                                AND NOT EXISTS (
                                    SELECT 1
                                    FROM attendance_logs excused
                                    WHERE excused.student_id = attendance.student_id
                                        AND excused.log_date = attendance.log_date
                                        AND excused.attendance_status = 'Excused'
                                )
                                THEN attendance.log_date END) AS absent_count,
                            COUNT(DISTINCT CASE WHEN attendance.attendance_status = 'Excused'
                                THEN attendance.log_date END) AS excused_count
                        FROM attendance_logs attendance
                        INNER JOIN students scoped_student
                            ON scoped_student.student_id = attendance.student_id
                        WHERE scoped_student.section_id = :legacy_section
                            AND scoped_student.is_active = 1
                        GROUP BY attendance.student_id
                    ) legacy ON legacy.student_id = s.student_id
                    LEFT JOIN (
                        SELECT r.student_id, COUNT(DISTINCT r.attendance_date) AS present_count
                        FROM manual_attendance_records r
                        WHERE r.attendance_type = 'time_in'
                            AND r.section_id = :manual_present_section
                            AND NOT EXISTS (
                                SELECT 1
                                FROM attendance_logs a
                                WHERE a.student_id = r.student_id
                                    AND a.log_date = r.attendance_date
                            )
                        GROUP BY r.student_id
                    ) manual_present ON manual_present.student_id = CAST(s.student_id AS CHAR)
                    LEFT JOIN (
                        SELECT s_abs.student_id,
                            COUNT(DISTINCT mas.attendance_date) AS absent_count
                        FROM students s_abs
                        INNER JOIN manual_attendance_sessions mas
                            ON mas.section_id = s_abs.section_id
                            AND mas.attendance_type = 'time_in'
                            AND mas.closed_at IS NOT NULL
                        WHERE s_abs.section_id = :manual_absent_section
                            AND s_abs.is_active = 1
                            AND NOT EXISTS (
                                SELECT 1
                                FROM manual_attendance_records r
                                WHERE r.section_id = s_abs.section_id
                                    AND r.student_id = CAST(s_abs.student_id AS CHAR)
                                    AND r.attendance_date = mas.attendance_date
                                    AND r.attendance_type = 'time_in'
                            )
                            AND NOT EXISTS (
                                SELECT 1
                                FROM attendance_logs a
                                WHERE a.student_id = s_abs.student_id
                                    AND a.log_date = mas.attendance_date
                            )
                        GROUP BY s_abs.student_id
                    ) manual_absent ON manual_absent.student_id = s.student_id
                    WHERE s.section_id = :section_id
                        AND s.is_active = 1
                    ORDER BY s.last_name, s.first_name";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                ':section_id' => $sectionId,
                ':legacy_section' => $sectionId,
                ':manual_present_section' => $sectionId,
                ':manual_absent_section' => $sectionId,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Masterlist error: " . $e->getMessage());
            return [];
        }
    }

    public function getStudentAbsences(string $studentId, int $sectionId): ?array
    {
        $studentStmt = $this->pdo->prepare(
            'SELECT 1
             FROM students
             WHERE student_id = :student_id AND section_id = :section_id
             LIMIT 1'
        );
        $studentStmt->execute([
            ':student_id' => $studentId,
            ':section_id' => $sectionId,
        ]);
        if (!$studentStmt->fetchColumn()) {
            return null;
        }

        $absenceStmt = $this->pdo->prepare(
            "SELECT log_date, attendance_status, remarks
             FROM (
                SELECT a.log_date,
                       CASE
                           WHEN COUNT(CASE WHEN a.attendance_status = 'Excused' THEN 1 END) > 0
                           THEN 'Excused'
                           ELSE 'Absent'
                       END AS attendance_status,
                       MAX(a.remarks) AS remarks
                FROM attendance_logs a
                WHERE a.student_id = :student_id
                    AND a.attendance_status IN ('Absent', 'Excused')
                GROUP BY a.log_date
             ) recorded_absences
             UNION ALL
             SELECT DISTINCT mas.attendance_date AS log_date,
                    'Absent' AS attendance_status,
                    'No time-in was recorded before attendance closed.' AS remarks
             FROM manual_attendance_sessions mas
             WHERE mas.section_id = :section_id
                AND mas.attendance_type = 'time_in'
                AND mas.closed_at IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1
                    FROM manual_attendance_records r
                    WHERE r.section_id = mas.section_id
                        AND r.student_id = :record_student_id
                        AND r.attendance_date = mas.attendance_date
                        AND r.attendance_type = 'time_in'
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM attendance_logs a
                    WHERE a.student_id = :log_student_id
                        AND a.log_date = mas.attendance_date
                )
             ORDER BY log_date DESC"
        );
        $absenceStmt->execute([
            ':student_id' => $studentId,
            ':section_id' => $sectionId,
            ':record_student_id' => $studentId,
            ':log_student_id' => $studentId,
        ]);
        return $absenceStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStudentAbsenceStatus(
        string $studentId,
        int $sectionId,
        string $date,
        string $status,
        ?string $remarks
    ): bool {
        $hasLogIdLock = false;
        $this->pdo->beginTransaction();

        try {
            $studentStmt = $this->pdo->prepare(
                'SELECT student_id
                 FROM students
                 WHERE student_id = :student_id AND section_id = :section_id
                 FOR UPDATE'
            );
            $studentStmt->execute([
                ':student_id' => $studentId,
                ':section_id' => $sectionId,
            ]);
            if (!$studentStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return false;
            }

            $logsStmt = $this->pdo->prepare(
                'SELECT log_id, attendance_status
                 FROM attendance_logs
                 WHERE student_id = :student_id AND log_date = :log_date
                 FOR UPDATE'
            );
            $logsStmt->execute([
                ':student_id' => $studentId,
                ':log_date' => $date,
            ]);
            $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

            $absenceLogs = array_filter(
                $logs,
                static fn (array $log): bool => in_array(
                    $log['attendance_status'],
                    ['Absent', 'Excused'],
                    true
                )
            );

            if ($absenceLogs !== []) {
                $updateStmt = $this->pdo->prepare(
                    "UPDATE attendance_logs
                     SET attendance_status = :attendance_status, remarks = :remarks
                     WHERE student_id = :student_id
                        AND log_date = :log_date
                        AND attendance_status IN ('Absent', 'Excused')"
                );
                $updateStmt->execute([
                    ':attendance_status' => $status,
                    ':remarks' => $remarks,
                    ':student_id' => $studentId,
                    ':log_date' => $date,
                ]);
                $this->pdo->commit();
                return true;
            }

            if ($logs !== []) {
                $this->pdo->rollBack();
                return false;
            }

            $sessionStmt = $this->pdo->prepare(
                "SELECT id
                 FROM manual_attendance_sessions
                 WHERE section_id = :section_id
                    AND attendance_date = :attendance_date
                    AND attendance_type = 'time_in'
                    AND closed_at IS NOT NULL
                 LIMIT 1
                 FOR UPDATE"
            );
            $sessionStmt->execute([
                ':section_id' => $sectionId,
                ':attendance_date' => $date,
            ]);
            if (!$sessionStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return false;
            }

            $recordStmt = $this->pdo->prepare(
                "SELECT 1
                 FROM manual_attendance_records
                 WHERE section_id = :section_id
                    AND student_id = :student_id
                    AND attendance_date = :attendance_date
                    AND attendance_type = 'time_in'
                 LIMIT 1"
            );
            $recordStmt->execute([
                ':section_id' => $sectionId,
                ':student_id' => $studentId,
                ':attendance_date' => $date,
            ]);
            if ($recordStmt->fetchColumn()) {
                $this->pdo->rollBack();
                return false;
            }

            $lockStmt = $this->pdo->query("SELECT GET_LOCK('attendance_logs_log_id', 5)");
            if ((int) $lockStmt->fetchColumn() !== 1) {
                throw new RuntimeException('Unable to reserve an attendance log ID.');
            }
            $hasLogIdLock = true;

            $nextLogId = (int) $this->pdo->query(
                'SELECT COALESCE(MAX(log_id), 0) + 1 FROM attendance_logs'
            )->fetchColumn();
            $insertStmt = $this->pdo->prepare(
                'INSERT INTO attendance_logs
                    (log_id, student_id, log_date, attendance_status, remarks)
                 VALUES
                    (:log_id, :student_id, :log_date, :attendance_status, :remarks)'
            );
            $insertStmt->execute([
                ':log_id' => $nextLogId,
                ':student_id' => $studentId,
                ':log_date' => $date,
                ':attendance_status' => $status,
                ':remarks' => $remarks,
            ]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        } finally {
            if ($hasLogIdLock) {
                $this->pdo->query("SELECT RELEASE_LOCK('attendance_logs_log_id')");
            }
        }
    }

    /**
     * Get monthly daily attendance averages for the section's active students.
     */
    public function getMonthlyAttendanceAnalytics(
        int $sectionId,
        int $monthsBack = 12,
        bool $excusedCountsAsAbsent = false
    ): array {
        $monthsBack = max(1, min(12, $monthsBack));

        $today = new DateTimeImmutable('today'); // uses the Asia/Manila default timezone
        $firstMonth = $today->modify('first day of this month')
            ->modify('-' . ($monthsBack - 1) . ' months');
        $start = $firstMonth->format('Y-m-d');
        $end = $today->format('Y-m-d');

        $months = [];
        for ($i = 0; $i < $monthsBack; $i++) {
            $month = $firstMonth->modify('+' . $i . ' months');
            $months[$month->format('Y-m')] = [
                'label'   => $month->format('M Y'),
                'days'    => [],
                'present' => ['M' => 0, 'F' => 0],
                'absent'  => ['M' => 0, 'F' => 0],
            ];
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                d.school_day,
                LOWER(TRIM(s.gender)) AS gender,
                (
                    EXISTS (
                        SELECT 1
                        FROM manual_attendance_records r
                        INNER JOIN manual_attendance_sessions rs
                            ON rs.id = r.session_id
                            AND rs.section_id = r.section_id
                            AND rs.attendance_date = r.attendance_date
                            AND rs.attendance_type = 'time_in'
                            AND rs.closed_at IS NOT NULL
                        WHERE r.student_id = CAST(s.student_id AS CHAR)
                            AND r.section_id = s.section_id
                            AND r.attendance_date = d.school_day
                            AND r.attendance_type = 'time_in'
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM attendance_logs ap
                        WHERE ap.student_id = s.student_id
                            AND ap.log_date = d.school_day
                            AND ap.attendance_status = 'Present'
                    )
                ) AS is_present,
                EXISTS (
                    SELECT 1
                    FROM attendance_logs ax
                    WHERE ax.student_id = s.student_id
                        AND ax.log_date = d.school_day
                        AND ax.attendance_status = 'Excused'
                ) AS is_excused
             FROM (
                SELECT mas.attendance_date AS school_day
                FROM manual_attendance_sessions mas
                WHERE mas.section_id = :days_section_sessions
                    AND mas.attendance_type = 'time_in'
                    AND mas.closed_at IS NOT NULL
                    AND mas.attendance_date BETWEEN :days_start_sessions AND :days_end_sessions
                UNION
                SELECT a1.log_date AS school_day
                FROM attendance_logs a1
                INNER JOIN students s1
                    ON s1.student_id = a1.student_id
                    AND s1.section_id = :days_section_logs
                    AND s1.is_active = 1
                WHERE a1.attendance_status IN ('Present', 'Absent', 'Excused')
                    AND a1.log_date BETWEEN :days_start_logs AND :days_end_logs
             ) d
             LEFT JOIN students s
                ON s.section_id = :student_section
                AND s.is_active = 1
             ORDER BY d.school_day"
        );
        $stmt->execute([
            ':days_section_sessions' => $sectionId,
            ':days_start_sessions'   => $start,
            ':days_end_sessions'     => $end,
            ':days_section_logs'    => $sectionId,
            ':days_start_logs'      => $start,
            ':days_end_logs'        => $end,
            ':student_section'      => $sectionId,
        ]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ym = substr($row['school_day'], 0, 7);
            if (!isset($months[$ym])) {
                continue;
            }

            $months[$ym]['days'][$row['school_day']] = true;

            $genderKey = match ($row['gender']) {
                'male'   => 'M',
                'female' => 'F',
                default  => null,
            };
            if ($genderKey === null) {
                continue;
            }

            if ((int) $row['is_present'] === 1) {
                $months[$ym]['present'][$genderKey]++;
                continue;
            }
            if ((int) $row['is_excused'] === 1 && !$excusedCountsAsAbsent) {
                continue;
            }
            $months[$ym]['absent'][$genderKey]++;
        }

        $result = [
            'months'        => [],
            'schoolDays'    => [],
            'presentMale'   => [],
            'presentFemale' => [],
            'absentMale'    => [],
            'absentFemale'  => [],
        ];
        $totals = ['days' => 0, 'pm' => 0, 'pf' => 0, 'am' => 0, 'af' => 0];

        foreach ($months as $month) {
            $days = count($month['days']);
            $result['months'][] = $month['label'];
            $result['schoolDays'][] = $days;

            $result['presentMale'][]   = $days > 0 ? round($month['present']['M'] / $days, 1) : null;
            $result['presentFemale'][] = $days > 0 ? round($month['present']['F'] / $days, 1) : null;
            $result['absentMale'][]    = $days > 0 ? round($month['absent']['M'] / $days, 1) : null;
            $result['absentFemale'][]  = $days > 0 ? round($month['absent']['F'] / $days, 1) : null;

            $totals['days'] += $days;
            $totals['pm'] += $month['present']['M'];
            $totals['pf'] += $month['present']['F'];
            $totals['am'] += $month['absent']['M'];
            $totals['af'] += $month['absent']['F'];
        }

        // Overall averages are weighted by school days, not an average of monthly averages.
        $overall = static fn(int $sum): ?float =>
            $totals['days'] > 0 ? round($sum / $totals['days'], 1) : null;

        $result['overall'] = [
            'schoolDays'    => $totals['days'],
            'presentMale'   => $overall($totals['pm']),
            'presentFemale' => $overall($totals['pf']),
            'absentMale'    => $overall($totals['am']),
            'absentFemale'  => $overall($totals['af']),
        ];

        return $result;
    }
}
?>