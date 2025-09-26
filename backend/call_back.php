<?php
include 'config.php';
include 'mpesa_config.php';

// Log the raw callback data
$callback_data = file_get_contents('php://input');
logMpesaTransaction("Callback received", ['raw_data' => $callback_data]);

$callback = json_decode($callback_data);

if (json_last_error() !== JSON_ERROR_NONE) {
    logMpesaTransaction("JSON decode error", ['error' => json_last_error_msg()]);
    exit;
}

if (isset($callback->Body->stkCallback)) {
    $result_code = $callback->Body->stkCallback->ResultCode;
    $checkout_id = $callback->Body->stkCallback->CheckoutRequestID;
    $merchant_request_id = $callback->Body->stkCallback->MerchantRequestID;

    logMpesaTransaction("Callback processed", [
        'result_code' => $result_code,
        'checkout_id' => $checkout_id,
        'merchant_request_id' => $merchant_request_id
    ]);

    if ($result_code == 0) {
        // Successful payment
        if (isset($callback->Body->stkCallback->CallbackMetadata->Item)) {
            $metadata = $callback->Body->stkCallback->CallbackMetadata->Item;
            
            $amount = $metadata[0]->Value ?? null;
            $receipt = $metadata[1]->Value ?? null;
            $transaction_date = $metadata[3]->Value ?? null;
            $phone = $metadata[4]->Value ?? null;

            logMpesaTransaction("Payment successful", [
                'amount' => $amount,
                'receipt' => $receipt,
                'transaction_date' => $transaction_date,
                'phone' => $phone
            ]);

            if ($phone) {
                // Format phone number (remove leading 0 and add 254)
                $formatted_phone = '254' . substr($phone, -9);
                
                // Update creator as paid using prepared statement
                $stmt = $conn->prepare("UPDATE creators SET paid = 1, checkout_id = NULL WHERE phone LIKE ?");
                $search_phone = '%' . substr($formatted_phone, -9);
                $stmt->bind_param("s", $search_phone);
                
                if ($stmt->execute()) {
                    logMpesaTransaction("Creator payment status updated", ['phone' => $formatted_phone]);
                    
                    // Insert transaction record
                    $stmt2 = $conn->prepare("INSERT INTO transactions (creator_id, amount, mpesa_code, phone_number, status) 
                                           SELECT id, ?, ?, ?, 'completed' 
                                           FROM creators WHERE phone LIKE ?");
                    $stmt2->bind_param("dsss", $amount, $receipt, $formatted_phone, $search_phone);
                    $stmt2->execute();
                    
                } else {
                    logMpesaTransaction("Failed to update creator payment status", ['error' => $conn->error]);
                }
            }
        }
    } else {
        // Failed payment
        $result_desc = $callback->Body->stkCallback->ResultDesc;
        logMpesaTransaction("Payment failed", [
            'result_code' => $result_code,
            'result_desc' => $result_desc
        ]);
    }
} else {
    logMpesaTransaction("Invalid callback format", ['callback' => $callback_data]);
}

// Send success response to M-Pesa
header('Content-Type: application/json');
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback processed successfully']);

$conn->close();
?>