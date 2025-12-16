<?php
// seed_media.php
// Generates consistent, meaningful PNG images for clubs/students/events
// and optionally updates DB with image paths.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

if (!extension_loaded('gd')) {
    die("GD extension is not enabled. In XAMPP: open php.ini and enable extension=gd then restart Apache.");
}

function ensure_dir(string $dir): void {
    if (!is_dir($dir)) mkdir($dir, 0775, true);
}

function hex2rgb(string $hex): array {
    $hex = ltrim($hex, '#');
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ];
}

function color($im, string $hex, int $alpha = 0) {
    [$r,$g,$b] = hex2rgb($hex);
    return imagecolorallocatealpha($im, $r, $g, $b, $alpha);
}

function save_png($im, string $path): void {
    imagesavealpha($im, true);
    imagepng($im, $path);
    imagedestroy($im);
}

function draw_center_text($im, string $text, int $y, int $size, string $hex, string $font): void {
    $col = color($im, $hex, 0);
    $bbox = imagettfbbox($size, 0, $font, $text);
    $textW = $bbox[2] - $bbox[0];
    $x = (imagesx($im) - $textW) / 2;
    imagettftext($im, $size, 0, (int)$x, $y, $col, $font, $text);
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $ini = '';
    foreach ($parts as $p) {
        if ($p !== '') $ini .= mb_strtoupper(mb_substr($p, 0, 1));
        if (mb_strlen($ini) >= 2) break;
    }
    return $ini ?: 'UH';
}

// --- Paths ---
$root = __DIR__;
$uploads = $root . '/uploads';
$clubsDir = $uploads . '/clubs';
$studentsDir = $uploads . '/students';
$eventsDir = $uploads . '/events';

ensure_dir($clubsDir);
ensure_dir($studentsDir);
ensure_dir($eventsDir);

// Try to find a font (Windows XAMPP often has none bundled)
// We'll ship with DejaVu if you place it; fallback to system fonts.
$fontCandidates = [
    $root . '/tools/fonts/DejaVuSans-Bold.ttf',
    $root . '/DejaVuSans-Bold.ttf',
    'C:\Windows\Fonts\arialbd.ttf',
    'C:\Windows\Fonts\arial.ttf',
];
$font = null;
foreach ($fontCandidates as $f) {
    if (file_exists($f)) { $font = $f; break; }
}
if (!$font) {
    die("Font not found. Put DejaVuSans-Bold.ttf in your project root OR tools/fonts/ .");
}

// --- Brand palette (from your UniHive style) ---
$NAVY  = '#242751';
$ROYAL = '#4871db';
$CORAL = '#ff5c5c';
$GOLD  = '#e5b758';
$PAPER = '#eef2f7';

// Pick nice color per category
function category_color(string $category): string {
    $c = strtolower(trim($category));
    if (str_contains($c, 'tech') || str_contains($c, 'ai') || str_contains($c, 'robot')) return '#4871db';
    if (str_contains($c, 'sport')) return '#e5b758';
    if (str_contains($c, 'art') || str_contains($c, 'photo') || str_contains($c, 'design')) return '#ff5c5c';
    if (str_contains($c, 'business')) return '#242751';
    return '#4871db';
}

// --- Generate CLUB logo + cover ---
function gen_club_logo(string $clubName, string $category, string $outPath, string $font): void {
    $W=512; $H=512;
    $im = imagecreatetruecolor($W,$H);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $bg = color($im, '#eef2f7', 0);
    imagefilledrectangle($im, 0,0,$W,$H,$bg);

    $accent = category_color($category);
    $ring = color($im, $accent, 0);
    $navy = color($im, '#242751', 0);

    // Circle badge
    imagefilledellipse($im, 256, 230, 340, 340, $ring);
    imagefilledellipse($im, 256, 230, 300, 300, $bg);

    $ini = initials($clubName);
    draw_center_text($im, $ini, 260, 96, '#242751', $font);

    // Club name small
    $name = mb_substr($clubName, 0, 22);
    draw_center_text($im, $name, 440, 28, '#242751', $font);

    // Category pill
    $pillW = 260; $pillH = 44; $pillX = (512-$pillW)/2; $pillY=468;
    $pill = color($im, $accent, 0);
    imagefilledroundedrectangle($im, (int)$pillX, $pillY, (int)($pillX+$pillW), $pillY+$pillH, 22, $pill);
    draw_center_text($im, strtoupper($category ?: 'CLUB'), $pillY+32, 18, '#eef2f7', $font);

    save_png($im, $outPath);
}

