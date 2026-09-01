<?php
// Retrieve payload and signature
$input = file_get_contents('php://input');
$event = json_decode($input, true);

// Respond with 200 OK to Paystack
http_response_code(200);

if ($event['event'] == 'charge.success') {
    $amount = $event['data']['amount'] / 100; // Convert kobo to Naira
    $email = $event['data']['customer']['email'];
    $reference = $event['data']['reference'];

    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $dbname = getenv('DB_NAME');

    $conn = new mysqli($host, $user, $pass, $dbname, $port);

    if ($conn->connect_error) {
        exit();
    }

    // Check if user exists
    $user_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $user_query->bind_param("s", $email);
    $user_query->execute();
    $result = $user_query->get_result();

    if ($user = $result->fetch_assoc()) {
        $user_id = $user['id'];

        // Credit user wallet
        $update_wallet = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
        $update_wallet->bind_param("di", $amount, $user_id);
        $update_wallet->execute();

        // Record transaction
        $log_tx = $conn->prepare("INSERT INTO transactions (user_id, reference, type, amount, status) VALUES (?, ?, 'wallet_topup', ?, 'success')");
        $log_tx->bind_param("isd", $user_id, $reference, $amount);
        $log_tx->execute();
    }
}
?>
