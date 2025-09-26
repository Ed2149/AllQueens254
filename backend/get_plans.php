<?php
include 'config.php';

header('Content-Type: application/json');

// Get all active plans from database
$result = $conn->query("SELECT * FROM packages WHERE enabled = 1 ORDER BY price ASC");

if ($result && $result->num_rows > 0) {
    $plans = [];
    while ($row = $result->fetch_assoc()) {
        $plans[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'price' => $row['price'],
            'duration' => $row['duration'],
            
        ];
    }
    
    echo json_encode([
        'success' => true,
        'plans' => $plans
    ]);
} else {
    // Fallback to default plans if none in database
    $defaultPlans = [
        [
            'id' => 1,
            'name' => 'Free Trial',
            'price' => 0,
            'duration' => '30 Days',
            
        ],
        [
            'id' => 2,
            'name' => 'Regular',
            'price' => 1000,
            'duration' => '30 Days',
            
        ],
        [
            'id' => 3,
            'name' => 'Premium',
            'price' => 1500,
            'duration' => '30 Days',
            
        ],
        [
            'id' => 4,
            'name' => 'Gold',
            'price' => 2000,
            'duration' => '30 Days',
            
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'plans' => $defaultPlans
    ]);
}

$conn->close();
?>
