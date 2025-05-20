<?php
// Start session
session_start();

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate password strength
function isStrongPassword($password) {
    // Password must be at least 8 characters long and contain at least one uppercase letter,
    // one lowercase letter, one number, and one special character
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);
   
    return strlen($password) >= 8 && $uppercase && $lowercase && $number && $specialChars;
}

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
        // Silently fail - we don't want logging errors to affect registration
        error_log("Error logging registration: " . $e->getMessage());
    }
}

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
// PHPMailer imports must be at the top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';

function sendOTPEmail($email, $name, $otp) {
    $mail = new PHPMailer(true);
    try {
        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // TODO: Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'ellijoyce7@gmail.com'; // TODO: Replace with your SMTP username
        $mail->Password = 'dnfg iddv wfhr nhqy'; // TODO: Replace with your SMTP password or app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

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
    
    // Set content-type header for sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@elitefitgym.com" . "\r\n";
    
    // Send email
    return $mail->send();
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data - Account Information
    $name = sanitizeInput($_POST["name"]);
    $email = sanitizeInput($_POST["email"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];
    $role = sanitizeInput($_POST["role"]);
    
    // Get form data - Fitness Profile
    $experienceLevel = isset($_POST["experience_level"]) ? sanitizeInput($_POST["experience_level"]) : null;
    $fitnessGoals = isset($_POST["fitness_goals"]) ? sanitizeInput($_POST["fitness_goals"]) : null;
    $preferredWorkoutTypes = isset($_POST["preferred_workout_types"]) ? sanitizeInput($_POST["preferred_workout_types"]) : null;
    $preferredRoutines = isset($_POST["preferred_routines"]) ? sanitizeInput($_POST["preferred_routines"]) : null;
    
    // Get form data - Health Information
    $healthConditions = isset($_POST["health_conditions"]) ? sanitizeInput($_POST["health_conditions"]) : null;
    $dateOfBirth = isset($_POST["date_of_birth"]) ? sanitizeInput($_POST["date_of_birth"]) : null;
    $height = isset($_POST["height"]) ? sanitizeInput($_POST["height"]) : null;
    $weight = isset($_POST["weight"]) ? sanitizeInput($_POST["weight"]) : null;
   
    // Validate inputs
    $errors = [];
   
    // Check if name is empty
    if (empty($name)) {
        $errors[] = "Name is required";
    }
   
    // Check if email is valid
    if (!isValidEmail($email)) {
        $errors[] = "Invalid email format";
    }
   
    // Check if password and confirm password match
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
   
    // Check password strength
    if (!isStrongPassword($password)) {
        $errors[] = "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character";
    }
   
    // Check if role is valid
    $validRoles = ['Member', 'Trainer', 'Admin', 'EquipmentManager'];
    $roleIsValid = false;

    foreach ($validRoles as $validRole) {
        if ($role === $validRole) {
            $roleIsValid = true;
            break;
        }
    }

    if (!$roleIsValid) {
        $errors[] = "Invalid role selected";
    }
   
    // If there are no errors, proceed with registration
    if (empty($errors)) {
        try {
            // Connect to database
            $conn = connectDB();
            
            // Begin transaction
            $conn->beginTransaction();
           
            // Check if email already exists
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
           
            if ($checkStmt->rowCount() > 0) {
                $_SESSION['register_error'] = "Email already exists. Please use a different email or login.";
                logRegistration($email, 0, $role, "Email already exists");
                header("Location: register.php");
                exit;
            }
           
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
           
            // Insert into users table with created_at and updated_at timestamps
            // Note: Set verified to 0 initially
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, verified, created_at, updated_at) VALUES (?, ?, ?, ?, 0, NOW(), NOW())");
            $stmt->execute([$name, $email, $hashedPassword, $role]);
            
            // Get the newly inserted user ID
            $userId = $conn->lastInsertId();
            
            // For Member role, insert into both members and member_profiles tables
            if ($role === 'Member') {
                // Insert into members table
                $memberStmt = $conn->prepare("INSERT INTO members (user_id, experience_level, fitness_goals, preferred_routines) VALUES (?, ?, ?, ?)");
                $memberStmt->execute([$userId, $experienceLevel, $fitnessGoals, $preferredRoutines]);
                
                // Insert into member_profiles table
                $profileStmt = $conn->prepare("INSERT INTO member_profiles (user_id, fitness_goals, experience_level, preferred_workout_types, health_conditions, date_of_birth, height, weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $profileStmt->execute([
                    $userId, 
                    $fitnessGoals, 
                    $experienceLevel, 
                    $preferredWorkoutTypes, 
                    $healthConditions, 
                    $dateOfBirth, 
                    $height, 
                    $weight
                ]);
            } else {
                // Handle other roles as before
                switch ($role) {
                    case 'Trainer':
                        $roleStmt = $conn->prepare("INSERT INTO trainers (user_id, specialization, professional_experience, training_approach) VALUES (?, ?, ?, ?)");
                        $roleStmt->execute([$userId, $experienceLevel, $fitnessGoals, $preferredRoutines]);
                        break;
                        
                    case 'Admin':
                        $roleStmt = $conn->prepare("INSERT INTO admins (user_id, position, responsibilities, access_requirements) VALUES (?, ?, ?, ?)");
                        $roleStmt->execute([$userId, $experienceLevel, $fitnessGoals, $preferredRoutines]);
                        break;
                        
                    case 'EquipmentManager':
                        $roleStmt = $conn->prepare("INSERT INTO equipment_managers (user_id, area_of_expertise, technical_experience, certifications) VALUES (?, ?, ?, ?)");
                        $roleStmt->execute([$userId, $experienceLevel, $fitnessGoals, $preferredRoutines]);
                        break;
                }
            }
            
            // Generate OTP code
            $otp = generateOTP(6);
            
            // Store OTP in database (expires in 15 minutes)
            $otpStmt = $conn->prepare("INSERT INTO otp_codes (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
            $otpStmt->execute([$userId, $email, $otp]);
            
            // Send OTP email
            $emailSent = sendOTPEmail($email, $name, $otp);
            
            if (!$emailSent) {
                // If email fails, log the error but continue with registration
                error_log("Failed to send OTP email to: $email");
            }
            
            // Commit transaction
            $conn->commit();
           
            // Log successful registration
            logRegistration($email, 1, $role, "Registration initiated, OTP verification pending");
           
            // Store user ID and email in session for verification
            $_SESSION['pending_verification_user_id'] = $userId;
            $_SESSION['pending_verification_email'] = $email;
           
            // Redirect to OTP verification page
            header("Location: verify_otp.php");
            exit;
           
        } catch (PDOException $e) {
            // Rollback transaction
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            // Log detailed error for debugging
            error_log("Registration error: " . $e->getMessage());
            
            // Log error
            logRegistration($email, 0, $role, "Database error: " . $e->getMessage());
           
            // Set error message
            $_SESSION['register_error'] = "An error occurred during registration. Please try again. Error: " . $e->getMessage();
           
            // Redirect back to registration page
            header("Location: register.php");
            exit;
        }
    } else {
        // If there are validation errors, set error message
        $_SESSION['register_error'] = implode("<br>", $errors);
       
        // Log failed registration
        logRegistration($email, 0, $role, implode(", ", $errors));
       
        // Redirect back to registration page
        header("Location: register.php");
        exit;
    }
} else {
    // If not a POST request, redirect to registration page
    header("Location: register.php");
    exit;
}

// End of file: ensure all brackets and PHP tags are properly closed.
?>
