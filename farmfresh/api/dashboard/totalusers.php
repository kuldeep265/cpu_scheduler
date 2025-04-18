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
$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? '';
isAdmin($token);
require_once(__DIR__ . '/../../config/Database.php');
$total = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$customers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'")->fetch_assoc()['total'];
$farmers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'farmer'")->fetch_assoc()['total'];
echo json_encode([
    "status" => "success",
    "total_users" => $total,
    "customers" => $customers,
    "farmers" => $farmers
]);
$conn->close();
?>
