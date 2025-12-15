<?php
session_start();

// user file
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'club_president') {
        header('Location: ../president/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$studentId = (int)$_SESSION['student_id'];

/* ✅ ONLY CHANGE: helper to make DB image paths work from /student/ */
function img_path_student($path){
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('/^https?:\/\//i', $path)) return $path; // full URL
    if ($path[0] === '/') return $path;                     // absolute path
    return '../' . ltrim($path, '/');                       // relative path from student folder
}

// ===== 1) Get student =====
$stmtStu = $conn->prepare("SELECT student_id, club_id FROM student WHERE student_id = ? LIMIT 1");
$stmtStu->bind_param("i", $studentId);
$stmtStu->execute();
$resStu = $stmtStu->get_result();
$student = $resStu->fetch_assoc();
$stmtStu->close();

if (!$student) {
    die("Student not found.");
}

$studentClubId = (int)($student['club_id'] ?? 1);
if ($studentClubId <= 0) $studentClubId = 1;

// ===== 2) Handle LEAVE (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'leave') {

    // Only allow leaving if the student is currently in a real club
    if ($studentClubId !== 1) {

        $conn->begin_transaction();

        try {
            // 1) Student back to Default Club (No Club)
            $stmt = $conn->prepare("UPDATE student SET club_id = 1 WHERE student_id = ?");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $stmt->close();

            // 2) Decrease member_count safely
            $stmt = $conn->prepare("UPDATE club SET member_count = GREATEST(member_count - 1, 0) WHERE club_id = ?");
            $stmt->bind_param("i", $studentClubId);
            $stmt->execute();
            $stmt->close();

            // 3) Update latest approved membership request to 'left'
            $stmt = $conn->prepare("
                SELECT request_id
                FROM club_membership_request
                WHERE club_id = ? AND student_id = ? AND status = 'approved'
                ORDER BY submitted_at DESC, request_id DESC
                LIMIT 1
            ");
            $stmt->bind_param("ii", $studentClubId, $studentId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();

            if ($row) {
                $reqId = (int)$row['request_id'];
                $stmt = $conn->prepare("
                    UPDATE club_membership_request
                    SET status = 'left',
                        decided_at = NOW(),
                        decided_by_student_id = ?
                    WHERE request_id = ?
                ");
                $stmt->bind_param("ii", $studentId, $reqId);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();

        } catch (Exception $e) {
            $conn->rollback();
            die("Leave failed: " . $e->getMessage());
        }
    }

    // redirect to index after leaving
    header("Location: index.php");
    exit;
}

// ===== 3) If student has NO club => show popup and stop page =====
$noClub = ($studentClubId === 1);

/* =========================
   Helper: Sponsor initials
========================= */
function makeInitials(string $name): string {
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '' || strtolower($name) === 'no sponsor yet') return 'SP';
    $parts = explode(' ', $name);
    $first = mb_substr($parts[0], 0, 1, 'UTF-8');
    $second = '';
    if (count($parts) > 1) $second = mb_substr($parts[1], 0, 1, 'UTF-8');
    else $second = mb_substr($parts[0], 1, 1, 'UTF-8');
    $ini = mb_strtoupper($first . $second, 'UTF-8');
    return $ini !== '' ? $ini : 'SP';
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — My Club</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* keep your existing styles as-is */
:root{
  --navy: #242751;
  --royal: #4871db;
  --light: #a9bff8;
  --paper: #eef2f7;
  --ink: #0e1228;
  --gold: #f4df6d;
  --white: #ffffff;
  --muted: #6b7280;
  --shadow:0 14px 34px rgba(10, 23, 60, .18);
  --radius:18px;
}

*{box-sizing:border-box}
html,body{margin:0}
body{
  font-family:"Raleway", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  color:var(--ink);
  background:
    radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
    radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%),
    var(--paper);
  line-height:1.5;
}

.section{padding:15px 20px}
.wrap{max-width:1100px; margin:0 auto}

.hero{
  padding:0 0 28px 0;
}
.hero-card{
  position:relative; overflow:hidden; border-radius:28px;
  box-shadow:var(--shadow);
  min-height:320px;
  display:flex; align-items:flex-end;
  background: none;
}
.hero-card::before{
  content:"";
  position:absolute; inset:0;
  background-image: var(--hero-bg, url("https://images.unsplash.com/photo-1531189611190-3c6c6b3c3d57?q=80&w=1650&auto=format&fit=crop"));
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  filter: grayscale(.12) contrast(1.03);
  opacity: .95;
}
.hero-card::after{
  content:"";
  position:absolute; inset:0;
  background: linear-gradient(180deg, rgba(36,39,81,.15) 0%,
                                        rgba(36,39,81,.35) 60%,
                                        rgba(36,39,81,.55) 100%);
  pointer-events:none;
}
.hero-top{
  position:absolute; left:24px; right:24px; top:20px;
  display:flex; justify-content:space-between; align-items:center; color:#fff;
  text-shadow:0 8px 26px rgba(0,0,0,.35);
}
.tag{
  background:rgba(244,223,109,.95); color:#2b2f55; font-weight:800;
  padding:8px 14px; border-radius:999px; font-size:12px;
}
.hero-pillrow{
  position:relative; width:100%; padding:18px; display:flex; gap:18px; flex-wrap:wrap;
}
.pill{
  flex:1 1 260px; display:flex; align-items:center; gap:14px;
  backdrop-filter: blur(6px);
  background:rgba(255,255,255,.82);
  border:1px solid rgba(255,255,255,.7);
  border-radius:20px; padding:12px 14px; color:#1d244d;
}
.circle{
  width:42px;height:42px;border-radius:50%;
  background:radial-gradient(circle at 30% 30%, #fff, #b9ccff);
  display:grid; place-items:center; font-weight:800; font-size:14px; color:#1d244d;
  border:2px solid rgba(255,255,255,.8);
}
.h-title{
  font-size:34px; letter-spacing:.35em; text-transform:uppercase; margin:34px 0 12px;
  text-align:left; color:#2b2f55;
}
.hr{
  height:3px; width:280px; background:#2b2f55; opacity:.35; border-radius:3px; margin:10px 0 24px;
}
.about{
  color: #fff;
  background:#4871db; margin-top:18px;
  border-radius:26px; padding:26px; box-shadow:var(--shadow);
}
.about p{max-width:800px; font-size:18px}
.link-grid{
  display:grid; grid-template-columns:repeat(3,1fr); gap:20px; max-width:720px; margin-top:18px;
}
.link-tile{
  display:flex; align-items:center; gap:12px;
  padding:12px 14px; border-radius:14px; background:#fff;color:#2b2f55; border:1px solid #e6e8f2;text-decoration:none;
}
.link-tile:hover{
   background: #f4df6d;transform :translateY(-10px);border-color:var(--royal);
}
.links{ font-weight:700 ;text-align:center; }

.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
@media (max-width:800px){ .grid{grid-template-columns:1fr} }

.card{
  background:#fff;border-radius:16px;box-shadow:0 14px 34px rgba(10,23,60,.12);
  padding:18px;display:grid;grid-template-columns:90px 1fr;gap:16px;
}
.date{
  display:flex;flex-direction:column;justify-content:center;align-items:center;
  background:#f2f5ff;border-radius:14px;padding:12px 10px;text-align:center;
  font-weight:800;min-height:90px;color:var(--navy);
}
.date .day{font-size:28px}
.date .mon{font-size:12px;margin-top:2px}
.date .sep{font-size:11px;color:#6b7280;margin-top:6px}
.topline{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.badge{background:#eaf6ee;color:#1f8f4e;padding:6px 10px;border-radius:12px;font-size:12px;font-weight:800}
.chip.sponsor{background:#fff7e6;color:#8a5b00;padding:6px 10px;border-radius:12px;font-size:12px;font-weight:700;border:1px solid #ffecb5}
.title{margin:8px 0 4px;font-weight:800;font-size:18px;color:var(--ink)}
.mini{color:#6b7280;font-size:13px;display:flex;gap:14px;flex-wrap:wrap}
.footer{margin-top:8px;font-size:13px;color:#6b7280;display:flex;align-items:center}
.stats{ margin-top:34px; display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
.stat{
  background:#fff; border-radius:18px; padding:18px; border:1px solid #e6e8f2; text-align:center;
  box-shadow:0 10px 24px rgba(10,23,60,.06);
}
.stat h5{margin:0 0 6px; letter-spacing:.25em; text-transform:uppercase; color: #2b2f55; font-size:13px}
.kpi{
  background:#f4df6d; border:2px #e5c94a; border-radius:14px; display:inline-block;
  padding:10px 18px; font-weight:900; font-size:22px; letter-spacing:.2em; margin-top:6px;
}
.leave{
  appearance:none; -webkit-appearance:none;
  width:100%; display:block;
  padding:14px 28px; line-height:1;
  font-size:18px; font-weight:900;
  border:none; border-radius:999px;
  background:#ff5e5e; color:#ffffff;
  box-shadow:0 12px 26px rgba(255,94,94,.35);
  cursor:pointer;
  margin:26px 0 0;
  text-align:center;
}
.leave:hover{
  background:#fff;
  color:#ff5e5e;
  box-shadow:0 12px 26px rgba(141,141,141,.35);
}
</style>
</head>

<body>
<?php include 'header.php'; ?>
<div class="underbar"></div>

<?php if ($noClub): ?>
  <script>
    if (typeof showNoClubPopup === "function") {
      showNoClubPopup('myclubpage.php');
    } else {
      alert("You haven’t joined a club yet. Please join a club first.");
      window.location.href = "discoverclubs.php";
    }
  </script>

  <?php include 'footer.php'; ?>
</body>
</html>
<?php exit; ?>
<?php endif; ?>

<?php
// ===== 4) Load club info =====
$stmtClub = $conn->prepare("SELECT * FROM club WHERE club_id = ? LIMIT 1");
$stmtClub->bind_param("i", $studentClubId);
$stmtClub->execute();
$resClub = $stmtClub->get_result();
$club = $resClub->fetch_assoc();
$stmtClub->close();

if (!$club) {
    die("Club not found.");
}

$clubName        = $club['club_name'] ?? 'Club';
$clubDescription = $club['description'] ?? '';

// ✅ logo from DB (fixed path)
$clubLogoRaw = !empty($club['logo']) ? $club['logo'] : "tools/pics/social_life.png";
$clubLogo    = img_path_student($clubLogoRaw);

// ✅ ✅ ONLY CHANGE FOR COVER: take cover from DB (try cover_image then banner_image), else keep fallback
$clubCoverRaw = '';
if (!empty($club['cover'])) {
    $clubCoverRaw = $club['cover'];
} elseif (!empty($club['banner_image'])) {
    $clubCoverRaw = $club['banner_image'];
}
$clubCover = ($clubCoverRaw !== '')
    ? img_path_student($clubCoverRaw)
    : "https://images.unsplash.com/photo-1531189611190-3c6c6b3c3d57?q=80&w=1650&auto=format&fit=crop";

$contactEmail    = $club['contact_email'] ?? '';
$facebookUrl     = !empty($club['facebook_url']) ? $club['facebook_url'] : "#";
$instagramUrl    = !empty($club['instagram_url']) ? $club['instagram_url'] : "#";
$linkedinUrl     = !empty($club['linkedin_url']) ? $club['linkedin_url'] : "#";
$memberCount     = (int)($club['member_count'] ?? 0);
$clubPoints      = (int)($club['points'] ?? 0);

/* =========================
   Fetch Sponsor for this club (club.sponsor_id)
========================= */
$sponsorName = 'No sponsor yet';
$sponsorInitials = 'SP';

$clubSponsorId = (int)($club['sponsor_id'] ?? 0);
if ($clubSponsorId > 0) {
    $stmtSp = $conn->prepare("SELECT company_name FROM sponsor WHERE sponsor_id = ? LIMIT 1");
    $stmtSp->bind_param("i", $clubSponsorId);
    $stmtSp->execute();
    $resSp = $stmtSp->get_result();
    if ($resSp && $resSp->num_rows > 0) {
        $sp = $resSp->fetch_assoc();
        $sponsorName = $sp['company_name'] ?? $sponsorName;
    }
    $stmtSp->close();
}
$sponsorInitials = makeInitials((string)$sponsorName);

// ===== 5) Club events =====
$events = [];
$stmtEv = $conn->prepare("
    SELECT *
    FROM event
    WHERE club_id = ?
    ORDER BY starting_date ASC
");
$stmtEv->bind_param("i", $studentClubId);
$stmtEv->execute();
$resEv = $stmtEv->get_result();
while ($row = $resEv->fetch_assoc()) $events[] = $row;
$stmtEv->close();

$eventsDone = count($events);
?>

<!-- ========== HERO ========== -->
<section class="section hero">
  <div class="wrap">
    <!-- ✅ ONLY CHANGE: cover comes from DB now -->
    <div class="hero-card" style="--hero-bg: url('<?php echo htmlspecialchars($clubCover); ?>');">

      <div class="hero-top">
        <div class="tag">
          <?php echo htmlspecialchars($club['category'] ?? 'Club'); ?>
        </div>
      </div>

      <div class="hero-pillrow">
        <div class="pill">
          <img src="<?php echo htmlspecialchars($clubLogo); ?>"
               alt="Club Logo"
               style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,.8)" />
          <div>
            <div style="font-size:12px;opacity:.8">club name</div>
            <strong><?php echo htmlspecialchars($clubName); ?></strong>
          </div>
        </div>

        <!-- Sponsor -->
        <div class="pill">
          <div class="circle"><?php echo htmlspecialchars($sponsorInitials); ?></div>
          <div>
            <div style="font-size:12px;opacity:.8">sponsor name</div>
            <strong><?php echo htmlspecialchars($sponsorName); ?></strong>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ========== ABOUT ========== -->
<section class="section">
  <div class="wrap">
    <h3 class="h-title" id="about">About Club</h3>
    <div class="hr"></div>

    <div class="about">
      <p><?php echo nl2br(htmlspecialchars($clubDescription)); ?></p>

      <div style="
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: 14px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 22px 0;
        color: #fff;
        box-shadow: inset 0 0 10px rgba(0,0,0,0.08);
      ">
        <svg viewBox='0 0 24 24' width='22' height='22' fill='none' stroke='#f4df6d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
          <path d='M4 4h16v16H4z' stroke='none'/>
          <path d='M4 4l8 8l8-8'/>
        </svg>
        <div>
          <div style="font-size:12px;opacity:.85;">President / Club Contact</div>
          <?php if (!empty($contactEmail)): ?>
            <strong><a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" style="color:#f4df6d; text-decoration:none;">
              <?php echo htmlspecialchars($contactEmail); ?>
            </a></strong>
          <?php else: ?>
            <strong>No contact email added yet.</strong>
          <?php endif; ?>
        </div>
      </div>

      <h4 style="letter-spacing:.4em; text-transform:uppercase; margin:16px 0 8px; color: #f4df6d">Links</h4>
      <div class="link-grid">
        <a class="link-tile" href="<?php echo htmlspecialchars($linkedinUrl ?: '#'); ?>" target="_blank" rel="noreferrer">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="#0a66c2" aria-hidden="true"><path d="M20.447 20.452h-3.555V14.86c0-1.333-.027-3.045-1.856-3.045-1.858 0-2.142 1.45-2.142 2.95v5.688H9.338V9h3.414v1.561h.048c.476-.9 1.637-1.85 3.369-1.85 3.602 0 4.268 2.371 4.268 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM6.99 20.452H3.68V9h3.31v11.452z"/></svg>
          <span class="links">LinkedIn</span>
        </a>
        <a class="link-tile" href="<?php echo htmlspecialchars($instagramUrl ?: '#'); ?>" target="_blank" rel="noreferrer">
          <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" style="color:#E4405F">
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/>
            <circle cx="12" cy="12" r="4.5" fill="none" stroke="currentColor" stroke-width="2"/>
            <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor"/>
          </svg>
          <span class="links">Instagram</span>
        </a>
        <a class="link-tile" href="<?php echo htmlspecialchars($facebookUrl ?: '#'); ?>" target="_blank" rel="noreferrer">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="#1877f2" aria-hidden="true"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06C2 17.08 5.66 21.2 10.44 22v-7.02H7.9v-2.92h2.54v-2.2c0-2.5 1.5-3.89 3.78-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.92h-2.34V22C18.34 21.2 22 17.08 22 12.06z"/></svg>
          <span class="links">Facebook</span>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ========== UPCOMING EVENTS ========== -->
<section class="section">
  <div class="wrap">
    <h2>Upcoming Events</h2>

    <div class="grid">
      <?php if (count($events) === 0): ?>
        <p style="color:#6b7280; font-size:14px; grid-column:1/-1;">
          No upcoming events have been added for this club yet.
        </p>
      <?php else: ?>
        <?php foreach ($events as $ev): ?>
          <?php
            $start = !empty($ev['starting_date']) ? new DateTime($ev['starting_date']) : null;
            $day  = $start ? $start->format('d') : '--';
            $mon  = $start ? strtoupper($start->format('M')) : '--';
            $dow  = $start ? $start->format('D') : '--';
            $time = $start ? $start->format('g:i A') : '--';
            $location = $ev['event_location'] ?? '';
          ?>
          <article class="card">
            <div class="date">
              <div class="day"><?php echo $day; ?></div>
              <div class="mon"><?php echo $mon; ?></div>
              <div class="sep"><?php echo $dow; ?></div>
            </div>
            <div>
              <div class="topline">
                <span class="chip sponsor">Club Event</span>
              </div>
              <div class="title"><?php echo htmlspecialchars($ev['event_name'] ?? 'Event'); ?></div>
              <div class="mini"><span>📍 <?php echo htmlspecialchars($location); ?></span></div>
              <div class="footer"><span class="mini">🕒 <?php echo $time; ?></span></div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="stats">
      <div class="stat">
        <h5>Events done</h5>
        <div class="kpi"><?php echo str_pad($eventsDone, 3, "0", STR_PAD_LEFT); ?></div>
      </div>
      <div class="stat">
        <h5>Member</h5>
        <div class="kpi"><?php echo str_pad($memberCount, 3, "0", STR_PAD_LEFT); ?></div>
      </div>
      <div class="stat">
        <h5>Earned points</h5>
        <div class="kpi"><?php echo str_pad($clubPoints, 4, "0", STR_PAD_LEFT); ?></div>
      </div>
    </div>

    <!-- Leave CTA -->
    <form method="post" style="margin-top:26px;">
      <button class="leave" type="submit" name="action" value="leave">
        Leave
      </button>
    </form>

  </div>
</section>

<div class="underbar"></div>
<?php include 'footer.php'; ?>

</body>
</html>
