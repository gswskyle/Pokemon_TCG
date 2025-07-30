<?php
require_once "db.php";
$method = $_SERVER['REQUEST_METHOD'];

if ($method === "POST") {
    if (!isset($_SESSION['user_id'])) { http_response_code(401); exit; }
    $data = json_decode(file_get_contents("php://input"), true);
    $token = $data['token'] ?? '';

    if (!isset($_SESSION['token']) || $token !== $_SESSION['token']) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
        exit;
    }

    $stmt = $mysqli->prepare("REPLACE INTO ratings (card_id, user_id, rating) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $data['card_id'], $_SESSION['user_id'], $data['rating']);
    echo json_encode(["success" => $stmt->execute()]);
    $stmt->close();
}

if ($method === "DELETE") { 
    parse_str(file_get_contents("php://input"), $data);
    $card_id = $data['card_id'];
    $token = $data['token'] ?? '';

    if (!isset($_SESSION['token']) || $token !== $_SESSION['token']) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
        exit;
    }
    $stmt = $mysqli->prepare("DELETE FROM ratings WHERE card_id=? AND user_id=?");
    $stmt->bind_param("ii", $card_id, $_SESSION['user_id']);
    echo json_encode(["success" => $stmt->execute()]);
    $stmt->close();
}
?>
