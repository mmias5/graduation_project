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
$sponsor    = trim($_POST['sponsor'] ?? ''); // company_name from form
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

/* Ensure this president belongs to this club_id */
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

/* Validate required */
if ($title === '' || $location === '' || $date === '' || $start_time === '' || $end_time === '' || $desc === '') {
    back_with_error("Please fill all required fields.", $old);
}

/* Build DATETIME values for DB (starting_date / ending_date) */
$startDT = $date . ' ' . $start_time . ':00';
$endDT   = $date . ' ' . $end_time . ':00';

if (strtotime($startDT) === false || strtotime($endDT) === false) {
    back_with_error("Invalid date/time.", $old);
}
if (strtotime($endDT) <= strtotime($startDT)) {
    back_with_error("End time must be after start time.", $old);
}

/* Handle cover upload (optional) -> saved in banner_image */
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

    // store relative path in DB
    $bannerPath = "uploads/events/" . $newName;
}

/*  sponsor_id in DB (lookup by company_name from input). If not found -> NULL */
$sponsorId = null;
if ($sponsor !== '') {
    $st = $conn->prepare("SELECT sponsor_id FROM sponsor WHERE company_name = ? LIMIT 1");
    $st->bind_param("s", $sponsor);
    $st->execute();
    $rr = $st->get_result();
    if ($rr && $rr->num_rows > 0) {
        $sponsorId = (int)$rr->fetch_assoc()['sponsor_id'];
    }
    $st->close();

    // if user typed a name that doesn't exist in sponsor table, keep sponsorId NULL
    //  append the typed name in description so don’t lose it:
    if ($sponsorId === null) {
        $desc .= "\n\nSponsor (typed): " . $sponsor;
    }
}

/*  Insert into event_creation_request exactly as DB columns */
$max_attendees = null;
$submitted_at  = date('Y-m-d H:i:s');

$stmt = $conn->prepare("
    INSERT INTO event_creation_request
      (club_id, sponsor_id, requested_by_student_id, event_name, event_location, category, description, max_attendees,
       starting_date, ending_date, banner_image, submitted_at)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iiissssissss",
    $club_id,
    $sponsorId,
    $presidentId,
    $title,
    $location,
    $category,
    $desc,
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

/* success */
header("Location: createevent.php?created=1");
exit;
