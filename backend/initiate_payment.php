<?php
include 'config.php';
include 'mpesa.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = mysqli_real_escape_string($conn, $_POST['plan']);
    $amount = (float)$_POST['amount'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $creator_id = $_SESSION['creator_id'];
    
    // Format phone number (remove leading 0, add 254)
    $formatted_phone = '254' . substr(preg_replace('/[^0-9]/', '', $phone), -9);
    
    // Initiate STK push
    $account_ref = 'Upgrade_' . $plan . '_' . $creator_id;
    $transaction_desc = 'Subscription upgrade to ' . $plan . ' plan';
    
    if (initiateSTKPush($formatted_phone, $amount, $account_ref, $transaction_desc, $creator_id)) {
        echo json_encode([
            'success' => true,
            'message' => 'Payment request sent to your phone'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to initiate payment. Please try again.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>