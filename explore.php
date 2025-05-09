<?php
require 'db.php';

$search = $_GET["q"] ?? '';
$tag_filter = $_GET["tag"] ?? '';

$query = "SELECT DISTINCT c.id, c.name, u.email, m.file_path
          FROM characters c
          JOIN users u ON c.user_id = u.id
          LEFT JOIN media m ON c.id = m.character_id
          LEFT JOIN character_tags ct ON c.id = ct.character_id
          LEFT JOIN tags t ON ct.tag_id = t.id
          WHERE c.is_public = 1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR u.email LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if (!empty($tag_filter)) {
    $query .= " AND t.name = ?";
    $params[] = $tag_filter;
    $types .= "s";
}

$query .= " ORDER BY c.created_at DESC";
$stmt = $yhendus->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="et">
<head>
  <meta charset="UTF-8">
  <title>Avasta Tegelased</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<nav class="bg-white shadow mb-6 p-4">
  <div class="max-w-6xl mx-auto flex justify-between items-center">
    <a href="explore.php" class="text-blue-600 font-bold text-lg">Explore</a>
    <a href="dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Minu Tegelased</a>
  </div>
</nav>

<body class="bg-gray-100 p-6 min-h-screen">
  <div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold mb-6 text-center">Avalikud Tegelased</h1>

    <form method="GET" class="mb-6 max-w-lg mx-auto flex gap-2">
      <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Otsi nime või autori järgi..."
             class="w-full border border-gray-300 p-2 rounded">
      <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Otsi</button>
    </form>

    <?php
    $taglist = $yhendus->query("SELECT name FROM tags ORDER BY name ASC");
    ?>
    <div class="text-center mb-6 space-x-2">
      <span class="text-sm text-gray-600">Filtreeri märksõna järgi:</span>
      <?php while ($tag = $taglist->fetch_assoc()): ?>
        <a href="?tag=<?php echo urlencode($tag['name']); ?>" class="inline-block text-sm px-2 py-1 bg-gray-200 rounded hover:bg-gray-300 <?php if ($tag_filter == $tag['name']) echo 'font-bold'; ?>">
          <?php echo htmlspecialchars($tag['name']); ?>
        </a>
      <?php endwhile; ?>
    </div>

    <?php if ($result->num_rows === 0): ?>
      <p class="text-center text-gray-500 mt-12 text-lg">Tulemusi ei leitud.</p>
    <?php endif; ?>

    <div class="grid md:grid-cols-3 gap-6">
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="bg-white rounded shadow p-4 hover:shadow-lg transition">
          <?php if (!empty($row["file_path"])): ?>
            <img src="<?php echo $row["file_path"]; ?>" class="w-full h-48 object-cover rounded mb-3">
          <?php else: ?>
            <div class="w-full h-48 bg-gray-200 flex items-center justify-center rounded mb-3 text-gray-500 text-xl">📄</div>
          <?php endif; ?>

          <h2 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($row["name"]); ?></h2>
          <p class="text-gray-600 text-sm mb-1">Autor: <?php echo htmlspecialchars($row["email"]); ?></p>

          <?php
          $tag_stmt = $yhendus->prepare("SELECT t.name FROM tags t 
                                         JOIN character_tags ct ON t.id = ct.tag_id 
                                         WHERE ct.character_id = ?");
          $tag_stmt->bind_param("i", $row["id"]);
          $tag_stmt->execute();
          $tag_result = $tag_stmt->get_result();
          ?>
          <div class="flex flex-wrap gap-2 mb-2">
            <?php while ($t = $tag_result->fetch_assoc()): ?>
              <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full"><?php echo htmlspecialchars($t["name"]); ?></span>
            <?php endwhile; ?>
          </div>

          <a href="character_view.php?id=<?php echo $row["id"]; ?>" class="text-blue-600 underline">Vaata lähemalt</a>
        </div>
      <?php endwhile; ?>
    </div>

    <div class="text-center mt-12">
      <a href="index.html" class="text-sm text-blue-500 underline">Tagasi esilehele</a>
    </div>
  </div>
</body>
</html>
