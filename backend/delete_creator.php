<?php
session_start();
include 'config.php';

// Verify admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $creator_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if (!$creator_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid creator ID']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Get media files to delete them physically
        $media_stmt = $conn->prepare("SELECT path FROM media WHERE creator_id = ?");
        $media_stmt->bind_param("i", $creator_id);
        $media_stmt->execute();
        $media_result = $media_stmt->get_result();
        
        $media_files = [];
        while ($media = $media_result->fetch_assoc()) {
            $media_files[] = $media['path'];
        }
        
        // 2. Delete media records
        $delete_media_stmt = $conn->prepare("DELETE FROM media WHERE creator_id = ?");
        $delete_media_stmt->bind_param("i", $creator_id);
        $delete_media_stmt->execute();
        
        // 3. Delete transaction records
        $delete_transactions_stmt = $conn->prepare("DELETE FROM transactions WHERE creator_id = ?");
        $delete_transactions_stmt->bind_param("i", $creator_id);
        $delete_transactions_stmt->execute();
        
        // 4. Delete the creator
        $delete_creator_stmt = $conn->prepare("DELETE FROM creators WHERE id = ?");
        $delete_creator_stmt->bind_param("i", $creator_id);
        $delete_creator_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // 5. Delete physical media files
        foreach ($media_files as $file_path) {
            if (file_exists("../" . $file_path) && is_file("../" . $file_path)) {
                unlink("../" . $file_path);
            }
        }
        
        // Also delete profile picture if exists
        $profile_stmt = $conn->prepare("SELECT profile_pic FROM creators WHERE id = ?");
        $profile_stmt->bind_param("i", $creator_id);
        $profile_stmt->execute();
        $profile_result = $profile_stmt->get_result();
        
        if ($profile_row = $profile_result->fetch_assoc()) {
            $profile_pic = $profile_row['profile_pic'];
            if ($profile_pic && file_exists("../" . $profile_pic) && is_file("../" . $profile_pic)) {
                unlink("../" . $profile_pic);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Creator deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting creator: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>