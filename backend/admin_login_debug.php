<?php
session_start();
include 'config.php';

echo "<h2>Admin Login Debug</h2>";

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    echo "<p>Already logged in. Redirecting to dashboard...</p>";
    header("Location: ../frontend/admin_dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<p>Form was submitted via POST</p>";
    
    // Get and sanitize inputs
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    echo "<p>Username: " . $username . "</p>";
    echo "<p>Password: " . $password . "</p>";
    
    // Check if admin exists
    $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p>Admin found in database</p>";
        $admin = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $admin['password'])) {
            echo "<p>Password is correct</p>";
            
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_logged_in'] = true;
            
            echo "<p>Session variables set:</p>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
            
            // Test redirect
            echo "<p>Testing redirect...</p>";
            header("Location: ../frontend/admin_dashboard.php");
            exit;
        } else {
            echo "<p style='color: red;'>Password verification failed</p>";
            echo "<p>Stored hash: " . $admin['password'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Admin not found in database</p>";
    }
} else {
    echo "<p style='color: red;'>Form was not submitted via POST</p>";
}

echo "<p><a href='../frontend/admin_login.html'>Back to Login</a></p>";

$conn->close();
?>
