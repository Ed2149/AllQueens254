<?php
// Assume admin login separately
include 'config.php';

if (isset($_POST['ban'])) {
    $id = $_POST['id'];
    $sql = "UPDATE creators SET banned = 1 WHERE id = $id";
    $conn->query($sql);
}

// List creators
$sql = "SELECT * FROM creators";
$result = $conn->query($sql);
echo "<table><tr><th>ID</th><th>Name</th><th>Actions</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td><form method='POST'><input type='hidden' name='id' value='{$row['id']}'><button name='ban'>Ban</button></form></td></tr>";
}
echo "</table>";

$conn->close();
?>