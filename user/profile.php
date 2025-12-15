<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'club_president') {
        header('Location: ../president/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$currentStudentId = (int)$_SESSION['student_id'];
$profileId = isset($_GET['id']) ? (int)$_GET['id'] : $currentStudentId;

/* ✅ helper: fix image path from DB for student folder */
function img_path_student(string $path): string {
    $path = trim($path);
    if ($path === '') return '';
    if (preg_match('/^https?:\/\//i', $path)) return $path;
    if ($path[0] === '/') return $path;
    return '../' . ltrim($path, '/');
}

/* Fetch member info (✅ added profile_photo) */
$stmt = $conn->prepare("
    SELECT s.student_id,
           s.student_name,
           s.email,
           s.major,
           s.role,
           s.total_points,
           s.club_id,
           s.profile_photo,
           c.club_name
    FROM student s
    LEFT JOIN club c ON s.club_id = c.club_id
    WHERE s.student_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $profileId);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();
$stmt->close();

if (!$member) {
    header('Location: memberspage.php');
    exit;
}

/* ✅ avatar logic: DB first, fallback pravatar */
$avatarUrl = '';
if (!empty($member['profile_photo'])) {
    $avatarUrl = img_path_student($member['profile_photo']);
} else {
    $avatarUrl = 'https://i.pravatar.cc/200?u=student_' . (int)$member['student_id'];
}

$roleLabel = ($member['role'] === 'club_president') ? 'President' : 'Member';
$clubName  = $member['club_name'] ?? 'No Club';
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

/* hero */
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
.hero-inner{
  position:relative;z-index:1;width:100%;
  display:grid;grid-template-columns:170px 1fr;
  gap:18px;align-items:flex-end;padding:24px 22px 26px;
}
@media (max-width:720px){
  .hero-inner{grid-template-columns:1fr;justify-items:center;text-align:center}
}

.avatar{
  width:160px;height:160px;border-radius:24px;
  object-fit:cover;
  border:4px solid rgba(255,255,255,.9);
  background:#dfe5ff;
  box-shadow:0 12px 26px rgba(0,0,0,.18);
}

.name{font-size:30px;font-weight:900;margin:0}
.sub{opacity:.95;font-weight:700;margin-top:4px}
.badges{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.role{
  background:#fff7e6;border:1px solid #ffecb5;
  color:#8a5b00;font-weight:900;
  border-radius:999px;padding:6px 12px;font-size:12px;
}
.joined{
  background:#f2f5ff;border:1px solid #e6e8f2;
  color:#1f2a6b;font-weight:900;
  border-radius:999px;padding:6px 12px;font-size:12px;
}

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
.kv b{font-size:12px;color:#596180}
.kv span{font-size:15px;font-weight:700}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="wrap">

  <section class="hero">
    <div class="hero-inner">
      <img class="avatar"
           src="<?php echo htmlspecialchars($avatarUrl); ?>"
           alt="Member avatar"
           onerror="this.src='https://i.pravatar.cc/200?u=student_<?php echo (int)$member['student_id']; ?>'">
      <div>
        <h2 class="name"><?php echo htmlspecialchars($member['student_name']); ?></h2>
        <div class="sub"><?php echo htmlspecialchars($member['email']); ?></div>
        <div class="badges">
          <span class="role"><?php echo $roleLabel; ?></span>
          <span class="joined">Club — <?php echo htmlspecialchars($clubName); ?></span>
        </div>
      </div>
    </div>
  </section>

  <section class="card">
    <h3>Member Info</h3>
    <div class="grid">
      <div class="kv"><b>Full name</b><div><?php echo htmlspecialchars($member['student_name']); ?></div></div>
      <div class="kv"><b>Email</b><div><?php echo htmlspecialchars($member['email']); ?></div></div>
      <div class="kv"><b>Major</b><div><?php echo htmlspecialchars($member['major'] ?? '—'); ?></div></div>
      <div class="kv"><b>Student ID</b><div><?php echo (int)$member['student_id']; ?></div></div>
    </div>
  </section>

</div>

<?php include 'footer.php'; ?>
</body>
</html>
