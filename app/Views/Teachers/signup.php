<?php
// app/Views/Teachers/signup.php
// Expects $data array with keys: message, message_type, auth_message, auth_message_type
$message = $data['message'] ?? $_SESSION['auth_message'] ?? '';
$message_type = $data['message_type'] ?? $_SESSION['auth_message_type'] ?? '';
unset($_SESSION['auth_message'], $_SESSION['auth_message_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/teachers_style.css">
    <title>BL1ES - Sign Up</title>
</head>
<body>
    <div class="side-cover">
        <img src="/images/BL1-Cover1.png" alt="Cover 1" class="cover-image cover1">
        <img src="/images/BL1-Cover2.png" alt="Cover 2" class="cover-image cover2">
        <img src="/images/BL1-Cover3.png" alt="Cover 3" class="cover-image cover3">
        <img src="/images/BL1-Cover4.png" alt="Cover 4" class="cover-image cover4">
        <img src="/images/BL1-Cover5.png" alt="Cover 5" class="cover-image cover5">
        <div class="gradient-overlay"></div>
    </div>

    <div class="form-wrapper">
        <div class="form-box signup">
            <img src="/images/BL1-Logo.png" alt="Logo" class="logo">
            <div class="signup-card">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <h1>Sign Up</h1>
                <form action="index.php?page=signup" method="POST">

                    <div class="input-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" class="custom-select">
                            <option selected>Adviser</option>
                            <option >Subject Teacher</option>
                        </select>
                    </div>

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

                    <div class="input-row">
                        <div class="input-group">
                            <label for="assigned_grade_level">Assigned Grade Level</label>
                            <select id="assigned_grade_level" name="assigned_grade_level" class="custom-select">
                                <option selected>Grade 4</option>
                                <option >Grade 5</option>
                                <option >Grade 6</option>
                            </select>
                        </div>

                        <div class="input-group">
                            <label for="advisory_section">Advisory Section</label>
                            <select id="advisory_section" name="advisory_section" class="custom-select">
                                <option selected>Mabait</option>
                                <option >Magalang</option>
                                <option >Masipag</option>
                                <option >Masayahin</option>
                                <option >Matapat</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="signup-btn">Create Account</button>

                    <a href="index.php?page=login" class="signup-link">I already have an account.</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>