function imagefilledroundedrectangle($im, $x1, $y1, $x2, $y2, $radius, $color) {
    // Basic rounded rectangle
    imagefilledrectangle($im, $x1+$radius, $y1, $x2-$radius, $y2, $color);
    imagefilledrectangle($im, $x1, $y1+$radius, $x2, $y2-$radius, $color);

    imagefilledellipse($im, $x1+$radius, $y1+$radius, $radius*2, $radius*2, $color);
    imagefilledellipse($im, $x2-$radius, $y1+$radius, $radius*2, $radius*2, $color);
    imagefilledellipse($im, $x1+$radius, $y2-$radius, $radius*2, $radius*2, $color);
    imagefilledellipse($im, $x2-$radius, $y2-$radius, $radius*2, $radius*2, $color);
}

// Cover banner
function gen_club_cover(string $clubName, string $category, string $outPath, string $font): void {
    $W=1200; $H=400;
    $im = imagecreatetruecolor($W,$H);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $accent = category_color($category);
    $bg = color($im, '#242751', 0); // navy
    imagefilledrectangle($im, 0,0,$W,$H,$bg);

    // Decorative shapes
    $a = color($im, $accent, 60);
    imagefilledellipse($im, 200, 200, 520, 520, $a);
    imagefilledellipse($im, 1080, 120, 420, 420, $a);

    // Title
    $title = $clubName;
    $cat   = strtoupper($category ?: 'CLUB');
    imagettftext($im, 44, 0, 60, 180, color($im,'#eef2f7',0), $font, $title);
    imagettftext($im, 22, 0, 60, 230, color($im,$accent,0), $font, $cat);

    // Small label
    imagettftext($im, 18, 0, 60, 320, color($im,'#eef2f7',20), $font, 'UniHive • Campus Clubs Hub');

    save_png($im, $outPath);
}

// --- Student avatar ---
function gen_student_avatar(string $studentName, string $major, string $outPath, string $font): void {
    $W=512; $H=512;
    $im = imagecreatetruecolor($W,$H);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $paper = color($im, '#eef2f7', 0);
    imagefilledrectangle($im,0,0,$W,$H,$paper);

    $royal = color($im, '#4871db', 0);
    $coral = color($im, '#ff5c5c', 0);
    $navy  = color($im, '#242751', 0);

    // Two-tone diagonal
    imagefilledpolygon($im, [0,0, 512,0, 512,260], 3, $royal);
    imagefilledpolygon($im, [0,0, 0,512, 512,512], 3, $coral);

    // Center badge
    imagefilledellipse($im, 256, 220, 280, 280, $paper);
    $ini = initials($studentName);
    draw_center_text($im, $ini, 245, 96, '#242751', $font);

    // Name + major
    $name = mb_substr($studentName, 0, 22);
    draw_center_text($im, $name, 430, 26, '#242751', $font);
    draw_center_text($im, strtoupper(mb_substr($major ?: 'Student', 0, 18)), 470, 18, '#242751', $font);

    save_png($im, $outPath);
}

// --- Event banner ---
function gen_event_banner(string $eventName, string $category, string $outPath, string $font): void {
    $W=1200; $H=520;
    $im = imagecreatetruecolor($W,$H);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $paper = color($im, '#eef2f7', 0);
    imagefilledrectangle($im,0,0,$W,$H,$paper);

    $accent = category_color($category);
    $accentCol = color($im, $accent, 0);
    $navyCol = color($im, '#242751', 0);

    // Header block
    imagefilledrectangle($im, 0,0,$W,180, $navyCol);

    // Accent blocks
    imagefilledroundedrectangle($im, 60, 220, 520, 460, 28, $accentCol);
    imagefilledellipse($im, 980, 320, 520, 520, color($im, $accent, 70));

    // Text
    imagettftext($im, 44, 0, 60, 120, color($im,'#eef2f7',0), $font, mb_substr($eventName,0,28));
    imagettftext($im, 22, 0, 60, 160, color($im,$accent,0), $font, strtoupper($category ?: 'EVENT'));
    imagettftext($im, 26, 0, 90, 330, color($im,'#eef2f7',0), $font, 'JOIN • LEARN • NETWORK');
    imagettftext($im, 18, 0, 90, 380, color($im,'#eef2f7',10), $font, 'UniHive Events');

    save_png($im, $outPath);
}

// ---- 1) Generate for existing DB rows ----
$created = [
  'club_logos' => 0,
  'club_covers' => 0,
  'student_avatars' => 0,
  'event_banners' => 0,
];

