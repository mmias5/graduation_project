<?php
// seed_rewards_media.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
if (!isset($conn) || !($conn instanceof mysqli)) die("DB connection not found.");

$dirFs  = __DIR__ . '/uploads/rewards';
$dirWeb = 'uploads/rewards';
if (!is_dir($dirFs)) mkdir($dirFs, 0775, true);

/**
 * Simple "product-like" SVG banners without any text.
 */
function rewardSvg(string $type, int $seed): string {
  $h1 = ($seed * 37) % 360;
  $h2 = ($h1 + 35) % 360;

  // background gradient + soft shapes
  $bg = <<<BG
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="hsl($h1,70%,55%)"/>
      <stop offset="100%" stop-color="hsl($h2,70%,55%)"/>
    </linearGradient>
    <filter id="shadow" x="-30%" y="-30%" width="160%" height="160%">
      <feDropShadow dx="0" dy="12" stdDeviation="14" flood-color="#000" flood-opacity="0.22"/>
    </filter>
    <filter id="soft" x="-20%" y="-20%" width="140%" height="140%">
      <feGaussianBlur stdDeviation="10"/>
    </filter>
  </defs>
  <rect width="900" height="420" fill="url(#bg)"/>
  <circle cx="760" cy="110" r="120" fill="rgba(255,255,255,0.18)"/>
  <circle cx="640" cy="250" r="80" fill="rgba(255,255,255,0.14)"/>
BG;

  // object drawings (no text)
  $obj = '';

  if ($type === 'cinema') {
    // ticket
    $obj = <<<OBJ
    <g transform="translate(140,90)" filter="url(#shadow)">
      <path d="M60,40 h420 a24,24 0 0 1 24,24 v56
               a22,22 0 0 0 0,44 v56 a24,24 0 0 1 -24,24 h-420
               a24,24 0 0 1 -24,-24 v-56
               a22,22 0 0 0 0,-44 v-56 a24,24 0 0 1 24,-24 z"
            fill="rgba(255,255,255,0.92)"/>
      <rect x="110" y="75" width="300" height="22" rx="10" fill="rgba(36,39,81,0.22)"/>
      <rect x="110" y="115" width="260" height="18" rx="9" fill="rgba(36,39,81,0.18)"/>
      <circle cx="92" cy="140" r="14" fill="rgba(255,92,92,0.85)"/>
      <circle cx="118" cy="140" r="14" fill="rgba(229,183,88,0.85)"/>
      <circle cx="144" cy="140" r="14" fill="rgba(72,113,219,0.85)"/>
    </g>
OBJ;
  } elseif ($type === 'coffee') {
    // cup
    $obj = <<<OBJ
    <g transform="translate(280,85)" filter="url(#shadow)">
      <path d="M90,80 h260 v170 a36,36 0 0 1 -36,36 h-188 a36,36 0 0 1 -36,-36 z"
            fill="rgba(255,255,255,0.92)"/>
      <path d="M122,70 h196 a18,18 0 0 1 18,18 v10 H104 v-10 a18,18 0 0 1 18,-18 z"
            fill="rgba(255,255,255,0.86)"/>
      <path d="M350,125 h35 a46,46 0 0 1 0,92 h-35"
            fill="none" stroke="rgba(255,255,255,0.88)" stroke-width="18" stroke-linecap="round"/>
      <path d="M140,120 c30,-40 30,-60 0,-95" fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="10" stroke-linecap="round"/>
      <path d="M205,120 c30,-40 30,-60 0,-95" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="10" stroke-linecap="round"/>
      <path d="M270,120 c30,-40 30,-60 0,-95" fill="none" stroke="rgba(255,255,255,0.40)" stroke-width="10" stroke-linecap="round"/>
      <rect x="120" y="215" width="200" height="20" rx="10" fill="rgba(36,39,81,0.18)"/>
    </g>
OBJ;
  } elseif ($type === 'hoodie') {
    // hoodie silhouette
    $obj = <<<OBJ
    <g transform="translate(270,60)" filter="url(#shadow)">
      <path d="M175,45
               c-38,0 -70,26 -84,62
               l-48,44 c-10,10 -10,26 0,36
               l28,28 v150 c0,18 14,32 32,32 h244 c18,0 32,-14 32,-32 v-150
               l28,-28 c10,-10 10,-26 0,-36 l-48,-44
               c-14,-36 -46,-62 -84,-62
               z"
            fill="rgba(255,255,255,0.92)"/>
      <path d="M155,55 c0,55 40,92 95,92 c55,0 95,-37 95,-92"
            fill="none" stroke="rgba(36,39,81,0.18)" stroke-width="14" stroke-linecap="round"/>
      <rect x="150" y="250" width="190" height="22" rx="11" fill="rgba(36,39,81,0.18)"/>
    </g>
OBJ;
  } else {
    // generic gift box
    $obj = <<<OBJ
    <g transform="translate(310,95)" filter="url(#shadow)">
      <rect x="40" y="110" width="280" height="200" rx="28" fill="rgba(255,255,255,0.92)"/>
      <rect x="40" y="90" width="280" height="60" rx="22" fill="rgba(255,255,255,0.86)"/>
      <rect x="170" y="90" width="26" height="220" rx="13" fill="rgba(255,92,92,0.80)"/>
      <rect x="40" y="170" width="280" height="26" rx="13" fill="rgba(255,92,92,0.80)"/>
      <path d="M184,86 c-18,-18 -50,-16 -64,6 c-12,20 8,46 64,46"
            fill="none" stroke="rgba(229,183,88,0.90)" stroke-width="16" stroke-linecap="round"/>
      <path d="M184,86 c18,-18 50,-16 64,6 c12,20 -8,46 -64,46"
            fill="none" stroke="rgba(229,183,88,0.90)" stroke-width="16" stroke-linecap="round"/>
    </g>
OBJ;
  }

  return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="420" viewBox="0 0 900 420">
  $bg
  $obj
</svg>
SVG;
}

// Map reward names -> image type
function pickType(string $name): string {
  $n = strtolower($name);
  if (str_contains($n, 'cinema') || str_contains($n, 'ticket')) return 'cinema';
  if (str_contains($n, 'coffee') || str_contains($n, 'cafe')) return 'coffee';
  if (str_contains($n, 'hoodie') || str_contains($n, 'shirt') || str_contains($n, 'bag')) return 'hoodie';
  return 'gift';
}

$res = $conn->query("SELECT item_id, item_name FROM items_to_redeem WHERE is_active=1 ORDER BY item_id");
if (!$res) die($conn->error);

while ($row = $res->fetch_assoc()) {
  $id = (int)$row['item_id'];
  $name = (string)$row['item_name'];
  $type = pickType($name);

  $svg = rewardSvg($type, $id);
  $file = "reward_$id.svg";
  file_put_contents("$dirFs/$file", $svg);

  $path = "$dirWeb/$file";
  $stmt = $conn->prepare("UPDATE items_to_redeem SET picture=? WHERE item_id=?");
  $stmt->bind_param("si", $path, $id);
  $stmt->execute();
  $stmt->close();
}

echo "<h2>✅ Realistic rewards images generated (no text)</h2>";
echo "<p>Check: <a target='_blank' href='./uploads/rewards/reward_1.svg'>reward_1.svg</a></p>";
