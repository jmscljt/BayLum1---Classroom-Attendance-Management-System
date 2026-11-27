<?php
// app/Views/Teachers/signup.php
// Expects $data array with keys: message, message_type, auth_message, auth_message_type
$message = $data['message'] ?? $_SESSION['auth_message'] ?? '';
$message_type = $data['message_type'] ?? $_SESSION['auth_message_type'] ?? '';
unset($_SESSION['auth_message'], $_SESSION['auth_message_type']);


$gradesStmt = $pdo->query("SELECT DISTINCT grade_level FROM sections ORDER BY grade_level ASC");
$grades = $gradesStmt->fetchAll(PDO::FETCH_ASSOC);

$sectionsStmt = $pdo->query("SELECT section_id, grade_level, section_name FROM sections ORDER BY grade_level ASC, section_name ASC");
$sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="auth-document">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/public/css/teachers_style.css">
    <title>BL1ES - Sign Up</title>
</head>
<body class="auth-page signup-page">
    <div class="side-cover">
        <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Cover1.png" alt="Cover 1" class="cover-image cover1">
        <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Cover2.png" alt="Cover 2" class="cover-image cover2">
        <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Cover3.png" alt="Cover 3" class="cover-image cover3">
        <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Cover4.png" alt="Cover 4" class="cover-image cover4">
        <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Cover5.png" alt="Cover 5" class="cover-image cover5">
        <div class="gradient-overlay"></div>
    </div>

    <div class="form-wrapper">
        <div class="form-box signup">
            <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Logo.png" alt="Logo" class="logo">
            <div class="signup-card">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <h1>Sign Up</h1>
                <form action="index.php?page=signup" method="POST">

                    <div class="input-group">
                        <label for="unique_id">Unique ID (Generated Automatically)</label>
                        <input type="text" id="unique_id" name="unique_id" value="<?php echo 'BL1es-' . date('Y') . mt_rand(1000, 9999); ?>" readonly>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" required>
                        </div>

                        <div class="input-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                    </div>

                    <div class="input-row" id="advisory-container">
                        <div class="input-group">
                            <label for="assigned_grade_level">Assigned Grade Level</label>
                            <select id="assigned_grade_level" name="assigned_grade_level" class="custom-select" required>
                                <option value="" disabled selected>Select Grade Level</option>
                                <?php foreach ($grades as $grade): ?>
                                    <option value="<?= htmlspecialchars($grade['grade_level']) ?>">
                                        <?= htmlspecialchars($grade['grade_level']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label for="advisory_section">Advisory Section</label>
                            <select id="advisory_section" name="advisory_section" class="custom-select" required>
                                <option value="" disabled selected>Select Grade Level First</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="signup-btn">Create Account</button>

                    <a href="index.php?page=login" class="signup-link">I already have an account.</a>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        window.allSections = <?= json_encode($sections, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/public/js/gradeLevelSectionSignup.js"></script>
    <script src="/public/js/alertTimer.js"></script>
</body>
</html>