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
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $status = trim($_POST['status'] ?? 'Available');
    
    // Validate required fields
    if (empty($name) || empty($category) || empty($type)) {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    } else {
        try {
            // Insert new equipment
            $stmt = $conn->prepare("
                INSERT INTO equipment 
                (name, description, category, status, type, last_maintenance_date) 
                VALUES (?, ?, ?, ?, ?, NULL)
            ");
            
            $stmt->execute([$name, $description, $category, $status, $type]);
            
            $message = 'Equipment added successfully!';
            $messageType = 'success';
            
            // Clear form data after successful submission
            $name = $description = $category = $type = '';
            $status = 'Available';
            
        } catch (PDOException $e) {
            $message = 'Error adding equipment: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get equipment categories for dropdown
$categoryStmt = $conn->prepare("SELECT DISTINCT category FROM equipment ORDER BY category");
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Get equipment types for dropdown
$typeStmt = $conn->prepare("SELECT DISTINCT type FROM equipment ORDER BY type");
$typeStmt->execute();
$types = $typeStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment - EliteFit Gym</title>
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
                <li><a href="#" class="active"><i class="fas fa-dumbbell"></i> <span>Equipment</span></a></li>
                <li><a href="schedule-maintenance.php"><i class="fas fa-tools"></i> <span>Maintenance</span></a></li>
                <li><a href="inventory.php"><i class="fas fa-clipboard-list"></i> <span>Inventory</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
       
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Add New Equipment</h1>
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
                <h2 class="form-title">Equipment Details</h2>
                
                <?php if (!empty($message)): ?>
                    <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-danger'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="name" class="required-field">Equipment Name</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="Available" <?php echo ($status ?? '') === 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="In Use" <?php echo ($status ?? '') === 'In Use' ? 'selected' : ''; ?>>In Use</option>
                                    <option value="Maintenance" <?php echo ($status ?? '') === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="category" class="required-field">Category</label>
                                <select id="category" name="category" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category ?? '') === $cat ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <option value="new">+ Add New Category</option>
                                </select>
                                <input type="text" id="new-category" name="new_category" class="form-control" style="display: none; margin-top: 10px;" placeholder="Enter new category">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="type" class="required-field">Equipment Type</label>
                                <select id="type" name="type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <?php if (!empty($types)): ?>
                                        <?php foreach ($types as $t): ?>
                                            <option value="<?php echo htmlspecialchars($t); ?>" <?php echo ($type ?? '') === $t ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($t); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <option value="new">+ Add New Type</option>
                                </select>
                                <input type="text" id="new-type" name="new_type" class="form-control" style="display: none; margin-top: 10px;" placeholder="Enter new type">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Add Equipment</button>
                        <a href="dashboard.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                    </div>
                </form>
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
        
        // Handle "Add New" options in dropdowns
        document.getElementById('category').addEventListener('change', function() {
            var newCategoryInput = document.getElementById('new-category');
            if (this.value === 'new') {
                newCategoryInput.style.display = 'block';
                newCategoryInput.setAttribute('required', 'required');
            } else {
                newCategoryInput.style.display = 'none';
                newCategoryInput.removeAttribute('required');
            }
        });
        
        document.getElementById('type').addEventListener('change', function() {
            var newTypeInput = document.getElementById('new-type');
            if (this.value === 'new') {
                newTypeInput.style.display = 'block';
                newTypeInput.setAttribute('required', 'required');
            } else {
                newTypeInput.style.display = 'none';
                newTypeInput.removeAttribute('required');
            }
        });
    </script>
</body>
</html>
