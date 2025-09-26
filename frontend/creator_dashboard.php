<?php
session_start();
include '../backend/config.php';

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("New CSRF token generated: " . $_SESSION['csrf_token']);
}
// Display session messages
if (isset($_SESSION['error'])) {
    echo '<div class="notification error">';
    echo '<i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($_SESSION['error']);
    echo '</div>';
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    echo '<div class="notification success">';
    echo '<i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success']);
    echo '</div>';
    unset($_SESSION['success']);
}

if (!isset($_SESSION['creator_id'])) {
    header("Location: creator_login.php");
    exit;
}

$creator_id = $_SESSION['creator_id'];
$sql = "SELECT name, paid, trail_start, trail_end, package FROM creators WHERE id = $creator_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

$trail_end = $row['trail_end'];
$is_in_trail = date('Y-m-d') <= $trail_end;
$is_privileged = $row['paid'] || $is_in_trail;

if (!$is_privileged && date('Y-m-d') > $trail_end) {
    header("Location: creator_register.html?error=Your free trail has ended. Please select a paid package.");
    exit;
}

// Get stats for dashboard
$media_count_sql = "SELECT COUNT(*) as count FROM media WHERE creator_id = $creator_id";
$media_result = $conn->query($media_count_sql);
$media_count = $media_result->fetch_assoc()['count'];



