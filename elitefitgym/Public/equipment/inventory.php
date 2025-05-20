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

// Get filter parameters
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the query with filters
$query = "
    SELECT 
        equipment_id, 
        name, 
        description, 
        category, 
        status, 
        type, 
        last_maintenance_date, 
        next_maintenance_date
    FROM 
        equipment
    WHERE 1=1
";

$params = [];

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
}

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR description LIKE ? OR type LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY name";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$equipmentList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter dropdown
$categoryStmt = $conn->prepare("SELECT DISTINCT category FROM equipment ORDER BY category");
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Get equipment statistics
$statsStmt = $conn->prepare("
    SELECT
        COUNT(CASE WHEN status = 'Available' THEN 1 END) as available_count,
        COUNT(CASE WHEN status = 'In Use' THEN 1 END) as in_use_count,
        COUNT(CASE WHEN status = 'Maintenance' THEN 1 END) as maintenance_count,
        COUNT(*) as total_equipment
    FROM equipment
");
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get maintenance needed equipment (where next_maintenance_date is in the past or within 7 days)
$maintenanceNeededStmt = $conn->prepare("
    SELECT 
        equipment_id, 
        name, 
        type, 
        next_maintenance_date
    FROM 
        equipment
    WHERE 
        next_maintenance_date IS NOT NULL 
        AND next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY 
        next_maintenance_date
    LIMIT 5
");
$maintenanceNeededStmt->execute();
$maintenanceNeeded = $maintenanceNeededStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - EliteFit Gym</title>
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
        
        .filter-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary);
        }
        
        .filter-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            height: 38px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h3 {
            font-size: 1.2rem;
            margin: 0;
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            color: var(--secondary);
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .action-btn:hover {
            color: var(--primary);
        }
        
        .maintenance-alert {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .maintenance-alert-header {
            background-color: var(--warning);
            color: white;
            padding: 15px 20px;
        }
        
        .maintenance-alert-body {
            padding: 15px 20px;
        }
        
        .maintenance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .maintenance-item:last-child {
            border-bottom: none;
        }
        
        .maintenance-item-name {
            font-weight: 500;
        }
        
        .maintenance-item-date {
            color: var(--danger);
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
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
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
                <li><a href="schedule-maintenance.php"><i class="fas fa-tools"></i> <span>Maintenance</span></a></li>
                <li><a href="#" class="active"><i class="fas fa-clipboard-list"></i> <span>Inventory</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
       
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Equipment Inventory</h1>
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
                        <h2><?php echo $stats['available_count'] ?? 0; ?></h2>
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
                        <h2><?php echo $stats['in_use_count'] ?? 0; ?></h2>
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
                        <h2><?php echo $stats['maintenance_count'] ?? 0; ?></h2>
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
                        <h2><?php echo $stats['total_equipment'] ?? 0; ?></h2>
                        <p>Total Equipment</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Container -->
            <div class="filter-container">
                <form class="filter-form" method="GET" action="">
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="filter-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="filter-control">
                            <option value="">All Status</option>
                            <option value="Available" <?php echo $status === 'Available' ? 'selected' : ''; ?>>Available</option>
                            <option value="In Use" <?php echo $status === 'In Use' ? 'selected' : ''; ?>>In Use</option>
                            <option value="Maintenance" <?php echo $status === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" class="filter-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search equipment...">
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="btn">Filter</button>
                        <a href="inventory.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
            
            <!-- Equipment Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Equipment Inventory</h3>
                    <a href="add-equipment.php" class="btn">Add New Equipment</a>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Last Maintenance</th>
                            <th>Next Maintenance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($equipmentList)): ?>
                            <?php foreach ($equipmentList as $equipment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($equipment['name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($equipment['category'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($equipment['type'] ?? ''); ?></td>
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
                                    <td>
                                        <?php 
                                            echo $equipment['last_maintenance_date'] 
                                                ? date('M d, Y', strtotime($equipment['last_maintenance_date'])) 
                                                : 'N/A'; 
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            echo $equipment['next_maintenance_date'] 
                                                ? date('M d, Y', strtotime($equipment['next_maintenance_date'])) 
                                                : 'N/A'; 
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="#" class="action-btn" title="View Details" onclick="viewEquipment(<?php echo $equipment['equipment_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" class="action-btn" title="Edit" onclick="editEquipment(<?php echo $equipment['equipment_id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="action-btn" title="Schedule Maintenance" onclick="scheduleMaintenance(<?php echo $equipment['equipment_id']; ?>)">
                                                <i class="fas fa-tools"></i>
                                            </a>
                                            <a href="#" class="action-btn" title="Delete" onclick="deleteEquipment(<?php echo $equipment['equipment_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No equipment found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Maintenance Needed Alert -->
            <?php if (!empty($maintenanceNeeded)): ?>
            <div class="maintenance-alert">
                <div class="maintenance-alert-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Equipment Needing Maintenance</h3>
                </div>
                <div class="maintenance-alert-body">
                    <?php foreach ($maintenanceNeeded as $item): ?>
                        <div class="maintenance-item">
                            <div class="maintenance-item-name">
                                <?php echo htmlspecialchars($item['name']); ?> (<?php echo htmlspecialchars($item['type']); ?>)
                            </div>
                            <div class="maintenance-item-date">
                                <?php 
                                    $date = new DateTime($item['next_maintenance_date']);
                                    $now = new DateTime();
                                    $interval = $now->diff($date);
                                    
                                    if ($date < $now) {
                                        echo '<span class="badge badge-danger">Overdue by ' . $interval->days . ' days</span>';
                                    } else {
                                        echo '<span class="badge badge-warning">Due in ' . $interval->days . ' days</span>';
                                    }
                                ?>
                                <a href="schedule-maintenance.php?equipment_id=<?php echo $item['equipment_id']; ?>" class="btn btn-sm">Schedule</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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
        
        // Equipment actions (these would be implemented with AJAX in a real application)
        function viewEquipment(id) {
            // In a real application, you would redirect to a details page or open a modal
            alert('View equipment with ID: ' + id);
        }
        
        function editEquipment(id) {
            // In a real application, you would redirect to an edit page
            window.location.href = 'edit-equipment.php?id=' + id;
        }
        
        function scheduleMaintenance(id) {
            // In a real application, you would redirect to the maintenance scheduling page
            window.location.href = 'schedule-maintenance.php?equipment_id=' + id;
        }
        
        function deleteEquipment(id) {
            if (confirm('Are you sure you want to delete this equipment?')) {
                // In a real application, you would use AJAX to delete the equipment
                alert('Equipment deleted');
                // Then refresh the page or update the UI
                // location.reload();
            }
        }
    </script>
</body>
</html>
