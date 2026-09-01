<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['name']) || !isset($data['email']) || !isset($data['phone']) || !isset($data['password'])) {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit();
}

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

// Check if user already exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $data['email']);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email already registered."]);
    exit();
}

// Hash password & insert user
$hashed_pass = password_hash($data['password'], PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $data['name'], $data['email'], $data['phone'], $hashed_pass);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;
    // Initialize user wallet with 0 balance
    $wallet = $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)");
    $wallet->bind_param("i", $user_id);
    $wallet->execute();

    echo json_encode(["status" => "success", "message" => "User registered successfully.", "user_id" => $user_id]);
} else {
    echo json_encode(["status" => "error", "message" => "Registration failed."]);
}
?>
