<?php
// Start session
session_start();

// PHPMailer imports and Composer autoload
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';

// Check if user is pending verification
if (!isset($_SESSION['pending_verification_user_id']) || !isset($_SESSION['pending_verification_email'])) {
    // Redirect to registration page if not pending verification
    header("Location: register.php");
    exit;
}

// Get pending verification details
$userId = $_SESSION['pending_verification_user_id'];
$email = $_SESSION['pending_verification_email'];

// Function to generate a random OTP code
function generateOTP($length = 6) {
    // Generate a random numeric OTP
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= mt_rand(0, 9);
    }
    return $otp;
}

// Function to send OTP email using PHPMailer
function sendOTPEmail($email, $name, $otp) {
    $mail = new PHPMailer(true);
    try {
        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // TODO: Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'ellijoyce7@gmail.com'; // TODO: Replace with your SMTP username
        $mail->Password = 'eyakqbgxylybotcu'; // TODO: Replace with your SMTP password or app password
        $mail->SMTPSecure = 'ssl'; 
        $mail->Port = 465;

        $mail->setFrom('ellijoyce7@gmail.com', 'ELITEFIT GYM');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'ELITEFIT GYM - Your OTP Verification Code';
        $mail->Body = "
        <html>
        <head>
            <title>OTP Verification</title>
            <style>
                body {
                    font-family: 'Poppins', Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #fff;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                .header {
                    background-color: #121212;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 8px 8px 0 0;
                }
                .content {
                    padding: 20px;
                }
                .otp-code {
                    font-size: 24px;
                    font-weight: bold;
                    text-align: center;
                    margin: 20px 0;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 4px;
                    letter-spacing: 5px;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                    color: #777;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>ELITEFIT GYM</h1>
                </div>
                <div class='content'>
                    <h2>Hello $name,</h2>
                    <p>Thank you for registering with ELITEFIT GYM. To complete your registration, please use the verification code below:</p>
                    <div class='otp-code'>$otp</div>
                    <p>This code will expire in 15 minutes.</p>
                    <p>If you did not request this code, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>ELITEFIT GYM - Transform Your Body, Transform Your Life.</p>
                    <p>&copy; " . date('Y') . " ELITEFIT GYM. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Send email
        return $mail->send();
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

// Check if form is submitted for resending OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend_otp'])) {
    // Include database connection
    require_once __DIR__ . '/db_connect.php';
    
    try {
        // Connect to database
        $conn = connectDB();
        
        // Get user name
        $nameStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $nameStmt->execute([$userId]);
        $user = $nameStmt->fetch(PDO::FETCH_ASSOC);
        $name = $user['name'];
        
        // Generate new OTP code
        $otp = generateOTP(6);
        
        // Delete any existing OTP for this user
        $deleteStmt = $conn->prepare("DELETE FROM otp_codes WHERE user_id = ?");
        $deleteStmt->execute([$userId]);
        
        // Store new OTP in database (expires in 15 minutes)
        $otpStmt = $conn->prepare("INSERT INTO otp_codes (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
        $otpStmt->execute([$userId, $email, $otp]);
        
        // Send OTP email
        $emailSent = sendOTPEmail($email, $name, $otp);
        
        if ($emailSent) {
            $_SESSION['otp_message'] = "A new verification code has been sent to your email.";
        } else {
            $_SESSION['otp_error'] = "Failed to send verification code. Please try again.";
        }
        
    } catch (PDOException $e) {
        $_SESSION['otp_error'] = "An error occurred. Please try again.";
        error_log("OTP resend error: " . $e->getMessage());
    }
    
    // Redirect to refresh the page
    header("Location: verify_otp.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELITEFIT GYM - Email Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: white;
            --secondary: #333;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --border-radius: 8px;
            --white: white;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: url('https://cdn.pixabay.com/photo/2018/09/13/16/46/fitness-studio-3675225_1280.jpg') no-repeat center center/cover fixed;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            color: white;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.5) 100%);
            z-index: -1;
        }
        
        .container {
            width: 500px;
            max-width: 95%;
            background: #121212;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            color: white;
            padding: 40px;
        }
        
        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .logo-icon {
            font-size: 3rem;
            color: var(--white);
            margin-bottom: 10px;
        }
        
        .logo-text {
            font-size: 2rem;
            font-weight: 700;
            color: var(--white);
            letter-spacing: 2px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: white;
        }
        
        p {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .email-highlight {
            font-weight: 600;
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .otp-input-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            border: 1px solid #444;
            background-color: #1e1e1e;
            border-radius: 8px;
            color: white;
        }
        
        .otp-input:focus {
            border-color: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.2);
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px 20px;
            background-color: white;
            color: #121212;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .btn:hover {
            background-color: #f0f0f0;
        }
        
        .btn-secondary {
            background-color: transparent;
            border: 1px solid white;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .alert {
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            color: #51cf66;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .timer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #aaa;
        }
        
        @media (max-width: 576px) {
            .otp-input {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <div class="logo-icon">
                <i class="fas fa-dumbbell"></i>
            </div>
            <div class="logo-text">ELITEFIT</div>
        </div>
        
        <h1>Email Verification</h1>
        
        <p>We've sent a verification code to your email: <span class="email-highlight"><?php echo htmlspecialchars($email); ?></span></p>
        
        <?php if (isset($_SESSION['otp_error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['otp_error']; 
                    unset($_SESSION['otp_error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['otp_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['otp_message']; 
                    unset($_SESSION['otp_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <form action="verify_otp_process.php" method="post">
            <div class="form-group">
                <label for="otp">Enter 6-Digit Verification Code:</label>
                <div class="otp-input-container">
                    <input type="text" name="otp_1" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required autofocus>
                    <input type="text" name="otp_2" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" name="otp_3" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" name="otp_4" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" name="otp_5" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" name="otp_6" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                </div>
            </div>
            
            <button type="submit" class="btn">Verify & Continue</button>
        </form>
        
        <form action="verify_otp.php" method="post">
            <input type="hidden" name="resend_otp" value="1">
            <button type="submit" class="btn btn-secondary">Resend Verification Code</button>
        </form>
        
        <div class="timer">
            Code expires in: <span id="countdown">15:00</span>
        </div>
    </div>
    
    <script>
        // Auto-tab functionality for OTP input
        const otpInputs = document.querySelectorAll('.otp-input');
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.target.value && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });
            
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });
        
        // Countdown timer
        function startTimer(duration, display) {
            let timer = duration, minutes, seconds;
            const interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(interval);
                    display.textContent = "Expired";
                    display.style.color = "#ff6b6b";
                }
            }, 1000);
        }

        window.onload = function () {
            const fifteenMinutes = 60 * 15;
            const display = document.querySelector('#countdown');
            startTimer(fifteenMinutes, display);
        };
    </script>
</body>
</html>
          