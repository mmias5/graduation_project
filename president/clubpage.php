<?php
session_start();
// user file
if (!isset($_SESSION['student_id']) || ($_SESSION['role'] ?? '') !== 'club_president') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
        header('Location: ../president/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$studentId = (int)$_SESSION['student_id'];

/* =========================
   FLASH (Swal after redirect)
========================= */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* =========================
   1) Fetch student club_id
========================= */
$stmtStu = $conn->prepare("SELECT club_id FROM student WHERE student_id = ? LIMIT 1");
$stmtStu->bind_param("i", $studentId);
$stmtStu->execute();
$stmtStu->bind_result($studentClubIdDb);
$stmtStu->fetch();
$stmtStu->close();

$studentClubId = (int)($studentClubIdDb ?? 1);
$studentHasClub = ($studentClubId !== 1);

/* =========================
   2) clubpage MUST have club_id (coming from discover)
========================= */
if (!isset($_GET['club_id']) || (int)$_GET['club_id'] < 1) {
    header("Location: discoverclubs.php");
    exit;
}
$clubId = (int)$_GET['club_id'];
if ($clubId === 1) {
    header("Location: discoverclubs.php");
    exit;
}

/* =========================
   3) Handle JOIN (POST)
   - Save reason
   - Prevent duplicate pending for same club
   - Block if student already has club
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'join') {

    // student already in a club -> block
    if ($studentHasClub) {
        $_SESSION['flash'] = [
            'icon' => 'info',
            'title' => 'You are already in a club',
            'text'  => 'You can’t request to join another club unless you leave your current club first.'
        ];
        header("Location: clubpage.php?club_id=" . (int)$clubId);
        exit;
    }

   // ✅ always trust the club_id from URL
$postedClubId = $clubId;


    if ($reason === '') {
        $_SESSION['flash'] = [
            'icon' => 'warning',
            'title' => 'Reason required',
            'text'  => 'Please write why you want to join this club.'
        ];
        header("Location: clubpage.php?club_id=" . (int)$clubId);
        exit;
    }

    // ✅ check if there is already a pending request for this club
    $stmtCheck = $conn->prepare("
        SELECT request_id
        FROM club_membership_request
        WHERE club_id = ? AND student_id = ? AND status = 'pending'
        LIMIT 1
    ");
    $stmtCheck->bind_param("ii", $clubId, $studentId);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $hasPending = ($resCheck && $resCheck->num_rows > 0);
    $stmtCheck->close();

    if ($hasPending) {
        $_SESSION['flash'] = [
            'icon' => 'info',
            'title' => 'Request already pending',
            'text'  => 'You already submitted a join request for this club. Please wait for approval.'
        ];
        header("Location: clubpage.php?club_id=" . (int)$clubId);
        exit;
    }

    // ✅ insert request with reason
    $stmtIns = $conn->prepare("
        INSERT INTO club_membership_request (club_id, student_id, reason, status, submitted_at)
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmtIns->bind_param("iis", $clubId, $studentId, $reason);
    $stmtIns->execute();
    $stmtIns->close();

    $_SESSION['flash'] = [
        'icon' => 'success',
        'title' => 'Request submitted',
        'text'  => 'Your request is under review.'
    ];
    header("Location: clubpage.php?club_id=" . (int)$clubId);
    exit;
}

/* =========================
   4) Fetch club
========================= */
$stmtClub = $conn->prepare("SELECT * FROM club WHERE club_id = ? LIMIT 1");
$stmtClub->bind_param("i", $clubId);
$stmtClub->execute();
$resClub = $stmtClub->get_result();
$club = $resClub->fetch_assoc();
$stmtClub->close();

if (!$club) {
    header("Location: discoverclubs.php");
    exit;
}

