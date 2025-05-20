<?php
session_start();
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['pending_reset_user_id'], $_SESSION['pending_reset_email'])) {
        $_SESSION['otp_error'] = 'Session expired. Please start over.';
        header('Location: forgot_password.php');
        exit;
    }
    $userId = $_SESSION['pending_reset_user_id'];
    $email = $_SESSION['pending_reset_email'];
    $otp = trim($_POST['otp']);
    if (!preg_match('/^\d{6}$/', $otp)) {
        $_SESSION['otp_error'] = 'Invalid OTP format.';
        header('Location: reset_password_otp.php');
        exit;
    }
    try {
        $conn = connectDB();
        $stmt = $conn->prepare('SELECT id, expires_at, verified FROM otp_codes WHERE user_id = ? AND email = ? AND otp_code = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$userId, $email, $otp]);
        $otpRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$otpRow) {
            $_SESSION['otp_error'] = 'Incorrect OTP. Please try again.';
            header('Location: reset_password_otp.php');
            exit;
        }
        // Check if OTP is expired
        $now = date('Y-m-d H:i:s');
        if ($otpRow['expires_at'] < $now) {
            $_SESSION['otp_error'] = 'OTP has expired. Please request a new one.';
            header('Location: forgot_password.php');
            exit;
        }
        // Check if already verified (optional, for extra security)
        if ($otpRow['verified']) {
            $_SESSION['otp_error'] = 'This OTP has already been used.';
            header('Location: forgot_password.php');
            exit;
        }
        // Mark OTP as verified
        $update = $conn->prepare('UPDATE otp_codes SET verified = 1 WHERE id = ?');
        $update->execute([$otpRow['id']]);
        $_SESSION['otp_verified'] = true;
        $_SESSION['otp_message'] = 'OTP verified. Please set your new password.';
        header('Location: reset_password.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['otp_error'] = 'An error occurred. Please try again.';
        error_log('OTP verification error: ' . $e->getMessage());
        header('Location: reset_password_otp.php');
        exit;
    }
} else {
    header('Location: forgot_password.php');
    exit;
}
