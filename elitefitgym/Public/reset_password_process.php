<?php
session_start();
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['otp_verified'], $_SESSION['pending_reset_user_id'], $_SESSION['pending_reset_email']) || !$_SESSION['otp_verified']) {
        $_SESSION['reset_error'] = 'Session expired. Please start over.';
        header('Location: forgot_password.php');
        exit;
    }
    $userId = $_SESSION['pending_reset_user_id'];
    $email = $_SESSION['pending_reset_email'];
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 6) {
        $_SESSION['reset_error'] = 'Password must be at least 6 characters.';
        header('Location: reset_password.php');
        exit;
    }
    if ($password !== $confirm) {
        $_SESSION['reset_error'] = 'Passwords do not match.';
        header('Location: reset_password.php');
        exit;
    }
    try {
        $conn = connectDB();
        // Hash the new password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // Update user's password
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ? AND email = ?');
        $stmt->execute([$hash, $userId, $email]);
        // Clean up OTP session and codes
        $del = $conn->prepare('DELETE FROM otp_codes WHERE user_id = ? AND email = ?');
        $del->execute([$userId, $email]);
        unset($_SESSION['otp_verified'], $_SESSION['pending_reset_user_id'], $_SESSION['pending_reset_email']);
        $_SESSION['reset_message'] = 'Password reset successful! You can now log in.';
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['reset_error'] = 'An error occurred. Please try again.';
        error_log('Password reset error: ' . $e->getMessage());
        header('Location: reset_password.php');
        exit;
    }
} else {
    header('Location: forgot_password.php');
    exit;
}
