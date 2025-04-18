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
require_once(__DIR__ . '/../../config/Database.php');
$data = json_decode(file_get_contents("php://input"), true);
$product_id = $data['product_id'] ?? null;
if (!$product_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Product ID is required"]);
    exit;
}
$stmt = $conn->prepare("
    SELECT 
        products.id,
        products.name,
        products.description,
        products.price,
        products.quantity,
        products.image_url,
        products.created_at,
        users.name AS farmer_name,
        users.email AS farmer_email
    FROM products
    INNER JOIN users ON products.farmer_id = users.id
    WHERE products.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $product = $result->fetch_assoc();
    echo json_encode(["status" => "success", "data" => $product]);
} else {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Product not found"]);
}
$stmt->close();
$conn->close();
?>
