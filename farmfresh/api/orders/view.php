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
    echo json_encode(["status" => "error", "message" => "Invalid token"]);
    exit;
}
$role = $decoded['role'];
$email = $decoded['email'];
$stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$userRes = $stmt->get_result();
$user = $userRes->fetch_assoc();
$user_id = $user['id'];
$user_name = $user['name'];
$stmt->close();
if ($role === 'customer') {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
} elseif ($role === 'farmer') {
    $stmt = $conn->prepare("
        SELECT o.* FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON p.id = oi.product_id
        WHERE o.id = ? AND p.farmer_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
} else {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}
$stmt->execute();
$orderRes = $stmt->get_result();
if ($orderRes->num_rows === 0) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "You are not authorized to view this order"]);
    exit;
}
$order = $orderRes->fetch_assoc();
$stmt->close();
$stmt = $conn->prepare("
    SELECT oi.product_id, p.name, oi.quantity, oi.price
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$itemsRes = $stmt->get_result();
$items = [];
while ($item = $itemsRes->fetch_assoc()) {
    $items[] = $item;
}
$stmt->close();
$stmt = $conn->prepare("SELECT delivery_date, delivery_time_slot FROM delivery_schedule WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$deliveryRes = $stmt->get_result();
$delivery = $deliveryRes->fetch_assoc();
$stmt->close();
echo json_encode([
    "status" => "success",
    "order" => [
        "id" => $order['id'],
        "status" => $order['status'],
        "total_price" => $order['total_price'],
        "created_at" => $order['created_at'],
        "customer" => ($role === 'customer') ? null : $user_name,
        "items" => $items,
        "delivery" => $delivery ?? null
    ]
]);
$conn->close();
?>
