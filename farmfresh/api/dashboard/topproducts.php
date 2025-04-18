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
$query = "
    SELECT 
        p.id,
        p.name,
        p.price,
        SUM(oi.quantity) AS total_sold,
        SUM(oi.quantity * oi.price) AS revenue_generated
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
";
$result = $conn->query($query);
$topProducts = [];
while ($row = $result->fetch_assoc()) {
    $topProducts[] = [
        "id" => $row['id'],
        "name" => $row['name'],
        "price" => (float)$row['price'],
        "total_sold" => (int)$row['total_sold'],
        "revenue_generated" => (float)$row['revenue_generated']
    ];
}
echo json_encode([
    "status" => "success",
    "top_products" => $topProducts
]);
$conn->close();
?>
