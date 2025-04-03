<?php
session_start();
require 'db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit();
}

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$user_id = $_SESSION["user_id"];

$stmt = $yhendus->prepare("SELECT c.name, c.description, c.template_id, m.file_path 
                           FROM characters c 
                           LEFT JOIN media m ON c.id = m.character_id 
                           WHERE c.id = ? AND c.user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$tag_stmt = $yhendus->prepare("SELECT t.name FROM tags t 
                                JOIN character_tags ct ON t.id = ct.tag_id 
                                WHERE ct.character_id = ?");
$tag_stmt->bind_param("i", $id);
$tag_stmt->execute();
$tag_result = $tag_stmt->get_result();

$existing_tags = [];
while ($t = $tag_result->fetch_assoc()) {
    $existing_tags[] = $t["name"];
}
?>

<!DOCTYPE html>
<html lang="et">
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const tagInput = document.getElementById('tagInput');
    const tagList = document.getElementById('tagList');
    const hiddenField = document.getElementById('tagHidden');

    let tags = [];

    tagInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        const tag = tagInput.value.trim();
        if (tag && !tags.includes(tag)) {
          tags.push(tag);
          renderTags();
        }
        tagInput.value = '';
      }
    });

    function renderTags() {
      tagList.innerHTML = '';
      tags.forEach((tag, i) => {
        const el = document.createElement('span');
        el.className = 'bg-blue-100 text-blue-800 text-xs rounded-full px-2 py-1 mr-2 mb-2 inline-block';
        el.innerHTML = `${tag} <button onclick="removeTag(${i})" class="ml-1 text-red-600">×</button>`;
        tagList.appendChild(el);
      });
      hiddenField.value = tags.join(', ');
    }

    window.removeTag = function (i) {
      tags.splice(i, 1);
      renderTags();
    }

    if (hiddenField.value) {
      tags = hiddenField.value.split(',').map(t => t.trim());
      renderTags();
    }
  });
</script>

<head>
  <meta charset="UTF-8">
  <title>Muuda Tegelast</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6 min-h-screen">
  <div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Muuda Tegelast</h1>
    <form action="update_character.php" method="POST" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="id" value="<?php echo $id; ?>">

      <div>
        <label class="block font-semibold mb-1">Nimi</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block font-semibold mb-1">Kirjeldus</label>
        <textarea name="description" rows="4" class="w-full border p-2 rounded"><?php echo htmlspecialchars($row['description']); ?></textarea>
      </div>

      <div>
        <label class="block font-semibold mb-1">Kujundusmall</label>
        <select name="template_id" class="w-full border p-2 rounded">
          <option value="1" <?php if ($row['template_id'] == 1) echo "selected"; ?>>Minimal</option>
          <option value="2" <?php if ($row['template_id'] == 2) echo "selected"; ?>>Card View</option>
          <option value="3" <?php if ($row['template_id'] == 3) echo "selected"; ?>>Gallery</option>
        </select>
      </div>

      <?php if (!empty($row['file_path'])): ?>
        <div>
          <label class="block font-semibold mb-1">Praegune pilt:</label>
          <img src="<?php echo $row['file_path']; ?>" class="w-48 h-auto rounded border mb-2">
        </div>
      <?php endif; ?>

      <div>
        <label class="block font-semibold mb-1">Uus pilt (asendab olemasoleva)</label>
        <input type="file" name="media_file" accept="image/*" class="w-full border p-2 rounded">
      </div>

      <div>
        <label class="block font-semibold mb-1">Märksõnad (komadega)</label>
        <input type="text" name="tags" value="<?php echo htmlspecialchars(implode(", ", $existing_tags)); ?>"
               class="w-full border border-gray-300 p-2 rounded">
      </div>

      <div>
        <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Salvesta muudatused</button>
        <a href="dashboard.php" class="ml-4 text-blue-600 hover:underline">Tagasi</a>
      </div>
    </form>
  </div>
</body>
</html>
