<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id']) || !isset($data['network']) || !isset($data['plan_id']) || !isset($data['phone']) || !isset($data['amount'])) {
    echo json_encode(["status" => "error", "message" => "All fields (user_id, network, plan_id, phone, amount) are required."]);
    exit();
}

$user_id = $data['user_id'];
$network = $data['network'];
$plan_id = $data['plan_id'];
$phone = $data['phone'];
$amount = floatval($data['amount']);
$reference = "DATA_" . uniqid() . "_" . time();

$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}

// 1. Check user wallet balance
$wallet_stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$wallet_stmt->bind_param("i", $user_id);
$wallet_stmt->execute();
$res = $wallet_stmt->get_result();

if ($row = $res->fetch_assoc()) {
    if ($row['balance'] < $amount) {
        echo json_encode(["status" => "error", "message" => "Insufficient wallet balance."]);
        exit();
    }
} else {
    echo json_encode(["status" => "error", "message" => "Wallet not found."]);
    exit();
}

// 2. Deduct amount from user wallet
$deduct_stmt = $conn->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND balance >= ?");
$deduct_stmt->bind_param("did", $amount, $user_id, $amount);
$deduct_stmt->execute();

if ($deduct_stmt->affected_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Transaction failed during wallet deduction."]);
    exit();
}

// 3. Record pending transaction
$log_stmt = $conn->prepare("INSERT INTO transactions (user_id, reference, type, amount, status) VALUES (?, ?, 'data_purchase', ?, 'pending')");
$log_stmt->bind_param("isd", $user_id, $reference, $amount);
$log_stmt->execute();

// 4. Hit VTU Provider API (Replace URL/Headers/Payload with your VTU Provider details)
$vtu_api_url = "https://your-vtu-provider-api.com/api/data/"; // Replace with provider API
$api_token = "YOUR_VTU_PROVIDER_API_TOKEN"; // Replace with your token

$payload = json_encode([
    "network" => $network,
    "mobile_number" => $phone,
    "plan" => $plan_id,
    "Ported_number" => true
]);

$ch = curl_init($vtu_api_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Token " . $api_token,
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

$provider_res = json_decode($response, true);

// 5. Update transaction status based on API response
if (isset($provider_res['Status']) && strtolower($provider_res['Status']) == 'successful') {
    $update_tx = $conn->prepare("UPDATE transactions SET status = 'success' WHERE reference = ?");
    $update_tx->bind_param("s", $reference);
    $update_tx->execute();

    echo json_encode([
        "status" => "success",
        "message" => "Data purchase successful!",
        "reference" => $reference
    ]);
} else {
    // Refund user if provider request fails
    $refund_stmt = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
    $refund_stmt->bind_param("di", $amount, $user_id);
    $refund_stmt->execute();

    $fail_tx = $conn->prepare("UPDATE transactions SET status = 'failed' WHERE reference = ?");
    $fail_tx->bind_param("s", $reference);
    $fail_tx->execute();

    echo json_encode([
        "status" => "error",
        "message" => "Data purchase failed. Wallet refunded.",
        "provider_error" => $provider_res
    ]);
}
?>
