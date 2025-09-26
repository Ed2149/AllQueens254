<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $creator_id = mysqli_real_escape_string($conn, $_POST['creator_id']);
    $package = mysqli_real_escape_string($conn, $_POST['package']);

    $sql = "UPDATE creators SET paid = 1, package = '$package' WHERE id = $creator_id";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

$conn->close();
?>