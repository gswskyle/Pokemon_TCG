<?php
require_once "db.php";

$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

if ($method === "GET") {
    $sort = $_GET['sort'] ?? "release_desc";
    $search = "%" . ($_GET['search'] ?? "") . "%";

    $orderBy = "c.set_releaseDate DESC";
    if ($sort === "release_asc") $orderBy = "c.set_releaseDate ASC";
    if ($sort === "rating_desc") $orderBy = "avg_rating DESC";
    if ($sort === "rating_asc") $orderBy = "avg_rating ASC";

    $query = "
        SELECT c.*, IFNULL(AVG(r.rating), 0) AS avg_rating
        FROM wishlist w
        JOIN cards c ON w.card_id = c.id
        LEFT JOIN ratings r ON c.id = r.card_id
        WHERE w.user_id=? AND c.name LIKE ?
        GROUP BY c.id
        ORDER BY $orderBy";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("is", $_SESSION['user_id'], $search);
    $stmt->execute();

    $result = $stmt->get_result();
    $cards = [];
    while ($row = $result->fetch_assoc()) {
        $row['avg_rating'] = (float) $row['avg_rating'];
        $cards[] = $row;
    }

    echo json_encode($cards);
    $stmt->close();
}

if ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $token = $data['token'] ?? '';

    // if (!isset($_SESSION['token']) || $token !== $_SESSION['token']) {
    //     http_response_code(403);
    //     echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
    //     exit;
    // }
    $stmt = $mysqli->prepare("INSERT IGNORE INTO wishlist (user_id, card_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $_SESSION['user_id'], $data['card_id']);
    echo json_encode(["success" => $stmt->execute()]);
    $stmt->close();
}

if ($method === "DELETE") {
    parse_str(file_get_contents("php://input"), $data);
    // $token = $data['token'] ?? '';

    // if (!isset($_SESSION['token']) || $token !== $_SESSION['token']) {
    //     http_response_code(403);
    //     echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
    //     exit;
    // }
    $stmt = $mysqli->prepare("DELETE FROM wishlist WHERE user_id=? AND card_id=?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $data['card_id']);
    echo json_encode(["success" => $stmt->execute()]);
    $stmt->close();
}
?>
