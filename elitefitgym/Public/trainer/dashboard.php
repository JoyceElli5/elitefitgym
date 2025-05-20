<?php
require_once __DIR__ . '/../auth_middleware.php';

// Require Trainer role to access this page
requireRole('Trainer');

// Get user data
$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];

// Connect to database
$conn = connectDB();

// 1) TODAY'S SESSIONS
$todayStmt = $conn->prepare("
    SELECT
        s.session_date,
        s.start_time,
        u.name AS member_name
    FROM sessions s
    JOIN members m ON s.member_id = m.member_id
    JOIN users u    ON m.user_id   = u.id
    WHERE s.trainer_id = ?
      AND DATE(s.session_date) = CURDATE()
    ORDER BY s.start_time
");
$todayStmt->execute([$userId]);
$todaySessions = $todayStmt->fetchAll(PDO::FETCH_ASSOC);

// 2) UPCOMING SESSIONS (exclude today, limit 5)
$upcomingStmt = $conn->prepare("
    SELECT
        s.session_date,
        s.start_time,
        u.name AS member_name
    FROM sessions s
    JOIN members m ON s.member_id = m.member_id
    JOIN users u    ON m.user_id   = u.id
    WHERE s.trainer_id = ?
      AND DATE(s.session_date) > CURDATE()
    ORDER BY s.session_date, s.start_time
    LIMIT 5
");
$upcomingStmt->execute([$userId]);
$upcomingSessions = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);

// 3) RECENT WORKOUT PLANS
$plansStmt = $conn->prepare("SELECT
        wp.created_at,
        wp.description,
        u.name AS member_name
    FROM workout_plans wp
    JOIN members m ON wp.member_id = m.member_id
    JOIN users u    ON m.user_id   = u.id
    WHERE wp.trainer_id = ?
    ORDER BY wp.created_at DESC
    LIMIT 5
");
$plansStmt->execute([$userId]);
$recentPlans = $plansStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - EliteFit Gym</title>
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
        
        .card-icon.members {
            background-color: var(--danger);
        }
        
        .sessions-list, .plans-list {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .sessions-list-header, .plans-list-header {
            padding: 15px 20px;
            background-color: var(--secondary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sessions-list-header h3, .plans-list-header h3 {
            font-size: 1.2rem;
        }
        
        .sessions-list-body, .plans-list-body {
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
        
        .today-sessions {
            margin-bottom: 30px;
        }
        
        .today-sessions h3 {
            margin-bottom: 15px;
            color: var(--secondary);
        }
        
        .session-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .session-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px;
        }
        
        .session-time {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .session-member {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .session-member img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .session-member span {
            font-weight: 500;
        }
        
        .session-status {
            margin-bottom: 10px;
        }
        
        .session-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .action-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
        }
        
        .action-icon.view {
            background-color: var(--primary);
        }
        
        .action-icon.edit {
            background-color: var(--warning);
        }
        
        .action-icon.complete {
            background-color: var(--success);
        }
        
        .action-icon.cancel {
            background-color: var(--danger);
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
            
            .session-cards {
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
                <li><a href="members.php"><i class="fas fa-users"></i> <span>Members</span></a></li>
                <li><a href="workout-plans.php"><i class="fas fa-dumbbell"></i> <span>Workout Plans</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Trainer Dashboard</h1>
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
                        <h3>Workout Plans</h3>
                        <div class="card-icon plans">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $stats['workout_plans'] ?? 0; ?></h2>
                        <p>Active Plans</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Active Members</h3>
                        <div class="card-icon members">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $stats['active_members'] ?? 0; ?></h2>
                        <p>Members Training</p>
                    </div>
                </div>
            </div>
            
            <!-- Today's Sessions -->
            <div class="today-sessions">
                <h3>Today's Sessions</h3>
                <?php if (!empty($todaySessions)): ?>
                    <div class="session-cards">
                        <?php foreach ($todaySessions as $session): ?>
                            <div class="session-card">
                                <div class="session-time">
                                    <?php echo date('g:i A', strtotime($session['start_time'])) . ' - ' . date('g:i A', strtotime($session['end_time'])); ?>
                                </div>
                                <div class="session-member">
                                    <img src="../assets/images/profile-placeholder.jpg" alt="Member">
                                    <span><?php echo htmlspecialchars($session['member_name']); ?></span>
                                </div>
                                <div class="session-status">
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
                                </div>
                                <div class="session-actions">
                                    <a href="session-details.php?id=<?php echo $session['session_id']; ?>" class="action-icon view" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($session['status'] === 'scheduled'): ?>
                                        <a href="edit-session.php?id=<?php echo $session['session_id']; ?>" class="action-icon edit" title="Edit Session">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="complete-session.php?id=<?php echo $session['session_id']; ?>" class="action-icon complete" title="Mark as Completed">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="cancel-session.php?id=<?php echo $session['session_id']; ?>" class="action-icon cancel" title="Cancel Session">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <p>No sessions scheduled for today.</p>
                            <a href="schedule-session.php" class="btn btn-sm">Schedule a Session</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Upcoming Sessions -->
            <div class="sessions-list">
                <div class="sessions-list-header">
                    <h3>Upcoming Sessions</h3>
                    <a href="schedule-session.php" class="btn btn-sm">Schedule New Session</a>
                </div>
                <div class="sessions-list-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Member</th>
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
                                        <td><?php echo htmlspecialchars($session['member_name']); ?></td>
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
                                            <a href="edit-session.php?id=<?php echo $session['session_id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="cancel-session.php?id=<?php echo $session['session_id']; ?>" title="Cancel Session"><i class="fas fa-times"></i></a>
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
            
            <!-- Recent Workout Plans -->
            <div class="plans-list">
                <div class="plans-list-header">
                    <h3>Recent Workout Plans</h3>
                    <a href="create-plan.php" class="btn btn-sm">Create New Plan</a>
                </div>
                <div class="plans-list-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Plan Name</th>
                                <th>Member</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentPlans)): ?>
                                <?php foreach ($recentPlans as $plan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['member_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($plan['created_at'])); ?></td>
                                        <td>
                                            <?php
                                                $statusClass = '';
                                                switch ($plan['status']) {
                                                    case 'active':
                                                        $statusClass = 'badge-success';
                                                        break;
                                                    case 'pending':
                                                        $statusClass = 'badge-warning';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'badge-info';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge-secondary';
                                                }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(htmlspecialchars($plan['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="plan-details.php?id=<?php echo $plan['plan_id']; ?>" title="View Details"><i class="fas fa-eye"></i></a>
                                            <a href="edit-plan.php?id=<?php echo $plan['plan_id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="duplicate-plan.php?id=<?php echo $plan['plan_id']; ?>" title="Duplicate Plan"><i class="fas fa-copy"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No workout plans found.</td>
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
    </script>
</body>
</html>
