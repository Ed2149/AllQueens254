<?php
include 'config.php';

// Security: Validate and sanitize input
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die("Invalid profile ID");
}

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM creators WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Profile not found");
}

$row = $result->fetch_assoc();

// Fix profile picture path
$profile_pic_path = !empty($row['profile_pic']) ? $row['profile_pic'] : 'assets/default-profile.jpg';
if (strpos($profile_pic_path, 'uploads/') !== 0 && strpos($profile_pic_path, 'assets/') !== 0) {
    $profile_pic_path = 'uploads/' . basename($profile_pic_path);
}

// Display profile
echo "<img src='$profile_pic_path' alt='Profile' style='width: 150px; height: 150px; border-radius: 50%; object-fit: cover;'>
      <h2>{$row['name']}</h2>
      <p>Location: {$row['location']}</p>
      <p>Age: {$row['age']}</p>
      <p>Gender: {$row['gender']}</p>
      <p>Orientation: {$row['orientation']}</p>
      <p>Phone: {$row['phone']}</p>
      <p>Description: {$row['description']}</p>";

// Media
$sql_media = "SELECT * FROM media WHERE creator_id = $id";
$result_media = $conn->query($sql_media);
echo "<section class='media' style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0;'>";

while($media = $result_media->fetch_assoc()) {
    // Fix media path
    $media_path = $media['path'];
    if (strpos($media_path, 'uploads/') !== 0) {
        $media_path = 'uploads/content/' . basename($media_path);
    }
    
    if ($media['type'] == 'photo') {
        echo "<img src='$media_path' alt='Photo' style='width: 100%; height: 200px; object-fit: cover;'>";
    } else {
        echo "<video controls style='width: 100%; height: 200px; object-fit: cover;'>
                <source src='$media_path' type='video/mp4'>
                Your browser does not support the video tag.
              </video>";
    }
}
echo "</section>";

$conn->close();
?>