<?php
require 'db.php';

$char_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $yhendus->prepare("SELECT c.name, c.description, c.template_id, m.file_path 
                           FROM characters c 
                           LEFT JOIN media m ON c.id = m.character_id 
                           WHERE c.id = ?");
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
$template_id = intval($row['template_id']);
$img = $row['file_path'] ?? null;
?>
<!DOCTYPE html>
<html lang="et">
<head>
  <meta charset="UTF-8">
  <title><?php echo $name; ?> | Tegelane</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen p-6">

<?php if ($template_id === 1): ?>
  <!-- Template 1: Minimal -->
  <div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-3xl font-bold mb-4"><?php echo $name; ?></h1>
    <?php if ($img): ?>
      <img src="<?php echo $img; ?>" class="w-full rounded mb-4">
    <?php endif; ?>
    <p class="text-gray-700"><?php echo $description; ?></p>
  </div>

<?php elseif ($template_id === 2): ?>
  <!-- Template 2: Card View -->
  <div class="flex flex-col items-center bg-white max-w-md mx-auto p-6 rounded-xl shadow">
    <?php if ($img): ?>
      <img src="<?php echo $img; ?>" class="rounded-full w-48 h-48 object-cover mb-4 border-4 border-blue-300">
    <?php endif; ?>
    <h1 class="text-2xl font-bold mb-2"><?php echo $name; ?></h1>
    <div class="text-gray-600 text-center"><?php echo $description; ?></div>
  </div>

<?php elseif ($template_id === 3): ?>
  <!-- Template 3: Gallery -->
  <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-4xl font-bold text-center mb-6"><?php echo $name; ?></h1>
    <div class="grid md:grid-cols-2 gap-6">
      <?php if ($img): ?>
        <img src="<?php echo $img; ?>" class="rounded w-full object-cover h-64">
      <?php endif; ?>
      <div class="text-lg text-gray-800"><?php echo $description; ?></div>
    </div>
  </div>

<?php else: ?>
  <p>Mall puudub või pole saadaval.</p>
<?php endif; ?>

</body>
</html>
