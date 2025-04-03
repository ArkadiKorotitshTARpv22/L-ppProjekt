<?php
session_start();
require 'db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit();
}

$id = intval($_GET["id"]);
$user_id = $_SESSION["user_id"];

// delete media
$media = $yhendus->prepare("SELECT file_path FROM media WHERE character_id = ?");
$media->bind_param("i", $id);
$media->execute();
$media_result = $media->get_result();
while ($row = $media_result->fetch_assoc()) {
    if (file_exists($row['file_path'])) {
        unlink($row['file_path']);
    }
}
$yhendus->query("DELETE FROM media WHERE character_id = $id");

// delete character
$stmt = $yhendus->prepare("DELETE FROM characters WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

header("Location: dashboard.php");
exit();
?>
