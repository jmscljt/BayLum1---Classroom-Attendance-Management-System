<?php
class TeachersModel{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handleSignup(string $uniqueId, string $role, string $firstName, string $lastName, string $email, string $password, string $confirmPassword, string $assignedGradeLevel, string $assignedSection): array
    {
        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match!'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long!'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $fullName = trim($firstName . ' ' . $lastName);

        // Check if email already exists
        $emailCheck = $this->pdo->prepare("SELECT COUNT(*) FROM teachers WHERE email = ?");
        $emailCheck->execute([$email]);
        if ($emailCheck->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'This email is already registered. Please use a different email or login.'];
        }

        

        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM teachers WHERE unique_id = ?");
        $checkStmt->execute([$uniqueId]);

        if ($checkStmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Failed to generate a Unique ID. Please try again.'];
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO teachers (unique_id, role, full_name, first_name, last_name, email, password, assigned_grade_level, assigned_section) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$uniqueId, $role, $fullName, $firstName, $lastName, $email, $hashedPassword, $assignedGradeLevel, $assignedSection])) {
                return [
                    'success' => true,
                    'unique_id' => $uniqueId,
                    'insert_id' => (int)$this->pdo->lastInsertId(),
                    'full_name' => $fullName,
                ];
            }
        } catch (PDOException $e) {
            // Log the error for debugging
            error_log('Signup error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to create the account. The email may already be registered. Please try a different email or contact support.'];
        }

        return ['success' => false, 'message' => 'Unable to create the account. Please try again.'];
    }
}
?>