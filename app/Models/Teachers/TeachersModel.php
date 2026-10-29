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
        string $assignedGradeLevel, 
        string $advisorySection
    ): array
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
            return ['success' => false, 'message' => 'This email is already registered. Please use a different email.'];
        }
        
        // Cjheck if unique ID already exists
        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM teachers WHERE user_id = ?");
        $checkStmt->execute([$uniqueId]);
        if ($checkStmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Failed to generate a Unique ID. Please try again.'];
        }

        try {
            $query = "INSERT INTO teachers (user_id, role, full_name, first_name, last_name, email, password, assigned_grade_level, advisory_section) VALUES (:user_id, :role, :full_name, :first_name, :last_name, :email, :password, :assigned_grade_level, :advisory_section)";
           
            $stmt = $this->pdo->prepare($query);
           
            $stmt->bindParam(':user_id', $uniqueId, PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->bindParam(':full_name', $fullName, PDO::PARAM_STR);
            $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':assigned_grade_level', $assignedGradeLevel, PDO::PARAM_STR);
            $stmt->bindParam(':advisory_section', $advisorySection, PDO::PARAM_STR);

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
            return ['success' => false, 'message' => 'Unable to create the account. The email may already be registered. Please contact support.'];
        }

        return ['success' => false, 'message' => 'Unable to create the account. Please try again.'];
    }



    public function handleLogin(string $loginInput, string $password): array
        {
            $stmt = $this->pdo->prepare("SELECT * FROM teachers WHERE user_id = ? OR full_name = ?");
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
}
?>