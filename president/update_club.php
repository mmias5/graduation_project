<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$presidentId = (int)$_SESSION['student_id'];

// ===== Get president club_id (SECURITY) =====
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? LIMIT 1");
$stmt->bind_param("i", $presidentId);
$stmt->execute();
$myClubId = (int)($stmt->get_result()->fetch_assoc()['club_id'] ?? 0);
$stmt->close();

if ($myClubId <= 1) {
    die("Not allowed.");
}

// ✅ Ignore posted club_id (prevent tampering)
$club_id = $myClubId;

// ===== Read inputs =====
$club_name     = trim($_POST['club_name'] ?? '');
$category      = trim($_POST['category'] ?? '');
$contact_email = trim($_POST['contact_email'] ?? '');
$description   = trim($_POST['description'] ?? '');
$instagram     = trim($_POST['instagram'] ?? '');
$facebook      = trim($_POST['facebook'] ?? '');
$linkedin      = trim($_POST['linkedin'] ?? '');

// Basic validation
if ($club_name === '' || $contact_email === '' || $description === '') {
    echo "<script>alert('Please fill required fields.'); history.back();</script>";
    exit;
}

// ===== Fetch current logo to keep if no upload =====
$currentLogo = '';
$stmt = $conn->prepare("SELECT logo FROM club WHERE club_id=? LIMIT 1");
$stmt->bind_param("i", $club_id);
$stmt->execute();
$currentLogo = (string)($stmt->get_result()->fetch_assoc()['logo'] ?? '');
$stmt->close();

// ===== Handle logo upload =====
$logoPath = $currentLogo;

if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $tmp  = $_FILES['logo']['tmp_name'];
    $name = $_FILES['logo']['name'] ?? '';
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allowed, true)) {
        echo "<script>alert('Logo must be jpg, jpeg, png, or webp.'); history.back();</script>";
        exit;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/clubs';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $newName = 'club_' . $club_id . '_logo_' . time() . '.' . $ext;
    $destAbs = $uploadDir . '/' . $newName;

    if (!move_uploaded_file($tmp, $destAbs)) {
        echo "<script>alert('Failed to upload logo.'); history.back();</script>";
        exit;
    }

    // save relative path for DB
    $logoPath = 'assets/uploads/clubs/' . $newName;
}

// ===== Update club table =====
// club columns you have:
// club_name, description, category, contact_email, logo, facebook_url, instagram_url, linkedin_url
$sql = "
  UPDATE club
  SET club_name=?,
      description=?,
      category=?,
      contact_email=?,
      logo=?,
      facebook_url=?,
      instagram_url=?,
      linkedin_url=?
  WHERE club_id=?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// TYPES: s s s s s s s s i  (8 strings + 1 int)
$stmt->bind_param(
    "ssssssssi",
    $club_name,
    $description,
    $category,
    $contact_email,
    $logoPath,
    $facebook,
    $instagram,
    $linkedin,
    $club_id
);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    die("Update failed: " . $err);
}
$stmt->close();

header("Location: clubpage.php?updated=1");
exit;
