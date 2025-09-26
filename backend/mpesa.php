<?php
include 'mpesa_config.php';
include 'config.php'; // DB connection

function getAccessToken() {
    $url = API_URL . '/oauth/v1/generate?grant_type=client_credentials';
    $credentials = base64_encode(CONSUMER_KEY . ':' . CONSUMER_SECRET);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $credentials,
        'Content-Type: application/json'
    ]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    logMpesaTransaction("Access token request", [
        'http_code' => $http_code,
        'response' => $response
    ]);
    
    if ($http_code !== 200 || !$response) {
        curl_close($curl);
        return false;
    }
    
    curl_close($curl);
    
    $data = json_decode($response);
    return isset($data->access_token) ? $data->access_token : false;
}

function initiateSTKPush($phone, $amount, $account_ref, $transaction_desc, $creator_id) {
    // Include the database connection
    global $conn;
    
    $access_token = getAccessToken();
    
    if (!$access_token) {
        logMpesaTransaction("Failed to get access token");
        return false;
    }
    
    $url = API_URL . '/mpesa/stkpush/v1/processrequest';
    
    $timestamp = date('YmdHis');
    $password = base64_encode(BUSINESS_SHORTCODE . PASSKEY . $timestamp);
    
    // Format phone number correctly (2547...)
    $formatted_phone = preg_replace('/^0/', '254', $phone);
    $formatted_phone = preg_replace('/[^0-9]/', '', $formatted_phone);
    
    $data = [
        'BusinessShortCode' => BUSINESS_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $formatted_phone,
        'PartyB' => BUSINESS_SHORTCODE,
        'PhoneNumber' => $formatted_phone,
        'CallBackURL' => CALLBACK_URL,
        'AccountReference' => $account_ref,
        'TransactionDesc' => $transaction_desc
    ];
    
    logMpesaTransaction("STK Push request data", $data);
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    
    logMpesaTransaction("STK Push response", [
        'http_code' => $http_code,
        'response' => $response,
        'curl_error' => $curl_error
    ]);
    
    curl_close($curl);
    
    if (!$response) {
        return false;
    }
    
    $resp = json_decode($response);
    
    if (isset($resp->ResponseCode) && $resp->ResponseCode == "0") {
        // Save CheckoutRequestID to track
        $checkout_id = $resp->CheckoutRequestID;
        $merchant_request_id = $resp->MerchantRequestID;
        
        $stmt = $conn->prepare("UPDATE creators SET checkout_id = ?, merchant_request_id = ? WHERE id = ?");
        $stmt->bind_param("ssi", $checkout_id, $merchant_request_id, $creator_id);
        
        if ($stmt->execute()) {
            logMpesaTransaction("Checkout IDs saved successfully", [
                'checkout_id' => $checkout_id,
                'merchant_request_id' => $merchant_request_id
            ]);
        } else {
            logMpesaTransaction("Failed to save checkout IDs", ['error' => $conn->error]);
        }
        
        return true;
    } else {
        $error_message = $resp->errorMessage ?? 'Unknown error';
        logMpesaTransaction("STK Push failed", [
            'error' => $error_message,
            'full_response' => $response
        ]);
        return false;
    }
}

// Test function to check if STK push is working
function testSTKPush() { 
    // Use test phone number from M-Pesa sandbox
    $test_phone = '254113415255'; // M-Pesa test number
    $amount = 1; // 1 KSH for testing
    $account_ref = 'Test_123';
    $transaction_desc = 'Test payment';
    
    logMpesaTransaction("Testing STK Push", [
        'phone' => $test_phone,
        'amount' => $amount
    ]);
    
    return initiateSTKPush($test_phone, $amount, $account_ref, $transaction_desc, 0);
}
?>