<?php
session_start();
// president file
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$presidentId = (int)$_SESSION['student_id'];

// ===== Get president club_id (his own club) =====
$president_club_id = 0;
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id = ? LIMIT 1");
$stmt->bind_param("i", $presidentId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $president_club_id = (int)$row['club_id'];
}
$stmt->close();

// No club / not assigned (club_id=1 عندك)
if ($president_club_id <= 0 || $president_club_id == 1) {
    echo "<script>alert('You are not assigned to any club yet.'); location.href='index.php';</script>";
    exit;
}

// ===== Displayed club (from URL) =====
$display_club_id = (int)($_GET['club_id'] ?? $president_club_id);
if ($display_club_id <= 0) {
    $display_club_id = $president_club_id;
}

// ===== Can Edit? only if displayed club == president club =====
$canEdit = ($display_club_id === $president_club_id);

// ===== Fetch displayed club =====
$club = [];
$stmt = $conn->prepare("SELECT * FROM club WHERE club_id = ? LIMIT 1");
$stmt->bind_param("i", $display_club_id);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

// If invalid club_id in URL
if (empty($club)) {
    echo "<script>alert('Club not found.'); location.href='discoverclubs.php';</script>";
    exit;
}

$club_name     = $club['club_name'] ?? '—';
$description   = $club['description'] ?? '';
$category      = $club['category'] ?? '';
$contact_email = $club['contact_email'] ?? '';
$logo_url      = $club['logo'] ?? '';
$facebook      = $club['facebook_url'] ?? '';
$instagram     = $club['instagram_url'] ?? '';
$linkedin      = $club['linkedin_url'] ?? '';
$club_points   = (int)($club['points'] ?? 0);

// Default logo if empty
if (!$logo_url) {
    $logo_url = "tools/pics/social_life.png";
}

/* =========================
   Helper: Sponsor initials
========================= */
function makeInitials(string $name): string {
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '' || strtolower($name) === 'no sponsor yet') return 'SP';
    $parts = explode(' ', $name);
    $first = mb_substr($parts[0], 0, 1, 'UTF-8');
    $second = '';
    if (count($parts) > 1) {
        $second = mb_substr($parts[1], 0, 1, 'UTF-8');
    } else {
        $second = mb_substr($parts[0], 1, 1, 'UTF-8');
    }
    $ini = mb_strtoupper($first . $second, 'UTF-8');
    return $ini !== '' ? $ini : 'SP';
}

