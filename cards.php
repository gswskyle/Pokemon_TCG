<?php
require_once "db.php";

$method = $_SERVER['REQUEST_METHOD'];

if ($method === "GET") {
    if (isset($_GET['id'])) {
        $card_id = intval($_GET['id']);

        $stmt = $mysqli->prepare("
            SELECT c.*, CAST(IFNULL(AVG(r.rating), 0) AS DECIMAL(3,1)) AS avg_rating
            FROM cards c
            LEFT JOIN ratings r ON c.id = r.card_id
            WHERE c.id=?
            GROUP BY c.id");
        $stmt->bind_param("i", $card_id);
        $stmt->execute();
        $cardResult = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cardResult) {
            echo json_encode(["error" => "Card not found"]);
            exit;
        }

        $reviewsStmt = $mysqli->prepare("
            SELECT reviews.*, users.username
            FROM reviews
            JOIN users ON reviews.user_id = users.id
            WHERE card_id=? ORDER BY created_at DESC");
        $reviewsStmt->bind_param("i", $card_id);
        $reviewsStmt->execute();
        $reviewsResult = $reviewsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $reviewsStmt->close();

        foreach ($reviewsResult as &$review) {
            $commentsStmt = $mysqli->prepare("
                SELECT comments.*, users.username
                FROM comments
                JOIN users ON comments.user_id = users.id
                WHERE review_id=? ORDER BY created_at ASC");
            $commentsStmt->bind_param("i", $review['id']);
            $commentsStmt->execute();
            $review['comments'] = $commentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $commentsStmt->close();
        }

        $userRating = 0;
        $inWishlist = false;
        if (isset($_SESSION['user_id'])) {
            $ratingStmt = $mysqli->prepare("SELECT rating FROM ratings WHERE card_id=? AND user_id=?");
            $ratingStmt->bind_param("ii", $card_id, $_SESSION['user_id']);
            $ratingStmt->execute();
            $ratingStmt->bind_result($userRatingVal);
            if ($ratingStmt->fetch()) $userRating = $userRatingVal;
            $ratingStmt->close();

            $wishlistStmt = $mysqli->prepare("SELECT 1 FROM wishlist WHERE card_id=? AND user_id=?");
            $wishlistStmt->bind_param("ii", $card_id, $_SESSION['user_id']);
            $wishlistStmt->execute();
            if ($wishlistStmt->get_result()->fetch_assoc()) $inWishlist = true;
            $wishlistStmt->close();
        }

        echo json_encode([
            "card" => $cardResult,
            "reviews" => $reviewsResult,
            "userRating" => $userRating,
            "inWishlist" => $inWishlist
        ]);
        exit;
    }

    $sort = $_GET['sort'] ?? "release_desc";
    $search = "%" . ($_GET['search'] ?? "") . "%";

    $orderBy = "set_releaseDate DESC";
    if ($sort === "release_asc") $orderBy = "set_releaseDate ASC";
    if ($sort === "rating_desc") $orderBy = "avg_rating DESC";
    if ($sort === "rating_asc") $orderBy = "avg_rating ASC";

    $query = "
      SELECT c.*, CAST(IFNULL(AVG(r.rating), 0) AS DECIMAL(3,1)) AS avg_rating
      FROM cards c
      LEFT JOIN ratings r ON c.id = r.card_id
      WHERE c.name LIKE ?
      GROUP BY c.id
      ORDER BY $orderBy";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
}

if ($method === "POST") { 
    if ($_SESSION['role'] !== "manager") {
        http_response_code(403);
        exit;
    }
    $data = json_decode(file_get_contents("php://input"), true);
    $api_id = $data['api_id'];
    $token = $data['token'] ?? '';

    if (!isset($_SESSION['token']) || $token !== $_SESSION['token']) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
        exit;
    }

    $json = file_get_contents("https://api.pokemontcg.io/v2/cards/$api_id");
    $cardData = json_decode($json, true)["data"];

    $stmt = $mysqli->prepare(
        "INSERT INTO cards (api_id, name, set_name, set_releaseDate, artist, rarity, image_url, tcgplayer_prices_market)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "ssssssss",
        $api_id,
        $cardData['name'],
        $cardData['set']['name'],
        $cardData['set']['releaseDate'],
        $cardData['artist'],
        $cardData['rarity'],
        $cardData['images']['large'],
        $cardData['tcgplayer']['prices']['market']['market']
    );
    echo json_encode(["success" => $stmt->execute()]);
    $stmt->close();
}

if ($method === "DELETE") { 
    if ($_SESSION['role'] !== "manager") {
        http_response_code(403);
        exit;
    }
    parse_str(file_get_contents("php://input"), $data);
    $card_id = $data['id'];
    $token = $data['token'] ?? '';

    if (!isset($_SESSION['token']) || $token !== $_SESSION['token']) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
        exit;
    }

    $stmt = $mysqli->prepare("DELETE FROM cards WHERE id=?");
    $stmt->bind_param("i", $card_id);
    echo json_encode(["success" => $stmt->execute()]);
    $stmt->close();
}
?>
