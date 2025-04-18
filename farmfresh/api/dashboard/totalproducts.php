<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost:1006");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Max-Age: 86400");
    http_response_code(204);
    exit;
}
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
require_once(__DIR__ . '/../../middlewares/CheckAdmin.php');
require_once(__DIR__ . '/../../config/Database.php');
$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? '';
isAdmin($token);
$result = $conn->query("SELECT COUNT(*) AS total FROM products");
$total = $result->fetch_assoc()['total'];
echo json_encode([
    "status" => "success",
    "total_products" => $total
]);
$conn->close();
?>