// Get recent uploads
$recent_uploads_sql = "SELECT * FROM media WHERE creator_id = $creator_id ORDER BY uploaded_at DESC LIMIT 6";
$recent_uploads_result = $conn->query($recent_uploads_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
        <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AllQueens254</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --black: #000000;
            --red: #E70008;
            --cream: #F9E4AD;
            --orange: #FF9940;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--black) 0%, #222 100%);
            color: var(--cream);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: linear-gradient(135deg, rgba(0,0,0,0.9) 0%, rgba(34,34,34,0.9) 100%);
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(231, 0, 8, 0.3);
            border: 2px solid var(--red);
        }
        
        .dashboard-header h1 {
            color: var(--orange);
            font-size: 24px;
        }
        
        .dashboard-nav {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            background: var(--red);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            background: var(--orange);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 0, 8, 0.4);
        }
        
        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(34,34,34,0.8) 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(231, 0, 8, 0.2);
            border: 1px solid var(--red);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(231, 0, 8, 0.4);
        }
        
        .stat-icon {
            font-size: 30px;
            margin-bottom: 15px;
            color: var(--orange);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: var(--orange);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 16px;
            color: var(--cream);
        }
        
        /* Upload Section */
        .upload-section {
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(34,34,34,0.8) 100%);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(231, 0, 8, 0.3);
            border: 2px solid var(--red);
        }
        
        .upload-section h2 {
            color: var(--orange);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .file-upload {
            border: 2px dashed var(--orange);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .file-upload:hover {
            border-color: var(--red);
            background: rgba(255, 153, 64, 0.05);
        }
        
        .file-upload i {
            font-size: 40px;
            color: var(--orange);
            margin-bottom: 15px;
        }
        
        .file-upload p {
            margin-bottom: 15px;
        }
        
        .file-input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .selected-files {
            margin-top: 15px;
            text-align: left;
        }
        
        .file-list {
            list-style: none;
            margin-top: 10px;
        }
        
        .file-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 5px;
            margin-bottom: 8px;
            border: 1px solid var(--red);
        }
        
        .remove-file {
            color: var(--red);
            cursor: pointer;
            background: none;
            border: none;
            font-size: 16px;
        }
        
        .help-text {
            font-size: 14px;
            color: var(--cream);
            margin-top: 10px;
            font-style: italic;
        }
        
        .upload-btn {
            background: var(--orange);
            color: var(--black);
            font-weight: bold;
            padding: 12px 24px;
            margin-top: 15px;
            width: 100%;
        }
        
        .upload-btn:hover {
            background: var(--red);
            color: white;
        }
        
        /* Recent Uploads */
        .recent-section {
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(34,34,34,0.8) 100%);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(231, 0, 8, 0.3);
            border: 2px solid var(--red);
        }
        
        .recent-section h2 {
            color: var(--orange);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .media-item {
            position: relative;
            aspect-ratio: 1/1;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(255, 153, 64, 0.3);
            border: 2px solid var(--orange);
            transition: all 0.3s ease;
        }
        
        .media-item:hover {
            border-color: var(--red);
            box-shadow: 0 6px 16px rgba(231, 0, 8, 0.4);
            transform: translateY(-5px);
        }
        
        .media-item img,
        .media-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .media-type {
            position: absolute;
            top: 10px;
            right: 10px;
            color: white;
            background: rgba(0,0,0,0.7);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .no-content {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: var(--cream);
        }
        
        /* Notifications */
        .notification {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-size: 16px;
            border: 2px solid transparent;
        }
        
        .success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border-color: #28a745;
        }
        
        .error {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border-color: #dc3545;
        }
        
        .info {
            background: rgba(23, 162, 184, 0.2);
            color: #17a2b8;
            border-color: #17a2b8;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Package Info */
        .package-info {
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(34,34,34,0.8) 100%);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(231, 0, 8, 0.3);
            border: 2px solid var(--orange);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .package-info p {
            margin: 0;
        }
        
        .upgrade-btn {
            background: var(--orange);
            color: var(--black);
        }
        
        .upgrade-btn:hover {
            background: var(--red);
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .media-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .media-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-nav {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                justify-content: center;
            }
        }
    
        /* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}

.modal-content {
    background: linear-gradient(135deg, var(--black) 0%, #222 100%);
    margin: 5% auto;
    padding: 0;
    border: 2px solid var(--red);
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 32px rgba(231, 0, 8, 0.3);
    animation: modalFadeIn 0.3s;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-50px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--red);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(231, 0, 8, 0.1);
}

.modal-header h2 {
    color: var(--orange);
    margin: 0;
    font-size: 1.8rem;
}

.close {
    color: var(--cream);
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s;
}

.close:hover {
    color: var(--orange);
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    flex-grow: 1;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid var(--red);
    text-align: center;
    background: rgba(231, 0, 8, 0.1);
}

/* Plans Modal Styles */
.plans-modal {
    max-width: 1000px;
}

.plans-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.plan-card {
    background: linear-gradient(135deg, rgba(0,0,0,0.9), rgba(34,34,34,0.9));
    border: 2px solid var(--red);
    border-radius: 12px;
    padding: 25px;
    position: relative;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.plan-card:hover {
    transform: translateY(-5px);
    border-color: var(--orange);
    box-shadow: 0 10px 25px rgba(255, 153, 64, 0.2);
}

.plan-card.popular {
    border-color: var(--orange);
    transform: scale(1.02);
}

.popular-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--orange);
    color: var(--black);
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
    text-transform: uppercase;
}

.plan-header {
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--red);
}

.plan-header h3 {
    color: var(--orange);
    margin: 0 0 10px 0;
    font-size: 1.4rem;
}

.plan-price {
    color: var(--cream);
    font-size: 2rem;
    font-weight: bold;
    margin: 10px 0;
}

.plan-duration {
    color: var(--cream);
    opacity: 0.8;
    font-size: 0.9rem;
}

.plan-features {
    flex-grow: 1;
    margin-bottom: 20px;
}

.plan-features ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.plan-features li {
    color: var(--cream);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    font-size: 0.95rem;
}

.plan-features i {
    margin-right: 10px;
    width: 16px;
    text-align: center;
}

.plan-features .fa-check {
    color: #28a745;
}

.plan-features .fa-times {
    color: var(--red);
}

.feature-disabled {
    opacity: 0.6;
    text-decoration: line-through;
}

