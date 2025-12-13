<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php'; // عدّلي المسار إذا الملف بمكان ثاني

// ====================== Get club_id from URL ======================
$clubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

if ($clubId <= 0) {
    header('Location: viewclubs.php');
    exit;
}

/* =========================
   Handle POST Actions (DB)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['club_id'])) {
    $action = $_POST['action'];
    $postClubId = (int)$_POST['club_id'];

    if ($postClubId <= 0) {
        header("Location: clubpage.php?club_id=" . (int)$clubId);
        exit;
    }

    // ✅ Delete club (connected)
    if ($action === 'delete_club') {
        // prevent deleting default club
        if ($postClubId === 1) {
            echo "<script>alert('You cannot delete the default club (ID = 1).'); window.location.href='clubpage.php?club_id={$clubId}';</script>";
            exit;
        }

        $conn->begin_transaction();
        try {
            // 1) delete ranking rows
            $stmt = $conn->prepare("DELETE FROM ranking WHERE club_id = ?");
            $stmt->bind_param("i", $postClubId);
            $stmt->execute();
            $stmt->close();

            // 2) delete events
            $stmt = $conn->prepare("DELETE FROM event WHERE club_id = ?");
            $stmt->bind_param("i", $postClubId);
            $stmt->execute();
            $stmt->close();

            // 3) move students to default club_id=1
            $defaultClubId = 1;
            $stmt = $conn->prepare("UPDATE student SET club_id = ? WHERE club_id = ?");
            $stmt->bind_param("ii", $defaultClubId, $postClubId);
            $stmt->execute();
            $stmt->close();

            // 4) delete club
            $stmt = $conn->prepare("DELETE FROM club WHERE club_id = ? LIMIT 1");
            $stmt->bind_param("i", $postClubId);
            $stmt->execute();

            if ($stmt->affected_rows <= 0) {
                $stmt->close();
                throw new Exception("Club not found or could not be deleted.");
            }
            $stmt->close();

            $conn->commit();

            echo "<script>alert('Club deleted successfully.'); window.location.href='viewclubs.php';</script>";
            exit;

        } catch (Throwable $e) {
            $conn->rollback();
            echo "<script>alert('Delete failed: " . addslashes($e->getMessage()) . "'); window.location.href='clubpage.php?club_id={$clubId}';</script>";
            exit;
        }
    }
}

// ====================== Fetch club from DB ======================
$stmt = $conn->prepare("
    SELECT 
        c.club_id,
        c.club_name,
        c.description,
        c.category,
        c.social_media_link,
        c.facebook_url,
        c.instagram_url,
        c.linkedin_url,
        c.logo,
        c.creation_date,
        c.status,
        c.contact_email,
        c.member_count,
        c.sponsor_id,
        COALESCE(sp.company_name, '') AS sponsor_name
    FROM club c
    LEFT JOIN sponsor sp ON sp.sponsor_id = c.sponsor_id
    WHERE c.club_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $clubId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo "<h2 style='font-family:system-ui;margin:40px;'>Club not found.</h2>";
    exit;
}

/* ======================
   Points from ranking (latest)
====================== */
$points = 0;
$stmtPts = $conn->prepare("
    SELECT r.total_points
    FROM ranking r
    WHERE r.club_id = ?
    ORDER BY r.period_end DESC, r.period_start DESC, r.ranking_id DESC
    LIMIT 1
");
$stmtPts->bind_param("i", $clubId);
$stmtPts->execute();
$resPts = $stmtPts->get_result();
if ($p = $resPts->fetch_assoc()) {
    $points = (int)($p['total_points'] ?? 0);
}
$stmtPts->close();

/* ======================
   Done/Past events COUNT (ending_date < NOW)
====================== */
$doneEventsCount = 0;
$stmtCnt = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM event
    WHERE club_id = ?
      AND ending_date IS NOT NULL
      AND ending_date < NOW()
");
$stmtCnt->bind_param("i", $clubId);
$stmtCnt->execute();
$resCnt = $stmtCnt->get_result();
if ($c = $resCnt->fetch_assoc()) {
    $doneEventsCount = (int)($c['cnt'] ?? 0);
}
$stmtCnt->close();

// Mapping
$sponsorName = trim($row['sponsor_name'] ?? '');
if ($sponsorName === '') $sponsorName = "No sponsor yet";

$club = [
    "id"              => (int)$row['club_id'],
    "name"            => $row['club_name'],
    "category"        => ($row['category'] ?: 'Uncategorized'),
    "sponsor"         => $sponsorName,
    "status"          => (strtolower(trim($row['status'])) === 'active') ? 'Active' : 'Inactive',
    "members"         => (int)($row['member_count'] ?? 0),
    "events_count"    => $doneEventsCount, // ✅ done events only
    "points"          => $points,          // ✅ from ranking
    "president_email" => ($row['contact_email'] ?: 'no-email@unihive'),
    "description"     => ($row['description'] ?: 'No description provided yet.'),
    "logo"            => (!empty($row['logo']) ? $row['logo'] : 'assets/club_placeholder.png'),
];

// ====================== Fetch DONE/Past events for this club ======================
$events = [];

$eventStmt = $conn->prepare("
    SELECT
        e.event_id,
        e.event_name,
        e.event_location,
        e.starting_date,
        e.ending_date,
        e.attendees_count,
        COALESCE(s.company_name,'–') AS sponsor_name
    FROM event e
    LEFT JOIN sponsor s ON s.sponsor_id = e.sponsor_id
    WHERE e.club_id = ?
      AND e.ending_date IS NOT NULL
      AND e.ending_date < NOW()
    ORDER BY e.ending_date DESC
");
$eventStmt->bind_param("i", $clubId);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();

while ($e = $eventResult->fetch_assoc()) {
    $start = new DateTime($e['starting_date']);

    $events[] = [
        "day"      => $start->format('d'),
        "month"    => strtoupper($start->format('M')),
        "weekday"  => $start->format('D'),
        "title"    => $e['event_name'],
        "location" => $e['event_location'],
        "time"     => $start->format('g:i A'),
        "points"   => ($e['attendees_count'] ?? 0) . " attending",
        "sponsor"  => ($e['sponsor_name'] ?? "–"),
    ];
}
$eventStmt->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>UniHive — Club details</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751;
  --coral:#ff5e5e;
  --paper:#eef2f7;
  --card:#ffffff;
  --ink:#0e1228;
  --muted:#6b7280;
  --shadow:0 14px 34px rgba(10,23,60,.16);
  --radius:20px;

  --sidebarWidth:260px;
}

*{box-sizing:border-box;margin:0;padding:0}

body{
  margin:0;
  background:var(--paper);
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
}

/* ===== Layout with sidebar ===== */
.content{
  margin-left:var(--sidebarWidth);
  padding:40px 50px 60px;
}

/* ===== Page header ===== */
.page-header{
  display:flex;
  justify-content:space-between;
  align-items:flex-end;
  gap:16px;
  margin-bottom:24px;
}

.page-title{
  font-size:1.9rem;
  font-weight:800;
  color:var(--ink);
}

.breadcrumbs{
  font-size:.85rem;
  color:var(--muted);
}

.breadcrumbs a{
  color:var(--muted);
  text-decoration:none;
}

.breadcrumbs a:hover{
  text-decoration:underline;
}

/* ===== Top club card ===== */
.club-header-card{
  background:var(--card);
  border-radius:var(--radius);
  padding:20px 22px;
  box-shadow:var(--shadow);
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:18px;
  margin-bottom:26px;
}

.club-main{
  display:flex;
  gap:16px;
}

.club-logo{
  width:56px;
  height:56px;
  border-radius:50%;
  background:#e5e7eb;
  object-fit:cover;
  flex-shrink:0;
}

.club-text{
  display:flex;
  flex-direction:column;
  gap:4px;
}

.club-name{
  font-size:1.4rem;
  font-weight:800;
}

.club-meta{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  font-size:.88rem;
  color:var(--muted);
}

.pill{
  display:inline-flex;
  align-items:center;
  padding:6px 12px;
  border-radius:999px;
  font-size:.8rem;
  font-weight:700;
}

.pill-category{
  background:#f3f4f6;
  color:#374151;
}

.pill-sponsor{
  background:#ffe3e3;
  color:#b91c1c;
}

/* status */
.pill-status-active{
  background:#ecfdf3;
  color:#166534;
}

.pill-status-inactive{
  background:#f3f4f6;
  color:#6b7280;
}

/* header actions (buttons) */
.header-actions{
  display:flex;
  flex-direction:column;
  align-items:flex-end;
  gap:10px;
}

.tags-row{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
  justify-content:flex-end;
}

.actions-row{
  display:flex;
  gap:8px;
}

.btn{
  padding:9px 14px;
  border-radius:999px;
  border:none;
  cursor:pointer;
  font-size:.85rem;
  font-weight:700;
  transition:background .15s ease, color .15s ease, transform .08s ease;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  justify-content:center;
}

.btn-primary{
  background:var(--navy);
  color:#ffffff;
}

.btn-primary:hover{
  background:#181b3b;
  transform:translateY(-1px);
}

.btn-outline{
  background:#ffffff;
  color:var(--navy);
  border:1px solid rgba(36,39,81,0.26);
}

.btn-outline:hover{
  background:#f3f4f6;
}

.btn-danger{
  background:#ffe3e3;
  color:#b91c1c;
}

.btn-danger:hover{
  background:var(--coral);
  color:#ffffff;
}

/* ===== Body layout ===== */
.layout{
  display:grid;
  grid-template-columns:2fr 1fr;
  gap:22px;
  margin-bottom:28px;
}

@media(max-width:960px){
  .layout{
    grid-template-columns:1fr;
  }
}

/* About card */
.card{
  background:var(--card);
  border-radius:var(--radius);
  padding:20px 22px;
  box-shadow:var(--shadow);
}

.card-title{
  font-size:1rem;
  font-weight:800;
  margin-bottom:8px;
}

.card-subtitle{
  font-size:.85rem;
  color:var(--muted);
  margin-bottom:14px;
}

.card p{
  font-size:.95rem;
  color:#111827;
}

/* President contact box */
.president-box{
  margin-top:18px;
  border-radius:14px;
  padding:12px 14px;
  background:#f9fafb;
  border:1px solid #e5e7eb;
  display:flex;
  align-items:center;
  gap:10px;
}

.president-box-icon{
  width:32px;
  height:32px;
  border-radius:50%;
  background:#ecfdf5;
  display:grid;
  place-items:center;
  flex-shrink:0;
}

/* Stats card */
.stats-grid{
  display:grid;
  grid-template-columns:1fr;
  gap:12px;
}

.stat-item{
  padding:12px 14px;
  border-radius:14px;
  background:#f9fafb;
  border:1px solid #e5e7eb;
}

.stat-label{
  font-size:.8rem;
  text-transform:uppercase;
  letter-spacing:.12em;
  color:var(--muted);
  margin-bottom:4px;
}

.stat-value{
  font-size:1.2rem;
  font-weight:800;
}

/* ===== Events ===== */
.section-block{
  margin-top:6px;
}

.section-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:10px;
}

