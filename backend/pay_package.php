<?php
session_start();
include 'config.php';
include 'mpesa.php';

if (!isset($_SESSION['creator_id'])) {
    header("Location: ../frontend/creator_login.php");
    exit();
}

$creator_id = $_SESSION['creator_id'];
$sql = "SELECT phone, package FROM creators WHERE id = $creator_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$phone = preg_replace('/^0/', '254', $row['phone']);
$phone = preg_replace('/\D/', '', $phone);
$package = $row['package'];
$amounts = ['Regular' => 1000, 'Premium' => 1500, 'Gold' => 2000];
$amount = $amounts[$package] ?? 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account_ref = 'Renewal_' . $creator_id;
    $transaction_desc = 'Renewal for ' . $package . ' package';
    if (initiateSTKPush($phone, $amount, $account_ref, $transaction_desc, $creator_id)) {
        echo "Check your phone for M-Pesa prompt.";
    } else {
        echo "Payment initiation failed.";
    }
}
?>

<form method="POST">
    <button type="submit">Pay for <?php echo $package; ?> (<?php echo $amount; ?> KSH)</button>
</form>