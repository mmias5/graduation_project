<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_auth.php';

$ROOT = realpath(__DIR__ . '/..');
$UPLOADS = $ROOT . '/uploads';

$folders = [
  $UPLOADS,
  $UPLOADS . '/sponsors',
  $UPLOADS . '/clubs',
  $UPLOADS . '/profiles',
  $UPLOADS . '/events',
  $UPLOADS . '/requests',
];

function ensureDir(string $dir): bool {
  if (is_dir($dir)) return true;
  return @mkdir($dir, 0775, true);
}

// 1) Create folders
foreach ($folders as $dir) {
  if (!ensureDir($dir)) {
    die("❌ Failed to create folder: " . htmlspecialchars($dir));
  }
}

// 2) Security
$htaccess = $UPLOADS . '/.htaccess';
if (!file_exists($htaccess)) {
  @file_put_contents($htaccess, "php_flag engine off\nOptions -Indexes\n");
}

// Helper: Make SVG placeholder (no GD needed)
function makeSvg(string $label): string {
  $label = strtoupper(substr(preg_replace('/\s+/', ' ', trim($label)), 0, 22));
  $bg1 = sprintf("#%02x%02x%02x", rand(30,120), rand(40,140), rand(90,200));
  $bg2 = sprintf("#%02x%02x%02x", rand(10,90), rand(20,110), rand(60,170));
  return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$bg1}"/>
      <stop offset="1" stop-color="{$bg2}"/>
    </linearGradient>
  </defs>
  <rect width="800" height="500" rx="26" fill="url(#g)"/>
  <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
        font-family="Raleway, Arial, sans-serif" font-size="48" font-weight="800"
        fill="#ffffff">{$label}</text>
</svg>
SVG;
}

// 3) Collect image paths from DB (same columns as your DB)
$paths = [];

function addPaths(mysqli $conn, string $sql, string $col, array &$paths) {
  $res = $conn->query($sql);
  if (!$res) return;
  while ($row = $res->fetch_assoc()) {
    $p = trim((string)($row[$col] ?? ''));
    if ($p !== '' && str_starts_with($p, 'uploads/')) {
      $paths[$p] = true;
    }
  }
  $res->free();
}

addPaths($conn, "SELECT DISTINCT logo FROM sponsor WHERE logo IS NOT NULL AND logo <> ''", "logo", $paths);
addPaths($conn, "SELECT DISTINCT logo FROM club WHERE logo IS NOT NULL AND logo <> ''", "logo", $paths);
addPaths($conn, "SELECT DISTINCT profile_photo FROM student WHERE profile_photo IS NOT NULL AND profile_photo <> ''", "profile_photo", $paths);
addPaths($conn, "SELECT DISTINCT banner_image FROM event WHERE banner_image IS NOT NULL AND banner_image <> ''", "banner_image", $paths);

$created = 0; $skipped = 0; $failed = 0;
$failSamples = [];

foreach (array_keys($paths) as $relPath) {

  // Safety: avoid traversal
  $relPath = ltrim($relPath, '/');
  if (!str_starts_with($relPath, 'uploads/')) continue;

  $absPath = $ROOT . '/' . $relPath;

  // Ensure dir exists
  $dir = dirname($absPath);
  if (!is_dir($dir)) {
    if (!@mkdir($dir, 0775, true)) {
      $failed++;
      if (count($failSamples) < 10) $failSamples[] = [$relPath, "mkdir failed for dir: $dir"];
      continue;
    }
  }

  if (file_exists($absPath)) { $skipped++; continue; }

  // Check writable
  if (!is_writable($dir)) {
    $failed++;
    if (count($failSamples) < 10) $failSamples[] = [$relPath, "dir not writable: $dir"];
    continue;
  }

  $label = basename(dirname($relPath)) . " " . pathinfo($relPath, PATHINFO_FILENAME);

  // If extension is not svg, we'll still write SVG content but keep file extension as-is.
  // Browsers will still display if served with correct content-type; but simplest: if ext not image, create .svg beside it.
  $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
  $writePath = $absPath;

  // If it's jpg/png/webp, create same-name .svg next to it and also create an empty file at original path (optional).
  if (in_array($ext, ['jpg','jpeg','png','webp'], true)) {
    $writePath = preg_replace('/\.[a-zA-Z0-9]+$/', '.svg', $absPath);
  } elseif ($ext === '') {
    $writePath = $absPath . '.svg';
  } elseif ($ext !== 'svg') {
    $writePath = preg_replace('/\.[a-zA-Z0-9]+$/', '.svg', $absPath);
  }

  $svg = makeSvg($label);
  $ok = @file_put_contents($writePath, $svg);

  if ($ok !== false) {
    $created++;
  } else {
    $failed++;
    $err = error_get_last();
    $msg = $err['message'] ?? 'file_put_contents failed';
    if (count($failSamples) < 10) $failSamples[] = [$relPath, $msg];
  }
}

echo "<h2>✅ Uploads setup done (DEBUG)</h2>";
echo "<p><b>Root:</b> " . htmlspecialchars($ROOT) . "</p>";
echo "<p><b>Total paths found:</b> " . count($paths) . "</p>";
echo "<ul>";
echo "<li><b>Files created:</b> {$created}</li>";
echo "<li><b>Files already existed:</b> {$skipped}</li>";
echo "<li><b>Failed:</b> {$failed}</li>";
echo "</ul>";

if (!empty($failSamples)) {
  echo "<h3>First failure samples</h3><ol>";
  foreach ($failSamples as $f) {
    echo "<li><code>" . htmlspecialchars($f[0]) . "</code> — " . htmlspecialchars($f[1]) . "</li>";
  }
  echo "</ol>";
}

echo "<p>After this, we will adjust display to use: <code>/uploads/...</code></p>";
