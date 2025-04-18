<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Max-Age: 86400");
    http_response_code(204);
    exit;
}
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
require_once(__DIR__ . '/../../middlewares/CheckAdmin.php');
require_once(__DIR__ . '/../../config/Database.php');
$input = json_decode(file_get_contents("php://input"), true);
$amount = $input['amount'] ?? 0;
if ($amount <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid amount"]);
    exit;
}
$key_id = "rzp_test_4lF84FAK3EkxMx";
$key_secret = "Cj7bpsQoqqfz1wjmCQTufB9L";
$orderData = [
    'amount' => $amount * 100,
    'currency' => 'INR',
    'receipt' => 'rcpt_' . rand(1000, 9999),
    'payment_capture' => 1
];
$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$key_id:$key_secret");
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
$response = curl_exec($ch);
curl_close($ch);
echo $response;
