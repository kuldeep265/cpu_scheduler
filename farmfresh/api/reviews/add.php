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
$product_id = $data['product_id'] ?? '';
$rating = $data['rating'] ?? '';
$review = $data['review'] ?? '';
if (!$token || !$product_id || !$rating || $rating < 1 || $rating > 5) {
    echo json_encode(["status" => "error", "message" => "Token, product ID, and valid rating are required"]);
    exit;
}
$jwt = new JWT();
$decoded = $jwt->decodeJWT($token);
if (isset($decoded['error']) || $decoded['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}
$email = $decoded['email'];
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$user_id = $user['id'];
$stmt->close();
$stmt = $conn->prepare("
    INSERT INTO reviews (product_id, user_id, rating, review) 
    VALUES (?, ?, ?, ?) 
    ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review)
");
$stmt->bind_param("iiis", $product_id, $user_id, $rating, $review);
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Review submitted successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to submit review"]);
}
$stmt->close();
$conn->close();
?>
