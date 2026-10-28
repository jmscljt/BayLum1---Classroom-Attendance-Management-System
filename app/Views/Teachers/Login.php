<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/css/teachers_style.css">
    <title>Login</title>
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
        <div class="form-box login">
            <img src="/images/BL1-Logo.png" alt="Logo" class="logo">
            <div class="login-card">
                <h1>Login</h1>
                <form action="index.php?page=login" method="POST">

                    <div class="input-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" class="custom-select">
                            <option value="adviser" selected>Adviser</option>
                            <option value="subject_teacher">Subject Teacher</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="user_id">Username or Unique ID</label>
                        <input type="text" id="user_id" name="user_id" placeholder="Enter your username or unique ID" required>
                    </div>

                    <div class="input-group">
                        <label for="user_password">Password</label>
                        <input type="password" id="user_password" name="user_password" placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="login-btn">Login</button>

                    <a href="signup.php" class="signup-link">I don't have an account yet.</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>