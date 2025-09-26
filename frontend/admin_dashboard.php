<?php
session_start();
include '../backend/config.php';

// Admin authentication check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.html");
    exit;
}

// Handle ban action with prepared statement
if (isset($_POST['ban'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("UPDATE creators SET banned = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

// Handle unban action
if (isset($_POST['unban'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("UPDATE creators SET banned = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}
// Handle delete action
if (isset($_POST['delete_creator'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        // First, delete associated media files and records
        $media_stmt = $conn->prepare("SELECT path FROM media WHERE creator_id = ?");
        $media_stmt->bind_param("i", $id);
        $media_stmt->execute();
        $media_result = $media_stmt->get_result();
        
        while ($media = $media_result->fetch_assoc()) {
            if (file_exists("../" . $media['path'])) {
                unlink("../" . $media['path']);
            }
        }
        
        // Delete media records
        $delete_media_stmt = $conn->prepare("DELETE FROM media WHERE creator_id = ?");
        $delete_media_stmt->bind_param("i", $id);
        $delete_media_stmt->execute();
        
        // Delete transaction records
        $delete_transactions_stmt = $conn->prepare("DELETE FROM transactions WHERE creator_id = ?");
        $delete_transactions_stmt->bind_param("i", $id);
        $delete_transactions_stmt->execute();
        
        // Finally, delete the creator
        $delete_creator_stmt = $conn->prepare("DELETE FROM creators WHERE id = ?");
        $delete_creator_stmt->bind_param("i", $id);
        
        if ($delete_creator_stmt->execute()) {
            $delete_success = "Creator deleted successfully.";
        } else {
            $delete_error = "Error deleting creator: " . $conn->error;
        }
    }
}

// Get all creators with prepared statement
$stmt = $conn->prepare("SELECT * FROM creators ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();


// Handle package management
if (isset($_POST['update_package'])) {
    $package_id = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $duration = filter_input(INPUT_POST, 'duration', FILTER_SANITIZE_STRING);
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    
    if ($package_id) {
        $stmt = $conn->prepare("UPDATE packages SET name = ?, price = ?, duration = ?, enabled = ? WHERE id = ?");
        $stmt->bind_param("sdsii", $name, $price, $duration, $enabled, $package_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO packages (name, price, duration, enabled) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdsi", $name, $price, $duration, $enabled);
        $stmt->execute();
    }
}

// Get all creators with prepared statement
$stmt = $conn->prepare("SELECT * FROM creators ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();

// Get packages
$packages_result = $conn->query("SELECT * FROM packages ORDER BY price ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AllQueens254</title>
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
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .admin-header {
            background: linear-gradient(135deg, rgba(0,0,0,0.9) 0%, rgba(34,34,34,0.9) 100%);
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(231, 0, 8, 0.3);
            border: 2px solid var(--red);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h2 {
            color: var(--orange);
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-header h2 i {
            font-size: 28px;
        }
        
        .admin-header div {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-header span {
            color: var(--cream);
            font-weight: 500;
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
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(34,34,34,0.8) 100%);
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(231, 0, 8, 0.2);
            border: 1px solid var(--red);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(231, 0, 8, 0.4);
            border-color: var(--orange);
        }
        
        .stat-icon {
            font-size: 32px;
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
        
        /* Tabs */
        .admin-tabs {
            display: flex;
            border-bottom: 2px solid var(--red);
            margin-bottom: 25px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px 8px 0 0;
            padding: 5px;
        }
        
        .tab-button {
            padding: 12px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: var(--cream);
            border-radius: 5px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-button.active {
            background: var(--red);
            color: white;
        }
        
        .tab-button:hover {
            background: rgba(231, 0, 8, 0.3);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Tables */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(34,34,34,0.8) 100%);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(231, 0, 8, 0.3);
            margin-bottom: 30px;
            border: 1px solid var(--red);
        }
        
        .admin-table th,
        .admin-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 153, 64, 0.2);
        }
        
        .admin-table th {
            background: rgba(231, 0, 8, 0.2);
            font-weight: 600;
            color: var(--orange);
            font-size: 16px;
        }
        
        .admin-table tr:hover {
            background: rgba(255, 153, 64, 0.05);
        }
        
        .status-banned {
            color: var(--red);
            font-weight: bold;
        }
        
        .status-active {
            color: var(--orange);
            font-weight: bold;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-danger {
            background: var(--red);
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        /* Package Form */
        .package-form {
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(34,34,34,0.8) 100%);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(231, 0, 8, 0.3);
            margin-bottom: 25px;
            border: 1px solid var(--red);
        }
        
        .package-form h3 {
            color: var(--orange);
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--cream);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--red);
            border-radius: 5px;
            background: var(--black);
            color: var(--cream);
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(255, 153, 64, 0.3);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Toggle switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--orange);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        /* Delete Modal Styles */
        .delete-confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .delete-modal-content {
            background: linear-gradient(135deg, rgba(0,0,0,0.9) 0%, rgba(34,34,34,0.9) 100%);
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 0 30px rgba(231, 0, 8, 0.5);
            border: 2px solid var(--red);
        }
        
        .delete-modal-content h3 {
            color: var(--orange);
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .delete-modal-content p {
            margin-bottom: 10px;
            color: var(--cream);
        }
        
        .delete-modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
        
        .btn-delete {
            background: var(--red);
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .creator-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .notification {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            font-weight: 500;
            border: 2px solid transparent;
        }
        
        .notification.success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border-color: #28a745;
        }
        
        .notification.error {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border-color: #dc3545;
        }
        
        /* Section Headers */
        h2 {
            color: var(--orange);
            margin-bottom: 20px;
            font-size: 24px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--red);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .admin-container {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-tabs {
                flex-wrap: wrap;
            }
            
            .tab-button {
                flex: 1;
                min-width: 120px;
                justify-content: center;
            }
            
            .creator-actions {
                flex-direction: column;
            }
            
            .admin-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 480px) {
            .delete-modal-buttons {
                flex-direction: column;
            }
            
            .tab-button {
                min-width: 100px;
                padding: 10px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
            <div>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="../backend/admin_logout.php" class="btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($delete_success)): ?>
            <div class="notification success">
                <i class="fas fa-check-circle"></i> <?php echo $delete_success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($delete_error)): ?>
            <div class="notification error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $delete_error; ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number">
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM creators");
                    $stmt->execute();
                    $count_result = $stmt->get_result();
                    echo $count_result->fetch_assoc()['total'];
                    ?>
                </div>
                <div class="stat-label">Total Creators</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number">
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) as active FROM creators WHERE banned = 0");
                    $stmt->execute();
                    $active_result = $stmt->get_result();
                    echo $active_result->fetch_assoc()['active'];
                    ?>
                </div>
                <div class="stat-label">Active Creators</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="stat-number">
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) as banned FROM creators WHERE banned = 1");
                    $stmt->execute();
                    $banned_result = $stmt->get_result();
                    echo $banned_result->fetch_assoc()['banned'];
                    ?>
                </div>
                <div class="stat-label">Banned Creators</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-number">
                    Ksh 
                    <?php
                    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_earnings FROM transactions WHERE status = 'completed'");
                    $stmt->execute();
                    $earnings_result = $stmt->get_result();
                    echo number_format($earnings_result->fetch_assoc()['total_earnings'], 2);
                    ?>
                </div>
                <div class="stat-label">Total Earnings</div>
            </div>
        </div>
        
        <div class="admin-tabs">
            <button class="tab-button active" onclick="switchTab('creators')">
                <i class="fas fa-users"></i> Creators
            </button>
            <button class="tab-button" onclick="switchTab('packages')">
                <i class="fas fa-crown"></i> Packages
            </button>
            <button class="tab-button" onclick="switchTab('transactions')">
                <i class="fas fa-receipt"></i> Transactions
            </button>
        </div>
        
        <div id="creators-tab" class="tab-content active">
            <h2><i class="fas fa-users"></i> Manage Creators</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Package</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                        <td><?php echo htmlspecialchars($row['package']); ?></td>
                        <td>
                            <?php if ($row['banned']): ?>
                                <span class="status-banned">Banned</span>
                            <?php else: ?>
                                <span class="status-active">Active</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <div class="creator-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <?php if ($row['banned']): ?>
                                        <button type="submit" name="unban" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Unban
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="ban" class="btn btn-danger btn-sm">
                                            <i class="fas fa-ban"></i> Ban
                                        </button>
                                    <?php endif; ?>
                                </form>
                                <a href="creator_profile.php?id=<?php echo $row['id']; ?>" class="btn btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <button class="btn btn-delete btn-sm" onclick="showDeleteConfirm(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['name'])); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div id="packages-tab" class="tab-content">
            <h2><i class="fas fa-crown"></i> Manage Packages</h2>
            
            <div class="package-form">
                <h3>Add New Package</h3>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Package Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="price">Price (Ksh)</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>
                       
                        <div class="form-group">
                            <label for="enabled">Status</label>
                            <div class="checkbox-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" id="enabled" name="enabled" checked>
                                    <span class="slider"></span>
                                </label>
                                <span>Enabled</span>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="update_package" class="btn">
                        <i class="fas fa-plus"></i> Add Package
                    </button>
                </form>
            </div>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Type</th>
                        <th>Enabled</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Default packages
                    $default_packages = [
                        ['Free Trial', 0, '30 Days', 'Subscription', 1],
                        ['Regular', 1000, '30 Days', 'Subscription', 1],
                        ['Premium', 1500, '30 Days', 'Subscription', 1],
                        ['Gold', 2000, '30 Days', 'Subscription', 1]
                    ];
                    
                    // Display packages from database or use defaults
                    if ($packages_result && $packages_result->num_rows > 0) {
                        while($package = $packages_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($package['name']); ?></td>
                        <td>Ksh <?php echo number_format($package['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($package['duration']); ?></td>
                        <td>Subscription</td>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" <?php echo $package['enabled'] ? 'checked' : ''; ?> disabled>
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td class="creator-actions">
                            <button class="btn btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                        <?php endwhile;
                    } else {
                        // Show default packages
                        foreach ($default_packages as $package): ?>
                    <tr>
                        <td><?php echo $package[0]; ?></td>
                        <td>Ksh <?php echo number_format($package[1], 2); ?></td>
                        <td><?php echo $package[2]; ?></td>
                        <td><?php echo $package[3]; ?></td>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" <?php echo $package[4] ? 'checked' : ''; ?> disabled>
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td class="creator-actions">
                            <button class="btn btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                        <?php endforeach;
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div id="transactions-tab" class="tab-content">
            <h2><i class="fas fa-receipt"></i> Transaction History</h2>
            <?php
            $transactions_result = $conn->query("
                SELECT t.*, c.name as creator_name 
                FROM transactions t 
                LEFT JOIN creators c ON t.creator_id = c.id 
                ORDER BY t.created_at DESC
            ");
            ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Creator</th>
                        <th>Amount</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions_result && $transactions_result->num_rows > 0): ?>
                        <?php while($transaction = $transactions_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $transaction['id']; ?></td>
                            <td><?php echo htmlspecialchars($transaction['creator_name']); ?></td>
                            <td>Ksh <?php echo number_format($transaction['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($transaction['phone_number']); ?></td>
                            <td>
                                <span class="status-<?php echo $transaction['status'] === 'completed' ? 'active' : 'banned'; ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: var(--cream);">
                                <i class="fas fa-receipt" style="font-size: 40px; margin-bottom: 10px; display: block; color: var(--orange);"></i>
                                No transactions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="delete-confirm-modal">
        <div class="delete-modal-content">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <p>Are you sure you want to delete creator: <strong id="deleteCreatorName" style="color: var(--orange);"></strong>?</p>
            <p style="color: var(--red); font-weight: bold;">
                <i class="fas fa-warning"></i> Warning: This action cannot be undone. All creator data will be permanently deleted.
            </p>
            
            <form id="deleteForm" method="POST">
                <input type="hidden" name="id" id="deleteCreatorId">
                <input type="hidden" name="delete_creator" value="1">
                <div class="delete-modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="hideDeleteConfirm()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-delete">
                        <i class="fas fa-trash"></i> Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Deactivate all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Activate selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activate selected button
            event.currentTarget.classList.add('active');
        }
        
        function showDeleteConfirm(creatorId, creatorName) {
            document.getElementById('deleteCreatorId').value = creatorId;
            document.getElementById('deleteCreatorName').textContent = creatorName;
            document.getElementById('deleteConfirmModal').style.display = 'flex';
        }
        
        function hideDeleteConfirm() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('deleteConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteConfirm();
            }
        });
    </script>
</body>
</html>

<?php 
$conn->close();
?>
