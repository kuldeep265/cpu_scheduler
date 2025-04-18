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

isAdmin($token); // Middleware will handle validation and exit if unauthorized

$query = "
    SELECT 
        o.id AS order_id,
        o.total_price,
        o.status,
        o.created_at,
        o.address,
        o.mobile,
        u.name AS customer_name,
        u.email AS customer_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
";


$result = $conn->query($query);

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = [
        "order_id" => $row['order_id'],
        "total_price" => $row['total_price'],
        "status" => $row['status'],
        "created_at" => $row['created_at'],
        "customer_name" => $row['customer_name'],
        "customer_email" => $row['customer_email'],
        "address" => $row['address'],
        "mobile" => $row['mobile']
    ];    
}

echo json_encode([
    "status" => "success",
    "orders" => $orders
]);

$conn->close();
?>
