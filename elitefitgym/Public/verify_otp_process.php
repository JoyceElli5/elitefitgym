<?php
// Start session
session_start();

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Function to log registration activity
function logRegistration($email, $success, $role, $message) {
    try {
        $conn = connectDB();
        
        // Check if registration_logs table exists, if not create it
        $conn->exec("CREATE TABLE IF NOT EXISTS registration_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            success TINYINT(1) NOT NULL,
            role VARCHAR(20) NOT NULL,
            message TEXT,
            ip_address VARCHAR(45),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $stmt = $conn->prepare("INSERT INTO registration_logs (email, success, role, message, ip_address, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$email, $success, $role, $message, $_SERVER['REMOTE_ADDR']]);
    } catch (PDOException $e) {
        error_log("Error logging registration: " . $e->getMessage());
    }
}

// Check if user is pending verification
if (!isset($_SESSION['pending_verification_user_id']) || !isset($_SESSION['pending_verification_email'])) {
    // Redirect to registration page if not pending verification
    header("Location: register.php");
    exit;
}

// Get pending verification details
$userId = $_SESSION['pending_verification_user_id'];
$email = $_SESSION['pending_verification_email'];

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect OTP digits and combine them
    $otp = '';
    for ($i = 1; $i <= 6; $i++) {
        if (isset($_POST["otp_$i"]) && is_numeric($_POST["otp_$i"])) {
            $otp .= $_POST["otp_$i"];
        } else {
            $_SESSION['otp_error'] = "Please enter a valid 6-digit code.";
            header("Location: verify_otp.php");
            exit;
        }
    }
    
    // Validate OTP length
    if (strlen($otp) !== 6) {
        $_SESSION['otp_error'] = "Please enter a valid 6-digit code.";
        header("Location: verify_otp.php");
        exit;
    }
    
    try {
        // Connect to database
        $conn = connectDB();
        
        // Check if OTP is valid and not expired
        $stmt = $conn->prepare("
            SELECT * FROM otp_codes 
            WHERE user_id = ? 
            AND email = ? 
            AND otp_code = ? 
            AND expires_at > NOW() 
            AND verified = 0
        ");
        $stmt->execute([$userId, $email, $otp]);
        
        if ($stmt->rowCount() === 1) {
            // OTP is valid, mark as verified
            $updateOtpStmt = $conn->prepare("UPDATE otp_codes SET verified = 1 WHERE user_id = ? AND email = ? AND otp_code = ?");
            $updateOtpStmt->execute([$userId, $email, $otp]);
            
            // Mark user as verified
            $updateUserStmt = $conn->prepare("UPDATE users SET verified = 1 WHERE id = ?");
            $updateUserStmt->execute([$userId]);
            
            // Get user role for logging
            $roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $roleStmt->execute([$userId]);
            $user = $roleStmt->fetch(PDO::FETCH_ASSOC);
            $role = $user['role'];
            
            // Log successful verification
            logRegistration($email, 1, $role, "Email verification successful");
            
            // Clear verification session variables
            unset($_SESSION['pending_verification_user_id']);
            unset($_SESSION['pending_verification_email']);
            
            // Set success message
            $_SESSION['login_message'] = "Email verification successful! You can now login with your credentials.";
            
            // Redirect to login page
            header("Location: login.php");
            exit;
        } else {
            // Invalid or expired OTP
            $_SESSION['otp_error'] = "Invalid or expired verification code. Please try again or request a new code.";
            
            // Log failed verification
            $roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $roleStmt->execute([$userId]);
            $user = $roleStmt->fetch(PDO::FETCH_ASSOC);
            $role = $user['role'];
            
            logRegistration($email, 0, $role, "Invalid or expired OTP");
            
            header("Location: verify_otp.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['otp_error'] = "An error occurred. Please try again.";
        error_log("OTP verification error: " . $e->getMessage());
        header("Location: verify_otp.php");
        exit;
    }
} else {
    // If not a POST request, redirect to verification page
    header("Location: verify_otp.php");
    exit;
}
?>
