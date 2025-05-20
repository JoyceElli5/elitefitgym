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

// Get all sessions
$sessionsStmt = $conn->prepare("
    SELECT s.*, t.user_id as trainer_name
    FROM sessions s
    JOIN trainers t ON s.trainer_id = t.user_id
    WHERE s.member_id = ?
    ORDER BY s.session_date DESC, s.start_time DESC
");
$sessionsStmt->execute([$memberId]);
$sessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Group sessions by status
$upcomingSessions = [];
$completedSessions = [];
$cancelledSessions = [];

foreach ($sessions as $session) {
    if ($session['status'] === 'scheduled' && (strtotime($session['session_date']) > strtotime('today') || 
        (strtotime($session['session_date']) == strtotime('today') && strtotime($session['start_time']) > time()))) {
        $upcomingSessions[] = $session;
    } else if ($session['status'] === 'completed') {
        $completedSessions[] = $session;
    } else if ($session['status'] === 'cancelled') {
        $cancelledSessions[] = $session;
    }
}

// Get all trainers for booking form
$trainersStmt = $conn->prepare("
    SELECT user_id, specialization
    FROM trainers
");
$trainersStmt->execute();
$trainers = $trainersStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle session booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_session'])) {
    $trainerId = $_POST['trainer_id'];
    $sessionDate = $_POST['session_date'];
    $startTime = $_POST['start_time'];
    $sessionType = $_POST['session_type'];
    $sessionNotes = $_POST['session_notes'] ?? null;
    
    // Calculate end time (1 hour after start time)
    $endTime = date('H:i:s', strtotime($startTime . ' + 1 hour'));
    
    // Check if trainer is available at the requested time
    $availabilityStmt = $conn->prepare("
        SELECT COUNT(*) as conflict_count
        FROM sessions
        WHERE trainer_id = ? AND session_date = ? AND status = 'scheduled'
        AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
    ");
    $availabilityStmt->execute([$trainerId, $sessionDate, $startTime, $startTime, $endTime, $endTime]);
    $availability = $availabilityStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($availability['conflict_count'] > 0) {
        $bookingError = "The trainer is not available at the selected time. Please choose a different time.";
    } else {
        // Insert session booking
        $insertStmt = $conn->prepare("
            INSERT INTO sessions (member_id, trainer_id, session_date, start_time, end_time, session_type, notes, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())
        ");
        $insertStmt->execute([$memberId, $trainerId, $sessionDate, $startTime, $endTime, $sessionType, $sessionNotes]);
        
        // Redirect to refresh the page
        header("Location: sessions.php");
        exit;
    }
}

// Handle session cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_session'])) {
    $sessionId = $_POST['session_id'];
    
    // Update session status to cancelled
    $cancelStmt = $conn->prepare("
        UPDATE sessions
        SET status = 'cancelled'
        WHERE session_id = ? AND member_id = ? AND status = 'scheduled'
    ");
    $cancelStmt->execute([$sessionId, $memberId]);
    
    // Redirect to refresh the page
    header("Location: sessions.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Sessions - EliteFit Gym</title>
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
        
        .book-session-form {
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
        
        .form-error {
            color: var(--warning);
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .sessions-container {
            margin-bottom: 30px;
        }
        
        .sessions-header {
            margin-bottom: 20px;
        }
        
        .sessions-header h2 {
            font-size: 1.5rem;
            color: var(--secondary);
        }
        
        .sessions-list {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .sessions-list-header {
            padding: 15px 20px;
            background-color: var(--secondary);
            color: white;
        }
        
        .sessions-list-header h3 {
            font-size: 1.2rem;
        }
        
        .sessions-list-body {
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
        
        .session-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }
        
        .session-status.scheduled {
            background-color: rgba(76, 201, 240, 0.2);
            color: var(--success);
        }
        
        .session-status.completed {
            background-color: rgba(58, 12, 163, 0.2);
            color: var(--secondary);
        }
        
        .session-status.cancelled {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--warning);
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
        
        .btn-danger {
            background-color: var(--warning);
        }
        
        .btn-danger:hover {
            background-color: #d91a74;
        }
        
        .empty-message {
            padding: 20px;
            text-align: center;
            color: #6c757d;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .table {
                display: block;
                overflow-x: auto;
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
                    <a href="workout-plans.php">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Workout Plans</span>
                    </a>
                </li>
                <li>
                    <a href="sessions.php" class="active">
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
                <h1>Training Sessions</h1>
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
            
            <div class="book-session-form">
                <div class="form-header">
                    <h3>Book a New Training Session</h3>
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
                            <label for="session_date">Date</label>
                            <input type="date" id="session_date" name="session_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="start_time">Time</label>
                            <input type="time" id="start_time" name="start_time" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_type">Session Type</label>
                        <select id="session_type" name="session_type" class="form-control" required>
                            <option value="Personal Training">Personal Training</option>
                            <option value="Fitness Assessment">Fitness Assessment</option>
                            <option value="Nutrition Consultation">Nutrition Consultation</option>
                            <option value="Rehabilitation">Rehabilitation</option>
                            <option value="Specialized Training">Specialized Training</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_notes">Additional Notes</label>
                        <textarea id="session_notes" name="session_notes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <?php if (isset($bookingError)): ?>
                        <div class="form-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $bookingError; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <button type="submit" name="book_session" class="btn">Book Session</button>
                    </div>
                </form>
            </div>
            
            <div class="sessions-container">
                <div class="sessions-header">
                    <h2>Upcoming Sessions</h2>
                </div>
                <div class="sessions-list">
                    <div class="sessions-list-header">
                        <h3>Scheduled Sessions</h3>
                    </div>
                    <div class="sessions-list-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Session Type</th>
                                    <th>Trainer</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($upcomingSessions) > 0): ?>
                                    <?php foreach ($upcomingSessions as $session): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($session['session_date'])); ?><br>
                                                <span style="font-size: 0.9rem; color: #6c757d;">
                                                    <?php echo date('h:i A', strtotime($session['start_time'])); ?> - 
                                                    <?php echo date('h:i A', strtotime($session['end_time'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($session['session_type']); ?></td>
                                            <td><?php echo htmlspecialchars($session['trainer_name']); ?></td>
                                            <td>
                                                <span class="session-status scheduled">Scheduled</span>
                                            </td>
                                            <td>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to cancel this session?');">
                                                    <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                                                    <button type="submit" name="cancel_session" class="btn btn-sm btn-danger">Cancel</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="empty-message">No upcoming sessions scheduled.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="sessions-container">
                <div class="sessions-header">
                    <h2>Session History</h2>
                </div>
                <div class="sessions-list">
                    <div class="sessions-list-header">
                        <h3>Completed Sessions</h3>
                    </div>
                    <div class="sessions-list-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Session Type</th>
                                    <th>Trainer</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($completedSessions) > 0): ?>
                                    <?php foreach ($completedSessions as $session): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($session['session_date'])); ?><br>
                                                <span style="font-size: 0.9rem; color: #6c757d;">
                                                    <?php echo date('h:i A', strtotime($session['start_time'])); ?> - 
                                                    <?php echo date('h:i A', strtotime($session['end_time'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($session['session_type']); ?></td>
                                            <td><?php echo htmlspecialchars($session['trainer_name']); ?></td>
                                            <td>
                                                <span class="session-status completed">Completed</span>
                                            </td>
                                            <td>
                                                <?php echo $session['feedback'] ? htmlspecialchars(substr($session['feedback'], 0, 30)) . (strlen($session['feedback']) > 30 ? '...' : '') : '-'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="empty-message">No completed sessions yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="sessions-list" style="margin-top: 20px;">
                    <div class="sessions-list-header">
                        <h3>Cancelled Sessions</h3>
                    </div>
                    <div class="sessions-list-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Session Type</th>
                                    <th>Trainer</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($cancelledSessions) > 0): ?>
                                    <?php foreach ($cancelledSessions as $session): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($session['session_date'])); ?><br>
                                                <span style="font-size: 0.9rem; color: #6c757d;">
                                                    <?php echo date('h:i A', strtotime($session['start_time'])); ?> - 
                                                    <?php echo date('h:i A', strtotime($session['end_time'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($session['session_type']); ?></td>
                                            <td><?php echo htmlspecialchars($session['trainer_name']); ?></td>
                                            <td>
                                                <span class="session-status cancelled">Cancelled</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="empty-message">No cancelled sessions.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
