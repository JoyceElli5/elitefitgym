<?php
require_once __DIR__ . '/../auth_middleware.php';
requireRole('Trainer');

$userId = $_SESSION['user_id'];
$conn = connectDB();

// 1. ACTIVE WORKOUT PLANS
$activePlansStmt = $conn->prepare("
    SELECT wp.*, u.name AS member_name
    FROM workout_plans wp
    JOIN members m ON wp.member_id = m.member_id
    JOIN users u ON m.user_id = u.id
    WHERE wp.trainer_id = ? AND wp.status = 'active'
    ORDER BY wp.created_at DESC
");
$activePlansStmt->execute([$userId]);
$activePlans = $activePlansStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. PENDING WORKOUT PLANS
$pendingPlansStmt = $conn->prepare("
    SELECT wp.*, u.name AS member_name
    FROM workout_plans wp
    JOIN members m ON wp.member_id = m.member_id
    JOIN users u ON m.user_id = u.id
    WHERE wp.trainer_id = ? AND wp.status = 'pending'
    ORDER BY wp.created_at DESC
");
$pendingPlansStmt->execute([$userId]);
$pendingPlans = $pendingPlansStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. COMPLETED WORKOUT PLANS (LIMIT 10)
$completedPlansStmt = $conn->prepare("
    SELECT wp.*, u.name AS member_name
    FROM workout_plans wp
    JOIN members m ON wp.member_id = m.member_id
    JOIN users u ON m.user_id = u.id
    WHERE wp.trainer_id = ? AND wp.status = 'completed'
    ORDER BY wp.created_at DESC
    LIMIT 10
");
$completedPlansStmt->execute([$userId]);
$completedPlans = $completedPlansStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Plans - Trainer Dashboard - EliteFit Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Same CSS as dashboard.php with additions for workout plans page */
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
        
        .plans-section {
            margin-bottom: 30px;
        }
        
        .plans-list {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .plans-list-header {
            padding: 15px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .plans-list-header.active {
            background-color: var(--success);
        }
        
        .plans-list-header.pending {
            background-color: var(--warning);
        }
        
        .plans-list-header.completed {
            background-color: var(--secondary);
        }
        
        .plans-list-header h3 {
            font-size: 1.2rem;
        }
        
        .plans-list-body {
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
        
        .badge-secondary {
            background-color: var(--secondary);
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
        
        .btn-success {
            background-color: var(--success);
        }
        
        .btn-success:hover {
            background-color: #3bb5db;
        }
        
        .btn-warning {
            background-color: var(--warning);
        }
        
        .btn-warning:hover {
            background-color: #e31b6d;
        }
        
        .btn-secondary {
            background-color: var(--secondary);
        }
        
        .btn-secondary:hover {
            background-color: #2f0a82;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 600px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="members.php"><i class="fas fa-users"></i> <span>Members</span></a></li>
                <li><a href="workout-plans.php" class="active"><i class="fas fa-dumbbell"></i> <span>Workout Plans</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Workout Plans</h1>
                <div class="user-info">
                    <img src="../assets/images/profile-placeholder.jpg" alt="User Avatar">
                    <div class="dropdown">
                        <div class="dropdown-toggle" onclick="toggleDropdown()">
                            <span><?php echo htmlspecialchars($name ?? ''); ?></span>
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
            
            <!-- Create Plan Button -->
            <div style="margin-bottom: 20px;">
                <a href="create-plan.php" class="btn">
                    <i class="fas fa-plus"></i> Create New Workout Plan
                </a>
            </div>
            
            <!-- Pending Plans Section -->
            <div class="plans-section">
                <div class="plans-list">
                    <div class="plans-list-header pending">
                        <h3>Pending Workout Plans</h3>
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
                                <?php if (!empty($pendingPlans)): ?>
                                    <?php foreach ($pendingPlans as $plan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                            <td><?php echo htmlspecialchars($plan['member_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($plan['created_at'])); ?></td>
                                            <td><span class="badge badge-warning">Pending</span></td>
                                            <td>
                                                <a href="plan-details.php?id=<?php echo $plan['plan_id']; ?>" class="btn btn-sm" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit-plan.php?id=<?php echo $plan['plan_id']; ?>" class="btn btn-sm btn-warning" title="Edit Plan">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-success" onclick="activatePlan(<?php echo $plan['plan_id']; ?>)" title="Activate Plan">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">No pending workout plans.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Active Plans Section -->
            <div class="plans-section">
                <div class="plans-list">
                    <div class="plans-list-header active">
                        <h3>Active Workout Plans</h3>
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
                                <?php if (!empty($activePlans)): ?>
                                    <?php foreach ($activePlans as $plan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                            <td><?php echo htmlspecialchars($plan['member_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($plan['created_at'])); ?></td>
                                            <td><span class="badge badge-success">Active</span></td>
                                            <td>
                                                <a href="plan-details.php?id=<?php echo $plan['plan_id']; ?>" class="btn btn-sm" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit-plan.php?id=<?php echo $plan['plan_id']; ?>" class="btn btn-sm btn-warning" title="Edit Plan">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-secondary" onclick="completePlan(<?php echo $plan['plan_id']; ?>)" title="Mark as Completed">
                                                    <i class="fas fa-check-double"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">No active workout plans.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Completed Plans Section -->
            <div class="plans-section">
                <div class="plans-list">
                    <div class="plans-list-header completed">
                        <h3>Completed Workout Plans</h3>
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
                                <?php if (!empty($completedPlans)): ?>
                                    <?php foreach ($completedPlans as $plan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                            <td><?php echo htmlspecialchars($plan['member_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($plan['created_at'])); ?></td>
                                            <td><span class="badge badge-secondary">Completed</span></td>
                                            <td>
                                                <a href="plan-details.php?id=<?php echo $plan['plan_id']; ?>" class="btn btn-sm" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="duplicate-plan.php?id=<?php echo $plan['plan_id']; ?>" class="btn btn-sm btn-info" title="Duplicate Plan">
                                                    <i class="fas fa-copy"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">No completed workout plans.</td>
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
        
        function activatePlan(planId) {
            if (confirm('Are you sure you want to activate this workout plan?')) {
                // Send AJAX request to activate plan
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'update-plan-status.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            alert('Workout plan activated successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    } else {
                        alert('Error updating plan status. Please try again.');
                    }
                };
                xhr.send('plan_id=' + planId + '&status=active');
            }
        }
        
        function completePlan(planId) {
            if (confirm('Are you sure you want to mark this workout plan as completed?')) {
                // Send AJAX request to complete plan
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'update-plan-status.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            alert('Workout plan marked as completed!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    } else {
                        alert('Error updating plan status. Please try again.');
                    }
                };
                xhr.send('plan_id=' + planId + '&status=completed');
            }
        }
    </script>
</body>
</html>
