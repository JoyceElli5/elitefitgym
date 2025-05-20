<?php
// Start session
session_start();
require_once __DIR__ . '/db_connect.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['forgot_error'] = 'Please enter a valid email address.';
        header('Location: forgot_password.php');
        exit;
    }
    try {
        $conn = connectDB();
        $stmt = $conn->prepare('SELECT id, name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $_SESSION['forgot_error'] = 'No account found with that email.';
            header('Location: forgot_password.php');
            exit;
        }
        $userId = $user['id'];
        $name = $user['name'];
        // Generate OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        // Delete old OTPs for this user/email (for password reset)
        $del = $conn->prepare('DELETE FROM otp_codes WHERE user_id = ? AND email = ?');
        $del->execute([$userId, $email]);
        // Insert new OTP
        $ins = $conn->prepare('INSERT INTO otp_codes (user_id, email, otp_code, expires_at, verified) VALUES (?, ?, ?, ?, 0)');
        $ins->execute([$userId, $email, $otp, $expires]);
        // Send OTP email
        function sendOTPEmail($email, $name, $otp) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ellijoyce7@gmail.com';
                $mail->Password = 'eyakqbgxylybotcu';
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;
                $mail->setFrom('ellijoyce7@gmail.com', 'ELITEFIT GYM');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset OTP - EliteFit Gym';
                $mail->Body = '<p>Dear ' . htmlspecialchars($name) . ',</p><p>Your OTP for password reset is: <b>' . $otp . '</b></p><p>This code will expire in 15 minutes.</p><p>If you did not request this, please ignore this email.</p>';
                return $mail->send();
            } catch (Exception $e) {
                error_log('PHPMailer Error (Forgot Password): ' . $mail->ErrorInfo);
                return false;
            }
        }
        if (sendOTPEmail($email, $name, $otp)) {
            $_SESSION['pending_reset_user_id'] = $userId;
            $_SESSION['pending_reset_email'] = $email;
            $_SESSION['forgot_message'] = 'An OTP has been sent to your email.';
            header('Location: reset_password_otp.php');
            exit;
        } else {
            $_SESSION['forgot_error'] = 'Failed to send OTP. Please try again.';
            header('Location: forgot_password.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['forgot_error'] = 'An error occurred. Please try again.';
        error_log('Forgot password DB error: ' . $e->getMessage());
        header('Location: forgot_password.php');
        exit;
    }
} else {
    header('Location: forgot_password.php');
    exit;
}
