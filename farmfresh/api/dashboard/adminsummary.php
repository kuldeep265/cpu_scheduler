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
if (!$token) {
    echo json_encode(["status" => "error", "message" => "Token is required"]);
    exit;
}
$jwt = new JWT();
$decoded = $jwt->decodeJWT($token);
if (isset($decoded['error']) || $decoded['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}
$totalUsersQuery = $conn->query("SELECT COUNT(*) AS count FROM users");
$total_users = $totalUsersQuery->fetch_assoc()['count'] ?? 0;
$totalProductsQuery = $conn->query("SELECT COUNT(*) AS count FROM products");
$total_products = $totalProductsQuery->fetch_assoc()['count'] ?? 0;
$totalOrdersQuery = $conn->query("SELECT COUNT(*) AS count FROM orders");
$total_orders = $totalOrdersQuery->fetch_assoc()['count'] ?? 0;
$totalRevenueQuery = $conn->query("SELECT SUM(total_price) AS revenue FROM orders WHERE status = 'delivered'");
$total_revenue = $totalRevenueQuery->fetch_assoc()['revenue'] ?? 0.00;
$pendingOrdersQuery = $conn->query("SELECT COUNT(*) AS pending FROM orders WHERE status = 'pending'");
$pending_orders = $pendingOrdersQuery->fetch_assoc()['pending'] ?? 0;
$currentMonth = date('Y-m');
$monthlyRevenueQuery = $conn->prepare("SELECT SUM(total_price) AS monthly_revenue FROM orders WHERE status = 'delivered' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
$monthlyRevenueQuery->bind_param("s", $currentMonth);
$monthlyRevenueQuery->execute();
$monthlyRevenueResult = $monthlyRevenueQuery->get_result();
$monthly_revenue = $monthlyRevenueResult->fetch_assoc()['monthly_revenue'] ?? 0.00;
$monthlyRevenueQuery->close();
$topProducts = [];
$topProductsQuery = $conn->query("
    SELECT p.name, SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 5
");
while ($row = $topProductsQuery->fetch_assoc()) {
    $topProducts[] = $row;
}
echo json_encode([
    "status" => "success",
    "data" => [
        "total_users" => (int)$total_users,
        "total_products" => (int)$total_products,
        "total_orders" => (int)$total_orders,
        "total_revenue" => round((float)$total_revenue, 2),
        "pending_orders" => (int)$pending_orders,
        "monthly_revenue" => round((float)$monthly_revenue, 2),
        "top_products" => $topProducts
    ]
]);
$conn->close();
