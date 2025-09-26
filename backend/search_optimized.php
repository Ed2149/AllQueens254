<?php
include 'config.php';
include 'cache.php';

// Get all parameters
$params = [
    'location' => filter_input(INPUT_GET, 'location', FILTER_SANITIZE_STRING),
    'gender' => filter_input(INPUT_GET, 'gender', FILTER_SANITIZE_STRING),
    'orientation' => filter_input(INPUT_GET, 'orientation', FILTER_SANITIZE_STRING),
    'age_range' => filter_input(INPUT_GET, 'age_range', FILTER_SANITIZE_STRING),
    'search' => filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING),
    'featured' => isset($_GET['featured']),
    'page' => filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1,
    'limit' => filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 12
];

// Generate cache key
$cache_key = 'search_' . md5(serialize($params));
$offset = ($params['page'] - 1) * $params['limit'];

// Try to get from cache first (only cache non-featured searches)
if (!$params['featured'] && $cached_result = Cache::get($cache_key)) {
    echo $cached_result;
    exit;
}

// Build query with prepared statements
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM creators WHERE banned = 0";
$types = "";
$query_params = [];

if ($params['location']) {
    $sql .= " AND location LIKE ?";
    $types .= "s";
    $query_params[] = "%{$params['location']}%";
}

if ($params['gender']) {
    $sql .= " AND gender = ?";
    $types .= "s";
    $query_params[] = $params['gender'];
}

if ($params['orientation']) {
    $sql .= " AND orientation = ?";
    $types .= "s";
    $query_params[] = $params['orientation'];
}

if ($params['age_range']) {
    switch ($params['age_range']) {
        case '18-25': $sql .= " AND age BETWEEN 18 AND 25"; break;
        case '26-35': $sql .= " AND age BETWEEN 26 AND 35"; break;
        case '36-45': $sql .= " AND age BETWEEN 36 AND 45"; break;
        case '46+': $sql .= " AND age >= 46"; break;
    }
}

if ($params['search']) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $types .= "ss";
    $query_params[] = "%{$params['search']}%";
    $query_params[] = "%{$params['search']}%";
}

// For featured, get random creators
if ($params['featured']) {
    $sql .= " ORDER BY RAND() LIMIT 12";
} else {
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $types .= "ii";
    $query_params[] = $params['limit'];
    $query_params[] = $offset;
}

// Execute query
$stmt = $conn->prepare($sql);
if ($types && !empty($query_params)) {
    $stmt->bind_param($types, ...$query_params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get total count for pagination
$total_count = 0;
$total_pages = 1;
if (!$params['featured']) {
    $count_result = $conn->query("SELECT FOUND_ROWS() as total");
    $total_count = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_count / $params['limit']);
}

// Generate HTML output
$output = '';
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $profile_pic = !empty($row['profile_pic']) ? $row['profile_pic'] : 'uploads/default_profile.jpg';
        
        $output .= '<div class="creator-card" data-creator-id="' . $row['id'] . '">';
        $output .= '<img src="../' . htmlspecialchars($profile_pic) . '" alt="' . htmlspecialchars($row['name']) . '" class="creator-image lazy" data-src="../' . htmlspecialchars($profile_pic) . '" onerror="this.src=\'../uploads/default_profile.jpg\'">';
        $output .= '<div class="creator-content">';
        $output .= '<h10 class="creator-name">' . htmlspecialchars($row['name']) . '</h10>';
        
        $description = htmlspecialchars($row['description']);
        if (strlen($description) > 100) {
            $description = substr($description, 0, 100) . '...';
        }
        $output .= '<p class="creator-description">' . $description . '</p>';
        $output .= '</div>';
        $output .= '<div class="creator-actions">';
        $output .= '<a href="tel:' . htmlspecialchars($row['phone']) . '" class="btn-call"><i class="fas fa-phone"></i> Call Me</a>';
        $output .= '<a href="creator_profile.php?id=' . $row['id'] . '" class="btn-profile"><i class="fas fa-eye"></i> View Profile</a>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
    }
    
    // Add pagination
    if (!$params['featured'] && $total_pages > 1) {
        $output .= '<div class="pagination">';
        if ($params['page'] > 1) {
            $output .= '<a href="javascript:void(0)" onclick="loadPage(' . ($params['page'] - 1) . ')" class="pagination-link">Previous</a>';
        }
        
        // Show limited pagination links
        $start_page = max(1, $params['page'] - 2);
        $end_page = min($total_pages, $params['page'] + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $params['page']) {
                $output .= '<span class="pagination-link active">' . $i . '</span>';
            } else {
                $output .= '<a href="javascript:void(0)" onclick="loadPage(' . $i . ')" class="pagination-link">' . $i . '</a>';
            }
        }
        
        if ($params['page'] < $total_pages) {
            $output .= '<a href="javascript:void(0)" onclick="loadPage(' . ($params['page'] + 1) . ')" class="pagination-link">Next</a>';
        }
        $output .= '</div>';
    }
} else {
    $output = '<div class="no-results">No creators found matching your criteria.</div>';
}

// Cache the result (1 hour for searches)
if (!$params['featured']) {
    Cache::set($cache_key, $output, 3600);
}

echo $output;
$conn->close();
?>