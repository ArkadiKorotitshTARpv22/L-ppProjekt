<?php
session_start();
require 'db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST["id"]);
    $name = $_POST["name"] ?? '';
    $description = $_POST["description"] ?? '';
    $template_id = intval($_POST["template_id"] ?? 1);
    $user_id = $_SESSION["user_id"];

    if ($id > 0 && !empty($name)) {
        $stmt = $yhendus->prepare("UPDATE characters SET name = ?, description = ?, template_id = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssiii", $name, $description, $template_id, $id, $user_id);
        $stmt->execute();

        if (!empty($_FILES["media_file"]["name"])) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir);

            $old_media = $yhendus->prepare("SELECT file_path FROM media WHERE character_id = ?");
            $old_media->bind_param("i", $id);
            $old_media->execute();
            $res = $old_media->get_result();
            while ($r = $res->fetch_assoc()) {
                if (file_exists($r["file_path"])) unlink($r["file_path"]);
            }
            $yhendus->query("DELETE FROM media WHERE character_id = $id");

            $filename = time() . "_" . basename($_FILES["media_file"]["name"]);
            $target_file = $target_dir . $filename;
            move_uploaded_file($_FILES["media_file"]["tmp_name"], $target_file);
            $filetype = mime_content_type($target_file);

            $insert = $yhendus->prepare("INSERT INTO media (character_id, file_path, file_type) VALUES (?, ?, ?)");
            $insert->bind_param("iss", $id, $target_file, $filetype);
            $insert->execute();
        }

        if (!empty($_POST["tags"])) {
            $tag_names = array_map('trim', explode(",", $_POST["tags"]));
            $yhendus->query("DELETE FROM character_tags WHERE character_id = $id");

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
                $stmt->bind_param("ii", $id, $tag_id);
                $stmt->execute();
            }
        }

        header("Location: dashboard.php");
        exit();
    } else {
        echo "Vigased andmed.";
    }
} else {
    echo "Vale päring.";
}
?>
