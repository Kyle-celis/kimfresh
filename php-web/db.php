<?php
// db.php
$host = 'localhost';
$dbname = 'kimfresh';
$username = 'root'; 
$password = 'KC2120320';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
