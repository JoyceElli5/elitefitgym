<?php
require_once __DIR__ . '/../auth_middleware.php';
requireRole('Trainer');

$userId = $_SESSION['user_id'];
$conn = connectDB();

// 1. GET ASSIGNED MEMBERS
$membersStmt = $conn->prepare("SELECT DISTINCT 
        m.member_id,
        u.name,
        u.email,
        u.created_at,
        (
            SELECT COUNT(*) 
            FROM workout_plans wp 
            WHERE wp.member_id = m.member_id AND wp.trainer_id = ?
        ) AS plan_count,
        (
            SELECT COUNT(*) 
            FROM sessions s 
            WHERE s.member_id = m.member_id AND s.trainer_id = ?
        ) AS session_count
    FROM members m
    JOIN users u ON m.user_id = u.id
    LEFT JOIN sessions s ON m.member_id = s.member_id
    LEFT JOIN workout_plans wp ON m.member_id = wp.member_id
    WHERE s.trainer_id = ? OR wp.trainer_id = ?
    ORDER BY u.name
");
$membersStmt->execute([$userId, $userId, $userId, $userId]);
$members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. GET UNASSIGNED MEMBERS (for search or assignment)
$allMembersStmt = $conn->prepare("
    SELECT 
        m.member_id,
        u.name,
        u.email,
        u.created_at
    FROM members m
    JOIN users u ON m.user_id = u.id
    WHERE m.member_id NOT IN (
        SELECT DISTINCT m2.member_id
        FROM members m2
        LEFT JOIN sessions s ON m2.member_id = s.member_id
        LEFT JOIN workout_plans wp ON m2.member_id = wp.member_id
        WHERE s.trainer_id = ? OR wp.trainer_id = ?
    )
    ORDER BY u.name
");
$allMembersStmt->execute([$userId, $userId]);
$allMembers = $allMembersStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members - Trainer Dashboard - EliteFit Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Same CSS as dashboard.php with additions for members page */
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
        
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-container input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
        }
        
        .search-container button {
            padding: 10px 15px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
        }
        
        .members-list {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .members-list-header {
            padding: 15px 20px;
            background-color: var(--secondary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .members-list-header h3 {
            font-size: 1.2rem;
        }
        
        .members-list-body {
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
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
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
                <li><a href="members.php" class="active"><i class="fas fa-users"></i> <span>Members</span></a></li>
                <li><a href="workout-plans.php"><i class="fas fa-dumbbell"></i> <span>Workout Plans</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Members</h1>
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
            
            <!-- Search and Add Member -->
            <div class="search-container">
                <input type="text" id="memberSearch" placeholder="Search members...">
                <button onclick="searchMembers()"><i class="fas fa-search"></i> Search</button>
                <button class="btn" onclick="openAddMemberModal()"><i class="fas fa-user-plus"></i> Add Member</button>
            </div>
            
            <!-- Members List -->
            <div class="members-list">
                <div class="members-list-header">
                    <h3>Your Members</h3>
                </div>
                <div class="members-list-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Join Date</th>
                                <th>Plans</th>
                                <th>Sessions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="membersTableBody">
                            <?php if (!empty($members)): ?>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td><?php echo htmlspecialchars($member['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($member['join_date'])); ?></td>
                                        <td><?php echo $member['plan_count']; ?></td>
                                        <td><?php echo $member['session_count']; ?></td>
                                        <td>
                                            <a href="member-details.php?id=<?php echo $member['id']; ?>" class="btn btn-sm" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="create-plan.php?member_id=<?php echo $member['id']; ?>" class="btn btn-sm btn-success" title="Create Plan">
                                                <i class="fas fa-dumbbell"></i>
                                            </a>
                                            <a href="schedule-session.php?member_id=<?php echo $member['id']; ?>" class="btn btn-sm btn-warning" title="Schedule Session">
                                                <i class="fas fa-calendar-plus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No members found. Add members to get started.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Member Modal -->
    <div id="addMemberModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddMemberModal()">&times;</span>
            <h2>Add Member</h2>
            <div id="availableMembersList">
                <h3>Available Members</h3>
                <p>Select a member to add to your training list:</p>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($allMembers)): ?>
                            <?php foreach ($allMembers as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td>
                                        <button class="btn btn-sm" onclick="addMember(<?php echo $member['id']; ?>)">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No available members found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
            
            // Close modal when clicking outside
            if (event.target == document.getElementById('addMemberModal')) {
                closeAddMemberModal();
            }
        }
        
        function searchMembers() {
            const searchTerm = document.getElementById('memberSearch').value.toLowerCase();
            const tableRows = document.getElementById('membersTableBody').getElementsByTagName('tr');
            
            for (let i = 0; i < tableRows.length; i++) {
                const name = tableRows[i].getElementsByTagName('td')[0];
                const email = tableRows[i].getElementsByTagName('td')[1];
                
                if (name && email) {
                    const nameText = name.textContent || name.innerText;
                    const emailText = email.textContent || email.innerText;
                    
                    if (nameText.toLowerCase().indexOf(searchTerm) > -1 || emailText.toLowerCase().indexOf(searchTerm) > -1) {
                        tableRows[i].style.display = "";
                    } else {
                        tableRows[i].style.display = "none";
                    }
                }
            }
        }
        
        function openAddMemberModal() {
            document.getElementById('addMemberModal').style.display = 'block';
        }
        
        function closeAddMemberModal() {
            document.getElementById('addMemberModal').style.display = 'none';
        }
        
        function addMember(memberId) {
            // Send AJAX request to add member
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'add-member.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        alert('Member added successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                } else {
                    alert('Error adding member. Please try again.');
                }
            };
            xhr.send('member_id=' + memberId);
        }
    </script>
</body>
</html>
