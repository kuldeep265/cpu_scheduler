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
$delivery_date = $data['delivery_date'] ?? '';
$delivery_time_slot = $data['delivery_time_slot'] ?? '';
if (!$token || !$order_id || !$delivery_date || !$delivery_time_slot) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}
$jwt = new JWT();
$decoded = $jwt->decodeJWT($token);
if (isset($decoded['error']) || $decoded['role'] !== 'customer') {
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
$user_id = $user['id'];
$stmt->close();
$stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "You can only schedule your own orders"]);
    exit;
}
$stmt->close();
$stmt = $conn->prepare("SELECT id FROM delivery_schedule WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();
if ($res->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE delivery_schedule SET delivery_date = ?, delivery_time_slot = ? WHERE order_id = ?");
    $stmt->bind_param("ssi", $delivery_date, $delivery_time_slot, $order_id);
} else {
    $stmt = $conn->prepare("INSERT INTO delivery_schedule (order_id, delivery_date, delivery_time_slot) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $order_id, $delivery_date, $delivery_time_slot);
}
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Delivery scheduled successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to schedule delivery"]);
}
$stmt->close();
$conn->close();
?>
