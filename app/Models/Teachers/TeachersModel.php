<?php
class TeachersModel{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handleSignup(
        string $uniqueId, 
        string $role, 
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

        $sectionCheck = $this->pdo->prepare("SELECT COUNT(*) FROM sections WHERE section_id = ?");
        $sectionCheck->execute([$sectionId]);
        if ($sectionCheck->fetchColumn() === 0) {
            return ['success' => false, 'message' => 'Invalid section selected. Please choose a valid grade and section.'];
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
            $query = "INSERT INTO teachers (user_id, role, full_name, first_name, last_name, email, password, section_id) VALUES (:user_id, :role, :full_name, :first_name, :last_name, :email, :password, :section_id)";
       
            $stmt = $this->pdo->prepare($query);
        
            $stmt->bindParam(':user_id', $uniqueId, PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->bindParam(':full_name', $fullName, PDO::PARAM_STR);
            $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':section_id', $sectionId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'user_id' => $uniqueId,
                    'insert_id' => (int)$this->pdo->lastInsertId(),
                    'full_name' => $fullName,
                ];
            }
        } catch (PDOException $e) {
            // Log the error for debugging
            error_log('Signup error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to create the account. Please try again.'];
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



   public function addStudent(array $studentData): array
    {
        try {
            $studentId = trim($studentData['lrn'] ?? '');
            $firstName = trim($studentData['first_name'] ?? '');
            $middleName = trim($studentData['middle_name'] ?? null);
            $lastName = trim($studentData['last_name'] ?? '');
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

            $query = "INSERT INTO students (student_id, section_id, first_name, middle_name, last_name, gender) VALUES (:student_id, :section_id, :first_name, :middle_name, :last_name, :gender)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                ':student_id' => $studentId,
                ':section_id' => $sectionId,
                ':first_name' => $firstName,
                ':middle_name' => $middleName !== '' ? $middleName : null,
                ':last_name' => $lastName,
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
        foreach (['lrn', 'first_name', 'middle_name', 'last_name', 'gender'] as $field) {
            if (isset($studentData[$field]) && !is_string($studentData[$field])) {
                return ['success' => false, 'message' => 'Enter valid student details and select a gender.'];
            }
        }

        $studentId = trim($studentData['lrn'] ?? '');
        $firstName = trim($studentData['first_name'] ?? '');
        $middleName = trim($studentData['middle_name'] ?? '');
        $lastName = trim($studentData['last_name'] ?? '');
        $gender = trim($studentData['gender'] ?? '');

        if (
            preg_match('/^\d{1,19}$/D', $studentId) !== 1
            || $firstName === ''
            || $lastName === ''
            || !in_array($gender, ['Male', 'Female'], true)
        ) {
            return ['success' => false, 'message' => 'Enter valid student details and select a gender.'];
        }

        try {
            $this->pdo->beginTransaction();

            $studentStmt = $this->pdo->prepare(
                'SELECT student_id
                 FROM students
                 WHERE student_id = :student_id AND section_id = :section_id
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

            $updateStmt = $this->pdo->prepare(
                'UPDATE students
                 SET student_id = :new_student_id,
                     first_name = :first_name,
                     middle_name = :middle_name,
                     last_name = :last_name,
                     gender = :gender
                 WHERE student_id = :current_student_id AND section_id = :section_id'
            );
            $updateStmt->execute([
                ':new_student_id' => $studentId,
                ':first_name' => $firstName,
                ':middle_name' => $middleName !== '' ? $middleName : null,
                ':last_name' => $lastName,
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
            return ['success' => true, 'message' => 'Student updated successfully.'];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $errorInfo = $e->errorInfo;
            if ((int) ($errorInfo[1] ?? 0) === 1062) {
                return ['success' => false, 'message' => 'A student with this LRN already exists.'];
            }

            error_log('Update student error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to update the student right now. Please try again or contact the school administrator.',
            ];
        }
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
            if ($sectionId === false) {
                throw new RuntimeException('Teacher account was not found.');
            }

            $activeStmt = $this->pdo->prepare(
                'SELECT id, attendance_type
                 FROM manual_attendance_sessions
                 WHERE teacher_id = :teacher_id AND closed_at IS NULL
                 ORDER BY id DESC
                 LIMIT 1
                 FOR UPDATE'
            );
            $activeStmt->execute([':teacher_id' => $teacherId]);
            $activeSession = $activeStmt->fetch(PDO::FETCH_ASSOC);

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

            $openStmt = $this->pdo->prepare(
                'INSERT INTO manual_attendance_sessions
                    (teacher_id, section_id, attendance_type, attendance_date, opened_at)
                 VALUES (:teacher_id, :section_id, :attendance_type, :attendance_date, :opened_at)'
            );
            $openStmt->execute([
                ':teacher_id' => $teacherId,
                ':section_id' => (int) $sectionId,
                ':attendance_type' => $attendanceType,
                ':attendance_date' => date('Y-m-d'),
                ':opened_at' => date('Y-m-d H:i:s'),
            ]);
            $sessionId = (int) $this->pdo->lastInsertId();
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

    public function recordManualAttendance(int $sessionId, string $studentLrn): array
    {
        $studentLrn = trim($studentLrn);
        if (preg_match('/^\d{1,19}$/D', $studentLrn) !== 1) {
            return ['success' => false, 'message' => 'Enter a valid student LRN.'];
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

            $studentStmt = $this->pdo->prepare(
                'SELECT student_id
                 FROM students
                 WHERE student_id = :student_id AND section_id = :section_id
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

            $insertStmt = $this->pdo->prepare(
                'INSERT INTO manual_attendance_records
                    (session_id, teacher_id, section_id, student_id, attendance_date, attendance_type, recorded_at)
                 VALUES
                    (:session_id, :teacher_id, :section_id, :student_id, :attendance_date, :attendance_type, :recorded_at)'
            );
            try {
                $insertStmt->execute([
                    ':session_id' => $session['id'],
                    ':teacher_id' => $session['teacher_id'],
                    ':section_id' => $session['section_id'],
                    ':student_id' => $studentLrn,
                    ':attendance_date' => $session['attendance_date'],
                    ':attendance_type' => $session['attendance_type'],
                    ':recorded_at' => date('Y-m-d H:i:s'),
                ]);
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
            return [
                'success' => true,
                'message' => ucfirst(str_replace('_', ' ', $session['attendance_type'])) . ' recorded for LRN ' . $studentLrn . '.',
            ];
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
                s.gender,
                MAX(CASE WHEN r.attendance_type = 'time_in' THEN r.recorded_at END) AS time_in,
                MAX(CASE WHEN r.attendance_type = 'time_out' THEN r.recorded_at END) AS time_out
             FROM manual_attendance_records r
             INNER JOIN students s
                ON s.student_id = r.student_id AND s.section_id = r.section_id
             WHERE r.teacher_id = :teacher_id
                AND r.section_id = :section_id
                AND r.attendance_date = :attendance_date
             GROUP BY s.student_id, s.first_name, s.middle_name, s.last_name, s.gender
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
                sec.grade_level,
                sec.section_name
             FROM manual_attendance_records r
             INNER JOIN students s
                ON s.student_id = r.student_id AND s.section_id = r.section_id
             INNER JOIN sections sec ON sec.section_id = r.section_id
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
            'SELECT COUNT(*) FROM students WHERE section_id = :section_id'
        );
        $stmt->execute([':section_id' => $sectionId]);
        return (int) $stmt->fetchColumn();
    }

    public function getDashboardAttendanceMetrics(int $teacherId, int $sectionId, string $date): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                (SELECT COUNT(*)
                 FROM students
                 WHERE section_id = :section_id) AS total_advisory_students,
                (SELECT COUNT(DISTINCT student_id)
                 FROM manual_attendance_records
                 WHERE teacher_id = :teacher_id
                    AND section_id = :attendance_section_id
                    AND attendance_date = :attendance_date
                    AND attendance_type = 'time_in') AS present_students_advisory"
        );
        $stmt->execute([
            ':section_id' => $sectionId,
            ':teacher_id' => $teacherId,
            ':attendance_section_id' => $sectionId,
            ':attendance_date' => $date,
        ]);
        $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalAdvisoryStudents = (int) $metrics['total_advisory_students'];
        $presentStudentsAdvisory = (int) $metrics['present_students_advisory'];

        return [
            'totalAdvisoryStudents' => $totalAdvisoryStudents,
            'presentStudentsAdvisory' => $presentStudentsAdvisory,
            'absentStudentsAdvisory' => max(0, $totalAdvisoryStudents - $presentStudentsAdvisory),
            'totalAssignedStudents' => $totalAdvisoryStudents,
        ];
    }



    public function getStudentsMasterlist(int $sectionId): array
    {
        try {
            $query = "SELECT 
                        s.status,
                        s.student_id AS lrn,
                        s.first_name,
                        s.middle_name,
                        CONCAT(UPPER(LEFT(s.middle_name, 1)), '.') AS middle_initial,
                        s.last_name,
                        s.gender,
                        (
                            SELECT COUNT(DISTINCT a.log_date)
                            FROM attendance_logs a
                            WHERE a.student_id = s.student_id
                                AND a.attendance_status = 'Present'
                        ) + (
                            SELECT COUNT(DISTINCT r.attendance_date)
                            FROM manual_attendance_records r
                            WHERE r.student_id = CAST(s.student_id AS CHAR)
                                AND r.attendance_type = 'time_in'
                                AND NOT EXISTS (
                                    SELECT 1
                                    FROM attendance_logs a
                                    WHERE a.student_id = s.student_id
                                        AND a.log_date = r.attendance_date
                                )
                        ) AS present_count,
                        (
                            SELECT COUNT(DISTINCT a.log_date)
                            FROM attendance_logs a
                            WHERE a.student_id = s.student_id
                                AND a.attendance_status = 'Absent'
                                AND NOT EXISTS (
                                    SELECT 1
                                    FROM attendance_logs excused
                                    WHERE excused.student_id = s.student_id
                                        AND excused.log_date = a.log_date
                                        AND excused.attendance_status = 'Excused'
                                )
                        ) + (
                            SELECT COUNT(DISTINCT mas.attendance_date)
                            FROM manual_attendance_sessions mas
                            WHERE mas.section_id = s.section_id
                                AND mas.attendance_type = 'time_in'
                                AND mas.closed_at IS NOT NULL
                                AND NOT EXISTS (
                                    SELECT 1
                                    FROM manual_attendance_records r
                                    WHERE r.section_id = s.section_id
                                        AND r.student_id = CAST(s.student_id AS CHAR)
                                        AND r.attendance_date = mas.attendance_date
                                        AND r.attendance_type = 'time_in'
                                )
                                AND NOT EXISTS (
                                    SELECT 1
                                    FROM attendance_logs a
                                    WHERE a.student_id = s.student_id
                                        AND a.log_date = mas.attendance_date
                                )
                        ) AS absent_count,
                        (
                            SELECT COUNT(DISTINCT a.log_date)
                            FROM attendance_logs a
                            WHERE a.student_id = s.student_id
                                AND a.attendance_status = 'Excused'
                        ) AS excused_count
                    FROM students s
                    INNER JOIN sections sec ON s.section_id = sec.section_id
                    WHERE s.section_id = :section_id
                    ORDER BY s.last_name, s.first_name";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':section_id' => $sectionId]);
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
}
?>