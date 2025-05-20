<?php
require_once __DIR__ . '/../auth_middleware.php';

// Require Member role to access this page
requireRole('Member');

// Get user data
$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];

// Connect to database
$conn = connectDB();

// Get member information
$memberStmt = $conn->prepare("SELECT m.*, mp.fitness_goals, mp.experience_level, mp.preferred_workout_types, 
           mp.health_conditions, mp.date_of_birth, mp.height, mp.weight, 
           u.email
    FROM members m
    LEFT JOIN member_profiles mp ON m.user_id = mp.user_id
    LEFT JOIN users u ON m.user_id = u.id
    WHERE m.user_id = ?
");
$memberStmt->execute([$userId]);
$member = $memberStmt->fetch(PDO::FETCH_ASSOC);
$memberId = $member['member_id'];

// Handle profile update
$updateSuccess = false;
$updateError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $phone = $_POST['phone'];
    $fitnessGoals = $_POST['fitness_goals'];
    $experienceLevel = $_POST['experience_level'];
    $preferredWorkoutTypes = $_POST['preferred_workout_types'];
    $healthConditions = $_POST['health_conditions'];
    $dateOfBirth = $_POST['date_of_birth'];
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Update user phone
        $userStmt = $conn->prepare("
            UPDATE users
            SET phone = ?
            WHERE user_id = ?
        ");
        $userStmt->execute([$phone, $userId]);
        
        // Check if member profile exists
        $checkStmt = $conn->prepare("
            SELECT COUNT(*) as profile_count
            FROM members_profiles
            WHERE user_id = ?
        ");
        $checkStmt->execute([$userId]);
        $profileExists = $checkStmt->fetch(PDO::FETCH_ASSOC)['profile_count'] > 0;
        
        if ($profileExists) {
            // Update existing profile
            $profileStmt = $conn->prepare("
                UPDATE members_profiles
                SET fitness_goals = ?, experience_level = ?, preferred_workout_types = ?,
                    health_conditions = ?, date_of_birth = ?, height = ?, weight = ?
                WHERE user_id = ?
            ");
            $profileStmt->execute([
                $fitnessGoals, $experienceLevel, $preferredWorkoutTypes,
                $healthConditions, $dateOfBirth, $height, $weight, $userId
            ]);
        } else {
            // Create new profile
            $profileStmt = $conn->prepare("
                INSERT INTO members_profiles (user_id, fitness_goals, experience_level, preferred_workout_types,
                                            health_conditions, date_of_birth, height, weight)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $profileStmt->execute([
                $userId, $fitnessGoals, $experienceLevel, $preferredWorkoutTypes,
                $healthConditions, $dateOfBirth, $height, $weight
            ]);
        }
        
        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = $_FILES['profile_image']['type'];
            
            if (in_array($fileType, $allowedTypes)) {
                $fileName = 'member_' . $memberId . '_' . time() . '.' . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $uploadPath = __DIR__ . '/../uploads/profile_images/' . $fileName;
                
                // Create directory if it doesn't exist
                if (!file_exists(__DIR__ . '/../uploads/profile_images/')) {
                    mkdir(__DIR__ . '/../uploads/profile_images/', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                    // Update profile image in database
                    $imageStmt = $conn->prepare("
                        UPDATE members_profiles
                        SET profile_image = ?
                        WHERE user_id = ?
                    ");
                    $imageStmt->execute([$fileName, $userId]);
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        $updateSuccess = true;
        
        // Refresh member data
        $memberStmt->execute([$userId]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $updateError = "An error occurred while updating your profile. Please try again.";
    }
}

// Handle password change
$passwordSuccess = false;
$passwordError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Get current password hash
    $passwordStmt = $conn->prepare("
        SELECT password
        FROM users
        WHERE user_id = ?
    ");
    $passwordStmt->execute([$userId]);
    $currentHash = $passwordStmt->fetch(PDO::FETCH_ASSOC)['password'];
    
    // Verify current password
    if (!password_verify($currentPassword, $currentHash)) {
        $passwordError = "Current password is incorrect.";
    } else if ($newPassword !== $confirmPassword) {
        $passwordError = "New passwords do not match.";
    } else if (strlen($newPassword) < 8) {
        $passwordError = "New password must be at least 8 characters long.";
    } else {
        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE user_id = ?
        ");
        $updateStmt->execute([$newHash, $userId]);
        $passwordSuccess = true;
    }
}

// Calculate age from date of birth
$age = null;
if (isset($member['date_of_birth']) && $member['date_of_birth']) {
    $dob = new DateTime($member['date_of_birth']);
    $now = new DateTime();
    $age = $dob->diff($now)->y;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile - EliteFit Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link
