<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (
  !isset($_SESSION['student_id']) ||
  !isset($_SESSION['role']) ||
  !in_array($_SESSION['role'], ['student', 'club_president'])
) {
  header('Location: ../login.php');
  exit;
}

require_once __DIR__ . '/../config.php';

$studentId = (int)$_SESSION['student_id'];

$stmt = $conn->prepare("
  SELECT student_name, qr_code
  FROM student
  WHERE student_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

$studentName = $row['student_name'] ?? ($_SESSION['student_name'] ?? 'Student');
$qrValue     = $row['qr_code'] ?? '';

if ($qrValue === '') {
  die("QR code not found for this student.");
}

// Generate QR image 
$qrImg = "https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=" . urlencode($qrValue);
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
  --navy:#242751; --royal:#4871db; --lightBlue:#a9bff8;
  --sun:#f4df6d; --paper:#e9ecef; --ink:#0e1228; --card:#fff;
  --shadow:0 10px 30px rgba(0,0,0,.16);
}
body{
  margin:0;
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--paper);
  color:var(--ink);
}
.wrap{ max-width:760px; margin:0 auto; padding:34px 16px 60px; }
.card{
  background:var(--card);
  border:2px solid #e7e9f2;
  border-radius:18px;
  box-shadow:var(--shadow);
  padding:26px;
  text-align:center;
}
.h1{ font-size:28px; font-weight:900; margin:0 0 8px; }
.p{ margin:0 0 18px; color:#6b7280; line-height:1.6; }

.qrBox{
  display:inline-block;
  padding:16px;
  border-radius:16px;
  background:#fff;
  border:2px solid #e7e9f2;
}
.qrBox img{ width:280px; height:280px; display:block; }

.name{ margin-top:16px; font-weight:900; font-size:18px; }
.code{
  margin-top:6px;
  font-weight:800;
  color:#6b7280;
  letter-spacing:1px;
}
.note{
  margin-top:18px;
  font-size:13px;
  color:#6b7280;
}
.btnRow{
  margin-top:18px;
  display:flex;
  gap:10px;
  justify-content:center;
  flex-wrap:wrap;
}
.btn{
  appearance:none;
  border:0;
  padding:12px 16px;
  border-radius:999px;
  font-weight:900;
  cursor:pointer;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  justify-content:center;
}
.btn.primary{
  background:linear-gradient(135deg, var(--royal), var(--lightBlue));
  color:#fff;
  box-shadow:0 8px 18px rgba(72,113,219,.25);
}
.btn.secondary{
  background:#ffffff;
  color:var(--navy);
  border:2px solid #e7e9f2;
}
</style>
</head>
<body>

<div class="wrap">
  <div class="card">
    <div class="h1">My QR Code</div>
    <div class="p">Show this QR to the event organizer to record your attendance.</div>

    <div class="qrBox">
      <img src="<?= $qrImg ?>" alt="My QR Code">
    </div>

    <div class="name"><?= htmlspecialchars($studentName) ?></div>
    <div class="code"><?= htmlspecialchars($qrValue) ?></div>

    <div class="note">
      This QR is linked to your student identity in UniHive.
    </div>

    <div class="btnRow">
      <a class="btn secondary" href="index.php">Back to Home</a>
      <button class="btn primary" type="button" onclick="window.print()">Print QR</button>
    </div>
  </div>
</div>

</body>
</html>
