<?php
include '../backend/config.php';

// Get search parameters
$location = isset($_GET['location']) ? $_GET['location'] : '';
$gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$orientation = isset($_GET['orientation']) ? $_GET['orientation'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build page title based on search criteria
$page_title = "Search Results";
if ($search) $page_title .= " for '" . htmlspecialchars($search) . "'";
if ($location) $page_title .= " in " . htmlspecialchars($location);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - AllQueens254</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .search-results-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .search-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .search-title {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: #333;
        }
        
        .search-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .filter-tag {
            background: #f0f0f0;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .results-count {
            color: #666;
            font-size: 1rem;
        }
        
        .back-to-search {
            display: inline-block;
            margin-top: 15px;
            color: #8a3ab9;
            text-decoration: none;
        }
        
        .back-to-search:hover {
            text-decoration: underline;
        }
        
        .creators-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .no-results i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-content">
            <a href="homepage.html" class="logo">AllQueens254</a>
            
            <nav class="main-nav">
                <button><a href="homepage.html">Homepage</a></button>
            </nav>
            
            <div class="auth-buttons">
                <a href="creator_login.php" class="btn btn-outline">Login</a>
                <a href="creator_register.html" class="btn">Sign Up</a>
            </div>
        </div>
    </header>

    <div class="search-results-container">
        <div class="search-header">
            <h1 class="search-title"><?php echo $page_title; ?></h1>
            
            <div class="search-filters">
                <?php if ($location): ?>
                    <span class="filter-tag">Location: <?php echo htmlspecialchars($location); ?></span>
                <?php endif; ?>
                
                <?php if ($gender): ?>
                    <span class="filter-tag">Gender: <?php echo htmlspecialchars($gender); ?></span>
                <?php endif; ?>
                
                <?php if ($orientation): ?>
                    <span class="filter-tag">Orientation: <?php echo htmlspecialchars($orientation); ?></span>
                <?php endif; ?>
            </div>
            
            <a href="homepage.html" class="back-to-search">
                <i class="fas fa-arrow-left"></i> Back to Search
            </a>
        </div>

        <div class="creators-grid" id="searchResults">
            <?php
            // Include the search functionality
            include '../backend/search.php';
            ?>
        </div>
    </div>

    <script>
        // Add error handling for images in search results
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.creator-image');
            images.forEach(img => {
                img.onerror = function() {
                    this.src = '../uploads/default_profile.jpg';
                };
            });
        });
    </script>
</body>
</html>
