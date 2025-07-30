<?php
$mysqli = new mysqli("localhost", "phpuser", "phpuser", "pokemon_tcg");
if ($mysqli->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
session_start();
?>
