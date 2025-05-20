<?php
// Include database connection
require_once __DIR__ . '/db_connect.php';

// Function to check table structure
function checkTableStructure($tableName) {
    try {
        $conn = connectDB();
        $stmt = $conn->prepare("DESCRIBE $tableName");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table: $tableName</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        return true;
    } catch (PDOException $e) {
        echo "<p>Error checking table $tableName: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Set headers for HTML output
header("Content-Type: text/html");
echo "<!DOCTYPE html><html><head><title>Database Structure Check</title></head><body>";

// Check all tables
$tables = ['users', 'members', 'trainers', 'admins', 'equipment_managers', 'sessions', 'registration_logs'];
foreach ($tables as $table) {
    checkTableStructure($table);
    echo "<br>";
}

echo "</body></html>";
?>