<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['creator_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $media_id = filter_input(INPUT_POST, 'media_id', FILTER_VALIDATE_INT);
    $creator_id = $_SESSION['creator_id'];
    
    if (!$media_id || $media_id < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid media ID']);
        exit;
    }
    
    // Verify the media belongs to the creator
    $check_sql = "SELECT path FROM media WHERE id = ? AND creator_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $media_id, $creator_id);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Media not found or access denied']);
        exit;
    }
    
    $media = $result->fetch_assoc();
    $file_path = '../' . $media['path'];
    
    // Delete from database first
    $delete_sql = "DELETE FROM media WHERE id = ? AND creator_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $media_id, $creator_id);
    
    if ($stmt->execute()) {
        // Delete physical file if it exists
        if (file_exists($file_path) && is_file($file_path)) {
            if (!unlink($file_path)) {
                // Log error but don't fail the request
                error_log("Failed to delete media file: $file_path");
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Media deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting media from database']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>