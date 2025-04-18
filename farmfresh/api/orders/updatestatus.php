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
require_once(__DIR__ . '/../../middlewares/JWT.php');
require_once(__DIR__ . '/../../config/Database.php');
$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? '';
$order_id = $data['order_id'] ?? '';
$status = $data['status'] ?? '';
if (!$token || !$order_id || !$status) {
    echo json_encode(["status" => "error", "message" => "Token, Order ID, and status are required"]);
    exit;
}
if (!in_array($status, ['confirmed', 'delivered'])) {
    echo json_encode(["status" => "error", "message" => "Invalid status. Use 'confirmed' or 'delivered'"]);
    exit;
}
$jwt = new JWT();
$decoded = $jwt->decodeJWT($token);
if (isset($decoded['error']) || $decoded['role'] !== 'farmer') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}
$email = $decoded['email'];
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$farmer_id = $user['id'];
$stmt->close();
$stmt = $conn->prepare("
    SELECT o.id FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN products p ON p.id = oi.product_id
    WHERE o.id = ? AND p.farmer_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $order_id, $farmer_id);
$stmt->execute();
$accessResult = $stmt->get_result();
$stmt->close();
if ($accessResult->num_rows === 0) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "You are not authorized to update this order"]);
    exit;
}
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $order_id);
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Order status updated to '$status'"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update order"]);
}
$stmt->close();
$conn->close();
?>
