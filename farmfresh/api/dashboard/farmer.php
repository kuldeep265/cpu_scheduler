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

if (!$token) {
    echo json_encode(["status" => "error", "message" => "Token is required"]);
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
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$farmer_id = $user['id'];
$stmt->close();

// Total Products
$stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM products WHERE farmer_id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$totalProducts = $result->fetch_assoc()['total_products'];
$stmt->close();

// Total Orders and Revenue
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.id) AS total_orders, SUM(oi.price * oi.quantity) AS total_revenue
    FROM orders o
    INNER JOIN order_items oi ON o.id = oi.order_id
    INNER JOIN products p ON oi.product_id = p.id
    WHERE p.farmer_id = ?
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$totalOrders = $row['total_orders'] ?? 0;
$totalRevenue = $row['total_revenue'] ?? 0;

echo json_encode([
    "status" => "success",
    "data" => [
        "total_orders" => (int)$totalOrders,
        "total_products" => (int)$totalProducts,
        "total_revenue" => round((float)$totalRevenue, 2)
    ]
]);

$conn->close();
?>
