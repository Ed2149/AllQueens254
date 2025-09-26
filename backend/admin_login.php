<?php
session_start();
include 'config.php';

// Check if already logged in
if (isset($_SESSION['admin_id']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../frontend/admin_dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize inputs
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Check if admin exists
    $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $admin['password'])) {
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_logged_in'] = true;
            
            // Redirect to admin dashboard
            header("Location: ../frontend/admin_dashboard.php");
            exit;
        } else {
            // Invalid password
            header("Location: ../frontend/admin_login.html?error=Invalid password");
            exit;
        }
    } else {
        // Admin not found
        header("Location: ../frontend/admin_login.html?error=Admin not found");
        exit;
    }
} else {
    // Invalid request method
    header("Location: ../frontend/admin_login.html?error=Invalid request");
    exit;
}

$conn->close();
?>