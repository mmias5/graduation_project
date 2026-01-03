<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$presidentId = (int)$_SESSION['student_id'];

// 1) get president's club_id
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? LIMIT 1");
$stmt->bind_param("i", $presidentId);
$stmt->execute();
$clubId = (int)($stmt->get_result()->fetch_assoc()['club_id'] ?? 0);
$stmt->close();

if ($clubId <= 1) {
    echo "<script>alert('You are not assigned to any club yet.'); location.href='index.php';</script>";
    exit;
}

// 2) fetch values from post
$club_name     = trim($_POST['club_name'] ?? '');
$category      = trim($_POST['category'] ?? '');
$contact_email = trim($_POST['contact_email'] ?? '');
$description   = trim($_POST['description'] ?? '');

$instagram = trim($_POST['instagram'] ?? '');
$facebook  = trim($_POST['facebook'] ?? '');
$linkedin  = trim($_POST['linkedin'] ?? '');

$new_social_media_link = trim($_POST['social_media_link'] ?? ''); 

if ($club_name === '' || $category === '' || $contact_email === '' || $description === '') {
    echo "<script>alert('Please fill all required fields.'); history.back();</script>";
    exit;
}

// 3) logo upload hanndling (if any)
$newLogoPath = null;

if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $tmp  = $_FILES['logo']['tmp_name'];
    $name = $_FILES['logo']['name'];

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['png','jpg','jpeg','webp','gif'];
    if (!in_array($ext, $allowed, true)) {
        echo "<script>alert('Invalid logo file type. Please upload png/jpg/webp.'); history.back();</script>";
        exit;
    }

    // place it in /uploads/club_edit_requests/
    $uploadDir = __DIR__ . '/../uploads/club_edit_requests/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $safeFile = 'club_'.$clubId.'_req_'.date('Ymd_His').'.'.$ext;
    $dest = $uploadDir . $safeFile;

    if (!move_uploaded_file($tmp, $dest)) {
        echo "<script>alert('Failed to upload logo.'); history.back();</script>";
        exit;
    }

    $newLogoPath = 'uploads/club_edit_requests/' . $safeFile;
}

// 4) dont allow a new request if there's a pending one 
try {
    $stmt = $conn->prepare("SELECT request_id FROM club_edit_request WHERE club_id=? AND reviewed_at IS NULL LIMIT 1");
    $stmt->bind_param("i", $clubId);
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($pending) {
        echo "<script>alert('You already have a pending edit request. Wait for admin review.'); location.href='index.php';</script>";
        exit;
    }
} catch (Throwable $e) {
    // ignore
}

// 5) INSERT into club_edit_request
$sql = "INSERT INTO club_edit_request
        (club_id, requested_by_student_id,
         new_club_name, new_description, new_category,
         new_social_media_link, instagram, facebook, linkedin,
         new_logo, new_contact_email,
         submitted_at, reviewed_at, review_admin_id, status)
        VALUES
        (?, ?,
         ?, ?, ?,
         ?, ?, ?, ?,
         ?, ?,
         NOW(), NULL, NULL, 'Pending')";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "<script>alert('DB Error: ".$conn->error."'); history.back();</script>";
    exit;
}

$stmt->bind_param(
    "iisssssssss",
    $clubId,
    $presidentId,
    $club_name,
    $description,
    $category,
    $new_social_media_link,
    $instagram,
    $facebook,
    $linkedin,
    $newLogoPath,
    $contact_email
);

if (!$stmt->execute()) {
    echo "<script>alert('Failed to submit request: ".$stmt->error."'); history.back();</script>";
    exit;
}

$stmt->close();

echo "<script>alert('✅ Edit request submitted successfully! Waiting for admin approval.'); location.href='index.php';</script>";
exit;
