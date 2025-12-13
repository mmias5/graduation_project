<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';

function safeFileName(string $name): string {
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9\._-]+/i', '_', $name);
    $name = trim($name, '_');
    return $name ?: ('file_' . time());
}

function uploadImage(array $file, string $destDir, string $prefix): ?string {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;

    $tmp  = $file['tmp_name'];
    $orig = $file['name'] ?? 'img';
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

    // allow common image extensions
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed, true)) return null;

    // basic mime check
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp);
    finfo_close($finfo);
    if (strpos($mime, 'image/') !== 0) return null;

    if (!is_dir($destDir)) {
        mkdir($destDir, 0777, true);
    }

    $base = safeFileName($prefix . '_' . pathinfo($orig, PATHINFO_FILENAME));
    $newName = $base . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;

    $fullPath = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $newName;

    if (!move_uploaded_file($tmp, $fullPath)) return null;

    // Return relative path for DB (Windows-safe)
    $rel = 'uploads/clubs/' . $newName;
    return $rel;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: viewclubs.php');
    exit;
}

$clubId = isset($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
if ($clubId <= 0) {
    echo "<script>alert('Invalid club id'); window.location.href='viewclubs.php';</script>";
    exit;
}

// Read inputs (match your form names)
$club_name     = trim($_POST['club_name'] ?? '');
$category      = trim($_POST['category'] ?? '');
$contact_email = trim($_POST['contact_email'] ?? '');
$description   = trim($_POST['description'] ?? '');

$instagram_in  = trim($_POST['instagram'] ?? '');
$facebook_in   = trim($_POST['facebook'] ?? '');
$linkedin_in   = trim($_POST['linkedin'] ?? '');

// Basic validation
if ($club_name === '' || $contact_email === '' || $description === '') {
    echo "<script>alert('Please fill required fields.'); window.location.href='editclub.php?club_id={$clubId}';</script>";
    exit;
}
if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Invalid contact email.'); window.location.href='editclub.php?club_id={$clubId}';</script>";
    exit;
}

// Fetch current logo/cover so if no new upload keep old
$stmt = $conn->prepare("SELECT logo, cover FROM club WHERE club_id = ? LIMIT 1");
$stmt->bind_param("i", $clubId);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current) {
    echo "<script>alert('Club not found.'); window.location.href='viewclubs.php';</script>";
    exit;
}

$currentLogo  = $current['logo'] ?? '';
$currentCover = $current['cover'] ?? '';

// Uploads
$uploadDir = __DIR__ . '/../uploads/clubs';

$newLogoPath  = null;
$newCoverPath = null;

if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $newLogoPath = uploadImage($_FILES['logo'], $uploadDir, 'logo_club_' . $clubId);
}
if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
    $newCoverPath = uploadImage($_FILES['cover'], $uploadDir, 'cover_club_' . $clubId);
}

$finalLogo  = $newLogoPath  !== null ? $newLogoPath  : $currentLogo;
$finalCover = $newCoverPath !== null ? $newCoverPath : $currentCover;

// ✅ UPDATE club table
$stmt = $conn->prepare("
    UPDATE club
    SET
        club_name = ?,
        category = ?,
        contact_email = ?,
        description = ?,
        logo = ?,
        cover = ?,
        instagram_url = ?,
        facebook_url = ?,
        linkedin_url = ?
    WHERE club_id = ?
    LIMIT 1
");
$stmt->bind_param(
    "sssssssssi",
    $club_name,
    $category,
    $contact_email,
    $description,
    $finalLogo,
    $finalCover,
    $instagram_in,
    $facebook_in,
    $linkedin_in,
    $clubId
);

$ok = $stmt->execute();
$err = $stmt->error;
$stmt->close();

if (!$ok) {
    echo "<script>alert('Update failed: " . addslashes($err) . "'); window.location.href='editclub.php?club_id={$clubId}';</script>";
    exit;
}

echo "<script>alert('Club updated successfully.'); window.location.href='clubpage.php?club_id={$clubId}';</script>";
exit;
