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
$token   = $data['token'] ?? '';
$items   = $data['items'] ?? [];
$address = $data['address'] ?? '';
$mobile  = $data['mobile'] ?? '';
if (!$token || empty($items) || !$address || !$mobile) {
    echo json_encode(["status" => "error", "message" => "Token, address, mobile, and items are required"]);
    exit;
}
$jwt = new JWT();
$decoded = $jwt->decodeJWT($token);
if (isset($decoded['error']) || $decoded['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid or unauthorized token"]);
    exit;
}
$email = $decoded['email'];
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];
$stmt->close();
$total_price = 0;
foreach ($items as $item) {
    $product_id = $item['product_id'];
    $quantity = $item['quantity'];
    $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Product ID $product_id not found"]);
        exit;
    }
    $product = $res->fetch_assoc();
    $total_price += $product['price'] * $quantity;
    $stmt->close();
}
$stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, address, mobile) VALUES (?, ?, ?, ?)");
$stmt->bind_param("idss", $user_id, $total_price, $address, $mobile);
$stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();
foreach ($items as $item) {
    $product_id = $item['product_id'];
    $quantity   = $item['quantity'];
    $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
    $price = $product['price'];
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
    $stmt->execute();
    $stmt->close();
}
$stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();
echo json_encode([
    "status" => "success",
    "message" => "Order placed successfully with address and cart cleared",
    "order_id" => $order_id,
    "total" => $total_price
]);
$conn->close();
?>
