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
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");
require __DIR__ . '/../../middlewares/JWT.php';
require __DIR__ . '/../../config/Database.php';
$data = json_decode(file_get_contents("php://input"), true);
$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$role = $data['role'] ?? 'customer';
if (!$name || !$email || !$password) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
try {
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $jwtInstance = new JWT();
        $payload = [
            "email" => $email,
            "role" => $role,
        ];
        $token = $jwtInstance->createJWT($payload);
        echo json_encode([
            "status" => "success",
            "message" => "User registered successfully",
            "token" => $token,
            "user" => [
                "id" => $user_id,
                "name" => $name,
                "email" => $email,
                "role" => $role
            ]
        ]);
    } else {
        if ($stmt->errno === 1062) {
            echo json_encode(["status" => "error", "message" => "Email already exists. Try logging in."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Registration failed."]);
        }
    }
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
$conn->close();
?>
