<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$studentId = $_SESSION['student_id'];

// If POST => handle form submit (create membership_request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clubId = isset($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
    $reason = trim($_POST['reason'] ?? '');

    if ($clubId <= 1 || $reason === '') {
        $_SESSION['club_flash'] = [
            'type' => 'error',
            'msg'  => 'Please choose a club and write a reason.'
        ];
        header('Location: joinclub.php' . ($clubId > 1 ? '?club_id='.$clubId : ''));
        exit;
    }

    // Check student not already in a club
    $stmt = $conn->prepare("
        SELECT club_id
        FROM student
        WHERE student_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $res = $stmt->get_result();
    $studentRow = $res->fetch_assoc();
    $stmt->close();

    if (!$studentRow) {
        $_SESSION['club_flash'] = [
            'type' => 'error',
            'msg'  => 'Student not found.'
        ];
        header('Location: clubpage.php');
        exit;
    }

    $currentClubId = (int)($studentRow['club_id'] ?? 0);
    if ($currentClubId !== 0 && $currentClubId !== 1) {
        $_SESSION['club_flash'] = [
            'type' => 'error',
            'msg'  => 'You are already a member of a club.'
        ];
        header('Location: clubpage.php');
        exit;
    }

    // Check if there is already a pending request for this club
    $statusPending = 'pending';
    $stmt = $conn->prepare("
        SELECT request_id
        FROM club_membership_request
        WHERE student_id = ?
          AND club_id = ?
          AND status = ?
        LIMIT 1
    ");
    $stmt->bind_param('iis', $studentId, $clubId, $statusPending);
    $stmt->execute();
    $res = $stmt->get_result();
    $existing = $res->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $_SESSION['club_flash'] = [
            'type' => 'error',
            'msg'  => 'You already have a pending request for this club.'
        ];
        header('Location: clubpage.php');
        exit;
    }

    // Insert new membership request (THIS uses the reason column)
    $stmt = $conn->prepare("
        INSERT INTO club_membership_request (club_id, student_id, reason, status, submitted_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param('iiss', $clubId, $studentId, $reason, $statusPending);
    $stmt->execute();
    $stmt->close();

    $_SESSION['club_flash'] = [
        'type' => 'success',
        'msg'  => 'Your request to join the club has been submitted and is under review.'
    ];
    header('Location: clubpage.php');
    exit;
}

// If GET => show form
// Get list of active clubs except "No Club / Not Assigned"
$clubs = [];
$stmt = $conn->prepare("
    SELECT club_id, club_name
    FROM club
    WHERE club_id <> 1
      AND (status IS NULL OR status = 'active')
    ORDER BY club_name
");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $clubs[] = $row;
}
$stmt->close();

// club_id الجاي من discover أو clubpage (اختياري)
$preselectedClubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

// Optional flash (errors) for this page
$pageFlash = '';
if (!empty($_SESSION['club_flash'])) {
    if (($_SESSION['club_flash']['type'] ?? '') === 'error') {
        $pageFlash = $_SESSION['club_flash']['msg'] ?? '';
    }
    // keep success flash for clubpage only
    if (($_SESSION['club_flash']['type'] ?? '') !== 'success') {
        unset($_SESSION['club_flash']);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Join a Club — UniHive</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --navy:#242751; --royal:#4871db; --paper:#eef2f7;
  --gold:#f4df6d; --coral:#ff5e5e; --white:#ffffff;
  --shadow:0 14px 34px rgba(10,23,60,.18);
}
*{box-sizing:border-box}
body{
  margin:0;
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--paper);
  color:#0e1228;
}
.section{padding:24px 20px}
.wrap{max-width:700px;margin:0 auto}
.card{
  background:#fff;border-radius:24px;box-shadow:var(--shadow);
  padding:24px 22px;
}
h1{
  margin:0 0 16px;
  font-size:26px;
  letter-spacing:.2em;
  text-transform:uppercase;
  color:var(--navy);
}
label{
  display:block;
  font-weight:700;
  margin:14px 0 6px;
}
select,textarea{
  width:100%;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid #d1d5db;
  font-family:inherit;
  font-size:14px;
}
textarea{min-height:120px;resize:vertical;}
.btn-primary{
  margin-top:18px;
  width:100%;
  border:none;
  border-radius:999px;
  padding:12px 18px;
  font-weight:800;
  font-size:16px;
  background:linear-gradient(135deg,var(--royal),#a9bff8);
  color:#fff;
  cursor:pointer;
  box-shadow:0 10px 22px rgba(72,113,219,.35);
}
.btn-primary:hover{
  filter:brightness(1.05);
}
.flash-error{
  margin:0 0 12px;
  padding:10px 12px;
  border-radius:12px;
  background:#fef2f2;
  color:#b91c1c;
  border:1px solid #fecaca;
  font-size:14px;
}
.back-link{
  display:inline-block;
  margin-top:10px;
  font-size:13px;
  text-decoration:none;
  color:var(--royal);
}
</style>
</head>
<body>
  <?php include 'header.php'; ?>
  <div class="underbar"></div>

  <section class="section">
    <div class="wrap">
      <div class="card">
        <h1>Join a Club</h1>

        <?php if ($pageFlash): ?>
          <div class="flash-error">
            <?php echo htmlspecialchars($pageFlash); ?>
          </div>
        <?php endif; ?>

        <?php if (empty($clubs)): ?>
          <p>There are no active clubs available at the moment.</p>
        <?php else: ?>
          <form method="post" action="joinclub.php<?php echo $preselectedClubId > 1 ? '?club_id='.$preselectedClubId : ''; ?>">
            <label for="club_id">Choose a club</label>
            <select id="club_id" name="club_id" required>
              <option value="">-- Select a club --</option>
              <?php foreach ($clubs as $c): ?>
                <option value="<?php echo (int)$c['club_id']; ?>"
                  <?php echo ($preselectedClubId === (int)$c['club_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($c['club_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>

            <label for="reason">Why do you want to join?</label>
            <textarea id="reason" name="reason" required
              placeholder="Tell the club briefly why you’d like to join..."></textarea>

            <button class="btn-primary" type="submit">Send join request</button>
          </form>
        <?php endif; ?>

        <a class="back-link" href="clubpage.php">← Back to your club page</a>
      </div>
    </div>
  </section>

  <?php include 'footer.php'; ?>
</body>
</html>
