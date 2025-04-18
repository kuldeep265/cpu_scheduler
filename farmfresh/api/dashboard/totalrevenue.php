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
require_once(__DIR__ . '/../../middlewares/CheckAdmin.php');
require_once(__DIR__ . '/../../config/Database.php');
$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? '';
isAdmin($token);
$total = $conn->query("SELECT ROUND(SUM(total_price), 2) AS total FROM orders")->fetch_assoc()['total'] ?? 0;
$delivered = $conn->query("SELECT ROUND(SUM(total_price), 2) AS total FROM orders WHERE status = 'delivered'")->fetch_assoc()['total'] ?? 0;
echo json_encode([
    "status" => "success",
    "total_revenue" => (float)$total,
    "delivered_revenue" => (float)$delivered
]);
$conn->close();
?>
