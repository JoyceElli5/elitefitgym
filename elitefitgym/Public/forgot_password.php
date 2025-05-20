<?php
// Start session
session_start();
$message = isset($_SESSION['forgot_message']) ? $_SESSION['forgot_message'] : '';
$error = isset($_SESSION['forgot_error']) ? $_SESSION['forgot_error'] : '';
unset($_SESSION['forgot_message'], $_SESSION['forgot_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - EliteFit Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #121212; color: #fff; min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .container { background: #232323; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); padding: 32px; width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #ff4d4d; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; }
        input[type=email] { width: 100%; padding: 10px; border-radius: 5px; border: none; font-size: 1rem; }
        .btn { width: 100%; background: #ff4d4d; color: #fff; border: none; padding: 12px; border-radius: 5px; font-size: 1rem; cursor: pointer; }
        .btn:hover { background: #e84343; }
        .message { color: #28a745; margin-bottom: 10px; text-align: center; }
        .error { color: #ff4d4d; margin-bottom: 10px; text-align: center; }
        .back-link { display: block; text-align: center; margin-top: 18px; color: #fff; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <?php if ($message): ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form action="forgot_password_process.php" method="post">
            <div class="form-group">
                <label for="email">Enter your registered email address:</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <button type="submit" class="btn">Send OTP</button>
        </form>
        <a href="login.php" class="back-link">Back to Login</a>
    </div>
</body>
</html>
