<?php
session_start();
include 'config.php';

if (!isset($_SESSION['creator_id'])) {
    header("Location: ../frontend/creator_login.html");
    exit();
}

$creator_id = $_SESSION['creator_id'];
$target_dir = "../uploads/content/";

// Create upload directory if it doesn't exist
if (!file_exists($target_dir)) {
    if (!mkdir($target_dir, 0777, true)) {
        header("Location: ../frontend/creator_dashboard.php?error=Cannot create upload directory");
        exit();
    }
}

if (!is_writable($target_dir)) {
    header("Location: ../frontend/creator_dashboard.php?error=Upload directory is not writable");
    exit();
}

// Check if user has active subscription
$stmt = $conn->prepare("SELECT paid, trail_end FROM creators WHERE id = ?");
$stmt->bind_param("i", $creator_id);
$stmt->execute();
$result = $stmt->get_result();
$creator = $result->fetch_assoc();

$trail_end = $creator['trail_end'];
$is_in_trail = date('Y-m-d') <= $trail_end;
$is_privileged = $creator['paid'] || $is_in_trail;

if (!$is_privileged) {
    header("Location: ../frontend/creator_dashboard.php?error=Your subscription has expired");
    exit();
}

// Check if files were uploaded
if (empty($_FILES['content']['name'][0])) {
    header("Location: ../frontend/creator_dashboard.php?error=No files selected");
    exit();
}

// Process each uploaded file
$upload_errors = [];
$upload_success = [];

foreach ($_FILES['content']['name'] as $key => $name) {
    if ($_FILES['content']['error'][$key] !== UPLOAD_ERR_OK) {
        $upload_errors[] = "Error uploading $name (Error code: " . $_FILES['content']['error'][$key] . ")";
        continue;
    }
    
    // Validate file
    $file_size = $_FILES['content']['size'][$key];
    $file_tmp = $_FILES['content']['tmp_name'][$key];
    $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    
    // Check file size (max 50MB)
    if ($file_size > 50 * 1024 * 1024) {
        $upload_errors[] = "$name is too large (max 50MB)";
        continue;
    }
    
    // Check file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi', 'webm'];
    if (!in_array($file_extension, $allowed_types)) {
        $upload_errors[] = "$name has invalid file type (.$file_extension)";
        continue;
    }
    
    // Generate unique filename
    $new_filename = "content_" . $creator_id . "" . time() . "" . uniqid() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file_tmp, $target_file)) {
    // For videos, try to generate a thumbnail
    if (in_array($file_extension, ['mp4', 'mov', 'avi', 'webm'])) {
        // Set video type
        $type = 'video';
        
        // Try to generate thumbnail (requires FFmpeg on server)
        $thumbnail_path = generateVideoThumbnail($target_file, $creator_id, $new_filename);
        
    } else {
        // For images
        $type = 'photo';
    }
        // Save relative path for easier access
        $relative_path = "uploads/content/" . $new_filename;
        
        // Save to database with prepared statement
        $stmt = $conn->prepare("INSERT INTO media (creator_id, path, type) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $creator_id, $relative_path, $type);
        
        if ($stmt->execute()) {
            $upload_success[] = $name;
        } else {
            $upload_errors[] = "Database error for $name: " . $conn->error;
            unlink($target_file); // Remove file if DB insert failed
        }
    } else {
        $upload_errors[] = "Failed to upload $name - check directory permissions";
    }
}

// Prepare response
if (!empty($upload_errors)) {
    $error_msg = implode(", ", $upload_errors);
    header("Location: ../frontend/creator_dashboard.php?error=" . urlencode($error_msg));
} else {
    $success_msg = count($upload_success) . " files uploaded successfully";
    header("Location: ../frontend/creator_profile.php?id=" . $creator_id . "&msg=" . urlencode($success_msg));
}

function generateVideoThumbnail($video_path, $creator_id, $filename) {
    $thumbnail_dir = "../uploads/thumbnails/";
    
    // Create thumbnail directory if it doesn't exist
    if (!file_exists($thumbnail_dir)) {
        mkdir($thumbnail_dir, 0777, true);
    }
    
    $thumbnail_name = "thumb_" . pathinfo($filename, PATHINFO_FILENAME) . ".jpg";
    $thumbnail_path = $thumbnail_dir . $thumbnail_name;
    
    // Try to generate thumbnail using FFmpeg (if available)
    if (function_exists('shell_exec')) {
        // Capture thumbnail at 5 seconds into the video
        $ffmpeg_command = "ffmpeg -i \"$video_path\" -ss 00:00:05 -vframes 1 \"$thumbnail_path\" 2>&1";
        @shell_exec($ffmpeg_command);
        
        // If FFmpeg succeeded and thumbnail was created
        if (file_exists($thumbnail_path)) {
            return "uploads/thumbnails/" . $thumbnail_name;
        }
    }
    
    return null; // Thumbnail generation failed
}
$conn->close();
?>