<?php
// Include authentication middleware
require_once __DIR__ . '/../auth_middleware.php';

// Require EquipmentManager role to access this page
requireRole('EquipmentManager');

// Get user data
$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];

// Connect to database
$conn = connectDB();

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $equipmentId = filter_input(INPUT_POST, 'equipment_id', FILTER_VALIDATE_INT);
    $scheduledDate = trim($_POST['scheduled_date'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'Scheduled');
    
    // Validate required fields
    if (!$equipmentId || empty($scheduledDate)) {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    } else {
        try {
            // Insert new maintenance schedule
            $stmt = $conn->prepare("INSERT INTO maintenance_schedule 
                (equipment_id, scheduled_date, description, status) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([$equipmentId, $scheduledDate, $description, $status]);
            
            $message = 'Maintenance scheduled successfully!';
            $messageType = 'success';
            
            // Clear form data after successful submission
            $equipmentId = $scheduledDate = $description = '';
            $status = 'Scheduled';
            
        } catch (PDOException $e) {
            $message = 'Error scheduling maintenance: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get equipment list for dropdown
$equipmentStmt = $conn->prepare("
    SELECT equipment_id, name, type, status 
    FROM equipment 
    ORDER BY name
");
$equipmentStmt->execute();
$equipmentList = $equipmentStmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming maintenance schedules
$maintenanceStmt = $conn->prepare("SELECT 
        ms.schedule_id,
        e.name AS equipment_name,
        e.type AS equipment_type,
        ms.scheduled_date,
        ms.description,
        ms.status
    FROM 
        maintenance_schedule ms
    JOIN 
        equipment e ON ms.equipment_id = e.equipment_id
    WHERE 
        ms.scheduled_date >= CURDATE()
    ORDER BY 
        ms.scheduled_date
    LIMIT 10
");
$maintenanceStmt->execute();
$maintenanceSchedules = $maintenanceStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Maintenance - EliteFit Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #ff4d4d;
            --secondary: #333;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
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
        
        .form-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-title {
            margin-bottom: 20px;
            color: var(--secondary);
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .btn {
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .btn:hover {
            background-color: #ff3333;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .required-field::after {
            content: " *";
            color: var(--danger);
        }
        
        .table-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .table-header {
            background-color: var(--secondary);
            color: white;
            padding: 15px 20px;
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
            background-color: var(--info);
        }
        
        .badge-danger {
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
            
            .form-row {
                flex-direction: column;
                gap: 0;
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
                <li><a href="add-equipment.php"><i class="fas fa-dumbbell"></i> <span>Equipment</span></a></li>
                <li><a href="schedule-maintenance.php" class="active"><i class="fas fa-tools"></i> <span>Maintenance</span></a></li>
                <li><a href="inventory.php"><i class="fas fa-clipboard-list"></i> <span>Inventory</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
       
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Schedule Maintenance</h1>
                <div class="user-info">
                    <img src="https://randomuser.me/api/portraits/men/3.jpg" alt="User Avatar">
                    <div class="dropdown">
                        <div class="dropdown-toggle" onclick="toggleDropdown()">
                            <span><?php echo htmlspecialchars($userName); ?></span>
                            <i class="fas fa-chevron-down ml-2"></i>
                        </div>
                        <div class="dropdown-menu" id="userDropdown">
                            <a href="#"><i class="fas fa-user"></i> Profile</a>
                            <a href="#"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Container -->
            <div class="form-container">
                <h2 class="form-title">Schedule New Maintenance</h2>
                
                <?php if (!empty($message)): ?>
                    <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-danger'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="equipment_id" class="required-field">Select Equipment</label>
                                <select id="equipment_id" name="equipment_id" class="form-control" required>
                                    <option value="">Select Equipment</option>
                                    <?php if (!empty($equipmentList)): ?>
                                        <?php foreach ($equipmentList as $equipment): ?>
                                            <option value="<?php echo $equipment['equipment_id']; ?>" <?php echo ($equipmentId ?? '') == $equipment['equipment_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($equipment['name'] . ' (' . $equipment['type'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="scheduled_date" class="required-field">Scheduled Date</label>
                                <input type="date" id="scheduled_date" name="scheduled_date" class="form-control" value="<?php echo htmlspecialchars($scheduledDate ?? ''); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="Scheduled" <?php echo ($status ?? '') === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="In Progress" <?php echo ($status ?? '') === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Completed" <?php echo ($status ?? '') === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <!-- Placeholder for balance -->
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Maintenance Details</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Schedule Maintenance</button>
                        <a href="dashboard.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                    </div>
                </form>
            </div>
            
            <!-- Maintenance Schedule Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Upcoming Maintenance Schedule</h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Type</th>
                            <th>Scheduled Date</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($maintenanceSchedules)): ?>
                            <?php foreach ($maintenanceSchedules as $schedule): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($schedule['equipment_name']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['equipment_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($schedule['scheduled_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['description']); ?></td>
                                    <td>
                                        <?php
                                            $statusClass = '';
                                            switch ($schedule['status']) {
                                                case 'Scheduled':
                                                    $statusClass = 'badge-info';
                                                    break;
                                                case 'In Progress':
                                                    $statusClass = 'badge-warning';
                                                    break;
                                                case 'Completed':
                                                    $statusClass = 'badge-success';
                                                    break;
                                                case 'Overdue':
                                                    $statusClass = 'badge-danger';
                                                    break;
                                                default:
                                                    $statusClass = 'badge-secondary';
                                            }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($schedule['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="#" title="Edit" onclick="editMaintenance(<?php echo $schedule['log_id']; ?>)"><i class="fas fa-edit"></i></a>
                                        <a href="#" title="Mark Complete" onclick="completeMaintenance(<?php echo $schedule['log_id']; ?>)"><i class="fas fa-check"></i></a>
                                        <a href="#" title="Cancel" onclick="cancelMaintenance(<?php echo $schedule['log_id']; ?>)"><i class="fas fa-times"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No maintenance scheduled.</td>
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
        }
        
        // Maintenance actions (these would be implemented with AJAX in a real application)
        function editMaintenance(id) {
            alert('Edit maintenance with ID: ' + id);
            // In a real application, you would redirect to an edit page or open a modal
        }
        
        function completeMaintenance(id) {
            if (confirm('Mark this maintenance as completed?')) {
                // In a real application, you would use AJAX to update the status
                alert('Maintenance marked as completed');
                // Then refresh the page or update the UI
                // location.reload();
            }
        }
        
        function cancelMaintenance(id) {
            if (confirm('Are you sure you want to cancel this maintenance?')) {
                // In a real application, you would use AJAX to cancel the maintenance
                alert('Maintenance cancelled');
                // Then refresh the page or update the UI
                // location.reload();
            }
        }
    </script>
</body>
</html>
