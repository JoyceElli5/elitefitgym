<?php
session_start();
$message = isset($_SESSION['otp_message']) ? $_SESSION['otp_message'] : '';
$error = isset($_SESSION['otp_error']) ? $_SESSION['otp_error'] : '';
unset($_SESSION['otp_message'], $_SESSION['otp_error']);

// Check if user has started the forgot password process
if (!isset($_SESSION['pending_reset_email'])) {
    header('Location: forgot_password.php');
    exit;
}
$email = $_SESSION['pending_reset_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter OTP - EliteFit Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #121212; color: #fff; min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .container { background: #232323; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); padding: 32px; width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #ff4d4d; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; }
        input[type=text] { width: 100%; padding: 10px; border-radius: 5px; border: none; font-size: 1rem; letter-spacing: 4px; text-align: center; }
        .btn { width: 100%; background: #ff4d4d; color: #fff; border: none; padding: 12px; border-radius: 5px; font-size: 1rem; cursor: pointer; }
        .btn:hover { background: #e84343; }
        .message { color: #28a745; margin-bottom: 10px; text-align: center; }
        .error { color: #ff4d4d; margin-bottom: 10px; text-align: center; }
        .back-link { display: block; text-align: center; margin-top: 18px; color: #fff; text-decoration: underline; }
        .email-info { text-align: center; margin-bottom: 18px; font-size: 0.98rem; color: #ccc; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Enter OTP</h2>
        <div class="email-info">An OTP has been sent to your email:<br><b><?php echo htmlspecialchars($email); ?></b></div>
        <?php if ($message): ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form action="reset_password_otp_process.php" method="post">
            <div class="form-group">
                <label for="otp">Enter 6-digit OTP:</label>
                <input type="text" id="otp" name="otp" maxlength="6" pattern="\d{6}" required autofocus autocomplete="one-time-code">
            </div>
            <button type="submit" class="btn">Verify OTP</button>
        </form>
        <a href="forgot_password.php" class="back-link">Back to Forgot Password</a>
    </div>
</body>
</html>
