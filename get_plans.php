<?php
header('Content-Type: application/json');

$network = $_GET['network'] ?? 'mtn-data';
$apiKey = getenv('VTPASS_API_KEY');
$secretKey = getenv('VTPASS_SECRET_KEY');

// Fetch live variation codes from VTpass
$ch = curl_init("https://sandbox.vtpass.com/api/service-variations?serviceID=" . urlencode($network));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "api-key: $apiKey",
    "secret-key: $secretKey"
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// If fetching data plans, inject your N50 profit markup
if (isset($data['content']['varations']) && strpos($network, 'data') !== false) {
    foreach ($data['content']['varations'] as &$plan) {
        $costPrice = (float)$plan['variation_amount'];
        $sellingPrice = $costPrice + 50.00; // Adding N50 profit margin
        $plan['variation_amount'] = (string)$sellingPrice;
    }
}

echo json_encode($data);
?>
