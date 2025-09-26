<?php
include 'config.php';

// Get and sanitize search parameters
$location = filter_input(INPUT_GET, 'location', FILTER_SANITIZE_STRING);
$gender = filter_input(INPUT_GET, 'gender', FILTER_SANITIZE_STRING);
$orientation = filter_input(INPUT_GET, 'orientation', FILTER_SANITIZE_STRING);
$age_range = filter_input(INPUT_GET, 'age_range', FILTER_SANITIZE_STRING);
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
$featured = isset($_GET['featured']);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 12; // Default 12 items per page

// Calculate offset for pagination
$offset = ($page - 1) * $limit;

// Build query with prepared statements
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM creators WHERE banned = 0";
$count_sql = "SELECT COUNT(*) as total FROM creators WHERE banned = 0";
$types = "";
$params = [];

if ($location) {
    $sql .= " AND location LIKE ?";
    $count_sql .= " AND location LIKE ?";
    $types .= "s";
    $params[] = "%$location%";
}

if ($gender) {
    $sql .= " AND gender = ?";
    $count_sql .= " AND gender = ?";
    $types .= "s";
    $params[] = $gender;
}

if ($orientation) {
    $sql .= " AND orientation = ?";
    $count_sql .= " AND orientation = ?";
    $types .= "s";
    $params[] = $orientation;
}

if ($age_range) {
    switch ($age_range) {
        case '18-25':
            $sql .= " AND age BETWEEN 18 AND 25";
            $count_sql .= " AND age BETWEEN 18 AND 25";
            break;
        case '26-35':
            $sql .= " AND age BETWEEN 26 AND 35";
            $count_sql .= " AND age BETWEEN 26 AND 35";
            break;
        case '36-45':
            $sql .= " AND age BETWEEN 36 AND 45";
            $count_sql .= " AND age BETWEEN 36 AND 45";
            break;
        case '46+':
            $sql .= " AND age >= 46";
            $count_sql .= " AND age >= 46";
            break;
    }
}

if ($search) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $count_sql .= " AND (name LIKE ? OR description LIKE ?)";
    $types .= "ss";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// For featured, get random creators (limit to 12)
if ($featured) {
    $sql .= " ORDER BY RAND() LIMIT 12";
} else {
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;
}

// Execute query with prepared statement
$stmt = $conn->prepare($sql);
if ($types && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get total count for pagination
if (!$featured) {
    $count_stmt = $conn->prepare($count_sql);
    if ($types && !empty($params)) {
        // Remove the limit and offset params for count query
        $count_params = array_slice($params, 0, -2);
        $count_types = substr($types, 0, -2);
        if (!empty($count_types)) {
            $count_stmt->bind_param($count_types, ...$count_params);
        }
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_count = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_count / $limit);
}

// Output as HTML cards
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Use correct path for profile pictures
        $profile_pic = !empty($row['profile_pic']) ? $row['profile_pic'] : 'uploads/default_profile.jpg';
        
        echo '<div class="creator-card">';
        echo '<img src="../' . htmlspecialchars($profile_pic) . '" alt="' . htmlspecialchars($row['name']) . '" class="creator-image" onerror="this.src=\'../uploads/default_profile.jpg\'">';
        echo '<div class="creator-content">';
        echo '<h3 class="creator-name">' . htmlspecialchars($row['name']) . '</h3>';
        echo '<div class="creator-details">';
        echo '<div class="creator-detail"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['location']) . '</div>';
        echo '<div class="creator-detail"><i class="fas fa-user"></i> ' . htmlspecialchars($row['gender']) . '</div>';
        echo '<div class="creator-detail"><i class="fas fa-heart"></i> ' . htmlspecialchars($row['orientation']) . '</div>';
        echo '<div class="creator-detail"><i class="fas fa-birthday-cake"></i> ' . $row['age'] . ' years</div>';
        
        // Truncate description if too long
        $description = htmlspecialchars($row['description']);
        if (strlen($description) > 100) {
            $description = substr($description, 0, 100) . '...';
        }
        echo '<p class="creator-description">' . $description . '</p>';
        echo '</div>';
        echo '<div class="creator-actions">';
        echo '<a href="tel:' . htmlspecialchars($row['phone']) . '" class="btn-call"><i class="fas fa-phone"></i> Call</a>';
        echo '<a href="creator_profile.php?id=' . $row['id'] . '" class="btn-profile"><i class="fas fa-eye"></i> View Profile</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    // Add pagination if not featured view
    if (!$featured && isset($total_pages) && $total_pages > 1) {
        echo '<div class="pagination">';
        if ($page > 1) {
            echo '<a href="javascript:void(0)" onclick="loadPage(' . ($page - 1) . ')" class="pagination-link">Previous</a>';
        }
        
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                echo '<span class="pagination-link active">' . $i . '</span>';
            } else {
                echo '<a href="javascript:void(0)" onclick="loadPage(' . $i . ')" class="pagination-link">' . $i . '</a>';
            }
        }
        
        if ($page < $total_pages) {
            echo '<a href="javascript:void(0)" onclick="loadPage(' . ($page + 1) . ')" class="pagination-link">Next</a>';
        }
        echo '</div>';
    }
} else {
    echo '<div class="no-results">No creators found matching your criteria.</div>';
}

$conn->close();
?>