.section-header h2{
  font-size:1rem;
  font-weight:800;
}

.section-header span{
  font-size:.85rem;
  color:var(--muted);
}

.events-grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:14px;
}

@media(max-width:900px){
  .events-grid{
    grid-template-columns:1fr;
  }
}

.event-card{
  background:#f9fafb;
  border-radius:14px;
  border:1px solid #e5e7eb;
  padding:12px 14px;
  display:grid;
  grid-template-columns:70px 1fr;
  gap:12px;
}

.event-date{
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  border-radius:12px;
  background:#eef2ff;
  color:var(--navy);
  padding:10px 6px;
  font-weight:800;
  min-height:80px;
}

.event-date .day{
  font-size:22px;
}

.event-date .mon{
  font-size:11px;
}

.event-date .weekday{
  font-size:11px;
  margin-top:4px;
  color:var(--muted);
}

.event-body{
  display:flex;
  flex-direction:column;
  gap:4px;
}

.event-topline{
  display:flex;
  flex-wrap:wrap;
  gap:6px;
  font-size:11px;
}

.event-badge{
  padding:4px 8px;
  border-radius:999px;
  background:#ecfdf3;
  color:#166534;
  font-weight:700;
}

.event-sponsor{
  padding:4px 8px;
  border-radius:999px;
  background:#fff7ed;
  color:#92400e;
  font-weight:600;
  border:1px solid #fed7aa;
}