$clubName        = $club['club_name'] ?? 'Club';
$clubDescription = $club['description'] ?? '';
$clubLogo        = !empty($club['logo']) ? $club['logo'] : "tools/pics/social_life.png";
$contactEmail    = $club['contact_email'] ?? '';
$facebookUrl     = !empty($club['facebook_url']) ? $club['facebook_url'] : "#";
$instagramUrl    = !empty($club['instagram_url']) ? $club['instagram_url'] : "#";
$linkedinUrl     = !empty($club['linkedin_url']) ? $club['linkedin_url'] : "#";
$memberCount     = (int)($club['member_count'] ?? 0);
$clubPoints      = (int)($club['points'] ?? 0);

/* =========================
   5) Last request status for this club (for button label)
========================= */
$stmtLastReq = $conn->prepare("
    SELECT status
    FROM club_membership_request
    WHERE club_id = ? AND student_id = ?
    ORDER BY submitted_at DESC, request_id DESC
    LIMIT 1
");
$stmtLastReq->bind_param("ii", $clubId, $studentId);
$stmtLastReq->execute();
$resLastReq = $stmtLastReq->get_result();
$lastReqRow = $resLastReq->fetch_assoc();
$stmtLastReq->close();

$lastReqStatus = $lastReqRow['status'] ?? null;

/* =========================
   6) Events for club
========================= */
$events = [];
$stmtEv = $conn->prepare("
    SELECT *
    FROM event
    WHERE club_id = ?
    ORDER BY starting_date ASC
");
$stmtEv->bind_param("i", $clubId);
$stmtEv->execute();
$resEv = $stmtEv->get_result();
while ($row = $resEv->fetch_assoc()) {
    $events[] = $row;
}
$stmtEv->close();

$eventsDone = count($events);

// show Join only if student not in a club
$canRequestJoin = !$studentHasClub;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Club Details</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751; --royal:#4871db; --light:#a9bff8;
  --paper:#eef2f7; --ink:#0e1228; --gold:#f4df6d;
  --muted:#6b7280; --shadow:0 14px 34px rgba(10,23,60,.18);
}
*{box-sizing:border-box}
html,body{margin:0}
body{
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  background:
    radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
    radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%),
    var(--paper);
  line-height:1.5;
}
.section{padding:15px 20px}
.wrap{max-width:1100px;margin:0 auto}

