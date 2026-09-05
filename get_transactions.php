<?php
header('Content-Type: application/json');

// Database credentials from environment variables
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: 3306;
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// Validate request
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID is required."]);
    exit;
}

// Connect to Aiven MySQL using SSL
$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
@$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}

// Fetch transaction history using service_id
$stmt = $conn->prepare("SELECT id, service_id, phone, amount, status, request_id, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode([
    "status" => "success",
    "user_id" => (int)$user_id,
    "count" => count($transactions),
    "transactions" => $transactions
]);
?>

