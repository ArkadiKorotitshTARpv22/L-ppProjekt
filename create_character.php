<?php
session_start();
require 'db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION["user_id"];
$name = $_POST["name"];
$description = $_POST["description"];
$template_id = $_POST["template_id"];

$stmt = $yhendus->prepare("INSERT INTO characters (user_id, name, description, template_id) VALUES (?, ?, ?, ?)");
$stmt->bind_param("issi", $user_id, $name, $description, $template_id);
$stmt->execute();
$character_id = $stmt->insert_id;

if (!empty($_FILES["media_file"]["name"])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir);

    $filename = time() . "_" . basename($_FILES["media_file"]["name"]);
    $target_file = $target_dir . $filename;
    move_uploaded_file($_FILES["media_file"]["tmp_name"], $target_file);
    $filetype = mime_content_type($target_file);

    $stmt2 = $yhendus->prepare("INSERT INTO media (character_id, file_path, file_type) VALUES (?, ?, ?)");
    $stmt2->bind_param("iss", $character_id, $target_file, $filetype);
    $stmt2->execute();
}

if (!empty($_POST["tags"])) {
    $tag_names = array_map('trim', explode(",", $_POST["tags"]));
    foreach ($tag_names as $tag_name) {
        if ($tag_name === '') continue;

        $stmt = $yhendus->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
        $stmt->bind_param("s", $tag_name);
        $stmt->execute();

        $stmt = $yhendus->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->bind_param("s", $tag_name);
        $stmt->execute();
        $tag_id = $stmt->get_result()->fetch_assoc()["id"];

        $stmt = $yhendus->prepare("INSERT IGNORE INTO character_tags (character_id, tag_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $character_id, $tag_id);
        $stmt->execute();
    }
}

header("Location: dashboard.php");
exit();
?>
