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
$name = $data['name'] ?? '';
$description = $data['description'] ?? '';
$price = $data['price'] ?? '';
$quantity = $data['quantity'] ?? '';
$image_url = $data['image_url'] ?? '';
if (!$token || !$name || !$description || !$price || !$quantity) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}
$jwt = new JWT();
$decoded = $jwt->decodeJWT($token);
if (isset($decoded['error'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid token"]);
    exit;
}
if ($decoded['role'] !== 'farmer') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Only farmers can add products"]);
    exit;
}
$farmer_email = $decoded['email'];
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $farmer_email);
$stmt->execute();
$result = $stmt->get_result();
$farmer = $result->fetch_assoc();
$farmer_id = $farmer['id'];
$stmt = $conn->prepare("INSERT INTO products (farmer_id, name, description, price, quantity, image_url) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issdis", $farmer_id, $name, $description, $price, $quantity, $image_url);
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Product added successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to add product"]);
}
$stmt->close();
$conn->close();
?>
