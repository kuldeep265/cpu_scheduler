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
require_once(__DIR__ . '/../../config/Database.php');
$query = "SELECT 
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
          ORDER BY products.created_at DESC";
$result = $conn->query($query);
$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    echo json_encode([
        "status" => "success",
        "data" => $products
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "data" => [],
        "message" => "No products found"
    ]);
}
$conn->close();
?>
