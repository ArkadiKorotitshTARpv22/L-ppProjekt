<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $yhendus->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $hash);

    if ($stmt->execute()) {
        header("Location: index.html");
    } else {
        echo "Viga: " . $stmt->error;
    }
}
?>
