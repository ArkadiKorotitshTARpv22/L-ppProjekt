<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Not logged in");
}

function json_validate($string) {
    json_decode($string);
    return (json_last_error() === JSON_ERROR_NONE);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $blocks_data = $_POST['blocks_data'] ?? '';
    $tag_ids = $_POST['tags'] ?? [];

    if (empty($blocks_data) || !json_validate($blocks_data)) {
        echo "<script>alert('Blokkide andmed puuduvad või on vigased!'); history.back();</script>";
        exit();
    }

    $blocks_array = json_decode($blocks_data, true);
    if (!is_array($blocks_array) || count($blocks_array) < 1) {
        echo "<script>alert('Peab olema vähemalt 1 plokk!'); history.back();</script>";
        exit();
    }
    if (count($blocks_array) > 15) {
        echo "<script>alert('Lubatud maksimum on 15 plokki!'); history.back();</script>";
        exit();
    }

    $main_image_path = null;
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === 0) {
        $targetDir = 'uploads/';
        if (!file_exists($targetDir)) mkdir($targetDir);
        $filename = time() . '_' . basename($_FILES['main_image']['name']);
        $targetFile = $targetDir . $filename;
        move_uploaded_file($_FILES['main_image']['tmp_name'], $targetFile);
        $main_image_path = 'uploads/' . $filename;
    }

    // Handle block file/image uploads
    $block_index = 0;
    foreach ($blocks_array as &$block) {
        foreach ($block['slots'] as $i => &$slot) {
            $type = $slot['type'];
            $fieldName = 'slot_' . $block_index . '_' . $i;
            if (($type === 'image' || $type === 'file') && isset($_FILES[$fieldName])) {
                $upload = $_FILES[$fieldName];
                if ($upload['error'] === 0) {
                    $targetDir = 'uploads/';
                    if (!file_exists($targetDir)) mkdir($targetDir);
                    $filename = time() . '_' . basename($upload['name']);
                    $targetFile = $targetDir . $filename;
                    move_uploaded_file($upload['tmp_name'], $targetFile);
                    $slot['content'] = 'uploads/' . $filename;
                }
            }
        }
        $block_index++;
    }
    unset($slot);
    unset($block);

    $blocks_data = json_encode($blocks_array);

    $stmt = $yhendus->prepare("INSERT INTO characters (name, description, main_image, character_blocks, user_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $name, $description, $main_image_path, $blocks_data, $user_id);
    $stmt->execute();
    $char_id = $stmt->insert_id;

    $tag_stmt = $yhendus->prepare("INSERT INTO character_tags (character_id, tag_id) VALUES (?, ?)");
    foreach ($tag_ids as $tag_id) {
        $tag_stmt->bind_param("ii", $char_id, $tag_id);
        $tag_stmt->execute();
    }

    header("Location: dashboard.php");
    exit();
}
?>
