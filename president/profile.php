<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$president_id = (int)$_SESSION['student_id'];
$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($member_id <= 0) {
    header('Location: memberspage.php');
    exit;
}

/* get president club_id */
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? AND role='club_president' LIMIT 1");
$stmt->bind_param("i", $president_id);
$stmt->execute();
$res = $stmt->get_result();
$pres = $res->fetch_assoc();
$stmt->close();
$club_id = isset($pres['club_id']) ? (int)$pres['club_id'] : 1;

if ($club_id <= 1) {
    header('Location: memberspage.php');
    exit;
}

/* check permission:
   - member is in same club OR
   - member has Pending request to same club
*/
$allowed = false;

$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? LIMIT 1");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$r = $stmt->get_result();
$memClub = $r->fetch_assoc();
$stmt->close();

if ($memClub && (int)$memClub['club_id'] === $club_id) {
    $allowed = true;
} else {
    $stmt = $conn->prepare("
        SELECT 1
        FROM club_membership_request
        WHERE student_id=? AND club_id=? AND status='Pending'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $member_id, $club_id);
    $stmt->execute();
    $rr = $stmt->get_result();
    $allowed = (bool)$rr->fetch_row();
    $stmt->close();
}

if (!$allowed) {
    header('Location: memberspage.php');
    exit;
}

/* get member info */
$stmt = $conn->prepare("
    SELECT student_id, student_name, email, major, profile_photo, club_id
    FROM student
    WHERE student_id=?
    LIMIT 1
");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$res = $stmt->get_result();
$m = $res->fetch_assoc();
$stmt->close();

if (!$m) {
    header('Location: memberspage.php');
    exit;
}

$avatar = $m['profile_photo'];
if (!$avatar) $avatar = "https://i.pravatar.cc/200?u=" . urlencode("profile_" . $member_id);

/* role badge */
$role = ($member_id === $president_id) ? 'President' : 'Member';

/* joined date from approved request */
$joined = '—';
$stmt = $conn->prepare("
    SELECT MAX(COALESCE(decided_at, submitted_at)) AS joined_at
    FROM club_membership_request
    WHERE student_id=? AND club_id=? AND status='Approved'
");
$stmt->bind_param("ii", $member_id, $club_id);
$stmt->execute();
$res = $stmt->get_result();
$j = $res->fetch_assoc();
$stmt->close();

if ($j && $j['joined_at']) $joined = date('Y-m-d', strtotime($j['joined_at']));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Member Profile</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --navy:#242751; --royal:#4871db; --light:#a9bff8;
  --paper:#eef2f7; --ink:#0e1228; --card:#fff; --gold:#e5b758;
  --shadow:0 18px 38px rgba(12,22,60,.16); --radius:22px; --maxw:1100px;
}
*{box-sizing:border-box} html,body{margin:0}
body{
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  background:
    radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
    radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%),
    var(--paper);
}

.wrap{max-width:var(--maxw); margin:28px auto 80px; padding:0 18px}

/* hero card */
.hero{
  position:relative;border-radius:26px;overflow:hidden;
  background:#4871db;
  box-shadow:var(--shadow);
  min-height:220px;
  color:#fff;
  display:flex;align-items:flex-end;
}
.hero::after{
  content:"";position:absolute;inset:0;
  background:radial-gradient(600px 300px at 10% 10%,rgba(255,255,255,.18),transparent 60%);
}

/* UPDATED: Perfectly balanced image + spacing */
.hero-inner{
  position:relative;z-index:1;width:100%;
  display:grid;grid-template-columns:170px 1fr;
  gap:18px;align-items:flex-end;padding:24px 22px 26px;
}
@media (max-width:720px){
  .hero-inner{grid-template-columns:1fr;justify-items:center;text-align:center}
}

/* UPDATED: Balanced avatar size */
.avatar{
  width:160px;
  height:160px;
  border-radius:24px;
  object-fit:cover;
  border:4px solid rgba(255,255,255,.9);
  background:#dfe5ff;
  box-shadow:0 12px 26px rgba(0,0,0,.18);
}

.name{font-size:30px;font-weight:900;margin:0}
.sub{opacity:.95;font-weight:700;margin-top:4px}
.badges{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.role{
  display:inline-flex;align-items:center;gap:8px;
  background:#fff7e6;border:1px solid #ffecb5;
  color:#8a5b00;font-weight:900;
  border-radius:999px;padding:6px 12px;font-size:12px;
}
.joined{
  display:inline-flex;align-items:center;gap:8px;
  background:#f2f5ff;border:1px solid #e6e8f2;
  color:#1f2a6b;font-weight:900;
  border-radius:999px;padding:6px 12px;font-size:12px;
}

/* content */
.card{
  background:var(--card);
  border:1px solid #e6e8f2;
  border-radius:18px;
  box-shadow:var(--shadow);
  padding:20px;
  margin-top:20px;
}
.card h3{margin:0 0 14px;font-size:18px;color:var(--navy)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media (max-width:620px){ .grid{grid-template-columns:1fr} }

.kv{
  background:#f6f8ff;
  border:1px solid #e7ecff;
  border-radius:14px;
  padding:12px;
}
.kv b{
  display:block;
  font-size:12px;
  color:#596180;
  margin-bottom:4px;
}
.kv span{
  font-size:15px;
  color:#1a1f36;
  font-weight:700;
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="wrap">

  <!-- HERO -->
  <section class="hero">
    <div class="hero-inner">
      <img class="avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="Member avatar">
      <div>
        <h2 class="name"><?php echo htmlspecialchars($m['student_name']); ?></h2>
        <div class="sub"><?php echo htmlspecialchars($m['email']); ?></div>
        <div class="badges">
          <span class="role"><?php echo htmlspecialchars($role); ?></span>
          <span class="joined">Joined — <?php echo htmlspecialchars($joined); ?></span>
        </div>
      </div>
    </div>
  </section>

  <!-- ABOUT ONLY -->
  <section class="card">
    <h3>Member Info</h3>
    <div class="grid">
      <div class="kv"><b>Full name</b><span><?php echo htmlspecialchars($m['student_name']); ?></span></div>
      <div class="kv"><b>Email</b><span><?php echo htmlspecialchars($m['email']); ?></span></div>
      <div class="kv"><b>Major</b><span><?php echo htmlspecialchars($m['major'] ?: '—'); ?></span></div>
      <div class="kv"><b>Student ID</b><span><?php echo (int)$m['student_id']; ?></span></div>
    </div>
  </section>

</div>

<?php include 'footer.php'; ?>
</body>
</html>
