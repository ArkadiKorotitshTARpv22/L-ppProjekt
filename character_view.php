<?php
require 'db.php';

$char_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $yhendus->prepare("SELECT * FROM characters WHERE id = ?");
$stmt->bind_param("i", $char_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Tegelast ei leitud.";
    exit();
}

$row = $result->fetch_assoc();
$name = htmlspecialchars($row['name']);
$description = nl2br(htmlspecialchars($row['description']));
$image = $row['main_image'];
$blocks = json_decode($row['character_blocks'], true);
?>
<!DOCTYPE html>
<html lang="et">
<head>
  <meta charset="UTF-8">
  <title><?php echo $name; ?> | Tegelane</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">
  <div class="max-w-5xl mx-auto">
    <h1 class="text-4xl font-bold mb-4"><?php echo $name; ?></h1>
    <?php if ($image): ?>
      <img src="<?php echo $image; ?>" alt="Pilt" class="w-full h-64 object-cover rounded mb-4">
    <?php endif; ?>
    <p class="text-gray-700 text-lg mb-6"><?php echo $description; ?></p>

    <?php foreach ($blocks as $block): ?>
      <?php
        $layout = $block['layout'];
        $slots = $block['slots'];
        $cols = match($layout) {
          'grid_2x2' => 'grid-cols-2 md:grid-cols-2',
          'grid_2x1' => 'grid-cols-2',
          'left2_right1' => 'grid-cols-3',
          'full' => 'grid-cols-1',
          'columns_1_2' => 'grid-cols-2',
          'columns_3' => 'grid-cols-3',
          default => 'grid-cols-1',
        };
      ?>
      <div class="grid <?php echo $cols; ?> gap-4 mb-6">
        <?php foreach ($slots as $slot): ?>
          <div class="p-4 bg-white rounded shadow text-sm">
            <?php if ($slot['type'] === 'text'): ?>
              <div class="prose max-w-full"><?php echo $slot['content']; ?></div>
            <?php elseif ($slot['type'] === 'image' && !empty($slot['content'])): ?>
              <img src="<?php echo htmlspecialchars($slot['content']); ?>" class="rounded w-full object-cover">
            <?php elseif ($slot['type'] === 'file' && !empty($slot['content'])): ?>
              <a href="<?php echo htmlspecialchars($slot['content']); ?>" download class="text-blue-600 underline">Lae fail alla</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
