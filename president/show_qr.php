<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$studentId = $_SESSION['president_id'] ?? $_SESSION['student_id'] ?? null;
if (!$studentId) {
    header('Location: index.php');
    exit;
}

// Get QR code + name
$stmt = $conn->prepare("
    SELECT student_name, qr_code
    FROM student
    WHERE student_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$stmt->close();

if (!$student || empty($student['qr_code'])) {
    die("QR code not found for this user.");
}

$qrValue = $student['qr_code'];
$studentName = $student['student_name'] ?? 'Student';

// Generate QR using Google Chart API
$qrImg = "https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=" . urlencode($qrValue);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — My QR Code</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --royal:#4871db; --lightBlue:#a9bff8; --paper:#e9ecef;
  --ink:#0e1228; --card:#fff; --sun:#f4df6d;
  --shadow:0 10px 30px rgba(0,0,0,.16);
}
body{
  margin:0;
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--paper);
  color:var(--ink);
}
.wrap{
  max-width:700px;
  margin:0 auto;
  padding:40px 16px;
}
.card{
  background:var(--card);
  border-radius:18px;
  box-shadow:var(--shadow);
  padding:28px;
  text-align:center;
}
.h1{
  font-size:28px;
  font-weight:900;
  margin:0 0 8px;
}
.p{
  color:#6b7280;
  margin-bottom:22px;
}
.qr-box{
  display:inline-block;
  padding:16px;
  border-radius:16px;
  background:#fff;
  border:2px solid #e7e9f2;
}
.qr-box img{
  width:260px;
  height:260px;
}
.name{
  margin-top:18px;
  font-weight:900;
  font-size:18px;
}
.code{
  margin-top:6px;
  font-weight:700;
  color:#6b7280;
  letter-spacing:1px;
}
.note{
  margin-top:20px;
  font-size:13px;
  color:#6b7280;
}
.btn{
  margin-top:22px;
  display:inline-block;
  padding:12px 18px;
  border-radius:999px;
  background:linear-gradient(135deg, var(--royal), var(--lightBlue));
  color:#fff;
  font-weight:900;
  text-decoration:none;
  box-shadow:0 8px 18px rgba(72,113,219,.3);
}
</style>
</head>

<body>
<div class="wrap">
  <div class="card">
    <div class="h1">My QR Code</div>
    <div class="p">Show this QR to check in when attending any event.</div>

    <div class="qr-box">
      <img src="<?= $qrImg ?>" alt="My QR Code">
    </div>

    <div class="name"><?= htmlspecialchars($studentName) ?></div>
    <div class="code"><?= htmlspecialchars($qrValue) ?></div>

    <div class="note">
      This QR represents your student identity.<br>
      It can be scanned by event organizers to record attendance.
    </div>

    <a href="qr_attendance.php" class="btn">Go to Scan Attendance</a>
  </div>
</div>
</body>
</html>
