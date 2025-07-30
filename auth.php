<?php
require_once "db.php";

$action = $_GET['action'] ?? '';

if ($action === "login") {
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['username'];
    $password = $data['password'];

    $stmt = $mysqli->prepare("SELECT id, password_hash, role FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hash, $role);
    if ($stmt->fetch() && password_verify($password, $hash)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['role'] = $role;
        $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
        echo json_encode(["success" => true, "role" => $role, "token" => $_SESSION['token']]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid credentials"]);
    }
}

if ($action === "register") {
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['username'];
    $password = password_hash($data['password'], PASSWORD_BCRYPT);
    $stmt = $mysqli->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'user')");
    $stmt->bind_param("ss", $username, $password);
    echo json_encode(["success" => $stmt->execute()]);
}

if ($action === "logout") {
    session_destroy();
    echo json_encode(["success" => true]);
}

if ($action === "session") {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            "loggedIn" => true,
            "user_id" => $_SESSION['user_id'],
            "role" => $_SESSION['role'],
            "token" => $_SESSION['token'] ?? ""
        ]);
    } else {
        echo json_encode(["loggedIn" => false]);
    }
}

?>
