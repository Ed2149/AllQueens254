<?php
session_start();
include 'config.php';
include 'backup_account.php';

header("Location: ../frontend/homepage.html?message=Account deleted successfully");

// CSRF protection
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';

if (empty($csrfToken) || $csrfToken !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

if (!isset($_SESSION['creator_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated', 'redirect' => '../frontend/creator_login.html']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$creator_id = $_SESSION['creator_id'];
$password = $_POST['password'] ?? '';

// Rate limiting check
$current_time = time();
$window_start = $current_time - DELETE_ATTEMPT_WINDOW;

// Clean old attempts
$conn->query("DELETE FROM delete_attempts WHERE timestamp < $window_start");

// Check current attempts
$attempt_check = $conn->prepare("SELECT COUNT(*) as attempt_count FROM delete_attempts WHERE creator_id = ? AND timestamp > ?");
$attempt_check->bind_param("ii", $creator_id, $window_start);
$attempt_check->execute();
$attempt_result = $attempt_check->get_result()->fetch_assoc();

if ($attempt_result['attempt_count'] >= DELETE_ATTEMPT_LIMIT) {
    echo json_encode([
        'success' => false, 
        'message' => 'Too many attempts. Please try again in ' . ceil((DELETE_ATTEMPT_WINDOW - ($current_time - $window_start)) / 60) . ' minutes.'
    ]);
    exit;
}

// Record attempt
$record_attempt = $conn->prepare("INSERT INTO delete_attempts (creator_id, timestamp) VALUES (?, ?)");
$record_attempt->bind_param("ii", $creator_id, $current_time);
$record_attempt->execute();

// Verify password
$check_sql = "SELECT id, password, profile_pic FROM creators WHERE id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $creator_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Account not found']);
    exit;
}

$creator = $result->fetch_assoc();

if (!password_verify($password, $creator['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password']);
    exit;
}

// Create backup before deletion
if (!backupCreatorAccount($conn, $creator_id)) {
    echo json_encode(['success' => false, 'message' => 'Backup failed. Account not deleted for safety.']);
    exit;
}

// Begin transaction for deletion
$conn->begin_transaction();

try {
    // Delete media files and records
    $media_sql = "SELECT id, path FROM media WHERE creator_id = ?";
    $stmt = $conn->prepare($media_sql);
    $stmt->bind_param("i", $creator_id);
    $stmt->execute();
    $media_result = $stmt->get_result();
    
    while ($media = $media_result->fetch_assoc()) {
        $file_path = '../' . $media['path'];
        if (file_exists($file_path) && is_file($file_path)) {
            unlink($file_path);
        }
    }
    
    $delete_media_sql = "DELETE FROM media WHERE creator_id = ?";
    $stmt = $conn->prepare($delete_media_sql);
    $stmt->bind_param("i", $creator_id);
    $stmt->execute();
    
    // Delete profile picture
    if (!empty($creator['profile_pic']) && file_exists('../' . $creator['profile_pic'])) {
        unlink('../' . $creator['profile_pic']);
    }
    
    // Delete from other tables
    $tables = ['transactions'];
    
    foreach ($tables as $table) {
        $table_check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($table_check->num_rows > 0) {
            $conn->query("DELETE FROM $table WHERE creator_id = $creator_id");
        }
    }
    
    // Finally delete the creator account
    $delete_creator_sql = "DELETE FROM creators WHERE id = ?";
    $stmt = $conn->prepare($delete_creator_sql);
    $stmt->bind_param("i", $creator_id);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Clear all session data
    session_unset();
    session_destroy();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Account deleted successfully', 
        'redirect' => '../frontend/homepage.html'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Account deletion error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error deleting account: ' . $e->getMessage()]);
}

$conn->close();
ob_end_flush();
?>
