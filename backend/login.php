<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM creators WHERE phone = '$phone'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['creator_id'] = $row['id'];
            // FIX: Redirect to creator's profile instead of homepage
            header("Location: ../frontend/creator_profile.php?id=" . $row['id']);
            exit;
        } else {
            header("Location: ../frontend/creator_login.php?error=Invalid password.");
            exit;
        }
    } else {
        header("Location: ../frontend/creator_login.php?error=No user found with that phone number.");
        exit;
    }
}

$conn->close();
?>