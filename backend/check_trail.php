<?php
include 'config.php';

header('Content-Type: application/json');

$phone = mysqli_real_escape_string($conn, $_GET['phone'] ?? '');
$response = ['expired' => false];

$sql = "SELECT trail_end FROM creators WHERE phone = '$phone'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $response['expired'] = date('Y-m-d') > $row['trail_end'];
}

echo json_encode($response);

$conn->close();
?>