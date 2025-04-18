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
$product_id = $data['product_id'] ?? '';
if (!$product_id) {
    echo json_encode(["status" => "error", "message" => "Product ID is required"]);
    exit;
}
$stmt = $conn->prepare("
    SELECT r.rating, r.review, r.created_at, u.name AS reviewer_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();
$reviews = [];
while ($row = $res->fetch_assoc()) {
    $reviews[] = $row;
}
echo json_encode(["status" => "success", "reviews" => $reviews]);
$stmt->close();
$conn->close();
?>
