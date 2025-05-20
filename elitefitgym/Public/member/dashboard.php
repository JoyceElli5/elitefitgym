<?php
session_start();
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

// Debug - check what data we're getting
echo '<pre style="display: none;">';
var_dump($member);
echo '</pre>';

// Get upcoming sessions
$sessionsStmt = $conn->prepare("
    SELECT s.*, u.name AS trainer_name
    FROM sessions s
    JOIN trainers t ON s.trainer_id = t.user_id
    JOIN users u ON t.user_id = u.id
    WHERE s.member_id = ? AND s.session_date >= CURDATE()
    ORDER BY s.session_date, s.start_time
    LIMIT 5
");
$sessionsStmt->execute([$memberId]);
$upcomingSessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get active workout plans
$plansStmt = $conn->prepare("
    SELECT wp.*, u.name AS trainer_name
    FROM workout_plans wp
    JOIN trainers t ON wp.trainer_id = t.user_id
    JOIN users u ON t.user_id = u.id
    WHERE wp.member_id = ? AND wp.status = 'active'
    ORDER BY wp.created_at DESC
");
$plansStmt->execute([$memberId]);
$workoutPlans = $plansStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent progress data
$progressStmt = $conn->prepare("
    SELECT * FROM progress_tracking
    WHERE member_id = ?
    ORDER BY tracking_date DESC
    LIMIT 5
");
$progressStmt->execute([$memberId]);
$progressData = $progressStmt->fetchAll(PDO::FETCH_ASSOC);

// Get progress metrics for charts
$metricsStmt = $conn->prepare("
    SELECT p.log_date, p.metric, p.value
    FROM progress p
    WHERE p.member_id = ? AND p.metric IN ('weight', 'body_fat', 'strength')
    ORDER BY p.log_date
    LIMIT 30
");
$metricsStmt->execute([$memberId]);
$metrics = $metricsStmt->fetchAll(PDO::FETCH_ASSOC);

// Organize metrics for charts
$weightData = [];
$bodyFatData = [];
$strengthData = [];

foreach ($metrics as $metric) {
    $date = date('M d', strtotime($metric['log_date']));
    
    if ($metric['metric'] === 'weight') {
        $weightData[$date] = $metric['value'];
    } elseif ($metric['metric'] === 'body_fat') {
        $bodyFatData[$date] = $metric['value'];
    } elseif ($metric['metric'] === 'strength') {
        $strengthData[$date] = $metric['value'];
    }
}

// Calculate stats
$statsStmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM sessions WHERE member_id = ? AND session_date >= CURDATE()) as upcoming_sessions,
        (SELECT COUNT(*) FROM sessions WHERE member_id = ? AND status = 'completed') as completed_sessions,
        (SELECT COUNT(*) FROM workout_plans WHERE member_id = ? AND status = 'active') as active_plans,
        (SELECT COUNT(*) FROM progress_tracking WHERE member_id = ?) as progress_entries
");
$statsStmt->execute([$memberId, $memberId, $memberId, $memberId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - EliteFit Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .card-header h3 {
            font-size: 1.2rem;
            color: var(--secondary);
        }
        
        .card-body {
            color: var(--secondary);
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .card-icon.sessions {
            background-color: var(--primary);
        }
        
        .card-icon.completed {
            background-color: var(--success);
        }
        
        .card-icon.plans {
            background-color: var(--warning);
        }
        
        .card-icon.progress {
            background-color: var(--danger);
        }
        
        .profile-overview {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .profile-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid var(--primary);
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--secondary);
        }
        
        .profile-info {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .profile-stats {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .stat-item {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: var(--border-radius);
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .goals-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .goals-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .goals-header h3 {
            font-size: 1.2rem;
            color: var(--secondary);
        }
        
        .goals-list {
            list-style: none;
        }
        
        .goals-list li {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .goals-list li:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .goal-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            background-color: var(--primary);
            margin-right: 10px;
        }
        
        .sessions-list, .plans-list, .progress-list {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .sessions-list-header, .plans-list-header, .progress-list-header {
            padding: 15px 20px;
            background-color: var(--secondary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sessions-list-header h3, .plans-list-header h3, .progress-list-header h3 {
            font-size: 1.2rem;
        }
        
        .sessions-list-body, .plans-list-body, .progress-list-body {
            padding: 0;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            font-weight: 600;
            color: var(--secondary);
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
            color: white;
        }
        
        .badge-success {
            background-color: var(--success);
        }
        
        .badge-warning {
            background-color: var(--warning);
        }
        
        .badge-info {
            background-color: var(--primary);
        }
        
        .badge-danger {
            background-color: var(--danger);
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
        
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .chart-header {
            margin-bottom: 15px;
        }
        
        .chart-header h3 {
            font-size: 1.2rem;
            color: var(--secondary);
        }
        
        .chart-container {
            position: relative;
            height: 250px;
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
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .profile-overview {
                grid-template-columns: 1fr;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-dumbbell fa-2x"></i>
                <h2>EliteFit Gym</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="workout-plans.php"><i class="fas fa-dumbbell"></i> <span>Workout Plans</span></a></li>
                <li><a href="sessions.php"><i class="fas fa-calendar-alt"></i> <span>Sessions</span></a></li>
                <li><a href="progress.php"><i class="fas fa-chart-line"></i> <span>Progress</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Member Dashboard</h1>
                <div class="user-info">
                    <img src="../assets/images/profile-placeholder.jpg" alt="User Avatar">
                    <div class="dropdown">
                        <div class="dropdown-toggle" onclick="toggleDropdown()">
                            <span><?php echo htmlspecialchars($userName); ?></span>
                            <i class="fas fa-chevron-down ml-2"></i>
                        </div>
                        <div class="dropdown-menu" id="userDropdown">
                            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <h3>Upcoming Sessions</h3>
                        <div class="card-icon sessions">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $stats['upcoming_sessions'] ?? 0; ?></h2>
                        <p>Sessions Scheduled</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Completed Sessions</h3>
                        <div class="card-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $stats['completed_sessions'] ?? 0; ?></h2>
                        <p>Sessions Completed</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Active Plans</h3>
                        <div class="card-icon plans">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $stats['active_plans'] ?? 0; ?></h2>
                        <p>Workout Plans</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Progress Entries</h3>
                        <div class="card-icon progress">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $stats['progress_entries'] ?? 0; ?></h2>
                        <p>Tracking Records</p>
                    </div>
                </div>
            </div>
            
            <!-- Profile Overview -->
            <div class="profile-overview">
                <div class="profile-card">
                    <img src="../assets/images/profile-placeholder.jpg" alt="Profile Image" class="profile-image">
                    <h2 class="profile-name"><?php echo htmlspecialchars($userName); ?></h2>
                    <div class="profile-info">
                        <p>Experience: <?php echo htmlspecialchars($member['experience_level'] ?? 'Not specified'); ?></p>
                        <p>Member since: <?php echo date('M d, Y', strtotime($member['join_date'] ?? 'now')); ?></p>
                    </div>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $member['weight'] ?? '-'; ?> kg</div>
                            <div class="stat-label">Weight</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $member['height'] ?? '-'; ?> cm</div>
                            <div class="stat-label">Height</div>
                        </div>
                    </div>
                </div>
                
                <div class="goals-card">
                    <div class="goals-header">
                        <h3>Fitness Goals</h3>
                        <a href="edit-goals.php" class="btn btn-sm">Edit Goals</a>
                    </div>
                    <?php
                    $goals = explode(',', $member['fitness_goals'] ?? '');
                    if (!empty($goals) && $goals[0] != ''): ?>
                        <ul class="goals-list">
                            <?php foreach ($goals as $goal): ?>
                                <li>
                                    <div class="goal-icon">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <div><?php echo htmlspecialchars(trim($goal)); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No fitness goals set yet. Click "Edit Goals" to add your fitness objectives.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Progress Charts -->
            <div class="charts-container">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Weight Progress</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="weightChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Body Fat Percentage</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="bodyFatChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Strength Progress</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="strengthChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Sessions -->
            <div class="sessions-list">
                <div class="sessions-list-header">
                    <h3>Upcoming Sessions</h3>
                    <a href="request-session.php" class="btn btn-sm">Request Session</a>
                </div>
                <div class="sessions-list-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Trainer</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($upcomingSessions)): ?>
                                <?php foreach ($upcomingSessions as $session): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($session['session_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($session['start_time'])) . ' - ' . date('g:i A', strtotime($session['end_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($session['trainer_name']); ?></td>
                                        <td>
                                            <?php
                                                $statusClass = '';
                                                switch ($session['status']) {
                                                    case 'scheduled':
                                                        $statusClass = 'badge-info';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'badge-success';
                                                        break;
                                                    case 'cancelled':
                                                        $statusClass = 'badge-danger';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge-warning';
                                                }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(htmlspecialchars($session['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="session-details.php?id=<?php echo $session['session_id']; ?>" title="View Details"><i class="fas fa-eye"></i></a>
                                            <?php if ($session['status'] === 'scheduled'): ?>
                                                <a href="cancel-session.php?id=<?php echo $session['session_id']; ?>" title="Cancel Session"><i class="fas fa-times"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No upcoming sessions found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Active Workout Plans -->
            <div class="plans-list">
                <div class="plans-list-header">
                    <h3>Active Workout Plans</h3>
                    <a href="request-plan.php" class="btn btn-sm">Request Plan</a>
                </div>
                <div class="plans-list-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Plan Name</th>
                                <th>Trainer</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($workoutPlans)): ?>
                                <?php foreach ($workoutPlans as $plan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['trainer_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($plan['created_at'])); ?></td>
                                        <td>
                                            <span class="badge badge-success">
                                                Active
                                            </span>
                                        </td>
                                        <td>
                                            <a href="plan-details.php?id=<?php echo $plan['plan_id']; ?>" title="View Details"><i class="fas fa-eye"></i></a>
                                            <a href="track-progress.php?plan_id=<?php echo $plan['plan_id']; ?>" title="Track Progress"><i class="fas fa-chart-line"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No active workout plans found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Progress -->
            <div class="progress-list">
                <div class="progress-list-header">
                    <h3>Recent Progress Tracking</h3>
                    <a href="add-progress.php" class="btn btn-sm">Add Progress</a>
                </div>
                <div class="progress-list-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Weight</th>
                                <th>Body Fat %</th>
                                <th>Measurements</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($progressData)): ?>
                                <?php foreach ($progressData as $progress): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($progress['tracking_date'])); ?></td>
                                        <td><?php echo $progress['weight'] ? $progress['weight'] . ' kg' : '-'; ?></td>
                                        <td><?php echo $progress['body_fat_percentage'] ? $progress['body_fat_percentage'] . '%' : '-'; ?></td>
                                        <td>
                                            <?php
                                                $measurements = [];
                                                if ($progress['chest_measurement']) $measurements[] = 'Chest: ' . $progress['chest_measurement'] . ' cm';
                                                if ($progress['waist_measurement']) $measurements[] = 'Waist: ' . $progress['waist_measurement'] . ' cm';
                                                if ($progress['hip_measurement']) $measurements[] = 'Hip: ' . $progress['hip_measurement'] . ' cm';
                                                echo !empty($measurements) ? implode(', ', $measurements) : '-';
                                            ?>
                                        </td>
                                        <td>
                                            <a href="progress-details.php?id=<?php echo $progress['id']; ?>" title="View Details"><i class="fas fa-eye"></i></a>
                                            <a href="edit-progress.php?id=<?php echo $progress['id']; ?>" title="Edit Progress"><i class="fas fa-edit"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No progress data found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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
        
        // Weight Chart
        const weightCtx = document.getElementById('weightChart').getContext('2d');
        const weightChart = new Chart(weightCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($weightData)); ?>,
                datasets: [{
                    label: 'Weight (kg)',
                    data: <?php echo json_encode(array_values($weightData)); ?>,
                    backgroundColor: 'rgba(67, 97, 238, 0.2)',
                    borderColor: 'rgba(67, 97, 238, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(67, 97, 238, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
        
        // Body Fat Chart
        const bodyFatCtx = document.getElementById('bodyFatChart').getContext('2d');
        const bodyFatChart = new Chart(bodyFatCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($bodyFatData)); ?>,
                datasets: [{
                    label: 'Body Fat (%)',
                    data: <?php echo json_encode(array_values($bodyFatData)); ?>,
                    backgroundColor: 'rgba(76, 201, 240, 0.2)',
                    borderColor: 'rgba(76, 201, 240, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(76, 201, 240, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
        
        // Strength Chart
        const strengthCtx = document.getElementById('strengthChart').getContext('2d');
        const strengthChart = new Chart(strengthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($strengthData)); ?>,
                datasets: [{
                    label: 'Strength (kg)',
                    data: <?php echo json_encode(array_values($strengthData)); ?>,
                    backgroundColor: 'rgba(247, 37, 133, 0.2)',
                    borderColor: 'rgba(247, 37, 133, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(247, 37, 133, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
</body>
</html>
