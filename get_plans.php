<?php
header('Content-Type: application/json');

$network = $_GET['network'] ?? 'mtn-data'; // Default to mtn-data

$apiKey = getenv('VTPASS_API_KEY');
$secretKey = getenv('VTPASS_SECRET_KEY');

$ch = curl_init("https://sandbox.vtpass.com/api/service-variations?serviceID=" . urlencode($network));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "api-key: $apiKey",
    "secret-key: $secretKey"
]);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
