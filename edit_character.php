<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Not logged in");
}

$char_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $yhendus->prepare("SELECT * FROM characters WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $char_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Tegelast ei leitud või puudub ligipääs.";
    exit();
}

$row = $result->fetch_assoc();
$name = htmlspecialchars($row['name']);
$description = htmlspecialchars($row['description']);
$main_image = $row['main_image'];
$blocks = json_decode($row['character_blocks'], true);
?>
<!DOCTYPE html>
<html lang="et">
<head>
  <meta charset="UTF-8">
  <title>Muuda tegelast - <?php echo $name; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-4xl mx-auto">
  <h1 class="text-2xl font-bold mb-4">Muuda tegelast: <?php echo $name; ?></h1>
  <form id="editForm" action="update_character.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo $char_id; ?>">
    <input type="text" name="name" value="<?php echo $name; ?>" class="w-full p-2 border mb-2">
    <textarea name="description" class="w-full p-2 border mb-2"><?php echo $description; ?></textarea>
    <label class="block mb-1 font-semibold">Praegune peapilt:</label>
    <?php if ($main_image): ?><img src="<?php echo $main_image; ?>" class="w-64 mb-2"><?php endif; ?>
    <input type="file" name="main_image" class="mb-4">

    <input type="hidden" name="blocks_data" id="blocksData">
    <div id="blocksContainer" class="space-y-4 mb-4"></div>
    <button type="button" onclick="addBlock()" class="bg-blue-600 text-white px-3 py-1 rounded">+ Lisa plokk</button>
    <button type="submit" class="ml-2 bg-green-600 text-white px-4 py-2 rounded">Salvesta muudatused</button>
  </form>
</div>

<template id="blockTemplate">
  <div class="p-4 border rounded bg-white">
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
const existingBlocks = <?php echo json_encode($blocks); ?>;

function addBlock(preData = null) {
  const container = document.getElementById('blocksContainer');
  const template = document.getElementById('blockTemplate').content.cloneNode(true);
  const block = template.querySelector('.p-4');
  const layoutSelect = block.querySelector('.layout');

  container.appendChild(block);

  if (preData) layoutSelect.value = preData.layout;
  renderBlockLayout(layoutSelect, preData?.slots || []);
}

function renderBlockLayout(select, slotData = []) {
  const block = select.closest('.p-4');
  const container = block.querySelector('.block-content');
  container.innerHTML = '';
  const layoutMap = {
    grid_2x2: 4, grid_2x1: 2, left2_right1: 3,
    full: 1, columns_1_2: 2, columns_3: 3
  };
  const slotCount = layoutMap[select.value];
  container.className = 'block-content grid gap-4 grid-cols-' + slotCount;

  for (let i = 0; i < slotCount; i++) {
    const slot = document.getElementById('slotTemplate').content.cloneNode(true);
    container.appendChild(slot);
    const selectEl = slot.querySelector('select');
    const slotType = slotData[i]?.type || 'text';
    selectEl.value = slotType;
    configureSlot(selectEl, slotData[i]?.content || '');
  }
}

function configureSlot(select, content = '') {
  const body = select.parentElement.querySelector('.slot-body');
  body.innerHTML = '';
  const type = select.value;

  if (type === 'text') {
    const div = document.createElement('div');
    div.className = 'editor';
    body.appendChild(div);
    const quill = new Quill(div, { theme: 'snow' });
    quill.root.innerHTML = content || '';
    select._quill = quill;
  } else {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = type === 'image' ? 'image/*' : '*';
    input.dataset.saved = content;
    body.appendChild(input);

    if (content && type === 'image') {
      const img = document.createElement('img');
      img.src = content;
      img.className = 'w-32 mt-2 rounded';
      body.appendChild(img);
    }

    if (content && type === 'file') {
      const list = document.createElement('div');
      list.innerHTML = content.split(',').map(f => `<a href="${f}" class="block text-blue-600 underline mt-1" target="_blank">${f}</a>`).join('');
      body.appendChild(list);
    }
  }
}

document.getElementById('editForm').addEventListener('submit', function (e) {
  const blocks = [];
  document.querySelectorAll('#blocksContainer > .p-4').forEach(block => {
    const layout = block.querySelector('.layout').value;
    const slots = [];
    block.querySelectorAll('.space').forEach(slot => {
      const type = slot.querySelector('.type').value;
      let content = '';
      if (type === 'text') {
        content = slot.querySelector('.ql-editor')?.innerHTML || '';
      } else if (slot.querySelector('input')?.dataset.saved) {
        content = slot.querySelector('input').dataset.saved;
      }
      slots.push({ type, content });
    });
    blocks.push({ layout, slots });
  });
  document.getElementById('blocksData').value = JSON.stringify(blocks);
});

// Trigger initial loading
existingBlocks.forEach(block => addBlock(block));
</script>
</body>
</html>
