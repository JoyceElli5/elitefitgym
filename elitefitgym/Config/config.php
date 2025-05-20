<?php
$host = 'localhost';
$db = 'elitefitgym';  // updated DB name
$user = 'root';
$pass = 'plantain2020';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}