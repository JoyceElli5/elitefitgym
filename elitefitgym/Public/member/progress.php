<?php
require_once __DIR__ . '/../auth_middleware.php';
requireRole('Member');

$userId   = $_SESSION['user_id'];
$conn     = connectDB();

// 1) MEMBER PROFILE
$memberStmt = $conn->prepare("
    SELECT m.member_id, mp.fitness_goals, mp.experience_level, mp.preferred_workout_types,
           mp.health_conditions, mp.date_of_birth, mp.height, mp.weight AS starting_weight
    FROM members m
    LEFT JOIN member_profiles mp ON m.user_id = mp.user_id
    WHERE m.user_id = ?
");
$memberStmt->execute([$userId]);
$member = $memberStmt->fetch(PDO::FETCH_ASSOC);
$memberId = $member['member_id'];

// 2) ALL TRACKING ENTRIES
$progressStmt = $conn->prepare("
    SELECT 
      tracking_date,
      weight,
      body_fat_percentage,
      chest_measurement   AS chest,
      waist_measurement   AS waist,
      hip_measurement     AS hip,
      arm_measurement     AS arm,
      thigh_measurement   AS thigh,
      notes
    FROM progress_tracking
    WHERE member_id = ?
    ORDER BY tracking_date DESC
");
$progressStmt->execute([$memberId]);
$progressData = $progressStmt->fetchAll(PDO::FETCH_ASSOC);

// 3) STATS (total, first/last dates, initial vs current)
$statsStmt = $conn->prepare("
    SELECT
      COUNT(*)                          AS total_entries,
      MIN(tracking_date)               AS first_entry,
      MAX(tracking_date)               AS last_entry,
      (SELECT weight 
         FROM progress_tracking 
        WHERE member_id = ? 
        ORDER BY tracking_date ASC 
        LIMIT 1)                       AS initial_weight,
      (SELECT weight 
         FROM progress_tracking 
        WHERE member_id = ? 
        ORDER BY tracking_date DESC 
        LIMIT 1)                       AS current_weight,
      (SELECT body_fat_percentage 
         FROM progress_tracking 
        WHERE member_id = ? 
        ORDER BY tracking_date ASC 
        LIMIT 1)                       AS initial_body_fat,
      (SELECT body_fat_percentage 
         FROM progress_tracking 
        WHERE member_id = ? 
        ORDER BY tracking_date DESC 
        LIMIT 1)                       AS current_body_fat
    FROM progress_tracking
    WHERE member_id = ?
");
$statsStmt->execute([$memberId, $memberId, $memberId, $memberId, $memberId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// 4) DERIVED METRICS
$weightChange        = $stats['current_weight'] - $stats['initial_weight'];
$weightChangePercent = $stats['initial_weight']
    ? ($weightChange / $stats['initial_weight']) * 100
    : 0;
$bodyFatChange       = $stats['current_body_fat'] - $stats['initial_body_fat'];

// 5) HANDLE NEW ENTRY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_progress'])) {
    // sanitize & cast inputs
    $date = $_POST['tracking_date'];
    $w    = (float) $_POST['weight'];
    $bf   = (float) $_POST['body_fat'];
    $c    = $_POST['chest'] ?? null;
    $wa   = $_POST['waist'] ?? null;
    $h    = $_POST['hip'] ?? null;
    $a    = $_POST['arm'] ?? null;
    $t    = $_POST['thigh'] ?? null;
    $n    = $_POST['notes'] ?? '';

    $insert = $conn->prepare("
        INSERT INTO progress_tracking
          (member_id, tracking_date, weight, body_fat_percentage, 
           chest_measurement, waist_measurement, hip_measurement, arm_measurement, thigh_measurement, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->execute([$memberId, $date, $w, $bf, $c, $wa, $h, $a, $t, $n]);

    header("Location: progress.php");
    exit;
}

// (Now pass $member, $progressData, $stats, $weightChange, etc. to your view)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Tracking - EliteFit Gym</title>
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
        
        .progress-summary {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .summary-header h3 {
            font-size: 1.2rem;
            color: var(--secondary);
        }
        
        .summary-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .summary-icon.weight {
            background-color: var(--primary);
        }
        
        .summary-icon.body-fat {
            background-color: var(--success);
        }
        
        .summary-icon.entries {
            background-color: var(--warning);
        }
        
        .summary-icon.duration {
            background-color: var(--danger);
        }
        
        .summary-value {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .summary-label {
            color: #6c757d;
        }
        
        .change-indicator {
            display: flex;
            align-items: center;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .change-indicator.positive {
            color: var(--success);
        }
        
        .change-indicator.negative {
            color: var(--danger);
        }
        
        .change-indicator i {
            margin-right: 5px;
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
            height: 300px;
        }
        
        .progress-list {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .progress-list-header {
            padding: 15px 20px;
            background-color: var(--secondary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .progress-list-header h3 {
            font-size: 1.2rem;
        }
        
        .progress-list-body {
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
        
        .measurements-chart-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .measurements-chart-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .add-progress-form {
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
            
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
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
                    <a href="workout-plans.php">
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
                    <a href="progress.php" class="active">
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
                <h1>Progress Tracking</h1>
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
            
            <div class="add-progress-form">
                <div class="form-header">
                    <h3>Add New Progress Entry</h3>
                </div>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tracking_date">Date</label>
                            <input type="date" id="tracking_date" name="tracking_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="weight">Weight (kg)</label>
                            <input type="number" id="weight" name="weight" class="form-control" step="0.1" required>
                        </div>
                        <div class="form-group">
                            <label for="body_fat">Body Fat (%)</label>
                            <input type="number" id="body_fat" name="body_fat" class="form-control" step="0.1" required>
                        </div>
                    </div>
                    
                    <div class="form-header">
                        <h3>Measurements (cm)</h3>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="chest">Chest</label>
                            <input type="number" id="chest" name="chest" class="form-control" step="0.1">
                        </div>
                        <div class="form-group">
                            <label for="waist">Waist</label>
                            <input type="number" id="waist" name="waist" class="form-control" step="0.1">
                        </div>
                        <div class="form-group">
                            <label for="hip">Hip</label>
                            <input type="number" id="hip" name="hip" class="form-control" step="0.1">
                        </div>
                        <div class="form-group">
                            <label for="arm">Arm</label>
                            <input type="number" id="arm" name="arm" class="form-control" step="0.1">
                        </div>
                        <div class="form-group">
                            <label for="thigh">Thigh</label>
                            <input type="number" id="thigh" name="thigh" class="form-control" step="0.1">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_progress" class="btn">Add Progress</button>
                    </div>
                </form>
            </div>
            
            <div class="progress-summary">
                <div class="summary-card">
                    <div class="summary-header">
                        <h3>Current Weight</h3>
                        <div class="summary-icon weight">
                            <i class="fas fa-weight"></i>
                        </div>
                    </div>
                    <div class="summary-value"><?php echo isset($stats['current_weight']) ? $stats['current_weight'] . ' kg' : 'N/A'; ?></div>
                    <div class="summary-label">Current weight</div>
                    <?php if ($weightChange != 0): ?>
                        <div class="change-indicator <?php echo $weightChange < 0 ? 'positive' : 'negative'; ?>">
                            <i class="fas fa-<?php echo $weightChange < 0 ? 'arrow-down' : 'arrow-up'; ?>"></i>
                            <?php echo abs($weightChange); ?> kg (<?php echo number_format(abs($weightChangePercent), 1); ?>%)
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="summary-card">
                    <div class="summary-header">
                        <h3>Body Fat %</h3>
                        <div class="summary-icon body-fat">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                    <div class="summary-value"><?php echo isset($stats['current_body_fat']) ? $stats['current_body_fat'] . '%' : 'N/A'; ?></div>
                    <div class="summary-label">Current body fat percentage</div>
                    <?php if ($bodyFatChange != 0): ?>
                        <div class="change-indicator <?php echo $bodyFatChange < 0 ? 'positive' : 'negative'; ?>">
                            <i class="fas fa-<?php echo $bodyFatChange < 0 ? 'arrow-down' : 'arrow-up'; ?>"></i>
                            <?php echo abs($bodyFatChange); ?>%
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="summary-card">
                    <div class="summary-header">
                        <h3>Total Entries</h3>
                        <div class="summary-icon entries">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="summary-value"><?php echo $stats['total_entries']; ?></div>
                    <div class="summary-label">Progress entries recorded</div>
                </div>
                
                <div class="summary-card">
                    <div class="summary-header">
                        <h3>Tracking Duration</h3>
                        <div class="summary-icon duration">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="summary-value">
                        <?php 
                        if ($stats['first_entry'] && $stats['last_entry']) {
                            $first = new DateTime($stats['first_entry']);
                            $last = new DateTime($stats['last_entry']);
                            $diff = $first->diff($last);
                            echo $diff->days + 1;
                        } else {
                            echo '0';
                        }
                        ?>
                    </div>
                    <div class="summary-label">Days tracking progress</div>
                </div>
            </div>
            
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
                        <h3>Body Fat Progress</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="bodyFatChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="measurements-chart-container">
                <div class="measurements-chart-card">
                    <div class="chart-header">
                        <h3>Body Measurements</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="measurementsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="progress-list">
                <div class="progress-list-header">
                    <h3>Progress History</h3>
                </div>
                <div class="progress-list-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Weight (kg)</th>
                                <th>Body Fat (%)</th>
                                <th>Chest (cm)</th>
                                <th>Waist (cm)</th>
                                <th>Hip (cm)</th>
                                <th>Arm (cm)</th>
                                <th>Thigh (cm)</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($progressData as $entry): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($entry['tracking_date'])); ?></td>
                                    <td><?php echo $entry['weight']; ?></td>
                                    <td><?php echo $entry['body_fat_percentage']; ?></td>
                                    <td><?php echo $entry['chest'] ?? '-'; ?></td>
                                    <td><?php echo $entry['waist'] ?? '-'; ?></td>
                                    <td><?php echo $entry['hip'] ?? '-'; ?></td>
                                    <td><?php echo $entry['arm'] ?? '-'; ?></td>
                                    <td><?php echo $entry['thigh'] ?? '-'; ?></td>
                                    <td><?php echo $entry['notes'] ? htmlspecialchars(substr($entry['notes'], 0, 30)) . (strlen($entry['notes']) > 30 ? '...' : '') : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($progressData) === 0): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center;">No progress entries yet. Add your first entry above.</td>
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
        var weightCtx = document.getElementById('weightChart').getContext('2d');
        var weightChart = new Chart(weightCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($weightData)); ?>,
                datasets: [{
                    label: 'Weight (kg)',
                    data: <?php echo json_encode(array_values($weightData)); ?>,
                    backgroundColor: 'rgba(67, 97, 238, 0.2)',
                    borderColor: 'rgba(67, 97, 238, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    pointBackgroundColor: 'rgba(67, 97, 238, 1)',
                    pointRadius: 4
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
        var bodyFatCtx = document.getElementById('bodyFatChart').getContext('2d');
        var bodyFatChart = new Chart(bodyFatCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($bodyFatData)); ?>,
                datasets: [{
                    label: 'Body Fat (%)',
                    data: <?php echo json_encode(array_values($bodyFatData)); ?>,
                    backgroundColor: 'rgba(76, 201, 240, 0.2)',
                    borderColor: 'rgba(76, 201, 240, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    pointBackgroundColor: 'rgba(76, 201, 240, 1)',
                    pointRadius: 4
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
        
        // Measurements Chart
        var measurementsCtx = document.getElementById('measurementsChart').getContext('2d');
        var measurementsChart = new Chart(measurementsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($chestData)); ?>,
                datasets: [
                    {
                        label: 'Chest (cm)',
                        data: <?php echo json_encode(array_values($chestData)); ?>,
                        borderColor: 'rgba(67, 97, 238, 1)',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        borderWidth: 2,
                        tension: 0.1,
                        pointBackgroundColor: 'rgba(67, 97, 238, 1)',
                        pointRadius: 3
                    },
                    {
                        label: 'Waist (cm)',
                        data: <?php echo json_encode(array_values($waistData)); ?>,
                        borderColor: 'rgba(76, 201, 240, 1)',
                        backgroundColor: 'rgba(76, 201, 240, 0.1)',
                        borderWidth: 2,
                        tension: 0.1,
                        pointBackgroundColor: 'rgba(76, 201, 240, 1)',
                        pointRadius: 3
                    },
                    {
                        label: 'Hip (cm)',
                        data: <?php echo json_encode(array_values($hipData)); ?>,
                        borderColor: 'rgba(247, 37, 133, 1)',
                        backgroundColor: 'rgba(247, 37, 133, 0.1)',
                        borderWidth: 2,
                        tension: 0.1,
                        pointBackgroundColor: 'rgba(247, 37, 133, 1)',
                        pointRadius: 3
                    },
                    {
                        label: 'Arm (cm)',
                        data: <?php echo json_encode(array_values($armData)); ?>,
                        borderColor: 'rgba(114, 9, 183, 1)',
                        backgroundColor: 'rgba(114, 9, 183, 0.1)',
                        borderWidth: 2,
                        tension: 0.1,
                        pointBackgroundColor: 'rgba(114, 9, 183, 1)',
                        pointRadius: 3
                    },
                    {
                        label: 'Thigh (cm)',
                        data: <?php echo json_encode(array_values($thighData)); ?>,
                        borderColor: 'rgba(58, 12, 163, 1)',
                        backgroundColor: 'rgba(58, 12, 163, 0.1)',
                        borderWidth: 2,
                        tension: 0.1,
                        pointBackgroundColor: 'rgba(58, 12, 163, 1)',
                        pointRadius: 3
                    }
                ]
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
