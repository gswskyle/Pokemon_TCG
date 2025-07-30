<?php
require_once "db.php";

$method = $_SERVER['REQUEST_METHOD'];

if ($method === "POST") {
    if (!isset($_SESSION['user_id'])) { 
        echo json_encode(["success" => false]); 
        exit; 
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $review_id = intval($data['review_id']);
    $content = trim($data['content']);
    $token = $data['token'] ?? '';

    if (!isset($_SESSION['token']) || $token !== $_SESSION['token']) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
        exit;
    }

    if ($content === "") {
        echo json_encode(["success" => false]); 
        exit;
    }

    $stmt = $mysqli->prepare("INSERT INTO comments (review_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $review_id, $_SESSION['user_id'], $content);
    echo json_encode(["success" => $stmt->execute()]);
    $stmt->close();
}

if ($method === "PUT") {
    if (!isset($_SESSION['user_id'])) {  
        echo json_encode(["success" => false]); 
        exit; 
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $comment_id = intval($data['comment_id']);
    $content = trim($data['content']);
    $token = $data['token'] ?? '';

    if (!isset($_SESSION['token']) || $token !== $_SESSION['token']) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
        exit;
    }

    if ($content === "") {
        echo json_encode(["success" => false]); 
        exit;
    }

    $stmt = $mysqli->prepare("UPDATE comments SET content=? WHERE id=? AND user_id=?");
    $stmt->bind_param("sii", $content, $comment_id, $_SESSION['user_id']);
    echo json_encode(["success" => $stmt->execute()]);
    $stmt->close();
}

if ($method === "DELETE") {
    parse_str(file_get_contents("php://input"), $data);
    $comment_id = intval($data['id']);
    $token = $data['token'] ?? '';

    if (!isset($_SESSION['token']) || $token !== $_SESSION['token']) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
        exit;
    }

    if ($_SESSION['role'] === "manager") {
        $stmt = $mysqli->prepare("DELETE FROM comments WHERE id=?");
        $stmt->bind_param("i", $comment_id);
    } else {
        $stmt = $mysqli->prepare("DELETE FROM comments WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $comment_id, $_SESSION['user_id']);
    }

    echo json_encode(["success" => $stmt->execute()]);
    $stmt->close();
}
?>