/* HERO */
.hero{padding:0 0 28px 0;}
.hero-card{
  position:relative; overflow:hidden; border-radius:28px;
  box-shadow:var(--shadow);
  min-height:320px;
  display:flex; align-items:flex-end;
}
.hero-card::before{
  content:""; position:absolute; inset:0;
  background-image: var(--hero-bg, url("https://images.unsplash.com/photo-1531189611190-3c6c6b3c3d57?q=80&w=1650&auto=format&fit=crop"));
  background-size:cover; background-position:center; opacity:.95;
}
.hero-card::after{
  content:""; position:absolute; inset:0;
  background: linear-gradient(180deg, rgba(36,39,81,.15) 0%,
                              rgba(36,39,81,.35) 60%,
                              rgba(36,39,81,.55) 100%);
}
.hero-top{position:absolute; left:24px; right:24px; top:20px; display:flex; justify-content:space-between; align-items:center; color:#fff;}
.tag{background:rgba(244,223,109,.95); color:#2b2f55; font-weight:800; padding:8px 14px; border-radius:999px; font-size:12px;}
.hero-pillrow{position:relative; width:100%; padding:18px; display:flex; gap:18px; flex-wrap:wrap;}
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
  display:grid; place-items:center; font-weight:800; color:#1d244d;
  border:2px solid rgba(255,255,255,.8);
}

/* ABOUT */
.h-title{font-size:34px; letter-spacing:.35em; text-transform:uppercase; margin:34px 0 12px; color:#2b2f55;}
.hr{height:3px; width:280px; background:#2b2f55; opacity:.35; border-radius:3px; margin:10px 0 24px;}
.about{color:#fff; background:#4871db; margin-top:18px; border-radius:26px; padding:26px; box-shadow:var(--shadow);}
.about p{max-width:800px; font-size:18px}
.link-grid{display:grid; grid-template-columns:repeat(3,1fr); gap:20px; max-width:720px; margin-top:18px;}
.link-tile{display:flex; align-items:center; justify-content:center; padding:12px 14px; border-radius:14px; background:#fff; color:#2b2f55; border:1px solid #e6e8f2; text-decoration:none; font-weight:800;}
.link-tile:hover{background:#f4df6d; transform:translateY(-6px); border-color:var(--royal);}

/* EVENTS */
.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
@media (max-width:800px){ .grid{grid-template-columns:1fr} }
.card{background:#fff;border-radius:16px;box-shadow:0 14px 34px rgba(10,23,60,.12);padding:18px;display:grid;grid-template-columns:90px 1fr;gap:16px;}
.date{display:flex;flex-direction:column;justify-content:center;align-items:center;background:#f2f5ff;border-radius:14px;padding:12px 10px;text-align:center;font-weight:800;min-height:90px;color:var(--navy);}
.date .day{font-size:28px}
.date .mon{font-size:12px;margin-top:2px}
.date .sep{font-size:11px;color:#6b7280;margin-top:6px}
.topline{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.badge{background:#eaf6ee;color:#1f8f4e;padding:6px 10px;border-radius:12px;font-size:12px;font-weight:800}
.chip.sponsor{background:#fff7e6;color:#8a5b00;padding:6px 10px;border-radius:12px;font-size:12px;font-weight:700;border:1px solid #ffecb5}
.title{margin:8px 0 4px;font-weight:800;font-size:18px;color:var(--ink)}
.mini{color:#6b7280;font-size:13px;display:flex;gap:14px;flex-wrap:wrap}
.footer{margin-top:8px;font-size:13px;color:#6b7280;display:flex;align-items:center}

/* STATS + CTA */
.stats{ margin-top:34px; display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
.stat{background:#fff; border-radius:18px; padding:18px; border:1px solid #e6e8f2; text-align:center; box-shadow:0 10px 24px rgba(10,23,60,.06);}
.stat h5{margin:0 0 6px; letter-spacing:.25em; text-transform:uppercase; color:#2b2f55; font-size:13px}
.kpi{background:#f4df6d; border-radius:14px; display:inline-block; padding:10px 18px; font-weight:900; font-size:22px; letter-spacing:.2em; margin-top:6px;}

.join{
  width:100%; display:block; padding:14px 28px;
  font-size:18px; font-weight:900;
  border:none; border-radius:999px;
  background:#f4df6d; color:#2b2f55;
  box-shadow:0 12px 26px rgba(255,213,1,.35);
  cursor:pointer; margin:26px 0 0;
}
.join:hover{background:#fff;}
.join[disabled]{opacity:.6;cursor:not-allowed;}

.notice{
  margin-top:18px; padding:14px 16px;
  border-radius:14px; background:#fff;
  border:1px solid #e6e8f2; font-weight:800; color:#374151;
}
</style>
</head>

<body>
<?php include 'header.php'; ?>
<div class="underbar"></div>

<section class="section hero">
  <div class="wrap">
    <div class="hero-card" style="--hero-bg: url('<?php echo htmlspecialchars($clubLogo); ?>');">
      <div class="hero-top">
        <div class="tag"><?php echo htmlspecialchars($club['category'] ?? 'Club'); ?></div>
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

        <div class="pill">
          <div class="circle"><?php echo (int)$memberCount; ?></div>
          <div>
            <div style="font-size:12px;opacity:.8">active members</div>
            <strong><?php echo htmlspecialchars($clubName); ?> Community</strong>
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
        color: #fff;">
        <div>
          <div style="font-size:12px;opacity:.85;">President / Club Contact</div>
          <?php if (!empty($contactEmail)): ?>
            <strong>
              <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" style="color:#f4df6d; text-decoration:none;">
                <?php echo htmlspecialchars($contactEmail); ?>
              </a>
            </strong>
          <?php else: ?>
            <strong>No contact email added yet.</strong>
          <?php endif; ?>
        </div>
      </div>

      <h4 style="letter-spacing:.4em; text-transform:uppercase; margin:16px 0 8px; color:#f4df6d">Links</h4>
      <div class="link-grid">
        <a class="link-tile" href="<?php echo htmlspecialchars($linkedinUrl ?: '#'); ?>" target="_blank" rel="noreferrer">LinkedIn</a>
        <a class="link-tile" href="<?php echo htmlspecialchars($instagramUrl ?: '#'); ?>" target="_blank" rel="noreferrer">Instagram</a>
        <a class="link-tile" href="<?php echo htmlspecialchars($facebookUrl ?: '#'); ?>" target="_blank" rel="noreferrer">Facebook</a>
      </div>
    </div>
  </div>
</section>

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
            $start = new DateTime($ev['starting_date']);
            $day  = $start->format('d');
            $mon  = strtoupper($start->format('M'));
            $dow  = $start->format('D');
            $time = $start->format('g:i A');
            $location = $ev['event_location'] ?? '';
            $maxAtt  = (int)($ev['max_attendees'] ?? 0);
          ?>
          <article class="card">
            <div class="date">
              <div class="day"><?php echo $day; ?></div>
              <div class="mon"><?php echo $mon; ?></div>
              <div class="sep"><?php echo $dow; ?></div>
            </div>
            <div>
              <div class="topline">
                <span class="badge">Max <?php echo $maxAtt; ?> seats</span>
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
      <div class="stat"><h5>Events done</h5><div class="kpi"><?php echo str_pad($eventsDone, 3, "0", STR_PAD_LEFT); ?></div></div>
      <div class="stat"><h5>Members</h5><div class="kpi"><?php echo str_pad($memberCount, 3, "0", STR_PAD_LEFT); ?></div></div>
      <div class="stat"><h5>Earned points</h5><div class="kpi"><?php echo str_pad($clubPoints, 4, "0", STR_PAD_LEFT); ?></div></div>
    </div>

    <!-- JOIN CTA -->
    <?php if ($canRequestJoin): ?>
      <form id="joinForm" method="post" style="margin-top:26px;">
        <input type="hidden" name="action" value="join">
        <input type="hidden" name="club_id" value="<?php echo (int)$clubId; ?>">
        <input type="hidden" name="reason" id="reasonInput" value="">
        <button id="joinBtn" class="join" type="button">
          <?php
            if ($lastReqStatus === 'pending') echo "Request pending…";
            elseif ($lastReqStatus === 'rejected') echo "Join us again?";
            else echo "Join us!";
          ?>
        </button>
      </form>
    <?php else: ?>
      <div class="notice">
        You’re already in a club. To request another club, leave your current club first.
      </div>
    <?php endif; ?>

  </div>
</section>

<div class="underbar"></div>
<?php include 'footer.php'; ?>

<script>
  // assumes SweetAlert is loaded in header.php as Swal
  const joinBtn = document.getElementById('joinBtn');
  const joinForm = document.getElementById('joinForm');
  const reasonInput = document.getElementById('reasonInput');

  // Show flash swal if exists
  <?php if (!empty($flash)): ?>
    Swal.fire({
      icon: <?php echo json_encode($flash['icon'] ?? 'info'); ?>,
      title: <?php echo json_encode($flash['title'] ?? ''); ?>,
      text: <?php echo json_encode($flash['text'] ?? ''); ?>,
      confirmButtonText: 'OK'
    });
  <?php endif; ?>

  if (joinBtn && joinForm) {
    joinBtn.addEventListener('click', async () => {

      // If already pending -> don’t allow another attempt
      const btnText = joinBtn.textContent.trim().toLowerCase();
      if (btnText.includes('pending')) {
        Swal.fire({
          icon: 'info',
          title: 'Request pending…',
          text: 'You already submitted a join request for this club. Please wait for approval.',
          confirmButtonText: 'OK'
        });
        return;
      }

      const { value: reason } = await Swal.fire({
        title: 'Why do you want to join?',
        input: 'textarea',
        inputPlaceholder: 'Write a short reason…',
        showCancelButton: true,
        confirmButtonText: 'Submit request',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
          if (!value || !value.trim()) return 'Reason is required';
        }
      });

      if (reason) {
        reasonInput.value = reason.trim();
        joinForm.submit();
      }
    });
  }
</script>

</body>
</html>
