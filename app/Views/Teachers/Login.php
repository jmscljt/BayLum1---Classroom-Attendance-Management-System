<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <div class="form-wrapper">
        <div class="form-box login">
            <div class="login-card">
                <h2>Login</h2>
                <form action="index.php?page=login" method="POST">

                    <div class="input-group">
                        <input type="text" id="user_id" name="user_id" placeholder=" " required>
                        <label for="user_id">User ID or Email</label>
                    </div>

                    <div class="input-group">
                        <input type="password" id="user_password" name="user_password" placeholder=" " required>
                        <label for="user_password">Password</label>
                    </div>

                    <button type="submit" class="login-btn">Login</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>