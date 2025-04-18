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
$search = $data['search'] ?? '';
$min_price = $data['min_price'] ?? 0;
$max_price = $data['max_price'] ?? 100000;
$farmer_id = $data['farmer_id'] ?? null;
$query = "
    SELECT 
        p.id,
        p.name,
        p.description,
        p.price,
        p.quantity,
        p.image_url,
        u.name AS farmer_name
    FROM products p
    JOIN users u ON p.farmer_id = u.id
    WHERE 
        (p.name LIKE ? OR p.description LIKE ?)
        AND p.price BETWEEN ? AND ?
";
$params = ["%$search%", "%$search%", $min_price, $max_price];
$types = "ssdd";
if ($farmer_id) {
    $query .= " AND p.farmer_id = ?";
    $params[] = $farmer_id;
    $types .= "i";
}
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
echo json_encode([
    "status" => "success",
    "data" => $products
]);
$stmt->close();
$conn->close();
?>