/* =========================
   NEW: Fetch CLUB sponsor (club.sponsor_id)
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

// ===== Members count (for displayed club) =====
$membersCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM student WHERE club_id = ?");
$stmt->bind_param("i", $display_club_id);
$stmt->execute();
$membersCount = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

// ===== Events Done (for displayed club) =====
$eventsDone = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM event WHERE club_id = ? AND ending_date < NOW()");
$stmt->bind_param("i", $display_club_id);
$stmt->execute();
$eventsDone = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

// ===== Upcoming Events (for displayed club) =====
$upcomingEvents = [];
$stmt = $conn->prepare("
  SELECT event_id, event_name, event_location, starting_date, ending_date
  FROM event
  WHERE club_id = ? AND starting_date >= NOW()
  ORDER BY starting_date ASC
  LIMIT 4
");
$stmt->bind_param("i", $display_club_id);
$stmt->execute();
$r = $stmt->get_result();
while ($e = $r->fetch_assoc()) $upcomingEvents[] = $e;
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Campus Clubs Hub — Club Page</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
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
.hero{ padding:0 0 28px 0; }
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
  background-image: var(--hero-bg, url("tools/pics/social_life.png"));
  background-size: cover; background-position: center; background-repeat: no-repeat;
  filter: grayscale(.12) contrast(1.03); opacity: .95;
}
.hero-card::after{
  content:""; position:absolute; inset:0;
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
.hero-top h1{margin:0; letter-spacing:.35em; font-size:32px}
.tag{
  background:rgba(244,223,109,.95); color:#2b2f55; font-weight:800;
  padding:8px 14px; border-radius:999px; font-size:12px;
}
.hero-actions{ display:flex; align-items:center; gap:10px; }
.edit-btn{
  display:inline-block; padding:10px 16px; border-radius:999px;
  font-weight:800; font-size:14px; text-decoration:none;
  color:#fff; background:linear-gradient(135deg,#5d7ff2,#3664e9);
  box-shadow:0 8px 20px rgba(54,100,233,.22);
}
.edit-btn:hover{ background:linear-gradient(135deg,#4d70ee,#2958e0); }

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
.hr{ height:3px; width:280px; background:#2b2f55; opacity:.35; border-radius:3px; margin:10px 0 24px; }

.about{
  color:#fff; background:#4871db; margin-top:18px;
  border-radius:26px; padding:26px; box-shadow:var(--shadow);
}
.about p{max-width:800px; font-size:18px}
.link-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:20px; max-width:720px; margin-top:18px; }
.link-tile{ display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:14px; background:#fff;color:#2b2f55; border:1px solid #e6e8f2;text-decoration:none; }
.link-tile svg{flex:0 0 22px}
.link-tile:hover{ background:#f4df6d;transform:translateY(-10px);border-color:var(--royal); }
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
</style>
</head>

<body>
  <?php include 'header.php'; ?>
  <div class="underbar"></div>

  <section class="section hero">
    <div class="wrap">
      <div class="hero-card" style="--hero-bg: url('tools/pics/social_life.png');">
        <div class="hero-top">
          <h1>CLUB PAGE</h1>
          <div class="hero-actions">
            <div class="tag"><?php echo htmlspecialchars($category ?: 'Club'); ?></div>

            <?php if ($canEdit): ?>
              <a href="clubedit.php" class="edit-btn">Edit</a>
            <?php endif; ?>

          </div>
        </div>

        <div class="hero-pillrow">
          <div class="pill">
            <img src="<?php echo htmlspecialchars($logo_url); ?>"
                alt="Club Logo"
                style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,.8)" />
            <div>
              <div style="font-size:12px;opacity:.8">club name</div>
              <strong id="clubs"><?php echo htmlspecialchars($club_name); ?></strong>
            </div>
          </div>

          <!-- UPDATED PILL: Sponsor -->
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

  <section class="section">
    <div class="wrap">
      <h3 class="h-title" id="about">About Club</h3>
      <div class="hr"></div>

      <div class="about">
        <p><?php echo nl2br(htmlspecialchars($description)); ?></p>

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
            <div style="font-size:12px;opacity:.85;">President Contact</div>
            <strong>
              <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" style="color:#f4df6d; text-decoration:none;">
                <?php echo htmlspecialchars($contact_email ?: '—'); ?>
              </a>
            </strong>
          </div>
        </div>

        <h4 style="letter-spacing:.4em; text-transform:uppercase; margin:16px 0 8px; color: #f4df6d">Links</h4>
        <div class="link-grid">
          <a class="link-tile" href="<?php echo $linkedin ? htmlspecialchars($linkedin) : '#'; ?>" target="_blank" rel="noreferrer">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="#0a66c2" aria-hidden="true"><path d="M20.447 20.452h-3.555V14.86c0-1.333-.027-3.045-1.856-3.045-1.858 0-2.142 1.45-2.142 2.95v5.688H9.338V9h3.414v1.561h.048c.476-.9 1.637-1.85 3.369-1.85 3.602 0 4.268 2.371 4.268 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM6.99 20.452H3.68V9h3.31v11.452z"/></svg>
            <span class="links">LinkedIn</span>
          </a>
          <a class="link-tile" href="<?php echo $instagram ? htmlspecialchars($instagram) : '#'; ?>" target="_blank" rel="noreferrer">
            <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" style="color:#E4405F">
              <rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/>
              <circle cx="12" cy="12" r="4.5" fill="none" stroke="currentColor" stroke-width="2"/>
              <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor"/>
            </svg>
            <span class="links">Instagram</span>
          </a>
          <a class="link-tile" href="<?php echo $facebook ? htmlspecialchars($facebook) : '#'; ?>" target="_blank" rel="noreferrer">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="#1877f2" aria-hidden="true"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06C2 17.08 5.66 21.2 10.44 22v-7.02H7.9v-2.92h2.54v-2.2c0-2.5 1.5-3.89 3.78-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.92h-2.34V22C18.34 21.2 22 17.08 22 12.06z"/></svg>
            <span class="links">Facebook</span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="wrap">
      <h2>Upcoming Events</h2>

      <div class="grid">
        <?php if(empty($upcomingEvents)): ?>
          <article class="card" style="grid-column:1/-1">
            <div class="date">
              <div class="day">—</div><div class="mon">—</div><div class="sep">—</div>
            </div>
            <div>
              <div class="title">No upcoming events</div>
              <div class="mini"><span>📍 No events scheduled.</span></div>
              <div class="footer"><span class="mini">🕒 —</span></div>
            </div>
          </article>
        <?php else: ?>
          <?php foreach($upcomingEvents as $e):
            $start = new DateTime($e['starting_date']);
            $day = $start->format('d');
            $mon = strtoupper($start->format('M'));
            $wk  = $start->format('D');
            $time= $start->format('g:i A');
          ?>
            <article class="card">
              <div class="date"><div class="day"><?php echo $day; ?></div><div class="mon"><?php echo $mon; ?></div><div class="sep"><?php echo $wk; ?></div></div>
              <div>
                <div class="title"><?php echo htmlspecialchars($e['event_name']); ?></div>
                <div class="mini"><span>📍 <?php echo htmlspecialchars($e['event_location'] ?? '—'); ?></span></div>
                <div class="footer"><span class="mini">🕒 <?php echo $time; ?></span></div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="stats">
        <div class="stat">
          <h5>Events done</h5>
          <div class="kpi"><?php echo (int)$eventsDone; ?></div>
        </div>
        <div class="stat">
          <h5>Member</h5>
          <div class="kpi"><?php echo (int)$membersCount; ?></div>
        </div>
        <div class="stat">
          <h5>Earned points</h5>
          <div class="kpi"><?php echo (int)$club_points; ?></div>
        </div>
      </div>

    </div>
  </section>

  <div class="underbar"></div>
  <?php include 'footer.php'; ?>
</body>
</html>
