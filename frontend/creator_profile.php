<?php
session_start();
include '../backend/config.php';

// Security: Validate and sanitize input
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: homepage.html?error=Invalid profile ID");
    exit;
}

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM creators WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: search_results.php?error=Profile not found");
    exit;
}

$row = $result->fetch_assoc();
$is_owner = isset($_SESSION['creator_id']) && $_SESSION['creator_id'] == $id;

// Handle profile update with prepared statements
if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_owner) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $age = (int)$_POST['age'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $orientation = mysqli_real_escape_string($conn, $_POST['orientation']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $mpesa = mysqli_real_escape_string($conn, $_POST['mpesa']);
    
    // Handle nearby places
    $nearby_places = [];
    if (isset($_POST['nearby_place']) && is_array($_POST['nearby_place'])) {
        foreach ($_POST['nearby_place'] as $place) {
            $trimmed_place = trim($place);
            if (!empty($trimmed_place)) {
                $nearby_places[] = mysqli_real_escape_string($conn, $trimmed_place);
            }
        }
    }
    $nearby_places_str = implode(',', $nearby_places);

    // Base update query
    $update_sql = "UPDATE creators SET name=?, location=?, age=?, description=?, gender=?, orientation=?, phone=?, mpesa=?, nearby_places=? WHERE id=?";
    $types = "ssissssssi";
    $params = [$name, $location, $age, $description, $gender, $orientation, $phone, $mpesa, $nearby_places_str, $id];
    
    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        // Validate file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_types)) {
            $target_dir = "../uploads/profiles/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $new_filename = "profile_" . $id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                // Delete old profile picture if exists
                if ($row['profile_pic'] && file_exists("../" . $row['profile_pic'])) {
                    unlink("../" . $row['profile_pic']);
                }
                
                // Update the query to include profile_pic
                $update_sql = "UPDATE creators SET name=?, location=?, age=?, description=?, gender=?, orientation=?, phone=?, mpesa=?, nearby_places=?, profile_pic=? WHERE id=?";
                $types = "ssisssssssi";
                
                // Use relative path for database storage
                $relative_path = "uploads/profiles/" . $new_filename;
                $params = [$name, $location, $age, $description, $gender, $orientation, $phone, $mpesa, $nearby_places_str, $relative_path, $id];
            }
        }
    }
    
    // Execute update with prepared statement
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param($types, ...$params);
    
    if ($update_stmt->execute()) {
        header("Location: creator_profile.php?id=$id&msg=Profile updated successfully");
        exit;
    } else {
        $error = $conn->error;
    }
}

// Get media count
$media_stmt = $conn->prepare("SELECT COUNT(*) as media_count FROM media WHERE creator_id = ?");
$media_stmt->bind_param("i", $id);
$media_stmt->execute();
$media_result = $media_stmt->get_result();
$media_count = $media_result->fetch_assoc()['media_count'];

// Get subscription count (placeholder - you'll need to implement this)
$subscriber_count = 0;

