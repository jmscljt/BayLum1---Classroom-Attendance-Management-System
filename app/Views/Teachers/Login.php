<?php
// app/Views/Teachers/login.php
// Expects $data array with keys: message, message_type, auth_message, auth_message_type
if (!isset($base_url)) {
    $base_url = '';
}
$message = $data['message'] ?? $_SESSION['auth_message'] ?? '';
$message_type = $data['message_type'] ?? $_SESSION['auth_message_type'] ?? '';
unset($_SESSION['auth_message'], $_SESSION['auth_message_type']);
?>
<!DOCTYPE html>
<html lang="en" class="auth-document">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="/images/BL1-Logo.png?v=2">
    <link rel="stylesheet" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/public/css/teachers_style.css">
    <title>BL1ES - Login</title>
</head>
<body class="auth-page login-page">
    <div class="side-cover">
        <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Cover1.png" alt="Cover 1" class="cover-image cover1">
        <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Cover2.png" alt="Cover 2" class="cover-image cover2">
        <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Cover3.png" alt="Cover 3" class="cover-image cover3">
        <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Cover4.png" alt="Cover 4" class="cover-image cover4">
        <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Cover5.png" alt="Cover 5" class="cover-image cover5">
        <div class="gradient-overlay"></div>
    </div>

    <div class="form-wrapper">
        <div class="form-box login">
            <img src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/images/BL1-Logo.png" alt="Logo" class="logo">
            <div class="login-card">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>
                
                <h1>Login</h1>
                <form action="index.php?page=login" method="POST">
                    <div class="input-group">
                        <label for="user_id">Username or Unique ID</label>
                        <input type="text" id="user_id" name="user_id" placeholder="Enter your username or unique ID" required>
                    </div>

                    <div class="input-group">
                        <label for="user_password">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="user_password" name="user_password" placeholder="Enter your password" required>
                            <button type="button" class="password-visibility-toggle" aria-controls="user_password" aria-label="Show password" aria-pressed="false" data-password-label="password">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-btn">Login</button>

                    <a href="index.php?page=signup" class="signup-link">I don't have an account yet.</a>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>/public/js/passwordVisibility.js"></script>
    <script src="/public/js/alertTimer.js"></script>
</body>
</html>