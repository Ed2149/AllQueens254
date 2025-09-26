<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config.php';
include 'mpesa.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    file_put_contents('debug.log', "Form submitted\n", FILE_APPEND);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $age = (int)$_POST['age'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $orientation = mysqli_real_escape_string($conn, $_POST['orientation']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $package = mysqli_real_escape_string($conn, $_POST['package']);
    $mpesa = mysqli_real_escape_string($conn, $_POST['mpesa']);

    $target_dir = "../uploads/profiles";
    $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
    file_put_contents('debug.log', "Upload attempt: $target_file\n", FILE_APPEND);

    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
        file_put_contents('debug.log', "Upload successful\n", FILE_APPEND);
        $check_sql = "SELECT trail_start FROM creators WHERE phone = '$phone'";
        $check_result = $conn->query($check_sql);
        file_put_contents('debug.log', "Trail check SQL: $check_sql, Result: " . ($check_result ? "Success" : "Failure") . "\n", FILE_APPEND);
        $is_trail_expired = false;
        $trail_end = date('Y-m-d', strtotime('+1 month'));
        if ($check_result && $check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            $trail_start = $row['trail_start'] ?: date('Y-m-d');
            $trail_end = date('Y-m-d', strtotime('+1 month', strtotime($trail_start)));
            $is_trail_expired = date('Y-m-d') > $trail_end;
            file_put_contents('debug.log', "Trail start: $trail_start, Trail end: $trail_end, Expired: $is_trail_expired\n", FILE_APPEND);
            if ($package == 'Free Trail' && !$is_trail_expired) { 
                header("Location: ../frontend/creator_register.html?error=You have already used the free trail or it is still active.");
                exit;
            }
        }

        $sql = "INSERT INTO creators (name, location, age, description, gender, orientation, phone, password, package, mpesa, profile_pic, paid, trail_start) 
                VALUES ('$name', '$location', $age, '$description', '$gender', '$orientation', '$phone', '$password', '$package', '$mpesa', '$target_file', 0, CURDATE())";
        file_put_contents('debug.log', "Insert SQL: $sql\n", FILE_APPEND);
        if ($conn->query($sql) === TRUE) {
            file_put_contents('debug.log', "Insert successful, creator_id: " . $conn->insert_id . "\n", FILE_APPEND);
            $creator_id = $conn->insert_id;
            $_SESSION['creator_id'] = $creator_id; // Auto-login after registration

            // Check trial status for the new user
            $check_sql = "SELECT trail_end FROM creators WHERE id = $creator_id";
            $check_result = $conn->query($check_sql);
            $row = $check_result->fetch_assoc();
            $trail_end = $row['trail_end'];
            $current_date = date('Y-m-d');

            file_put_contents('debug.log', "New user trail end: $trail_end, Current date: $current_date\n", FILE_APPEND);

            if ($current_date > $trail_end) {
                // Trial expired, show popup for package selection
                $packages = ['Regular' => 1000, 'Premium' => 1500, 'Gold' => 2000];
                ?>
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Trail Expired</title>
                    <style>
                        .popup {
                            display: none;
                            position: fixed;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            background: white;
                            padding: 20px;
                            border: 1px solid #ccc;
                            box-shadow: 0 0 10px rgba(0,0,0,0.5);
                            z-index: 1000;
                        }
                        .overlay {
                            display: none;
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background: rgba(0,0,0,0.5);
                            z-index: 999;
                        }
                    </style>
                </head>
                <body>
                    <div class="overlay" id="overlay"></div>
                    <div class="popup" id="popup">
                        <h2>Your Trial Has Expired</h2>
                        <p>Please select a package to continue:</p>
                        <form id="packageForm">
                            <?php foreach ($packages as $pkg => $amount): ?>
                                <input type="radio" name="package" value="<?php echo $pkg; ?>" required> <?php echo $pkg; ?> (KSH <?php echo $amount; ?>)<br>
                            <?php endforeach; ?>
                            <button type="submit">Pay Now</button>
                        </form>
                    </div>

                    <script>
                        document.getElementById('overlay').style.display = 'block';
                        document.getElementById('popup').style.display = 'block';

                        document.getElementById('packageForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            var package = document.querySelector('input[name="package"]:checked').value;
                            var amount = <?php echo json_encode($packages); ?>[package];
                            var phone = "<?php echo $phone; ?>".replace(/^0/, '254').replace(/\D/g, '');
                            var creator_id = "<?php echo $creator_id; ?>";
                            var account_ref = 'Subscription_' + creator_id;
                            var transaction_desc = 'Payment for ' + package + ' package';

                            // Simulate M-Pesa payment (replace with actual API call)
                            fetch('../backend/mpesa.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'phone=' + encodeURIComponent(phone) + '&amount=' + encodeURIComponent(amount) + '&account_ref=' + encodeURIComponent(account_ref) + '&transaction_desc=' + encodeURIComponent(transaction_desc) + '&creator_id=' + encodeURIComponent(creator_id)
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update database to mark as paid (simulated)
                                    fetch('../backend/update_paid.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: 'creator_id=' + encodeURIComponent(creator_id) + '&package=' + encodeURIComponent(package)
                                    })
                                    .then(() => {
                                        alert('Payment successful! Redirecting...');
                                        window.location.href = '../frontend/creator_profile.php?id=<?php echo $creator_id; ?>';
                                    });
                                } else {
                                    alert('Payment failed: ' + data.error);
                                }
                            })
                            .catch(error => {
                                alert('Error during payment: ' + error);
                            });
                        });
                    </script>
                </body>
                </html>
                <?php
                exit;
            } else {
                // Trial active, redirect to profile
                header("Location: ../frontend/creator_profile.php?id=" . $creator_id);
                exit;
            }
        } else {
            file_put_contents('debug.log', "Insert failed: " . $conn->error . "\n", FILE_APPEND);
            header("Location: ../frontend/creator_register.html?error=Database error: " . urlencode($conn->error));
            exit;
        }
    } else {
        file_put_contents('debug.log', "Upload failed: " . ($_FILES["profile_pic"]["error"] ? "Error " . $_FILES["profile_pic"]["error"] : "No file") . "\n", FILE_APPEND);
        header("Location: ../frontend/creator_register.html?error=File upload failed.");
        exit;
    }
}

$conn->close();
?>