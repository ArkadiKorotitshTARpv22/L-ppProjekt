<?php
require 'db.php';

$q = $_GET['q'] ?? '';
$like = '%' . $q . '%';

$stmt = $yhendus->prepare("SELECT name FROM tags WHERE name LIKE ? ORDER BY name ASC LIMIT 10");
$stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();

$tags = [];
while ($row = $result->fetch_assoc()) {
    $tags[] = $row['name'];
}

header('Content-Type: application/json');
echo json_encode($tags);
?>
