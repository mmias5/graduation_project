<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

function back_with_error($msg, $data = []) {
    $q = http_build_query(array_merge(['err' => $msg], $data));
    header("Location: createevent.php?$q");
    exit;
}

$presidentId = (int)$_SESSION['student_id'];

$club_id    = (int)($_POST['club_id'] ?? 0);
$title      = trim($_POST['title'] ?? '');
$location   = trim($_POST['location'] ?? '');
$date       = trim($_POST['date'] ?? '');
$start_time = trim($_POST['start_time'] ?? '');
$end_time   = trim($_POST['end_time'] ?? '');
$category   = trim($_POST['category'] ?? '');
$sponsor    = trim($_POST['sponsor'] ?? '');
$desc       = trim($_POST['description'] ?? '');

$old = [
  'title' => $title,
  'location' => $location,
  'date' => $date,
  'start_time' => $start_time,
  'end_time' => $end_time,
  'category' => $category,
  'sponsor' => $sponsor,
  'description' => $desc,
];

/* ✅ Make sure this president belongs to this club_id */
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id = ? LIMIT 1");
$stmt->bind_param("i", $presidentId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

$realClubId = $row['club_id'] ?? null;
if (empty($realClubId) || (int)$realClubId !== (int)$club_id) {
    back_with_error("Unauthorized club.", $old);
}

/* ✅ Validate required */
if ($title === '' || $location === '' || $date === '' || $start_time === '' || $end_time === '' || $desc === '') {
    back_with_error("Please fill all required fields.", $old);
}

/* ✅ Build DATETIME values for DB (starting_date / ending_date) */
$startDT = $date . ' ' . $start_time . ':00';
$endDT   = $date . ' ' . $end_time . ':00';

if (strtotime($startDT) === false || strtotime($endDT) === false) {
    back_with_error("Invalid date/time.", $old);
}
if (strtotime($endDT) <= strtotime($startDT)) {
    back_with_error("End time must be after start time.", $old);
}

/* ✅ Handle cover upload (optional) -> saved in banner_image */
$bannerPath = null;

if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
        back_with_error("Upload failed. Try another image.", $old);
    }

    $tmp  = $_FILES['cover']['tmp_name'];
    $name = $_FILES['cover']['name'];

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allowed, true)) {
        back_with_error("Only JPG, PNG, or WebP images are allowed.", $old);
    }

    $uploadDir = __DIR__ . "/uploads/events";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newName = "event_" . $club_id . "_" . time() . "_" . bin2hex(random_bytes(3)) . "." . $ext;
    $destAbs = $uploadDir . "/" . $newName;

    if (!move_uploaded_file($tmp, $destAbs)) {
        back_with_error("Could not save the image.", $old);
    }

    // ✅ store relative path in DB (adjust if you prefer another path)
    $bannerPath = "uploads/events/" . $newName;
}

/*
  ✅ Your DB tables:
  - event_creation_request: (request_id, club_id, requested_by_student_id, event_name, event_location, description,
                            max_attendees, starting_date, ending_date, banner_image, submitted_at, reviewed_at, review_admin_id)

  Note: category + sponsor columns are NOT in your DB.
  We will safely append them inside description so you don’t lose them.
*/
$finalDesc = $desc;
if ($category !== '' || $sponsor !== '') {
    $finalDesc .= "\n\n";
    if ($category !== '') $finalDesc .= "Category: " . $category . "\n";
    if ($sponsor !== '')  $finalDesc .= "Sponsor: " . $sponsor . "\n";
}

/* ✅ Insert as a CREATION REQUEST (admin can approve later) */
$max_attendees = null;
$submitted_at  = date('Y-m-d H:i:s');

$stmt = $conn->prepare("
    INSERT INTO event_creation_request
      (club_id, requested_by_student_id, event_name, event_location, description, max_attendees,
       starting_date, ending_date, banner_image, submitted_at)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iisssissss",
    $club_id,
    $presidentId,
    $title,
    $location,
    $finalDesc,
    $max_attendees,
    $startDT,
    $endDT,
    $bannerPath,
    $submitted_at
);

if (!$stmt->execute()) {
    $stmt->close();
    back_with_error("DB error while creating the request.", $old);
}
$stmt->close();

/* ✅ success */
header("Location: createevent.php?created=1");
exit;
