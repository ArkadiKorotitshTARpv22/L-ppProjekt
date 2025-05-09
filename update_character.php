<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Not logged in");
}

function json_validate($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $blocks_data = $_POST['blocks_data'] ?? '';

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

    $stmt = $yhendus->prepare("SELECT main_image FROM characters WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        die("Tegelast ei leitud või puudub ligipääs.");
    }

    $existing = $res->fetch_assoc();
    $main_image_path = $existing['main_image'];

    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === 0) {
        $targetDir = 'uploads/';
        if (!file_exists($targetDir)) mkdir($targetDir);
        $filename = time() . '_' . basename($_FILES['main_image']['name']);
        $targetFile = $targetDir . $filename;
        move_uploaded_file($_FILES['main_image']['tmp_name'], $targetFile);
        $main_image_path = $targetFile;
    }

    $update = $yhendus->prepare("UPDATE characters SET name = ?, description = ?, main_image = ?, character_blocks = ? WHERE id = ? AND user_id = ?");
    $update->bind_param("ssssii", $name, $description, $main_image_path, $blocks_data, $id, $user_id);
    $update->execute();

    header("Location: dashboard.php");
    exit();
}
?>
