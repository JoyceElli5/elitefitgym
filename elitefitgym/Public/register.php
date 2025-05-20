<?php
// Start session
session_start();

// Check for success or error messages
$success = isset($_SESSION['register_success']) ? $_SESSION['register_success'] : '';
$error = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : '';

// Clear session messages
unset($_SESSION['register_success']);
unset($_SESSION['register_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELITEFIT GYM REGISTER</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #ff4d4d;
            --secondary: #333;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --border-radius: 8px;
            --red:  #ff4d4d;
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
            padding: 20px;
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
            width: 900px;
            max-width: 95%;
            background: #121212;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            color: white;
            display: flex;
            flex-direction: column;
        }
       
        .header {
            background: #1e1e1e;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #333;
        }
       
        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
       
        .logo-icon {
            font-size: 2.5rem;
            color: var(--orange);
            margin-bottom: 10px;
        }
       
        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--orange);
            letter-spacing: 2px;
        }
       
        .header h2 {
            color: white;
            font-size: 1.5rem;
            margin-top: 10px;
        }
       
        .role-selection {
            padding: 30px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
       
        .role-card {
            background: #1e1e1e;
            border-radius: var(--border-radius);
            padding: 20px;
            width: 180px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
       
        .role-card:hover {
            transform: translateY(-5px);
            border-color: var(--orange);
        }
       
        .role-card.active {
            border-color: var(--orange);
            background: #2a2a2a;
        }
       
        .role-icon {
            font-size: 2.5rem;
            color: var(--orange);
            margin-bottom: 15px;
        }
       
        .role-title {
            font-weight: 600;
            margin-bottom: 10px;
        }
       
        .role-description {
            font-size: 0.8rem;
            color: #aaa;
        }
       
        .registration-form {
            padding: 30px;
            display: none;
        }
       
        .form-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: white;
            text-align: center;
        }
       
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
       
        .form-group {
            flex: 1;
            margin-bottom: 20px;
            position: relative;
        }
       
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #e0e0e0;
            font-weight: 500;
        }
       
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #444;
            background-color: #1e1e1e;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s;
            color: white;
        }
       
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--orange);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.2);
        }
       
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
       
        .form-group .icon {
            position: absolute;
            left: 15px;
            top: 42px;
            color: #aaa;
        }
       
        .form-group .icon + input {
            padding-left: 40px;
        }
       
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
       
        .btn {
            padding: 12px 20px;
            background-color: var(--orange);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
       
        .btn:hover {
            background-color: red;
        }
       
        .btn-secondary {
            background-color: transparent;
            border: 1px solid #444;
        }
       
        .btn-secondary:hover {
            background-color: #2a2a2a;
        }
       
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #e0e0e0;
        }
       
        .login-link a {
            color: var(--orange);
            text-decoration: none;
            font-weight: 500;
        }
       
        .success-message {
            background-color: rgba(40, 167, 69, 0.2);
            color: #75e096;
            padding: 10px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
       
        .error-message {
            background-color: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            padding: 10px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
       .form-progress {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            right: -50%;
            width: 100%;
            height: 2px;
            background-color: #ddd;
            z-index: 1;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ddd;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;
            margin-bottom: 5px;
            position: relative;
            z-index: 2;
        }
        
        .step.active .step-number {
            background-color: var(--primary);
        }
        
        .step.completed .step-number {
            background-color: var(--success);
        }
        
        .step-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .step.active .step-label {
            color: var(--primary);
            font-weight: 500;
        }
        
        .step.completed .step-label {
            color: var(--success);
        }
        
        .form-section {
            padding: 30px;
            display: none;
        }
        
        .form-section.active {
            display: block;
        }
        
        .form-title {
            font-size: 1.5rem;
            color: var(--secondary);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary);
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            padding-left: 40px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group select {
            padding-left: 15px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%233a0ca3' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: calc(100% - 15px) center;
            background-size: 12px;
        }
        
        .form-group textarea {
            min-height: 100px;
            padding-left: 15px;
        }
        
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            outline: none;
        }
        
        .form-group .icon {
            position: absolute;
            left: 15px;
            top: 42px;
            color: var(--secondary);
        }
        
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background-color: #3a56d4;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: rgba(247, 37, 133, 0.1);
            border: 1px solid var(--warning);
            color: var(--warning);
            padding: 10px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        
        .success-message {
            background-color: rgba(76, 201, 240, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
            padding: 10px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .registration-container {
                max-width: 100%;
            }
        }
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
           
            .role-selection {
                flex-direction: column;
                align-items: center;
            }
           
            .role-card {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <div class="logo-text">ELITEFIT</div>
            </div>
            <h2>Join Our Fitness Community</h2>
        </div>
       
        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
       
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
       
        <div class="role-selection" id="roleSelection">
            <div class="role-card" data-role="member">
                <div class="role-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="role-title">MEMBER</div>
                <div class="role-description">Join as a gym member to access personalized workout plans</div>
            </div>
           
            <div class="role-card" data-role="trainer">
                <div class="role-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="role-title">TRAINER</div>
                <div class="role-description">Register as a fitness trainer to guide members</div>
            </div>
<!--            
            <div class="role-card" data-role="admin">
                <div class="role-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="role-title">ADMIN</div>
                <div class="role-description">Manage the gym platform and user accounts</div>
            </div> -->
           
            <div class="role-card" data-role="equipment">
                <div class="role-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="role-title">EQUIPMENT MANAGER</div>
                <div class="role-description">Oversee gym equipment maintenance and availability</div>
            </div>
        </div>
       
        <!-- Member Registration Form -->
        <div class="registeration-form" id="memberForm">
        <div class="registration-header">
            <h2>EliteFit Gym Membership</h2>
            <p>Join our community and start your fitness journey today!</p>
        </div>
        
        <div class="form-progress">
            <div class="step active" id="step1">
                <div class="step-number">1</div>
                <div class="step-label">Account</div>
            </div>
            <div class="step" id="step2">
                <div class="step-number">2</div>
                <div class="step-label">Profile</div>
            </div>
            <div class="step" id="step3">
                <div class="step-number">3</div>
                <div class="step-label">Health</div>
            </div>
        </div>
        
        <?php
        // Display error message if any
        if (isset($_SESSION['register_error'])) {
            echo '<div class="error-message">' . $_SESSION['register_error'] . '</div>';
            unset($_SESSION['register_error']);
        }
        ?>
        
        <form action="register_process.php" method="post" id="registrationForm">
            <input type="hidden" name="role" value="Member">
            
            <!-- Step 1: Account Information -->
            <div class="form-section active" id="section1">
                <h3 class="form-title">Account Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <i class="fas fa-user icon"></i>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <i class="fas fa-envelope icon"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                    </div>
                </div>
                
                <div class="btn-container">
                    <div></div> <!-- Empty div for spacing -->
                    <button type="button" class="btn" onclick="nextStep(1)">Next</button>
                </div>
            </div>
            
            <!-- Step 2: Fitness Profile -->
            <div class="form-section" id="section2">
                <h3 class="form-title">Fitness Profile</h3>
                
                <div class="form-group">
                    <label for="experience_level">Experience Level</label>
                    <select id="experience_level" name="experience_level" required>
                        <option value="">Select your experience level</option>
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                        <option value="Professional">Professional</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fitness_goals">Fitness Goals</label>
                    <textarea id="fitness_goals" name="fitness_goals" placeholder="Describe your fitness goals (e.g., weight loss, muscle gain, endurance)"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="preferred_workout_types">Preferred Workout Types</label>
                    <textarea id="preferred_workout_types" name="preferred_workout_types" placeholder="What types of workouts do you prefer? (e.g., cardio, strength training, yoga)"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="preferred_routines">Preferred Workout Routines</label>
                    <textarea id="preferred_routines" name="preferred_routines" placeholder="Describe your preferred workout routines or schedule"></textarea>
                </div>
                
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(2)">Previous</button>
                    <button type="button" class="btn" onclick="nextStep(2)">Next</button>
                </div>
            </div>
            
            <!-- Step 3: Health Information -->
            <div class="form-section" id="section3">
                <h3 class="form-title">Health Information</h3>
                
                <div class="form-group">
                    <label for="health_conditions">Health Conditions</label>
                    <textarea id="health_conditions" name="health_conditions" placeholder="List any health conditions or injuries we should be aware of"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <i class="fas fa-calendar icon"></i>
                        <input type="date" id="date_of_birth" name="date_of_birth">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="height">Height (cm)</label>
                        <i class="fas fa-ruler-vertical icon"></i>
                        <input type="number" id="height" name="height" placeholder="Enter your height in centimeters">
                    </div>
                    
                    <div class="form-group">
                        <label for="weight">Weight (kg)</label>
                        <i class="fas fa-weight icon"></i>
                        <input type="number" id="weight" name="weight" placeholder="Enter your weight in kilograms">
                    </div>
                </div>
                
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(3)">Previous</button>
                    <button type="submit" class="btn">Complete Registration</button>
                </div>
            </div>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">LOGIN</a>
        </div>
    </div>
       
        <!-- Trainer Registration Form -->
        <div class="registration-form" id="trainerForm">
            <h3 class="form-title">TRAINER REGISTRATION</h3>
            <form action="register_process.php" method="post">
                <input type="hidden" name="role" value="Trainer">
               
                <div class="form-row">
                    <div class="form-group">
                        <label for="trainer_name">Full Name</label>
                        <i class="fas fa-user icon"></i>
                        <input type="text" id="trainer_name" name="name" placeholder="Enter your full name" required>
                    </div>
                   
                    <div class="form-group">
                        <label for="trainer_email">Email Address</label>
                        <i class="fas fa-envelope icon"></i>
                        <input type="email" id="trainer_email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
               
                <div class="form-row">
                    <div class="form-group">
                        <label for="trainer_password">Password</label>
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="trainer_password" name="password" placeholder="Create a password" required>
                    </div>
                   
                    <div class="form-group">
                        <label for="trainer_confirm_password">Confirm Password</label>
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="trainer_confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                    </div>
                </div>
               
                <div class="form-group">
                    <label for="trainer_specialization">Specialization</label>
                    <select id="trainer_specialization" name="experience_level" required>
                        <option value="">Select your specialization</option>
                        <option value="Strength Training">Strength Training</option>
                        <option value="Cardio">Cardio</option>
                        <option value="Yoga">Yoga</option>
                        <option value="CrossFit">CrossFit</option>
                        <option value="Nutrition">Nutrition</option>
                        <option value="Weight Loss">Weight Loss</option>
                        <option value="Bodybuilding">Bodybuilding</option>
                    </select>
                </div>
               
                <div class="form-group">
                    <label for="trainer_experience">Professional Experience</label>
                    <textarea id="trainer_experience" name="fitness_goals" placeholder="Describe your professional experience and certifications"></textarea>
                </div>
               
                <div class="form-group">
                    <label for="trainer_approach">Training Approach</label>
                    <textarea id="trainer_approach" name="preferred_routines" placeholder="Describe your training approach and methodology"></textarea>
                </div>
               
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="showRoleSelection()">Back</button>
                    <button type="submit" class="btn">Register as Trainer</button>
                </div>
            </form>
           
            <div class="login-link">
                Already have an account? <a href="../Public/login.php">LOGIN</a>
            </div>
        </div>
       
        <!-- Admin Registration Form -->
        <div class="registration-form" id="adminForm">
            <h3 class="form-title">ADMIN REGISTRATION</h3>
            <form action="register_process.php" method="post">
                <input type="hidden" name="role" value="Admin">
               
                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_name">Full Name</label>
                        <i class="fas fa-user icon"></i>
                        <input type="text" id="admin_name" name="name" placeholder="Enter your full name" required>
                    </div>
                   
                    <div class="form-group">
                        <label for="admin_email">Email Address</label>
                        <i class="fas fa-envelope icon"></i>
                        <input type="email" id="admin_email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
               
                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_password">Password</label>
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="admin_password" name="password" placeholder="Create a password" required>
                    </div>
                   
                    <div class="form-group">
                        <label for="admin_confirm_password">Confirm Password</label>
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="admin_confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                    </div>
                </div>
               
                <div class="form-group">
                    <label for="admin_position">Position</label>
                    <input type="text" id="admin_position" name="experience_level" placeholder="Enter your position (e.g., Gym Manager, System Administrator)" required>
                </div>
               
                <div class="form-group">
                    <label for="admin_responsibilities">Responsibilities</label>
                    <textarea id="admin_responsibilities" name="fitness_goals" placeholder="Describe your administrative responsibilities"></textarea>
                </div>
               
                <div class="form-group">
                    <label for="admin_access">Access Requirements</label>
                    <textarea id="admin_access" name="preferred_routines" placeholder="Describe your system access requirements"></textarea>
                </div>
               
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="showRoleSelection()">Back</button>
                    <button type="submit" class="btn">Register as Admin</button>
                </div>
            </form>
           
            <div class="login-link">
                Already have an account? <a href="login.php">LOGIN</a>
            </div>
        </div>
       
        <!-- Equipment Manager Registration Form -->
        <div class="registration-form" id="equipmentForm">
            <h3 class="form-title">EQUIPMENT MANAGER REGISTRATION</h3>
            <form action="register_process.php" method="post">
                <input type="hidden" name="role" value="EquipmentManager">
               
                <div class="form-row">
                    <div class="form-group">
                        <label for="equipment_name">Full Name</label>
                        <i class="fas fa-user icon"></i>
                        <input type="text" id="equipment_name" name="name" placeholder="Enter your full name" required>
                    </div>
                   
                    <div class="form-group">
                        <label for="equipment_email">Email Address</label>
                        <i class="fas fa-envelope icon"></i>
                        <input type="email" id="equipment_email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
               
                <div class="form-row">
                    <div class="form-group">
                        <label for="equipment_password">Password</label>
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="equipment_password" name="password" placeholder="Create a password" required>
                    </div>
                   
                    <div class="form-group">
                        <label for="equipment_confirm_password">Confirm Password</label>
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="equipment_confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                    </div>
                </div>
               
                <div class="form-group">
                    <label for="equipment_expertise">Area of Expertise</label>
                    <select id="equipment_expertise" name="experience_level" required>
                        <option value="">Select your area of expertise</option>
                        <option value="Cardio Equipment">Cardio Equipment</option>
                        <option value="Strength Machines">Strength Machines</option>
                        <option value="Free Weights">Free Weights</option>
                        <option value="General Maintenance">General Maintenance</option>
                        <option value="Equipment Procurement">Equipment Procurement</option>
                    </select>
                </div>
               
                <div class="form-group">
                    <label for="equipment_experience">Technical Experience</label>
                    <textarea id="equipment_experience" name="fitness_goals" placeholder="Describe your technical experience with gym equipment"></textarea>
                </div>
               
                <div class="form-group">
                    <label for="equipment_certifications">Certifications</label>
                    <textarea id="equipment_certifications" name="preferred_routines" placeholder="List any relevant certifications or qualifications"></textarea>
                </div>
               
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="showRoleSelection()">Back</button>
                    <button type="submit" class="btn">Register as Equipment Manager</button>
                </div>
            </form>
           
            <div class="login-link">
                Already have an account? <a href="login.php">LOGIN</a>
            </div>
        </div>
    </div>
   
    <script>
        // Show role selection and hide all forms
        function showRoleSelection() {
            document.getElementById('roleSelection').style.display = 'flex';
            document.getElementById('memberForm').style.display = 'none';
            document.getElementById('trainerForm').style.display = 'none';
            document.getElementById('adminForm').style.display = 'none';
            document.getElementById('equipmentForm').style.display = 'none';
           
            // Remove active class from all role cards
            const roleCards = document.querySelectorAll('.role-card');
            roleCards.forEach(card => {
                card.classList.remove('active');
            });
        }
       
        // Show specific form based on role
        function showForm(role) {
            document.getElementById('roleSelection').style.display = 'none';
            document.getElementById('memberForm').style.display = 'none';
            document.getElementById('trainerForm').style.display = 'none';
            document.getElementById('adminForm').style.display = 'none';
            document.getElementById('equipmentForm').style.display = 'none';
           
            document.getElementById(role + 'Form').style.display = 'block';
        }
       
        // Add click event listeners to role cards
        document.addEventListener('DOMContentLoaded', function() {
            const roleCards = document.querySelectorAll('.role-card');
           
            roleCards.forEach(card => {
                card.addEventListener('click', function() {
                    const role = this.getAttribute('data-role');
                   
                    // Add active class to selected card
                    roleCards.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                   
                    // Show corresponding form after a short delay
                    setTimeout(() => {
                        showForm(role);
                    }, 300);
                });
            });
           
            // Form validation for password matching
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    const password = this.querySelector('input[name="password"]').value;
                    const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
                   
                    if (password !== confirmPassword) {
                        event.preventDefault();
                        alert('Passwords do not match. Please try again.');
                    }
                });
            });
        });
    </script>
    <script>
        function nextStep(currentStep) {
            // Validate current step
            if (currentStep === 1) {
                const name = document.getElementById('name').value;
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (!name || !email || !password || !confirmPassword) {
                    alert('Please fill in all required fields.');
                    return;
                }
                
                if (password !== confirmPassword) {
                    alert('Passwords do not match.');
                    return;
                }
                
                // Basic password strength validation
                if (password.length < 8) {
                    alert('Password must be at least 8 characters long.');
                    return;
                }
            }
            
            // Hide current section and show next section
            document.getElementById(`section${currentStep}`).classList.remove('active');
            document.getElementById(`section${currentStep + 1}`).classList.add('active');
            
            // Update progress steps
            document.getElementById(`step${currentStep}`).classList.remove('active');
            document.getElementById(`step${currentStep}`).classList.add('completed');
            document.getElementById(`step${currentStep + 1}`).classList.add('active');
        }
        
        function prevStep(currentStep) {
            // Hide current section and show previous section
            document.getElementById(`section${currentStep}`).classList.remove('active');
            document.getElementById(`section${currentStep - 1}`).classList.add('active');
            
            // Update progress steps
            document.getElementById(`step${currentStep}`).classList.remove('active');
            document.getElementById(`step${currentStep - 1}`).classList.remove('completed');
            document.getElementById(`step${currentStep - 1}`).classList.add('active');
        }
    </script>
</body>
</html>