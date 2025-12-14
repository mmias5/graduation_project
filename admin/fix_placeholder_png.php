<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_auth.php';

$ROOT = realpath(__DIR__ . '/..');

function makePng(string $absPath, string $label): bool {
  if (!function_exists('imagecreatetruecolor')) return false;

  $w = 700; $h = 420;
  $im = imagecreatetruecolor($w, $h);

  $bg = imagecolorallocate($im, rand(30,120), rand(40,140), rand(90,200));
  imagefilledrectangle($im, 0, 0, $w, $h, $bg);

  $white = imagecolorallocate($im, 255, 255, 255);
  $label = strtoupper(substr(preg_replace('/\s+/', ' ', trim($label)), 0, 22));
  imagestring($im, 5, 24, 24, $label, $white);

  // Save PNG
  $ok = imagepng($im, $absPath);
  imagedestroy($im);
  return (bool)$ok;
}

function collectPaths(mysqli $conn, string $sql, string $col): array {
  $out = [];
  $res = $conn->query($sql);
  if (!$res) return $out;
  while ($row = $res->fetch_assoc()) {
    $p = trim((string)($row[$col] ?? ''));
    if ($p !== '' && str_starts_with($p, 'uploads/')) $out[$p] = true;
  }
  $res->free();
  return array_keys($out);
}

// get all image paths from DB
$paths = [];
$paths = array_merge($paths, collectPaths($conn, "SELECT DISTINCT logo FROM sponsor WHERE logo<>''", "logo"));
$paths = array_merge($paths, collectPaths($conn, "SELECT DISTINCT logo FROM club WHERE logo<>''", "logo"));
$paths = array_merge($paths, collectPaths($conn, "SELECT DISTINCT profile_photo FROM student WHERE profile_photo<>''", "profile_photo"));
$paths = array_merge($paths, collectPaths($conn, "SELECT DISTINCT banner_image FROM event WHERE banner_image<>''", "banner_image"));

$paths = array_values(array_unique($paths));

$created = 0; $skipped = 0; $failed = 0;
$sample = [];

foreach ($paths as $rel) {
  $abs = $ROOT . '/' . ltrim($rel, '/');
  $dir = dirname($abs);

  if (!is_dir($dir)) @mkdir($dir, 0775, true);

  // only create if file missing
  if (file_exists($abs)) { $skipped++; continue; }

  // create png/jpg/webp placeholders ONLY for those extensions
  $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
  if (!in_array($ext, ['png','jpg','jpeg','webp'], true)) { $skipped++; continue; }

  $label = basename(dirname($rel)) . " " . pathinfo($rel, PATHINFO_FILENAME);

  // For jpg/webp entries, still create a PNG with same name (simple + works)
  // If you insist on real JPG/WEBP we can do that later.
  $ok = makePng($abs, $label);

  if ($ok) $created++;
  else {
    $failed++;
    if (count($sample) < 10) $sample[] = $rel;
  }
}

echo "<h2>✅ PNG placeholders fixed</h2>";
echo "<ul>";
echo "<li><b>Created:</b> {$created}</li>";
echo "<li><b>Skipped (already existed or not image ext):</b> {$skipped}</li>";
echo "<li><b>Failed:</b> {$failed}</li>";
echo "</ul>";

if ($failed > 0) {
  echo "<h3>Sample failed paths</h3><pre>" . htmlspecialchars(implode("\n", $sample)) . "</pre>";
}
