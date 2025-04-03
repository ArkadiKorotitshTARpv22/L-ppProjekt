<?php
session_start();
require 'db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION["user_id"];
$stmt = $yhendus->prepare("SELECT id, name FROM characters WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="et">
<head>
  <meta charset="UTF-8">
  <title>Minu Tegelased</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-6">

<nav class="bg-white shadow mb-6 p-4">
  <div class="max-w-6xl mx-auto flex justify-between items-center">
    <a href="explore.php" class="text-blue-600 font-bold text-lg">🌐 Explore</a>
    <a href="dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Minu Tegelased</a>
  </div>
</nav>

<div class="max-w-4xl mx-auto">
  <h1 class="text-2xl font-bold mb-6">Tere tulemast!</h1>
  <a href="logout.php" class="text-red-500 mb-4 inline-block">Logi välja</a>

  <h2 class="text-xl font-semibold mb-2">Sinu tegelased</h2>
  <ul class="mb-6 space-y-2">
    <?php while ($row = $result->fetch_assoc()): ?>
      <li class="bg-white shadow p-3 rounded">
        <a class="text-blue-600 underline font-semibold" href="character_view.php?id=<?php echo $row['id']; ?>">
          <?php echo htmlspecialchars($row['name']); ?>
        </a>
        <span class="ml-4">
          <a class="text-yellow-500 hover:underline" href="edit_character.php?id=<?php echo $row['id']; ?>">Muuda</a> |
          <a class="text-red-600 hover:underline" href="delete_character.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Kustutada tegelane?');">Kustuta</a>
        </span>
      </li>
    <?php endwhile; ?>
  </ul>

  <hr class="my-6">

  <h2 class="text-xl font-semibold mb-2">Loo uus tegelane</h2>
  <form action="create_character.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow space-y-4">
    <div>
      <label class="block mb-1 font-medium">Nimi</label>
      <input type="text" name="name" class="w-full border border-gray-300 p-2 rounded" required>
    </div>

    <div>
      <label class="block mb-1 font-medium">Kirjeldus</label>
      <textarea name="description" class="w-full border border-gray-300 p-2 rounded" rows="4"></textarea>
    </div>

    <div>
      <label class="block mb-1 font-medium">Kujundusmall</label>
      <select name="template_id" class="w-full border border-gray-300 p-2 rounded">
        <option value="1">Minimal</option>
        <option value="2">Card View</option>
        <option value="3">Gallery</option>
      </select>
    </div>

    <div>
      <label class="block mb-1 font-medium">Pilt</label>
      <input type="file" name="media_file" accept="image/*" class="border border-gray-300 p-2 rounded w-full">
    </div>

    <div>
      <label class="block mb-1 font-medium">Märksõnad</label>
      <div id="tagList" class="flex flex-wrap mb-2"></div>
      <input type="text" id="tagInput" placeholder="Lisa märksõna ja vajuta Enter või ,"
             class="w-full border border-gray-300 p-2 rounded">
      <input type="hidden" name="tags" id="tagHidden">
    </div>

    <div>
      <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">Loo Tegelane</button>
    </div>
  </form>
</div>

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
  });
</script>
</body>
</html>
