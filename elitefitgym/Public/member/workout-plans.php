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
$memberStmt = $conn->prepare("
    SELECT m.*, mp.fitness_goals, mp.experience_level, mp.preferred_workout_types, 
           mp.health_conditions, mp.date_of_birth, mp.height, mp.weight
    FROM members m
    LEFT JOIN member_profiles mp ON m.user_id = mp.user_id
    WHERE m.user_id = ?
");
$memberStmt->execute([$userId]);
$member = $memberStmt->fetch(PDO::FETCH_ASSOC);
$memberId = $member['member_id'];

// Get all workout plans
$plansStmt = $conn->prepare("
    SELECT wp.*, t.user_id as trainer_name
    FROM workout_plans wp
    JOIN trainers t ON wp.trainer_id = t.user_id
    WHERE wp.member_id = ?
    ORDER BY wp.created_at DESC
");
$plansStmt->execute([$memberId]);
$workoutPlans = $plansStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all trainers for request form
$trainersStmt = $conn->prepare("
    SELECT t.user_id, t.user_id as trainer_id, u.name, t.specialization
    FROM trainers t
    JOIN users u ON t.user_id = u.id
    WHERE u.role = 'Trainer'
");
$trainersStmt->execute();
$trainers = $trainersStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle workout plan request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_plan'])) {
    $trainerId = $_POST['trainer_id'];
    $planName = $_POST['plan_name'];
    $planGoals = $_POST['plan_goals'];
    $planDuration = $_POST['plan_duration'];
    $planNotes = $_POST['plan_notes'] ?? null;
    
    // Insert workout plan request
    $insertStmt = $conn->prepare("
        INSERT INTO workout_plans (member_id, trainer_id, plan_name, goals, duration, notes, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'requested', NOW())
    ");
    $insertStmt->execute([$memberId, $trainerId, $planName, $planGoals, $planDuration, $planNotes]);
    
    // Redirect to refresh the page
    header("Location: workout-plans.php");
    exit;
}

// Get exercises for a specific plan when viewing details
$planId = isset($_GET['plan_id']) ? $_GET['plan_id'] : null;
$exercises = [];

if ($planId) {
    $exercisesStmt = $conn->prepare("
        SELECT we.*, e.name as exercise_name, e.equipment, e.muscle_group, e.difficulty
        FROM workout_exercises we
        JOIN exercises e ON we.exercise_id = e.exercise_id
        WHERE we.plan_id = ?
        ORDER BY we.day_number, we.exercise_order
    ");
    $exercisesStmt->execute([$planId]);
    $exercises = $exercisesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group exercises by day
    $exercisesByDay = [];
    foreach ($exercises as $exercise) {
        $dayNumber = $exercise['day_number'];
        if (!isset($exercisesByDay[$dayNumber])) {
            $exercisesByDay[$dayNumber] = [];
        }
        $exercisesByDay[$dayNumber][] = $exercise;
    }
    
    // Get plan details
    $planStmt = $conn->prepare("
        SELECT wp.*, t.name as trainer_name
        FROM workout_plans wp
        JOIN trainers t ON wp.trainer_id = t.trainer_id
        WHERE wp.plan_id = ? AND wp.member_id = ?
    ");
    $planStmt->execute([$planId, $memberId]);
    $planDetails = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    // If plan doesn't exist or doesn't belong to this member, redirect
    if (!$planDetails) {
        header("Location: workout-plans.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Plans - EliteFit Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --warning: #f72585;
            --danger: #7209b7;
            --light: #f8f9fa;
            --dark: #212529;
            --border-radius: 8px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--dark);
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--dark);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-left: 10px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 10px;
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 15px 20px;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .header h1 {
            font-size: 1.8rem;
            color: var(--secondary);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .user-info .dropdown {
            position: relative;
        }
        
        .user-info .dropdown-toggle {
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .user-info .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 10px 0;
            min-width: 180px;
            z-index: 1000;
            display: none;
        }
        
        .user-info .dropdown-menu.show {
            display: block;
        }
        
        .user-info .dropdown-menu a {
            display: block;
            padding: 8px 20px;
            color: var(--secondary);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .user-info .dropdown-menu a:hover {
            background-color: #f8f9fa;
        }
        
        .plans-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .plan-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .plan-header {
            padding: 15px 20px;
            background-color: var(--secondary);
            color: white;
        }
        
        .plan-header h3 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .plan-trainer {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .plan-body {
            padding: 20px;
        }
        
        .plan-info {
            margin-bottom: 15px;
        }
        
        .plan-info-item {
            display: flex;
            margin-bottom: 10px;
        }
        
        .plan-info-label {
            font-weight: 600;
            width: 100px;
        }
        
        .plan-info-value {
            flex: 1;
        }
        
        .plan-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .plan-status.active {
            background-color: rgba(76, 201, 240, 0.2);
            color: var(--success);
        }
        
        .plan-status.requested {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--warning);
        }
        
        .plan-status.completed {
            background-color: rgba(114, 9, 183, 0.2);
            color: var(--danger);
        }
        
        .plan-actions {
            display: flex;
            justify-content: space-between;
        }
        
        .btn {
            padding: 8px 15px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background-color: #3a56d4;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.9rem;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .request-plan-form {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .form-header {
            margin-bottom: 20px;
        }
        
        .form-header h3 {
            font-size: 1.2rem;
            color: var(--secondary);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .form-actions {
            margin-top: 20px;
        }
        
        .plan-details {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .plan-details-header {
            padding: 20px;
            background-color: var(--secondary);
            color: white;
        }
        
        .plan-details-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .plan-details-trainer {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .plan-details-info {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .plan-details-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .plan-details-info-item {
            margin-bottom: 10px;
        }
        
        .plan-details-info-label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .plan-details-info-value {
            color: #6c757d;
        }
        
        .plan-details-days {
            padding: 20px;
        }
        
        .day-card {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .day-header {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--secondary);
        }
        
        .exercise-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .exercise-item:last-child {
            border-bottom: none;
        }
        
        .exercise-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .exercise-details {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .exercise-detail {
            background-color: white;
            padding: 3px 8px;
            border-radius: var(--border-radius);
        }
        
        .back-button {
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 20px 10px;
            }
            
            .sidebar-header h2, .sidebar-menu a span {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .plans-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .plan-details-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-dumbbell fa-2x"></i>
                <h2>EliteFit Gym</h2>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="workout-plans.php" class="active">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Workout Plans</span>
                    </a>
                </li>
                <li>
                    <a href="sessions.php">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Sessions</span>
                    </a>
                </li>
                <li>
                    <a href="progress.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Progress</span>
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1><?php echo isset($planDetails) ? htmlspecialchars($planDetails['plan_name']) : 'Workout Plans'; ?></h1>
                <div class="user-info">
                    <img src="../assets/images/default-avatar.png" alt="User Avatar">
                    <div class="dropdown">
                        <div class="dropdown-toggle" onclick="toggleDropdown()">
                            <span><?php echo htmlspecialchars($userName); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-menu" id="userDropdown">
                            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($planDetails)): ?>
                <!-- Plan Details View -->
                <div class="back-button">
                    <a href="workout-plans.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Back to Plans</a>
                </div>
                
                <div class="plan-details">
                    <div class="plan-details-header">
                        <h2><?php echo htmlspecialchars($planDetails['plan_name']); ?></h2>
                        <div class="plan-details-trainer">Trainer: <?php echo htmlspecialchars($planDetails['trainer_name']); ?></div>
                    </div>
                    
                    <div class="plan-details-info">
                        <div class="plan-details-info-grid">
                            <div class="plan-details-info-item">
                                <div class="plan-details-info-label">Status</div>
                                <div class="plan-details-info-value">
                                    <span class="plan-status <?php echo $planDetails['status']; ?>">
                                        <?php echo ucfirst($planDetails['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="plan-details-info-item">
                                <div class="plan-details-info-label">Created</div>
                                <div class="plan-details-info-value"><?php echo date('M d, Y', strtotime($planDetails['created_at'])); ?></div>
                            </div>
                            
                            <div class="plan-details-info-item">
                                <div class="plan-details-info-label">Duration</div>
                                <div class="plan-details-info-value"><?php echo $planDetails['duration']; ?> weeks</div>
                            </div>
                            
                            <div class="plan-details-info-item">
                                <div class="plan-details-info-label">Goals</div>
                                <div class="plan-details-info-value"><?php echo htmlspecialchars($planDetails['goals']); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($planDetails['notes']): ?>
                            <div class="plan-details-info-item" style="margin-top: 15px;">
                                <div class="plan-details-info-label">Notes</div>
                                <div class="plan-details-info-value"><?php echo nl2br(htmlspecialchars($planDetails['notes'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="plan-details-days">
                        <h3 style="margin-bottom: 20px;">Workout Schedule</h3>
                        
                        <?php if (count($exercises) > 0): ?>
                            <?php foreach ($exercisesByDay as $dayNumber => $dayExercises): ?>
                                <div class="day-card">
                                    <div class="day-header">Day <?php echo $dayNumber; ?></div>
                                    
                                    <?php foreach ($dayExercises as $exercise): ?>
                                        <div class="exercise-item">
                                            <div class="exercise-name"><?php echo htmlspecialchars($exercise['exercise_name']); ?></div>
                                            <div class="exercise-details">
                                                <div class="exercise-detail">
                                                    <i class="fas fa-sync-alt"></i> <?php echo $exercise['sets']; ?> sets
                                                </div>
                                                <div class="exercise-detail">
                                                    <i class="fas fa-redo"></i> <?php echo $exercise['reps']; ?> reps
                                                </div>
                                                <?php if ($exercise['rest_time']): ?>
                                                    <div class="exercise-detail">
                                                        <i class="fas fa-clock"></i> <?php echo $exercise['rest_time']; ?> sec rest
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($exercise['equipment']): ?>
                                                    <div class="exercise-detail">
                                                        <i class="fas fa-dumbbell"></i> <?php echo htmlspecialchars($exercise['equipment']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($exercise['muscle_group']): ?>
                                                    <div class="exercise-detail">
                                                        <i class="fas fa-running"></i> <?php echo htmlspecialchars($exercise['muscle_group']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($exercise['notes']): ?>
                                                <div class="exercise-notes" style="margin-top: 5px; font-size: 0.9rem;">
                                                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($exercise['notes']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php if ($planDetails['status'] === 'requested'): ?>
                                <p>Your workout plan is currently being prepared by your trainer.</p>
                            <?php else: ?>
                                <p>No exercises have been added to this plan yet.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Plans List View -->
                <div class="request-plan-form">
                    <div class="form-header">
                        <h3>Request a New Workout Plan</h3>
                    </div>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="trainer_id">Select Trainer</label>
                                <select id="trainer_id" name="trainer_id" class="form-control" required>
                                    <option value="">-- Select Trainer --</option>
                                    <?php foreach ($trainers as $trainer): ?>
                                        <option value="<?php echo $trainer['trainer_id']; ?>">
                                            <?php echo htmlspecialchars($trainer['name']); ?> (<?php echo htmlspecialchars($trainer['specialization']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="plan_name">Plan Name</label>
                                <input type="text" id="plan_name" name="plan_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="plan_duration">Duration (weeks)</label>
                                <select id="plan_duration" name="plan_duration" class="form-control" required>
                                    <option value="4">4 weeks</option>
                                    <option value="8">8 weeks</option>
                                    <option value="12">12 weeks</option>
                                    <option value="16">16 weeks</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="plan_goals">Goals</label>
                            <select id="plan_goals" name="plan_goals" class="form-control" required>
                                <option value="Weight Loss">Weight Loss</option>
                                <option value="Muscle Gain">Muscle Gain</option>
                                <option value="Strength Training">Strength Training</option>
                                <option value="Endurance">Endurance</option>
                                <option value="General Fitness">General Fitness</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="plan_notes">Additional Notes</label>
                            <textarea id="plan_notes" name="plan_notes" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="request_plan" class="btn">Request Plan</button>
                        </div>
                    </form>
                </div>
                
                <h2 style="margin-bottom: 20px;">Your Workout Plans</h2>
                
                <div class="plans-container">
                    <?php if (count($workoutPlans) > 0): ?>
                        <?php foreach ($workoutPlans as $plan): ?>
                            <div class="plan-card">
                                <div class="plan-header">
                                    <h3><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                                    <div class="plan-trainer">Trainer: <?php echo htmlspecialchars($plan['trainer_name']); ?></div>
                                </div>
                                <div class="plan-body">
                                    <div class="plan-status <?php echo $plan['status']; ?>">
                                        <?php echo ucfirst($plan['status']); ?>
                                    </div>
                                    
                                    <div class="plan-info">
                                        <div class="plan-info-item">
                                            <div class="plan-info-label">Created:</div>
                                            <div class="plan-info-value"><?php echo date('M d, Y', strtotime($plan['created_at'])); ?></div>
                                        </div>
                                        <div class="plan-info-item">
                                            <div class="plan-info-label">Duration:</div>
                                            <div class="plan-info-value"><?php echo $plan['duration']; ?> weeks</div>
                                        </div>
                                        <div class="plan-info-item">
                                            <div class="plan-info-label">Goals:</div>
                                            <div class="plan-info-value"><?php echo htmlspecialchars($plan['goals']); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="plan-actions">
                                        <a href="workout-plans.php?plan_id=<?php echo $plan['plan_id']; ?>" class="btn">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>You don't have any workout plans yet. Request one using the form above.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function toggleDropdown() {
            document.getElementById('userDropdown').classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-toggle') && !event.target.matches('.dropdown-toggle *')) {
                var dropdowns = document.getElementsByClassName('dropdown-menu');
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
</body>
</html>