// Parse nearby places
$nearby_places = [];
if (!empty($row['nearby_places'])) {
    $nearby_places = explode(',', $row['nearby_places']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($row['name']); ?> | AllQueens254</title>
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        .profile-header {
            background: linear-gradient(135deg, rgba(0,0,0,0.9) 0%, rgba(34,34,34,0.9) 100%);
            padding: 2rem 0;
            border-bottom: 3px solid var(--red);
        }
        
        .profile-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
            border: 4px solid var(--orange);
            box-shadow: 0 6px 16px rgba(231, 0, 8, 0.3);
        }
        
        .profile-stats {
            display: flex;
            margin-bottom: 20px;
        }
        
        .stat {
            margin-right: 40px;
            text-align: center;
        }
        
        .stat-count {
            font-size: 20px;
            font-weight: bold;
            color: var(--orange);
        }
        
        .stat-label {
            font-size: 16px;
            color: var(--cream);
        }
        
        .profile-bio h1 {
            font-size: 28px;
            margin-bottom: 5px;
            color: var(--orange);
        }
        
        .profile-bio p {
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .profile-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 16px;
            margin-top: 15px;
        }
        
        .profile-details span {
            display: flex;
            align-items: center;
            background: rgba(255, 153, 64, 0.1);
            padding: 5px 12px;
            border-radius: 20px;
            border: 1px solid var(--orange);
        }
        
        .profile-details i {
            margin-right: 5px;
            color: var(--orange);
        }
        
        .btn {
            background: var(--red);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: var(--orange);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 0, 8, 0.4);
        }
        
        .btn-call {
            background: var(--orange);
        }
        
        .btn-call:hover {
            background: var(--red);
        }
        
        /* Edit Form */
        .edit-form {
            background: linear-gradient(135deg, rgba(0,0,0,0.9) 0%, rgba(26,26,26,0.9) 100%);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 0 20px rgba(231, 0, 8, 0.3);
            border: 2px solid var(--red);
        }
        
        .edit-form h2 {
            color: var(--orange);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--red);
            padding-bottom: 10px;
        }
        
        .edit-form input,
        .edit-form select,
        .edit-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 2px solid var(--red);
            border-radius: 5px;
            background: var(--black);
            color: var(--cream);
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .edit-form input:focus,
        .edit-form select:focus,
        .edit-form textarea:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(255, 153, 64, 0.3);
        }
        
        .edit-form textarea {
            height: 100px;
            resize: vertical;
        }
        
        /* Nearby Places */
        .nearby-places {
            margin: 20px 0;
        }
        
        .places-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .place-input-group {
            display: flex;
            margin-bottom: 10px;
        }
        
        .place-input {
            flex: 1;
            padding: 10px;
            border: 2px solid var(--red);
            border-radius: 5px 0 0 5px;
            background: var(--black);
            color: var(--cream);
        }
        
        .remove-place {
            background: var(--red);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
        }
        
        .add-place {
            background: var(--orange);
            color: var(--black);
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 10px;
            cursor: pointer;
            font-weight: bold;
        }
        
        /* Media Grid */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
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
            transition: transform 0.3s;
        }
        
        .media-item:hover img,
        .media-item:hover video {
            transform: scale(1.05);
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
        
        /* Navigation */
        .profile-nav {
            display: flex;
            justify-content: center;
            border-top: 2px solid var(--red);
            margin-top: 20px;
            padding-top: 20px;
        }
        
        .nav-item {
            padding: 15px 0;
            margin: 0 20px;
            font-size: 16px;
            font-weight: 600;
            color: var(--cream);
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .nav-item.active {
            color: var(--orange);
            border-bottom: 2px solid var(--orange);
        }
        
        .nav-item:hover {
            color: var(--orange);
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .profile-info {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .media-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .profile-details {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .media-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-stats {
                justify-content: center;
            }
            
            .stat {
                margin-right: 20px;
            }
        }
        
        /* Nearby places display */
        .nearby-places-display {
            margin: 20px 0;
        }
        
        .places-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .place-tag {
            background: rgba(255, 153, 64, 0.2);
            color: var(--orange);
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid var(--orange);
            font-size: 14px;
        }

     /* Lightbox Styles */
    .enlarged-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.95);
        z-index: 10000;
        justify-content: center;
        align-items: center;
    }
    
    .enlarged-content {
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
    }
    
    .enlarged-image {
        max-width: 100%;
        max-height: 80vh;
        border-radius: 8px;
        box-shadow: 0 5px 25px rgba(255, 153, 64, 0.5);
        transform-origin: center center;
        transition: transform 0.3s ease;
    }
    
    .enlarged-controls {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
        background: rgba(0, 0, 0, 0.7);
        padding: 10px;
        border-radius: 50px;
        z-index: 10001;
    }
    
    .enlarged-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--orange);
        color: var(--black);
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        border: none;
        font-size: 16px;
        transition: all 0.3s ease;
    }
    
    .enlarged-btn:hover {
        background: var(--red);
        color: white;
        transform: scale(1.1);
    }
    
    .close-enlarged {
        position: absolute;
        top: 20px;
        right: 20px;
        color: white;
        font-size: 30px;
        cursor: pointer;
        background: rgba(231, 0, 8, 0.7);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all 0.3s ease;
        z-index: 10001;
    }
    
    .close-enlarged:hover {
        background: var(--red);
        transform: scale(1.1);
    }
    
    .zoom-info {
        position: absolute;
        bottom: 70px;
        left: 50%;
        transform: translateX(-50%);
        color: white;
        background: rgba(0, 0, 0, 0.7);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
        z-index: 10001;
    }
    
    /* Make images clickable */
    .clickable-image {
        cursor: pointer;
        transition: transform 0.3s ease;
    }
    
    .clickable-image:hover {
        transform: scale(1.05);
    }


    </style>
