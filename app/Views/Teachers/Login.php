<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>
    <div class="form-wrapper">

        <div class="form-box login">
            <div class="login-card">
                <img class="logo" src="images/BL1-Logo.png" alt="Bayan Luma 1 Elementary School logo">
                <h2>Login</h2>
 
                <form action="index.php?page=login" method="POST">
 
                    <div class="input-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="adviser">Adviser</option>
                            <option value="teacher">Subject Teacher</option>
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
                    <a class="signup-link" href="Signup.php">I don't have an account yet.</a>
                </form>
            </div>
        </div>
 
    </div>
</body>
</html>