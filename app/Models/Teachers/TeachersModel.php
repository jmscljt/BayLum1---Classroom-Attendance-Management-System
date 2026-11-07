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



    public function getStudentsMasterlist(int $sectionId): array
    {
        try {
            $query = "SELECT 
                        s.status,
                        s.student_id AS lrn,
                        s.first_name,
                        CONCAT(UPPER(LEFT(s.middle_name, 1)), '.') AS middle_initial,
                        s.last_name,
                        s.gender,
                        s.present_count,
                        s.absent_count,
                        s.excused_count
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
}
?>