.plan-footer {
    text-align: center;
}

.btn-plan {
    background: linear-gradient(135deg, var(--red), var(--orange));
    color: var(--black);
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    font-size: 1rem;
}

.btn-plan:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(231, 0, 8, 0.3);
}

.btn-plan:disabled {
    background: #666;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-popular {
    background: linear-gradient(135deg, var(--orange), #ffcc00);
    font-size: 1.1rem;
    padding: 15px 25px;
}

.current-plan {
    background: #666;
    cursor: not-allowed;
}

/* Delete Account Modal Styles */
.warning-message {
    text-align: center;
    padding: 20px;
    background: rgba(220, 53, 69, 0.1);
    border-radius: 8px;
    border-left: 4px solid var(--red);
    margin-bottom: 20px;
}

.warning-message i {
    font-size: 3rem;
    color: var(--red);
    margin-bottom: 15px;
}

.warning-message h3 {
    color: var(--red);
    margin: 10px 0;
}

.btn-danger {
    background: var(--red);
    color: white;
}

.btn-secondary {
    background: #666;
    color: white;
    margin-right: 10px;
}

.btn-danger:hover {
    background: #c82333;
}

/* Media item delete button */
.media-item {
    position: relative;
}

.delete-media {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(231, 0, 8, 0.8);
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.media-item:hover .delete-media {
    opacity: 1;
}

.delete-media:hover {
    background: var(--red);
}

/* Account Settings Section */
.account-settings {
    background: linear-gradient(135deg, rgba(0,0,0,0.8), rgba(34,34,34,0.8));
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 4px 12px rgba(231, 0, 8, 0.3);
    border: 2px solid var(--red);
}

.account-settings h2 {
    color: var(--orange);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.danger-zone {
    background: rgba(220, 53, 69, 0.1);
    padding: 20px;
    border-radius: 8px;
    border: 2px solid var(--red);
    margin-top: 20px;
}

.danger-zone h3 {
    color: var(--red);
    margin-bottom: 15px;
}

/* Responsive design */
@media (max-width: 768px) {
    .plans-container {
        grid-template-columns: 1fr;
    }
    
    .plan-card.popular {
        transform: none;
    }
    
    .plan-price {
        font-size: 1.8rem;
    }
    
    .modal-content {
        width: 95%;
        margin: 2% auto;
    }
}

    </style>
</head>
<body>
    
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Creator Dashboard</h1>
            <div class="dashboard-nav">
                <a href="creator_profile.php?id=<?php echo $creator_id; ?>" class="btn">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="../backend/logout.php" class="btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="notification error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="notification success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Package Info -->
        <div class="package-info">
            <p>
                <i class="fas fa-crown"></i> 
                <strong>Current Plan:</strong> 
                <?php echo htmlspecialchars($row['package']); ?>
                <?php if ($is_in_trail): ?>
                    <span style="color: var(--orange);">(Trial ends: <?php echo $trail_end; ?>)</span>
                <?php endif; ?>
            </p>
            <button class="btn upgrade-plan-btn">Upgrade Plan</button>
                <i class="fas fa-arrow-up"></i>
            </a>
        </div>
        
        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-number"><?php echo $media_count; ?></div>
                <div class="stat-label">Total Content</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
               
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-number">
                    <?php echo $is_in_trail ? floor((strtotime($trail_end) - time()) / (60 * 60 * 24)) : '0'; ?>
                </div>
                <div class="stat-label">Days Left in Trial</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number">
                    <?php echo $row['paid'] ? 'Premium' : 'Standard'; ?>
                </div>
                <div class="stat-label">Account Status</div>
            </div>
        </div>
        
        <!-- Upload Section -->
        <div class="upload-section">
            <h2><i class="fas fa-upload"></i> Upload Exclusive Content</h2>
            
            <form action="../backend/upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="file-upload" id="dropZone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag & drop files here or click to browse</p>
                    <span class="btn">Select Files</span>
                    <input type="file" name="content[]" multiple accept="image/,video/" required class="file-input" id="fileInput">
                </div>
                
                <div class="selected-files" id="selectedFiles" style="display: none;">
                    <p>Selected files:</p>
                    <ul class="file-list" id="fileList"></ul>
                </div>
                
                <p class="help-text">Max file size: 50MB. Supported formats: JPG, PNG, GIF, MP4, MOV, AVI</p>
                
                <button type="submit" class="btn upload-btn">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Files
                </button>
            </form>
        </div>
        
        <!-- Recent Uploads Section -->
        
        <div class="recent-section">
            <h2><i class="fas fa-history"></i> Recent Uploads</h2>
            
            <div class="media-grid">
                <?php if ($recent_uploads_result->num_rows > 0): ?>
                    <?php while ($media = $recent_uploads_result->fetch_assoc()): ?>
                        <?php
                        // Get the correct path
                        $media_path = $media['path'];
                        
                        // If path doesn't start with "uploads/", assume it's relative to root
                        if (strpos($media_path, 'uploads/') !== 0) {
                            $media_path = "uploads/content/" . basename($media_path);
                        }
                        
                        // Check if file actually exists
                        $full_path = "../" . $media_path;
                        $file_exists = file_exists($full_path);
                        ?>
                        
                         <div class="media-item" data-id="<?php echo $media['id']; ?>">
                            <?php if ($media['type'] == 'photo'): ?>
                                <?php if ($file_exists): ?>
                                    <img src="../<?php echo htmlspecialchars($media_path); ?>" alt="Content">
                                <?php else: ?>
                                    <div style="background: #333; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--cream);">
                                        <p>Image not found</p>
                                    </div>
                                <?php endif; ?>
                                <div class="media-type"><i class="fas fa-image"></i></div>
                            <?php else: ?>
                                <?php if ($file_exists): ?>
                                    <video>
                                        <source src="../<?php echo htmlspecialchars($media_path); ?>">
                                    </video>
                                    <div class="media-type"><i class="fas fa-video"></i></div>
                                <?php else: ?>
                                    <div style="background: #333; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--cream);">
                                        <p>Video not found</p>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-content">
                        <i class="fas fa-folder-open" style="font-size: 50px; margin-bottom: 15px; color: var(--orange);"></i>
                        <p>You haven't uploaded any content yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($is_in_trail): ?>
            <div class="notification info">
                <i class="fas fa-info-circle"></i> Your free trial ends on <?php echo $trail_end; ?>.
            </div>
        <?php endif; ?>
    </div>
    
   <!-- Account Settings Section -->
<div class="account-settings">
    <h2><i class="fas fa-user-cog"></i> Account Settings</h2>
    
    <div class="danger-zone">
        <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
        <p>These actions are irreversible. Please proceed with caution.</p>
        
        <button class="btn btn-danger" onclick="openDeleteAccountModal()">
            <i class="fas fa-trash"></i> Delete My Account
        </button>
    </div>
</div>

    <script>
        // File upload handling
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const selectedFiles = document.getElementById('selectedFiles');
        const fileList = document.getElementById('fileList');
        const uploadForm = document.getElementById('uploadForm');
        
        let files = [];
        
        // Drag and drop functionality
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--red)';
            dropZone.style.background = 'rgba(255, 153, 64, 0.1)';
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.style.borderColor = 'var(--orange)';
            dropZone.style.background = 'transparent';
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--orange)';
            dropZone.style.background = 'transparent';
            
            if (e.dataTransfer.files.length > 0) {
                handleFiles(e.dataTransfer.files);
            }
        });
        
        // File input change
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                handleFiles(fileInput.files);
            }
        });
        
        // Handle selected files
        function handleFiles(fileList) {
            files = Array.from(fileList);
            updateFileDisplay();
        }
        
        // Update file display
        function updateFileDisplay() {
            fileList.innerHTML = '';
            
            if (files.length > 0) {
                selectedFiles.style.display = 'block';
                
                files.forEach((file, index) => {
                    const listItem = document.createElement('li');
                    
                    const fileName = document.createElement('span');
                    fileName.textContent = file.name;
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    removeBtn.classList.add('remove-file');
                    removeBtn.addEventListener('click', () => removeFile(index));
                    
                    listItem.appendChild(fileName);
                    listItem.appendChild(removeBtn);
                    fileList.appendChild(listItem);
                });
            } else {
                selectedFiles.style.display = 'none';
            }
        }
        
        // Remove file from list
        function removeFile(index) {
            files.splice(index, 1);
            updateFileDisplay();
            
            // Create a new DataTransfer object and set the files
            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }
        
        // Form submission
        uploadForm.addEventListener('submit', (e) => {
            if (files.length === 0) {
                e.preventDefault();
                alert('Please select at least one file to upload.');
                return false;
            }
        });
    </script>
   <script>