</head>
<body>
    <div class="container">
        <a href="homepage.html" class="btn" style="margin: 20px 0; display: inline-block;">
            <i class="fas fa-home"></i> Homepage
        </a>

        <div class="profile-header">
            <div class="profile-info">
                <img src="<?php 
                    // Handle profile picture path
                    if (!empty($row['profile_pic'])) {
                        // Check if it's already a full path or relative path
                        if (strpos($row['profile_pic'], 'uploads/') === 0) {
                            // It's already a relative path
                            $profile_path = $row['profile_pic'];
                        } else if (strpos($row['profile_pic'], '../uploads/') === 0) {
                            // Remove the leading ../
                            $profile_path = substr($row['profile_pic'], 3);
                        } else {
                            // It's probably just a filename
                            $profile_path = "uploads/profiles/" . basename($row['profile_pic']);
                        }
                        
                        // Check if file exists
                        if (file_exists("../" . $profile_path)) {
                            echo "../" . htmlspecialchars($profile_path);
                        } else {
                            echo "assets/default-profile.jpg";
                        }
                    } else {
                        echo "assets/default-profile.jpg";
                    }
                ?>" alt="Profile" class="profile-avatar" id="profileAvatar">
                
                <div class="profile-bio">
                    <div style="display: flex; align-items: center; margin-bottom: 15px; flex-wrap: wrap;">
                        <h1><?php echo htmlspecialchars($row['name']); ?></h1>
                        <?php if ($is_owner): ?>
                            <a href="creator_dashboard.php" class="btn" style="margin-left: 15px;">
                                <i class="fas fa-cog"></i> Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stat">
                            <span class="stat-count"><?php echo $media_count; ?></span>
                            <span class="stat-label">Posts</span>
                        </div>
                    
                    </div>
                    
                    <p><?php echo htmlspecialchars($row['description']); ?></p>
                    
                    <div class="profile-details">
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['location']); ?></span>
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['gender']); ?></span>
                        <span><i class="fas fa-heart"></i> <?php echo htmlspecialchars($row['orientation']); ?></span>
                        <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['phone']); ?></span>
                        <a href="tel:<?php echo htmlspecialchars($row['phone']); ?>" class="btn btn-call"><i class="fas fa-phone"></i> Call Me</a>
                    </div>
                    
                    <!-- Display nearby places -->
                    <?php if (!empty($nearby_places)): ?>
                    <div class="nearby-places-display">
                        <h3 style="color: var(--orange); margin-top: 15px;">Nearby Places I Frequent:</h3>
                        <div class="places-tags">
                            <?php foreach ($nearby_places as $place): ?>
                                <span class="place-tag"><?php echo htmlspecialchars($place); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="notification success">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error">
                Error: <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($is_owner): ?>
            <div class="edit-form">
                <h2><i class="fas fa-edit"></i> Edit Profile</h2>
                <form action="" method="POST" enctype="multipart/form-data" id="profileForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" placeholder="Name" required>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($row['location']); ?>" placeholder="Location" required>
                            <input type="number" name="age" value="<?php echo $row['age']; ?>" placeholder="Age" required min="18">
                            <textarea name="description" placeholder="About you"><?php echo htmlspecialchars($row['description']); ?></textarea>
                        </div>
                        <div>
                            <select name="gender" required>
                                <option value="Male" <?php if ($row['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                                <option value="Female" <?php if ($row['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                                <option value="Other" <?php if ($row['gender'] == 'Other') echo 'selected'; ?>>Other</option>
                            </select>
                            <select name="orientation" required>
                                <option value="Straight" <?php if ($row['orientation'] == 'Straight') echo 'selected'; ?>>Straight</option>
                                <option value="Gay" <?php if ($row['orientation'] == 'Gay') echo 'selected'; ?>>Gay</option>
                                <option value="Bisexual" <?php if ($row['orientation'] == 'Bisexual') echo 'selected'; ?>>Bisexual</option>
                                <option value="Pansexual" <?php if ($row['orientation'] == 'Pansexual') echo 'selected'; ?>>Pansexual</option>
                            </select>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($row['phone']); ?>" placeholder="Phone Number" required>
                            <input type="text" name="mpesa" value="<?php echo htmlspecialchars($row['mpesa']); ?>" placeholder="M-Pesa Number" required>
                            <div style="margin: 15px 0;">
                                <label for="profile_pic" style="display: block; margin-bottom: 5px; color: var(--orange);">Profile Picture:</label>
                                <input type="file" name="profile_pic" accept="image/*" id="profile_pic">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Nearby Places Input -->
                    <div class="nearby-places">
                        <h3 style="color: var(--orange); margin-bottom: 15px;">Nearby Places I Frequent:</h3>
                        <div id="places-container">
                            <?php foreach ($nearby_places as $index => $place): ?>
                                <div class="place-input-group">
                                    <input type="text" name="nearby_place[]" value="<?php echo htmlspecialchars($place); ?>" placeholder="Enter a nearby place" class="place-input">
                                    <button type="button" class="remove-place" onclick="removePlace(this)"><i class="fas fa-times"></i></button>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($nearby_places)): ?>
                                <div class="place-input-group">
                                    <input type="text" name="nearby_place[]" placeholder="Enter a nearby place" class="place-input">
                                    <button type="button" class="remove-place" onclick="removePlace(this)"><i class="fas fa-times"></i></button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="add-place" onclick="addPlace()"><i class="fas fa-plus"></i> Add Another Place</button>
                    </div>
                    
                    <button type="submit" class="btn" style="margin-top: 20px;"><i class="fas fa-save"></i> Update Profile</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="profile-nav">
            <div class="nav-item active">
                <i class="fas fa-grid-2"></i> Photos & Videos
            </div>
        </div>

        <div class="media-grid">
            <?php
            $sql_media = "SELECT * FROM media WHERE creator_id = ? ORDER BY uploaded_at DESC";
            $media_stmt = $conn->prepare($sql_media);
            $media_stmt->bind_param("i", $id);
            $media_stmt->execute();
            $result_media = $media_stmt->get_result();
            
            if ($result_media->num_rows > 0) {
                while ($media = $result_media->fetch_assoc()) {
                    // Get the correct path
                    $media_path = $media['path'];
                    
                    // If path doesn't start with "uploads/", assume it's relative to root
                    if (strpos($media_path, 'uploads/') !== 0) {
                        $media_path = "uploads/content/" . basename($media_path);
                    }
                    
                    // Check if file actually exists
                    $full_path = "../" . $media_path;
                    $file_exists = file_exists($full_path);
                    
                    if ($media['type'] == 'photo') {
    echo '<div class="media-item">';
    if ($file_exists) {
        echo '<img src="../' . htmlspecialchars($media_path) . '" alt="Content" loading="lazy" class="clickable-image">';
    } else {
        echo '<div style="background: #333; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--cream);">';
        echo '<p>Image not found</p>';
        echo '</div>';
    }
    echo '<div class="media-type"><i class="fas fa-image"></i></div>';
    echo '</div>';
} else {
                        echo '<div class="media-item">';
                        if ($file_exists) {
                            // Get file extension to determine video type
                            $file_ext = strtolower(pathinfo($media_path, PATHINFO_EXTENSION));
                            $video_type = "video/mp4"; // Default
                            
                            if ($file_ext == "webm") $video_type = "video/webm";
                            if ($file_ext == "ogg" || $file_ext == "ogv") $video_type = "video/ogg";
                            
                            echo '<video controls preload="metadata" style="width:100%; height:100%; object-fit:cover;">';
                            echo '<source src="../' . htmlspecialchars($media_path) . '" type="' . $video_type . '">';
                            echo 'Your browser does not support the video tag.';
                            echo '</video>';
                        } else {
                            echo '<div style="background: #333; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--cream);">';
                            echo '<p>Video not found</p>';
                            echo '</div>';
                        }
                        echo '<div class="media-type"><i class="fas fa-video"></i></div>';
                        echo '</div>';
                    }
                }
            } else {
                echo '<p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--cream);">No content yet.</p>';
            }
            ?>
        </div>
    </div>

    <script>
        // Preview image before upload
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileAvatar').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            let age = document.querySelector('input[name="age"]').value;
            if (age < 18) {
                e.preventDefault();
                alert('You must be at least 18 years old.');
                return false;
            }
        });
        
        // Nearby places functionality
        function addPlace() {
            const container = document.getElementById('places-container');
            const newInputGroup = document.createElement('div');
            newInputGroup.className = 'place-input-group';
            newInputGroup.innerHTML = `
                <input type="text" name="nearby_place[]" placeholder="Enter a nearby place" class="place-input">
                <button type="button" class="remove-place" onclick="removePlace(this)"><i class="fas fa-times"></i></button>
            `;
            container.appendChild(newInputGroup);
        }
        
        function removePlace(button) {
            const container = document.getElementById('places-container');
            if (container.children.length > 1) {
                button.parentElement.remove();
            } else {
                // If it's the last input, just clear it
                button.previousElementSibling.value = '';
            }
        }
         
    // Lightbox functionality
    let currentMediaIndex = 0;
    let mediaItems = [];
    
    // Initialize media items and click events
    function initLightbox() {
        // Add profile picture to media items
        const profilePic = document.getElementById('profileAvatar');
        if (profilePic) {
            profilePic.addEventListener('click', function() {
                openLightbox(profilePic.src, 'profile');
            });
        }
        
        // Get all media items
        mediaItems = document.querySelectorAll('.media-item');
        
        // Add click event to each media item
        mediaItems.forEach((item, index) => {
            item.addEventListener('click', function() {
                openLightbox(index, 'media');
            });
        });
    }
     
     // Simple zoom functionality
    let currentScale = 1;
    let currentImageSrc = '';
    
    // Make profile picture clickable
    document.addEventListener('DOMContentLoaded', function() {
        // Profile picture
        const profilePic = document.getElementById('profileAvatar');
        if (profilePic) {
            profilePic.classList.add('clickable-image');
            profilePic.addEventListener('click', function() {
                openEnlarged(this.src, 'Profile Picture');
            });
        }
        
        // Media images
        const mediaImages = document.querySelectorAll('.media-item img');
        mediaImages.forEach(img => {
            img.classList.add('clickable-image');
            img.addEventListener('click', function() {
                openEnlarged(this.src, 'Photo');
            });
        });
    });
    
    // Open enlarged view
    function openEnlarged(src, caption) {
        currentImageSrc = src;
        currentScale = 1;
        
        const overlay = document.getElementById('enlargedOverlay');
        const image = document.getElementById('enlargedImage');
        const zoomInfo = document.getElementById('zoomInfo');
        
        image.src = src;
        image.style.transform = 'scale(1)';
        zoomInfo.textContent = '100%';
        
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    // Close enlarged view
    function closeEnlarged() {
        const overlay = document.getElementById('enlargedOverlay');
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        resetZoom();
    }
    
    // Zoom functions
    function zoomIn() {
        if (currentScale >= 3) return;
        currentScale += 0.25;
        applyZoom();
    }
    
    function zoomOut() {
        if (currentScale <= 0.5) return;
        currentScale -= 0.25;
        applyZoom();
    }
    
    function resetZoom() {
        currentScale = 1;
        applyZoom();
    }
    
    function applyZoom() {
        const image = document.getElementById('enlargedImage');
        const zoomInfo = document.getElementById('zoomInfo');
        
        image.style.transform = `scale(${currentScale})`;
        zoomInfo.textContent = Math.round(currentScale * 100) + '%';
    }
    
    // Close with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEnlarged();
        }
    });
    
    // Close when clicking outside image
    document.getElementById('enlargedOverlay').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEnlarged();
        }
    });

</script>

<div class="enlarged-overlay" id="enlargedOverlay">
    <div class="close-enlarged" onclick="closeEnlarged()">
        <i class="fas fa-times"></i>
    </div>
    
    <div class="enlarged-content">
        <img class="enlarged-image" id="enlargedImage" src="" alt="">
        <div class="zoom-info" id="zoomInfo">100%</div>
    </div>
    
    <div class="enlarged-controls">
        <button class="enlarged-btn" onclick="zoomIn()" title="Zoom In">
            <i class="fas fa-search-plus"></i>
        </button>
        <button class="enlarged-btn" onclick="zoomOut()" title="Zoom Out">
            <i class="fas fa-search-minus"></i>
        </button>
        <button class="enlarged-btn" onclick="resetZoom()" title="Reset Zoom">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>

</body>
</html>

<?php 
$conn->close();
?>
