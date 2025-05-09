<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Not logged in");
}

$tags = $yhendus->query("SELECT * FROM tags")->fetch_all(MYSQLI_ASSOC);

$characters = [];
$stmt = $yhendus->prepare("SELECT * FROM characters WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($c = $result->fetch_assoc()) {
  $tag_stmt = $yhendus->prepare("SELECT t.name FROM tags t JOIN character_tags ct ON ct.tag_id = t.id WHERE ct.character_id = ?");
  $tag_stmt->bind_param("i", $c['id']);
  $tag_stmt->execute();
  $tags_result = $tag_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $c['tags'] = array_column($tags_result, 'name');
  $characters[] = $c;
}

if (isset($_GET['delete'])) {
  $stmt = $yhendus->prepare("DELETE FROM characters WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $_GET['delete'], $user_id);
  $stmt->execute();
  header("Location: dashboard.php");
  exit();
}

if (isset($_GET['toggle'])) {
  $id = intval($_GET['toggle']);
  $stmt = $yhendus->prepare("UPDATE characters SET is_public = NOT is_public WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $id, $user_id);
  $stmt->execute();
  header("Location: dashboard.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="et">
<head>
  <meta charset="UTF-8">
  <title>Minu tegelased</title>
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-5xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Minu tegelased</h1>

    <div class="mb-6 p-4 bg-white rounded shadow">
      <h2 class="text-xl font-semibold mb-2">Loo uus tegelane</h2>
      
      <form id="charForm" method="post" enctype="multipart/form-data" action="save_character.php">
        <input type="text" name="name" placeholder="Tegelase nimi" class="w-full p-2 border mb-2">
        <textarea name="description" placeholder="Lühikirjeldus" class="w-full border p-2 mb-2"></textarea>
        <input type="file" name="main_image" class="mb-2">

        <label class="block font-semibold mb-1">Märksõnad</label>
        <div class="flex flex-wrap gap-2 mb-4">
          <?php foreach ($tags as $tag): ?>
            <label class="flex items-center space-x-1 text-sm bg-gray-100 px-2 py-1 rounded">
              <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>">
              <span><?= htmlspecialchars($tag['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <input type="hidden" name="blocks_data" id="blocksData">
        <h3 class="font-bold mb-2">Plokid</h3>
        <div id="blocksContainer" class="space-y-4 mb-2"></div>
        <button type="button" onclick="addBlock()" class="bg-blue-600 text-white px-3 py-1 rounded">+ Lisa plokk</button>

        <pre id="blockPreview" class="hidden whitespace-pre-wrap text-xs bg-gray-100 p-2 mt-4 border border-gray-300 rounded"></pre>

        <button type="submit" class="mt-4 bg-green-600 text-white px-6 py-2 rounded">Salvesta tegelane</button>
      </form>
    </div>

    <h2 class="text-xl font-bold mb-4">Eksisteerivad tegelased</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach ($characters as $c): ?>
        <div class="bg-white p-4 rounded shadow">
          <?php if ($c['main_image']): ?>
            <img src="<?= $c['main_image'] ?>" class="mb-2 rounded w-full h-48 object-cover">
          <?php endif; ?>
          <h3 class="font-semibold text-lg"><?= htmlspecialchars($c['name']) ?></h3>
          <p class="text-gray-600 text-sm mb-2"><?= htmlspecialchars($c['description']) ?></p>
          <?php if (!empty($c['tags'])): ?>
            <div class="text-xs text-gray-500 italic mb-2">
              <?php foreach ($c['tags'] as $tag): ?>
                <span class="inline-block bg-gray-200 text-gray-700 px-2 py-0.5 rounded mr-1"><?= htmlspecialchars($tag) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <a href="character_view.php?id=<?= $c['id'] ?>" class="text-blue-600 hover:underline mr-2">Vaata lehte</a>
          <a href="edit_character.php?id=<?= $c['id'] ?>" class="text-yellow-600 hover:underline mr-2">Muuda</a>
          <a href="dashboard.php?delete=<?= $c['id'] ?>" onclick="return confirm('Kustuta tegelane?')" class="text-red-600 hover:underline mr-2">Kustuta</a>
          <a href="dashboard.php?toggle=<?= $c['id'] ?>" class="text-gray-600 hover:underline">
            <?= $c['is_public'] ? '🔓 Avalik' : '🔒 Privaatne' ?>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

<template id="blockTemplate">
  <div class="p-4 border rounded bg-gray-50">
    <label class="block font-semibold mb-1">Ploki paigutus</label>
    <select class="layout w-full border p-2 mb-4" onchange="renderBlockLayout(this)">
      <option value="grid_2x2">2x2 Grid</option>
      <option value="grid_2x1">2x1</option>
      <option value="left2_right1">2 vasak, 1 parem</option>
      <option value="full">Täislaius</option>
      <option value="columns_1_2">1 kitsas, 1 lai</option>
      <option value="columns_3">3 veergu</option>
    </select>
    <div class="block-content grid gap-4"></div>
  </div>
</template>

<template id="slotTemplate">
  <div class="space">
    <select class="type w-full border p-2 mb-2" onchange="configureSlot(this)">
      <option value="text">Tekst</option>
      <option value="image">Pilt</option>
      <option value="file">Fail</option>
    </select>
    <div class="slot-body"></div>
  </div>
</template>

<script>
function addBlock() {
  const blocks = document.getElementById('blocksContainer');
  if (blocks.children.length >= 15) return alert('Max 15 plokki!');
  const index = blocks.children.length;
  const tpl = document.getElementById('blockTemplate').content.cloneNode(true);
  const block = tpl.querySelector('.p-4');
  block.dataset.index = index;
  blocks.appendChild(tpl);
}


function renderBlockLayout(select) {
  const block = select.closest('.p-4');
  const container = block.querySelector('.block-content');
  container.innerHTML = '';
  const layoutMap = {
    grid_2x2: 4, grid_2x1: 2, left2_right1: 3,
    full: 1, columns_1_2: 2, columns_3: 3
  };
  container.className = 'block-content grid gap-4';
  container.classList.add('grid-cols-' + layoutMap[select.value]);
  for (let i = 0; i < layoutMap[select.value]; i++) {
    const slot = document.getElementById('slotTemplate').content.cloneNode(true);
    container.appendChild(slot);
  }
}

function configureSlot(select) {
  const body = select.parentElement.querySelector('.slot-body');
  body.innerHTML = '';
  const type = select.value;
  if (type === 'text') {
    const div = document.createElement('div');
    div.className = 'editor';
    body.appendChild(div);
    new Quill(div, { theme: 'snow' });
  } else {
    const input = document.createElement('input');
    input.type = 'file';
    input.name = `slot_${select.closest('.p-4').dataset.index}_${[...select.closest('.block-content').children].indexOf(select.closest('.space'))}`;

    input.multiple = (type === 'file');
    input.accept = (type === 'image' ? 'image/*' : '*');
    body.appendChild(input);
  }
}

document.getElementById('charForm').addEventListener('submit', function (e) {
  const filesToUpload = [];
  document.querySelectorAll('.space').forEach(slot => {
    const type = slot.querySelector('.type')?.value;
    if (type === 'image' || type === 'file') {
      const input = slot.querySelector('input[type=file]');
      if (input?.files.length) {
        const filenames = [];
        for (let i = 0; i < input.files.length; i++) {
          const f = input.files[i];
          filenames.push('uploads/' + Date.now() + '_' + f.name);
          filesToUpload.push(f);
        }
        slot.dataset.content = type === 'image' ? filenames[0] : filenames.join(',');
      }
    }
  });

  const blocks = [];
  document.querySelectorAll('#blocksContainer > .p-4').forEach(block => {
    const layout = block.querySelector('.layout')?.value || 'grid_2x2';
    const slots = [];
    block.querySelectorAll('.space').forEach(slot => {
      const type = slot.querySelector('.type')?.value || 'text';
      const content =
        type === 'text'
          ? slot.querySelector('.ql-editor')?.innerHTML || ''
          : slot.dataset.content || '';
      slots.push({ type, content });
    });
    blocks.push({ layout, slots });
  });

  const json = JSON.stringify(blocks, null, 2);
  document.getElementById('blocksData').value = JSON.stringify(blocks);
  const preview = document.getElementById('blockPreview');
  preview.classList.remove('hidden');
  preview.textContent = json;
});

</script>
</body>
</html>