// Plans Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const plansModal = document.getElementById('plansModal');
    const deleteAccountModal = document.getElementById('deleteAccountModal');
    const upgradeBtn = document.querySelector('.upgrade-plan-btn');
    const closeButtons = document.querySelectorAll('.close');
    const plansContainer = document.getElementById('plansContainer');
    
    // Get current user's plan
    const currentPlan = "<?php echo isset($row['package']) ? $row['package'] : 'Free Trail'; ?>";
    
    // Open plans modal when Upgrade button is clicked
    if (upgradeBtn) {
        upgradeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            loadPlans();
            openModal('plansModal');
        });
    }
    
    // Close modals when X is clicked
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal.id);
        });
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                if (modal.style.display === 'block') {
                    closeModal(modal.id);
                }
            });
        }
    });
    
    // Modal functions
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Load plans from backend
    function loadPlans() {
        plansContainer.innerHTML = '<div class="loading">Loading plans...</div>';
        
        fetch('../backend/get_plans.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPlans(data.plans);
                } else {
                    plansContainer.innerHTML = '<div class="error">Failed to load plans. Please try again.</div>';
                }
            })
            .catch(error => {
                plansContainer.innerHTML = '<div class="error">Error loading plans. Please try again.</div>';
            });
    }
    
    // Display plans in the modal
    function displayPlans(plans) {
        let html = '';
        
        plans.forEach(plan => {
            const isCurrentPlan = plan.name === currentPlan;
            const isPopular = plan.name === 'Regular'; // Or use a field from your database
            
            html += `
                <div class="plan-card ${isPopular ? 'popular' : ''}">
                    ${isPopular ? '<div class="popular-badge">MOST POPULAR</div>' : ''}
                    <div class="plan-header">
                        <h3>${plan.name}</h3>
                        <div class="plan-price">Ksh ${parseInt(plan.price).toLocaleString()}</div>
                        <div class="plan-duration">${plan.duration}</div>
                    </div>
                    <div class="plan-features">
                        <ul>
                            <li><i class="fas fa-check"></i> ${getFeatureForPlan(plan.name, 'feature1')}</li>
                            <li><i class="fas fa-check"></i> ${getFeatureForPlan(plan.name, 'feature2')}</li>
                            <li><i class="fas fa-check"></i> ${getFeatureForPlan(plan.name, 'feature3')}</li>
                            <li><i class="${plan.name === 'Gold' ? 'fas fa-check' : 'fas fa-times'}"></i> 
                                <span ${plan.name !== 'Gold' ? 'class="feature-disabled"' : ''}>
                                    ${getFeatureForPlan(plan.name, 'feature4')}
                                </span>
                            </li>
                            <li><i class="${plan.name === 'Gold' ? 'fas fa-check' : 'fas fa-times'}"></i> 
                                <span ${plan.name !== 'Gold' ? 'class="feature-disabled"' : ''}>
                                    ${getFeatureForPlan(plan.name, 'feature5')}
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div class="plan-footer">
                        <button class="btn-plan ${isCurrentPlan ? 'current-plan' : ''} ${isPopular ? 'btn-popular' : ''}" 
                                data-plan="${plan.name}" 
                                data-amount="${plan.price}"
                                ${isCurrentPlan ? 'disabled' : ''}
                                onclick="${isCurrentPlan ? '' : 'selectPlan(\'' + plan.name + '\', ' + plan.price + ')'}">
                            ${isCurrentPlan ? 'Current Plan' : 'Upgrade to ' + plan.name}
                        </button>
                    </div>
                </div>
            `;
        });
        
        plansContainer.innerHTML = html;
    }
    
    // Get appropriate features for each plan
    function getFeatureForPlan(planName, featureType) {
        const features = {
            'Free Trial': {
                'feature1': 'Basic profile listing',
                'feature2': '5 photos upload',
                'feature3': '1 video upload',
                'feature4': 'Featured placement',
                'feature5': 'Priority support'
            },
            'Regular': {
                'feature1': 'Enhanced profile visibility',
                'feature2': '20 photos upload',
                'feature3': '5 videos upload',
                'feature4': 'Featured in search results',
                'feature5': 'Priority support'
            },
            'Premium': {
                'feature1': 'Premium profile placement',
                'feature2': 'Unlimited photos upload',
                'feature3': '10 videos upload',
                'feature4': 'Top search results placement',
                'feature5': 'Priority support'
            },
            'Gold': {
                'feature1': 'VIP profile placement',
                'feature2': 'Unlimited photos + videos',
                'feature3': 'Verified badge',
                'feature4': 'Homepage featuring',
                'feature5': '24/7 priority support'
            }
        };
        
        return features[planName]?.[featureType] || 'Feature';
    }
    
    // Handle plan selection
    function selectPlan(planName, amount) {
        // Initiate M-Pesa payment
        initiatePayment(planName, amount);
    }
    
    // Initiate M-Pesa payment
    function initiatePayment(planName, amount) {
        // Show loading state
        const buttons = document.querySelectorAll('.btn-plan');
        buttons.forEach(btn => btn.disabled = true);
        
        // Get user phone number
        const userPhone = "<?php echo isset($row['phone']) ? $row['phone'] : ''; ?>";
        
        if (!userPhone) {
            alert('Please update your phone number in profile settings.');
            buttons.forEach(btn => btn.disabled = false);
            return;
        }
        
        // Call backend to initiate STK push
        fetch('../backend/initiate_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `plan=${encodeURIComponent(planName)}&amount=${amount}&phone=${encodeURIComponent(userPhone)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Please check your phone to complete the M-Pesa payment.');
                closeModal('plansModal');
            } else {
                alert('Payment initiation failed: ' + data.message);
                buttons.forEach(btn => btn.disabled = false);
            }
        })
        .catch(error => {
            alert('Error initiating payment. Please try again.');
            buttons.forEach(btn => btn.disabled = false);
        });
    }
    
    // Add delete buttons to media items
    function addDeleteButtonsToMedia() {
        const mediaItems = document.querySelectorAll('.media-item');
        mediaItems.forEach(item => {
            // Check if delete button already exists
            if (!item.querySelector('.delete-media')) {
                const mediaId = item.dataset.id || '';
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'delete-media';
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                deleteBtn.title = 'Delete this media';
                deleteBtn.onclick = function(e) {
                    e.stopPropagation();
                    deleteMedia(mediaId, item);
                };
                item.appendChild(deleteBtn);
            }
        });
    }
    
   // Delete media function
