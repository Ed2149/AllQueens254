<?php
function backupCreatorAccount($conn, $creator_id) {
    if (!defined('BACKUP_ENABLED') || !BACKUP_ENABLED) {
        error_log("Backups are disabled, proceeding with deletion");
        return true;
    }
    
    // Create backups directory if it doesn't exist
    $backup_base_dir = '../backups/';
    if (!file_exists($backup_base_dir) && !mkdir($backup_base_dir, 0755, true)) {
        error_log("Failed to create backup base directory: $backup_base_dir");
        return false;
    }
    
    $backup_dir = $backup_base_dir . date('Y-m-d_H-i-s') . '_creator_' . $creator_id . '/';
    
    if (!file_exists($backup_dir) && !mkdir($backup_dir, 0755, true)) {
        error_log("Failed to create backup directory: $backup_dir");
        return false;
    }
    
    try {
        // Backup creator data
        $stmt = $conn->prepare("SELECT * FROM creators WHERE id = ?");
        $stmt->bind_param("i", $creator_id);
        $stmt->execute();
        $creator_data = $stmt->get_result()->fetch_assoc();
        
        if ($creator_data) {
            file_put_contents($backup_dir . 'creator.json', json_encode($creator_data, JSON_PRETTY_PRINT));
        }
        
        // Backup media records
        $media_data = [];
        $stmt = $conn->prepare("SELECT * FROM media WHERE creator_id = ?");
        $stmt->bind_param("i", $creator_id);
        $stmt->execute();
        $media_result = $stmt->get_result();
        
        // Create media subdirectory
        $media_backup_dir = $backup_dir . 'media/';
        if (!file_exists($media_backup_dir) && !mkdir($media_backup_dir, 0755, true)) {
            error_log("Failed to create media backup directory: $media_backup_dir");
            // Continue with JSON backup even if media directory creation fails
        }
        
        while ($row = $media_result->fetch_assoc()) {
            $media_data[] = $row;
            
            // Backup actual media files if directory exists
            if (file_exists($media_backup_dir)) {
                $file_path = '../' . $row['path'];
                if (file_exists($file_path) && is_file($file_path)) {
                    $file_backup_path = $media_backup_dir . basename($row['path']);
                    if (!copy($file_path, $file_backup_path)) {
                        error_log("Failed to copy media file: " . $row['path']);
                    }
                }
            }
        }
        file_put_contents($backup_dir . 'media.json', json_encode($media_data, JSON_PRETTY_PRINT));
        
        // Backup other related data
        $tables = ['transactions'];
        foreach ($tables as $table) {
            $table_check = $conn->query("SHOW TABLES LIKE '$table'");
            if ($table_check->num_rows > 0) {
                $table_data = [];
                $stmt = $conn->prepare("SELECT * FROM $table WHERE creator_id = ?");
                $stmt->bind_param("i", $creator_id);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $table_data[] = $row;
                    }
                    file_put_contents($backup_dir . $table . '.json', json_encode($table_data, JSON_PRETTY_PRINT));
                }
            }
        }
        
        error_log("Backup completed successfully for creator ID: $creator_id");
        return true;
        
    } catch (Exception $e) {
        error_log("Backup failed for creator ID $creator_id: " . $e->getMessage());
        return false;
    }
}
?>
