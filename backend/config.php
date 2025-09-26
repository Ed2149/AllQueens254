<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "allqueens254";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// New security settings
define('DELETE_ATTEMPT_LIMIT', 5); // Max 5 attempts per hour
define('DELETE_ATTEMPT_WINDOW', 3600); // 1 hour in seconds
define('BACKUP_ENABLED', true); // Enable/disable backups
?>