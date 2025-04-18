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
if (!$token || !$order_id) {
    echo json_encode(["status" => "error", "message" => "Token and Order ID are required"]);
    exit;
}
$jwt = new JWT();
$decoded = $jwt->decodeJWT($token);
if (isset($decoded['error'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized token"]);
    exit;
}
$email = $decoded['email'];
$role = $decoded['role'];
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$user_id = $user['id'];
$stmt->close();
if ($role === 'customer') {
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
} else if ($role === 'farmer') {
    $stmt = $conn->prepare("
        SELECT o.id FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON p.id = oi.product_id
        WHERE o.id = ? AND p.farmer_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
} else {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access denied"]);
    exit;
}
$stmt->execute();
$accessRes = $stmt->get_result();
$stmt->close();
if ($accessRes->num_rows === 0) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "You do not have access to this order"]);
    exit;
}
$stmt = $conn->prepare("SELECT delivery_date, delivery_time_slot FROM delivery_schedule WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 1) {
    $delivery = $res->fetch_assoc();
    echo json_encode([
        "status" => "success",
        "delivery" => $delivery
    ]);
} else {
    echo json_encode(["status" => "success", "message" => "No delivery schedule found"]);
}
$stmt->close();
$conn->close();
?>
