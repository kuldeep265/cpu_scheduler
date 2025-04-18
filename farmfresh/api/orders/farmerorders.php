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
$farmer = $result->fetch_assoc();
$farmer_id = $farmer['id'];
$stmt->close();
$query = "
    SELECT 
        orders.id AS order_id,
        orders.status,
        orders.created_at,
        orders.address,
        orders.mobile,
        users.name AS customer_name,
        products.name AS product_name,
        order_items.product_id,
        order_items.quantity,
        order_items.price
    FROM order_items
    INNER JOIN orders ON order_items.order_id = orders.id
    INNER JOIN users ON orders.user_id = users.id
    INNER JOIN products ON order_items.product_id = products.id
    WHERE products.farmer_id = ?
    ORDER BY orders.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $order_id = $row['order_id'];
    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            "order_id" => $order_id,
            "status" => $row['status'],
            "created_at" => $row['created_at'],
            "customer_name" => $row['customer_name'],
            "address" => $row['address'],
            "mobile" => $row['mobile'],
            "items" => []
        ];
    }
    $orders[$order_id]["items"][] = [
        "product_id" => $row['product_id'],
        "product_name" => $row['product_name'],
        "quantity" => $row['quantity'],
        "price" => $row['price']
    ];
}
echo json_encode([
    "status" => "success",
    "orders" => array_values($orders)
]);
$stmt->close();
$conn->close();
?>