.event-title{
  font-size:.98rem;
  font-weight:700;
}

.event-meta{
  font-size:.85rem;
  color:var(--muted);
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">

  <!-- Page header -->
  <div class="page-header">
    <div>
      <div class="breadcrumbs">
        <a href="index.php">Dashboard</a> ·
        <a href="viewclubs.php">Clubs</a> ·
        <span><?= htmlspecialchars($club['name']); ?></span>
      </div>
      <div class="page-title"><?= htmlspecialchars($club['name']); ?></div>
    </div>
  </div>

  <!-- Top club info card -->
  <div class="club-header-card">
    <div class="club-main">
      <img src="<?= htmlspecialchars($club['logo']); ?>"
           alt="Club logo" class="club-logo">

      <div class="club-text">
        <div class="club-name"><?= htmlspecialchars($club['name']); ?></div>
        <div class="club-meta">
          <span><?= htmlspecialchars($club['category']); ?></span>
        </div>

        <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px;">
          <span class="pill pill-category">
            Category: <?= htmlspecialchars($club['category']); ?>
          </span>
          <span class="pill pill-sponsor">
            Sponsor: <?= htmlspecialchars($club['sponsor']); ?>
          </span>
          <span class="pill <?= $club['status'] === 'Active' ? 'pill-status-active' : 'pill-status-inactive'; ?>">
            <?= htmlspecialchars($club['status']); ?>
          </span>
        </div>
      </div>
    </div>

    <div class="header-actions">
      <div class="tags-row">
        <span style="font-size:.8rem;color:var(--muted);">
          ID #<?= $club['id']; ?>
        </span>
      </div>

      <div class="actions-row">
        <a href="editclub.php?club_id=<?= $club['id']; ?>" class="btn btn-outline">Edit club</a>
        <a href="viewmembers.php?club_id=<?= $club['id']; ?>" class="btn btn-primary">View members</a>

        <!-- ✅ Delete connected -->
        <form method="POST" action="clubpage.php?club_id=<?= (int)$club['id']; ?>" style="display:inline;">
          <input type="hidden" name="action" value="delete_club">
          <input type="hidden" name="club_id" value="<?= (int)$club['id']; ?>">
          <button class="btn btn-danger" type="submit"
            onclick="return confirm('Are you sure you want to delete this club? This will delete its events & ranking and move its students to the default club.')">
            Delete
          </button>
        </form>

      </div>
    </div>
  </div>

  <!-- Main layout: about + stats -->
  <div class="layout">

    <!-- About + president contact -->
    <div class="card">
      <div class="card-title">About this club</div>
      <div class="card-subtitle">Description provided by the club president.</div>
      <p>
        <?= nl2br(htmlspecialchars($club['description'])); ?>
      </p>

      <div class="president-box">
        <div class="president-box-icon">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M4 4h16v16H4z" fill="none"/>
            <path d="M4 4l8 8l8-8" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div>
          <div style="font-size:.8rem;color:var(--muted);">President contact</div>
          <a href="mailto:<?= htmlspecialchars($club['president_email']); ?>"
             style="font-weight:700;color:var(--navy);text-decoration:none;">
            <?= htmlspecialchars($club['president_email']); ?>
          </a>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="card">
      <div class="card-title">Overview</div>
      <div class="card-subtitle">Key numbers for this club.</div>

      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-label">Members</div>
          <div class="stat-value"><?= $club['members']; ?></div>
        </div>
        <div class="stat-item">
          <div class="stat-label">Events (done)</div>
          <div class="stat-value"><?= $club['events_count']; ?></div>
        </div>
        <div class="stat-item">
          <div class="stat-label">Total points</div>
          <div class="stat-value"><?= $club['points']; ?></div>
        </div>
      </div>
    </div>

  </div>

  <!-- DONE events -->
  <div class="card section-block">
    <div class="section-header">
      <h2>Done events</h2>
      <span><?= count($events); ?> finished</span>
    </div>

    <?php if (empty($events)): ?>
      <p style="font-size:.95rem;color:var(--muted);margin:4px 0 0;">
        No done events for this club.
      </p>
    <?php else: ?>
      <div class="events-grid">
        <?php foreach($events as $e): ?>
          <article class="event-card">
            <div class="event-date">
              <div class="day"><?= htmlspecialchars($e['day']); ?></div>
              <div class="mon"><?= htmlspecialchars($e['month']); ?></div>
              <div class="weekday"><?= htmlspecialchars($e['weekday']); ?></div>
            </div>
            <div class="event-body">
              <div class="event-topline">
                <span class="event-badge"><?= htmlspecialchars($e['points']); ?></span>
                <span class="event-sponsor">Sponsor: <?= htmlspecialchars($e['sponsor']); ?></span>
              </div>
              <div class="event-title"><?= htmlspecialchars($e['title']); ?></div>
              <div class="event-meta">📍 <?= htmlspecialchars($e['location']); ?></div>
              <div class="event-meta">🕒 <?= htmlspecialchars($e['time']); ?></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

</body>
</html>
