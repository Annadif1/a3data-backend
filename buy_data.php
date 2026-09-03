<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id']) || !isset($data['service_id']) || !isset($data['variation_code']) || !isset($data['phone']) || !isset($data['amount'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields: user_id, service_id, variation_code, phone, amount"]);
    exit();
}

$user_id = $data['user_id'];
$service_id = $data['service_id']; // e.g., 'mtn-data', 'airtel-data', 'glo-data'
$variation_code = $data['variation_code']; // e.g., 'mtn-100mb-24hrs'
$phone = $data['phone'];
$amount = floatval($data['amount']);
$request_id = date('YmdHis') . rand(1000, 9999); // VTpass required format: YYYYMMDDHHII + random digits

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
$log_stmt->bind_param("isd", $user_id, $request_id, $amount);
$log_stmt->execute();

// 4. Hit VTpass API
// Use 'https://sandbox.vtpass.com/api/pay' for testing or 'https://vtpass.com/api/pay' for production
$vtpass_url = "https://sandbox.vtpass.com/api/pay"; 
$api_key = getenv('VTPASS_API_KEY'); 
$secret_key = getenv('VTPASS_SECRET_KEY');

$payload = [
    'request_id' => $request_id,
    'serviceID' => $service_id,
    'billersCode' => $phone,
    'variation_code' => $variation_code,
    'amount' => $amount,
    'phone' => $phone
];

$ch = curl_init($vtpass_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "api-key: " . $api_key,
    "secret-key: " . $secret_key
]);

$response = curl_exec($ch);
curl_close($ch);

$res_data = json_decode($response, true);

// 5. Check VTpass response (000 = Successful)
if (isset($res_data['code']) && $res_data['code'] === "000") {
    $update_tx = $conn->prepare("UPDATE transactions SET status = 'success' WHERE reference = ?");
    $update_tx->bind_param("s", $request_id);
    $update_tx->execute();

    echo json_encode([
        "status" => "success",
        "message" => "Data purchase successful!",
        "request_id" => $request_id,
        "vtpass_response" => $res_data
    ]);
} else {
    // Refund user if purchase fails
    $refund_stmt = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
    $refund_stmt->bind_param("di", $amount, $user_id);
    $refund_stmt->execute();

    $fail_tx = $conn->prepare("UPDATE transactions SET status = 'failed' WHERE reference = ?");
    $fail_tx->bind_param("s", $request_id);
    $fail_tx->execute();

    echo json_encode([
        "status" => "error",
        "message" => "Purchase failed. Wallet refunded.",
        "vtpass_error" => $res_data
    ]);
}
?>
