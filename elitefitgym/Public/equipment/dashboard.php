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

// Get equipment statistics
$equipmentStmt = $conn->prepare("
    SELECT
        COUNT(CASE WHEN status = 'Available' THEN 1 END) as available_count,
        COUNT(CASE WHEN status = 'In Use' THEN 1 END) as in_use_count,
        COUNT(CASE WHEN status = 'Maintenance' THEN 1 END) as maintenance_count,
        COUNT(*) as total_equipment
    FROM equipment
");
$equipmentStmt->execute();
$equipmentStats = $equipmentStmt->fetch(PDO::FETCH_ASSOC);

// Get equipment list - FIXED: Using equipment_id AS id instead of id
$listStmt = $conn->prepare("
    SELECT equipment_id AS id, name, type, status, last_maintenance_date
    FROM equipment
    ORDER BY status, name
");
$listStmt->execute();
$equipmentList = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// Get maintenance schedule
$maintenanceStmt = $conn->prepare("
    SELECT e.name, m.scheduled_date, m.description, m.status
    FROM maintenance_schedule m
    JOIN equipment e ON m.equipment_id = e.equipment_id
    WHERE m.scheduled_date >= CURDATE()
    ORDER BY m.scheduled_date
    LIMIT 5
");
$maintenanceStmt->execute();
$maintenanceSchedule = $maintenanceStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Manager Dashboard - EliteFit Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS styles would go here - similar to the other dashboard styling */
        /* This is a placeholder for the equipment manager dashboard styling */
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
       
        .card-icon.available {
            background-color: var(--success);
        }
       
        .card-icon.in-use {
            background-color: var(--info);
        }
       
        .card-icon.maintenance {
            background-color: var(--warning);
        }
       
        .card-icon.total {
            background-color: var(--primary);
        }
       
        .equipment-list, .maintenance-list {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
       
        .equipment-list-header, .maintenance-list-header {
            padding: 15px 20px;
            background-color: var(--secondary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
       
        .equipment-list-header h3, .maintenance-list-header h3 {
            font-size: 1.2rem;
        }
       
        .equipment-list-body, .maintenance-list-body {
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
            background-color: var(--info);
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
            background-color: #ff3333;
        }
       
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.9rem;
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
                <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="add-equipment.php"><i class="fas fa-dumbbell"></i> <span>Add Equipment</span></a></li>
                <li><a href="schedule-maintenance.php"><i class="fas fa-tools"></i> <span>Maintenance</span></a></li>
                <li><a href="inventory.php"><i class="fas fa-clipboard-list"></i> <span>Inventory</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
       
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Equipment Manager Dashboard</h1>
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
           
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <h3>Available</h3>
                        <div class="card-icon available">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $equipmentStats['available_count'] ?? 0; ?></h2>
                        <p>Equipment Available</p>
                    </div>
                </div>
               
                <div class="card">
                    <div class="card-header">
                        <h3>In Use</h3>
                        <div class="card-icon in-use">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $equipmentStats['in_use_count'] ?? 0; ?></h2>
                        <p>Equipment In Use</p>
                    </div>
                </div>
               
                <div class="card">
                    <div class="card-header">
                        <h3>Maintenance</h3>
                        <div class="card-icon maintenance">
                            <i class="fas fa-tools"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $equipmentStats['maintenance_count'] ?? 0; ?></h2>
                        <p>Under Maintenance</p>
                    </div>
                </div>
               
                <div class="card">
                    <div class="card-header">
                        <h3>Total</h3>
                        <div class="card-icon total">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $equipmentStats['total_equipment'] ?? 0; ?></h2>
                        <p>Total Equipment</p>
                    </div>
                </div>
            </div>
           
            <!-- Equipment List -->
            <div class="equipment-list">
                <div class="equipment-list-header">
                    <h3>Equipment Status</h3>
                    <a href="#" class="btn btn-sm">Add New Equipment</a>
                </div>
                <div class="equipment-list-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Last Maintenance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
if (is_object($equipmentList) && !($equipmentList instanceof Countable)) {
    // Convert to array or handle accordingly
    $equipmentList = (array)$equipmentList;
}
if (!empty($equipmentList)): ?>
                                <?php foreach ($equipmentList as $equipment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($equipment['name']); ?></td>
                                        <td><?php echo htmlspecialchars($equipment['type']); ?></td>
                                        <td>
                                            <?php
                                                $statusClass = '';
                                                switch ($equipment['status']) {
                                                    case 'Available':
                                                        $statusClass = 'badge-success';
                                                        break;
                                                    case 'In Use':
                                                        $statusClass = 'badge-info';
                                                        break;
                                                    case 'Maintenance':
                                                        $statusClass = 'badge-warning';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge-secondary';
                                                }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($equipment['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $equipment['last_maintenance_date'] ? date('M d, Y', strtotime($equipment['last_maintenance_date'])) : 'N/A'; ?></td>
                                        <td>
                                            <a href="#" title="View Details"><i class="fas fa-eye"></i></a>
                                            <a href="#" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="#" title="Schedule Maintenance"><i class="fas fa-tools"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No equipment found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
           
            <!-- Maintenance Schedule -->
            <div class="maintenance-list">
                <div class="maintenance-list-header">
                    <h3>Upcoming Maintenance</h3>
                    <a href="#" class="btn btn-sm">Schedule Maintenance</a>
                </div>
                <div class="maintenance-list-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Scheduled Date</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
if (!empty($maintenanceSchedule)): ?>
                                <?php foreach ($maintenanceSchedule as $maintenance): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($maintenance['name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($maintenance['scheduled_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($maintenance['description']); ?></td>
                                        <td>
                                            <?php
                                                $statusClass = '';
                                                switch ($maintenance['status']) {
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
                                                <?php echo htmlspecialchars($maintenance['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="#" title="View Details"><i class="fas fa-eye"></i></a>
                                            <a href="#" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="#" title="Mark Complete"><i class="fas fa-check"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No maintenance scheduled.</td>
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