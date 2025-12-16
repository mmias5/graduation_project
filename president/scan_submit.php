<?php
// president/scan_submit.php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'club_president') {
  http_response_code(401);
  echo json_encode(['status'=>'error','message'=>'Unauthorized']);
  exit;
}

require_once __DIR__ . '/../config.php';

$presidentId = $_SESSION['president_id'] ?? $_SESSION['student_id'] ?? null;
$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$qrCode  = isset($_POST['qr_code']) ? trim($_POST['qr_code']) : '';

if (!$presidentId || $eventId <= 0 || $qrCode === '') {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'Missing data']);
  exit;
}

// 1) Get president club_id
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? LIMIT 1");
$stmt->bind_param("i", $presidentId);
$stmt->execute();
$res = $stmt->get_result();
$pres = $res->fetch_assoc();
$stmt->close();

$clubId = (int)($pres['club_id'] ?? 0);
if ($clubId <= 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'President club not found']);
  exit;
}

// 2) Validate event belongs to this club
$stmt = $conn->prepare("SELECT event_id, event_name, starting_date, ending_date, club_id FROM event WHERE event_id=? LIMIT 1");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$evRes = $stmt->get_result();
$event = $evRes->fetch_assoc();
$stmt->close();

if (!$event) {
  http_response_code(404);
  echo json_encode(['status'=>'error','message'=>'Event not found']);
  exit;
}
if ((int)$event['club_id'] !== $clubId) {
  http_response_code(403);
  echo json_encode(['status'=>'error','message'=>'This event is not in your club']);
  exit;
}

// (Optional) time check: allow scanning only during event window
// You can comment this out if you want scanning anytime.
$now = new DateTime("now");
if (!empty($event['starting_date']) && !empty($event['ending_date'])) {
  $start = new DateTime($event['starting_date']);
  $end   = new DateTime($event['ending_date']);
  if ($now < $start || $now > $end) {
    // not fatal; if you want strict, return error
    // echo json_encode(['status'=>'error','message'=>'Event is not currently active']); exit;
  }
}

// 3) Find student by qr_code
$stmt = $conn->prepare("
  SELECT student_id, student_name, major, profile_photo
  FROM student
  WHERE qr_code = ?
  LIMIT 1
");
$stmt->bind_param("s", $qrCode);
$stmt->execute();
$sRes = $stmt->get_result();
$student = $sRes->fetch_assoc();
$stmt->close();

if (!$student) {
  http_response_code(404);
  echo json_encode(['status'=>'error','message'=>'Student not found for this QR code']);
  exit;
}

$studentId = (int)$student['student_id'];

// 4) Duplicate check (attendance already exists)
$stmt = $conn->prepare("SELECT attendance_id FROM attendance WHERE student_id=? AND event_id=? LIMIT 1");
$stmt->bind_param("ii", $studentId, $eventId);
$stmt->execute();
$dupRes = $stmt->get_result();
$dup = $dupRes->fetch_assoc();
$stmt->close();

if ($dup) {
  echo json_encode([
    'status'  => 'duplicate',
    'message' => 'This student is already checked in for this event.',
    'student' => $student
  ]);
  exit;
}

// 5) Insert attendance + points in one transaction
$pointsToAdd = 10; // you can change this later
$source = 'event_attendance_qr';

$conn->begin_transaction();

try {
  // attendance
  $stmt = $conn->prepare("INSERT INTO attendance (student_id, event_id, checked_in_at) VALUES (?, ?, NOW())");
  $stmt->bind_param("ii", $studentId, $eventId);
  $stmt->execute();
  $stmt->close();

  // points_ledger
  $stmt = $conn->prepare("
    INSERT INTO points_ledger (student_id, club_id, event_id, rule_id, points, source, occurred_at)
    VALUES (?, ?, ?, NULL, ?, ?, NOW())
  ");
  $stmt->bind_param("iiiis", $studentId, $clubId, $eventId, $pointsToAdd, $source);
  $stmt->execute();
  $stmt->close();

  // update student total_points (you already have total_points column)
  $stmt = $conn->prepare("UPDATE student SET total_points = COALESCE(total_points,0) + ? WHERE student_id=?");
  $stmt->bind_param("ii", $pointsToAdd, $studentId);
  $stmt->execute();
  $stmt->close();

  $conn->commit();

  echo json_encode([
    'status'  => 'ok',
    'message' => "Checked in successfully (+{$pointsToAdd} pts).",
    'student' => $student
  ]);
  exit;

} catch (Throwable $e) {
  $conn->rollback();
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>'DB error while saving attendance']);
  exit;
}
