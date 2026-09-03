<?php
$host = trim(getenv('DB_HOST'));
$port = getenv('DB_PORT');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

if (!@$conn->real_connect($host, $user, $pass, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// Create Test User (ID: 1)
$passHash = password_hash('password123', PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO users (id, name, email, phone, password) VALUES (1, 'Test User', 'test@a3data.com', '08011111111', '$passHash')");

// Fund User #1 Wallet with 5,000 NGN
$conn->query("INSERT INTO wallets (user_id, balance) VALUES (1, 5000.00) ON DUPLICATE KEY UPDATE balance = balance + 5000.00");

echo "<h3>Test user #1 created and wallet funded with ₦5,000!</h3>";
?>