function deleteMedia(mediaId, mediaElement) {
    if (!confirm('Are you sure you want to delete this media? This action cannot be undone.')) {
        return;
    }
    
    fetch('../backend/delete_media.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'media_id=' + mediaId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Remove the media element from DOM with animation
            mediaElement.style.opacity = '0';
            mediaElement.style.transition = 'opacity 0.3s ease';
            
            setTimeout(() => {
                mediaElement.remove();
                
                // Update media count
                const mediaCountElement = document.querySelector('.stat-number');
                if (mediaCountElement) {
                    let currentCount = parseInt(mediaCountElement.textContent);
                    if (!isNaN(currentCount) && currentCount > 0) {
                        mediaCountElement.textContent = currentCount - 1;
                    }
                }
                
                // Show success message
                showNotification('Media deleted successfully', 'success');
            }, 300);
        } else {
            throw new Error(data.message || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting media: ' + error.message, 'error');
    });
}

// Show notification function
function showNotification(message, type) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create new notification
    const notification = document.createElement('div');
    notification.className = `custom-notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    // Add styles for notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        max-width: 300px;
    `;
    
    if (type === 'success') {
        notification.style.background = 'var(--success-green, #28a745)';
    } else {
        notification.style.background = 'var(--error-red, #dc3545)';
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Add CSS for notifications animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .custom-notification {
        display: flex;
        align-items: center;
        gap: 10px;
    }
`;
document.head.appendChild(style);

// Handle delete account form submission
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('deleteAccountForm');
    
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const passwordInput = this.querySelector('input[name="password"]');
            const password = passwordInput.value.trim();
            
            if (!password) {
                alert('Please enter your password to confirm account deletion');
                passwordInput.focus();
                return;
            }
            
            if (!confirm('⚠ WARNING: This will permanently delete your account, all media, and all data. This action cannot be undone. Are you absolutely sure?')) {
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting Account...';
            submitBtn.disabled = true;
            
            // Get CSRF token from meta tag
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.content : '';
            
            console.log('CSRF Token:', csrfToken);
            
            if (!csrfToken) {
                alert('Security token missing. Please refresh the page and try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                return;
            }
            
            // Use URLSearchParams instead of FormData for better debugging
            const formData = new URLSearchParams();
            formData.append('password', password);
            formData.append('csrf_token', csrfToken);
            
            console.log('Sending data:', formData.toString());
            
            fetch('../backend/delete_account.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.success) {
                    // Redirect to homepage
                    window.location.href = data.redirect || '../frontend/homepage.html';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting account. Please try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});
    // Initialize media delete buttons
    addDeleteButtonsToMedia();
});

// Open delete account modal
function openDeleteAccountModal() {
    document.getElementById('deleteAccountModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}



</script>
<!-- Upgrade Plans Modal -->
<div id="plansModal" class="modal">
    <div class="modal-content plans-modal">
        <div class="modal-header">
            <h2><i class="fas fa-crown"></i> Upgrade Your Plan</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="plans-container" id="plansContainer">
                <div class="loading">Loading plans...</div>
            </div>
        </div>
        <div class="modal-footer">
            <p>All plans include 24/7 support and secure M-Pesa payments</p>
        </div>
    </div>
</div>

<!-- Delete Account Confirmation Modal -->
<div id="deleteAccountModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Delete Account</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="warning-message">
                <i class="fas fa-exclamation-circle"></i>
                <h3>Warning: This action cannot be undone!</h3>
                <p>All your data, including photos, videos, and profile information will be permanently deleted.</p>
                <p>Are you sure you want to delete your account?</p>
            </div>
            <form id="deleteAccountForm" method="POST" action="../backend/delete_account.php">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    
    <div class="form-group">
        <label for="deletePassword">Enter your password to confirm:</label>
        <input type="password" id="deletePassword" name="password" required 
               placeholder="Your account password">
    </div>
    
    <button type="submit" class="btn btn-danger">
        <i class="fas fa-trash"></i> Permanently Delete My Account
    </button>
</form>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