// Clubs
$clubRes = $conn->query("SELECT club_id, club_name, category FROM club WHERE club_id <> 1 ORDER BY club_id ASC");
if ($clubRes) {
    while ($c = $clubRes->fetch_assoc()) {
        $id = (int)$c['club_id'];
        $name = $c['club_name'] ?? "Club $id";
        $cat  = $c['category'] ?? "Club";

        $logoRel  = "uploads/clubs/club_$id.png";
        $coverRel = "uploads/clubs/club_{$id}_cover.png";

        gen_club_logo($name, $cat, $root . '/' . $logoRel, $font);
        gen_club_cover($name, $cat, $root . '/' . $coverRel, $font);

        $stmt = $conn->prepare("UPDATE club SET logo=?, cover=? WHERE club_id=?");
        $stmt->bind_param("ssi", $logoRel, $coverRel, $id);
        $stmt->execute();
        $stmt->close();

        $created['club_logos']++;
        $created['club_covers']++;
    }
}

// Students
$stuRes = $conn->query("SELECT student_id, student_name, major FROM student ORDER BY student_id ASC");
if ($stuRes) {
    while ($s = $stuRes->fetch_assoc()) {
        $id = (int)$s['student_id'];
        $name = $s['student_name'] ?? "Student $id";
        $major = $s['major'] ?? "Student";

        $avatarRel = "uploads/students/student_$id.png";
        gen_student_avatar($name, $major, $root . '/' . $avatarRel, $font);

        $stmt = $conn->prepare("UPDATE student SET profile_photo=? WHERE student_id=?");
        $stmt->bind_param("si", $avatarRel, $id);
        $stmt->execute();
        $stmt->close();

        $created['student_avatars']++;
    }
}

// Events (table name might be `event`)
$evtRes = $conn->query("SELECT event_id, event_name, category FROM `event` ORDER BY event_id ASC");
if ($evtRes) {
    while ($e = $evtRes->fetch_assoc()) {
        $id = (int)$e['event_id'];
        $name = $e['event_name'] ?? "Event $id";
        $cat  = $e['category'] ?? "Event";

        $bannerRel = "uploads/events/event_$id.png";
        gen_event_banner($name, $cat, $root . '/' . $bannerRel, $font);

        $stmt = $conn->prepare("UPDATE `event` SET banner_image=? WHERE event_id=?");
        $stmt->bind_param("si", $bannerRel, $id);
        $stmt->execute();
        $stmt->close();

        $created['event_banners']++;
    }
}
// ---- NEWS images ----
$newsRes = $conn->query("SELECT news_id, title, category FROM news ORDER BY news_id ASC");
if ($newsRes) {
    while ($n = $newsRes->fetch_assoc()) {
        $id = (int)$n['news_id'];
        $title = $n['title'] ?? "News $id";
        $cat = $n['category'] ?? "General";

        $newsRel = "uploads/news/news_$id.png";

        // make a simple news banner (1200x520) similar to event banners
        $W=1200; $H=520;
        $im = imagecreatetruecolor($W,$H);
        imagealphablending($im, true);
        imagesavealpha($im, true);

        $paper = color($im, '#eef2f7', 0);
        imagefilledrectangle($im,0,0,$W,$H,$paper);

        $accent = category_color($cat);
        $navyCol = color($im, '#242751', 0);
        $accentCol = color($im, $accent, 0);

        imagefilledrectangle($im, 0,0,$W,180, $navyCol);
        imagefilledroundedrectangle($im, 60, 220, 540, 460, 28, $accentCol);
        imagefilledellipse($im, 980, 320, 520, 520, color($im, $accent, 70));

        imagettftext($im, 42, 0, 60, 120, color($im,'#eef2f7',0), $font, mb_substr($title,0,32));
        imagettftext($im, 22, 0, 60, 160, color($im,$accent,0), $font, strtoupper($cat));
        imagettftext($im, 26, 0, 90, 330, color($im,'#eef2f7',0), $font, 'CAMPUS UPDATE');
        imagettftext($im, 18, 0, 90, 380, color($im,'#eef2f7',10), $font, 'UniHive News');

        save_png($im, $root . '/' . $newsRel);

        $stmt = $conn->prepare("UPDATE news SET image=?, updated_at=NOW() WHERE news_id=?");
        $stmt->bind_param("si", $newsRel, $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Simple output
echo "<h2>✅ Media generated & DB updated</h2>";
echo "<pre>";
print_r($created);
echo "</pre>";

echo "<p>Now refresh your website pages (clubs, events, student profiles). Images should appear consistently.</p>";
