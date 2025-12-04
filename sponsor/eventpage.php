<?php
session_start();

if (!isset($_SESSION['sponsor_id']) || $_SESSION['role'] !== 'sponsor') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

// ========== قراءة ID من الرابط ==========
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($eventId <= 0) {
    // لو مافي ID نرجع للـ Upcoming Events
    header('Location: events.php');
    exit;
}

// ========== جلب بيانات الحدث من الداتابيس ==========
$sql = "
    SELECT 
        e.event_id,
        e.event_name,
        e.description,
        e.event_location,
        e.starting_date,
        e.ending_date,
        e.attendees_count,
        c.club_name,
        c.club_id,
        s.company_name AS sponsor_name
    FROM event e
    INNER JOIN club c ON e.club_id = c.club_id
    LEFT JOIN sponsor_club_support scs ON scs.club_id = c.club_id
    LEFT JOIN sponsor s ON scs.sponsor_id = s.sponsor_id
    WHERE e.event_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // لو مافي حدث بهالرقم
    $event = null;
} else {
    $event = $result->fetch_assoc();
}
$stmt->close();

// ========== جلب قواعد النقاط الخاصة بالحضور (اختياري للعرض) ==========
$eventRules = [];
$rulesSql = "
    SELECT rule_id, rule_type, min_attendees, max_attendees, points
    FROM points_rule
    WHERE rule_type LIKE 'event_attendance_%'
";
$rulesRes = $conn->query($rulesSql);
if ($rulesRes && $rulesRes->num_rows > 0) {
    while ($r = $rulesRes->fetch_assoc()) {
        $eventRules[] = $r;
    }
}

// helper بسيط لحساب النقاط المتوقعة حسب attendees_count
function getEventPointsBadge($attendees_count, $eventRules) {
    if ($attendees_count === null) return null;

    foreach ($eventRules as $rule) {
        $min = is_null($rule['min_attendees']) ? 0 : (int)$rule['min_attendees'];
        $max = is_null($rule['max_attendees']) ? 999999 : (int)$rule['max_attendees'];

        if ($attendees_count >= $min && $attendees_count <= $max) {
            return (int)$rule['points'];
        }
    }
    return null;
}

$pointsBadge = null;
if ($event) {
    $pointsBadge = getEventPointsBadge((int)$event['attendees_count'], $eventRules);
}

// تجهيز تواريخ للعرض
$startObj = $event && $event['starting_date'] ? new DateTime($event['starting_date']) : null;
$endObj   = $event && $event['ending_date']   ? new DateTime($event['ending_date'])   : null;

$startDateStr = $startObj ? $startObj->format('l, d M Y') : '—';
$startTimeStr = $startObj ? $startObj->format('g:i A')    : '—';

$endDateStr   = $endObj   ? $endObj->format('l, d M Y')   : '—';
$endTimeStr   = $endObj   ? $endObj->format('g:i A')      : '—';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Event Details</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  :root{
    --navy:#242751;
    --royal:#4871db;
    --gold:#e5b758;
    --lightGold:#f4df6d;
    --paper:#EEF2F7;
    --card:#ffffff;
    --ink:#0e1228;
    --muted:#6b7280;
    --shadow:0 14px 34px rgba(10,23,60,.12);
    --radius:18px;
  }

  body{
    margin:0;
    font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    color:var(--ink);
    background:
      radial-gradient(1200px 700px at -10% 40%, rgba(168,186,240,.3), transparent 60%),
      radial-gradient(900px 600px at 110% 60%, rgba(72,113,219,.22), transparent 60%),
      var(--paper);
    background-repeat:no-repeat;
  }

  .wrapper{
    max-width:960px;
    margin:32px auto 48px;
    padding:0 18px;
  }

  .back-link{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-size:13px;
    color:var(--royal);
    text-decoration:none;
    font-weight:600;
    margin-bottom:10px;
  }
  .back-link span{
    font-size:16px;
  }
  .back-link:hover{
    text-decoration:underline;
  }

  .page-title{
    font-size:28px;
    font-weight:800;
    color:var(--navy);
    margin:6px 0 4px;
  }

  .page-sub{
    font-size:14px;
    color:var(--muted);
    margin:0 0 18px;
  }

  .layout{
    display:grid;
    grid-template-columns: minmax(0,2.1fr) minmax(0,1.3fr);
    gap:20px;
  }
  @media (max-width:880px){
    .layout{
      grid-template-columns:1fr;
    }
  }

  .card{
    background:var(--card);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:18px 18px 20px;
  }

  .section-title{
    font-size:16px;
    font-weight:800;
    color:var(--navy);
    margin:0 0 10px;
  }

  .desc{
    font-size:14px;
    line-height:1.6;
    color:var(--ink);
    white-space:pre-line;
  }

  .meta-row{
    display:flex;
    align-items:flex-start;
    gap:10px;
    font-size:14px;
    color:var(--ink);
    margin-bottom:8px;
  }

  .meta-label{
    font-weight:700;
    min-width:80px;
  }

  .pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:5px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    background:#e8f5ff;
    color:#135f9b;
  }

  .pill.gold{
    background:#fff7db;
    color:#8a5b00;
    border:1px solid #ffe9a7;
  }

  .pill.neutral{
    background:#f3f4f6;
    color:#374151;
  }

  .stack{
    display:flex;
    flex-direction:column;
    gap:8px;
    margin-top:10px;
  }

  .badge-row{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-top:6px;
  }

  .empty-state{
    text-align:center;
    padding:40px 22px;
    background:rgba(255,255,255,.6);
    border-radius:var(--radius);
    border:1px dashed rgba(148,163,184,.8);
    font-size:14px;
    color:var(--muted);
  }
</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="wrapper">

  <a href="events.php" class="back-link">
    <span>←</span> Back to Upcoming Events
  </a>

  <?php if (!$event): ?>
    <div class="empty-state">
      Event not found or no longer available.
    </div>
  <?php else: ?>

    <h1 class="page-title">
      <?php echo htmlspecialchars($event['event_name']); ?>
    </h1>
    <p class="page-sub">
      Club: <?php echo htmlspecialchars($event['club_name']); ?>
      <?php if (!empty($event['sponsor_name'])): ?>
        · Sponsored by <?php echo htmlspecialchars($event['sponsor_name']); ?>
      <?php endif; ?>
    </p>

    <div class="layout">
      <!-- ========== Left: Description ========== -->
      <section class="card">
        <h2 class="section-title">About this event</h2>
        <p class="desc">
          <?php 
            echo $event['description'] 
              ? nl2br(htmlspecialchars($event['description'])) 
              : 'No description provided for this event yet.';
          ?>
        </p>
      </section>

      <!-- ========== Right: Details & Points ========== -->
      <aside class="card">
        <h2 class="section-title">Event details</h2>

        <div class="meta-row">
          <div class="meta-label">Club</div>
          <div><?php echo htmlspecialchars($event['club_name']); ?></div>
        </div>

        <div class="meta-row">
          <div class="meta-label">Location</div>
          <div>
            <?php echo $event['event_location'] 
              ? htmlspecialchars($event['event_location']) 
              : 'Not specified'; ?>
          </div>
        </div>

        <div class="meta-row">
          <div class="meta-label">Starts</div>
          <div><?php echo htmlspecialchars($startDateStr); ?><br>
            <span style="font-size:13px; color:var(--muted);">
              <?php echo htmlspecialchars($startTimeStr); ?>
            </span>
          </div>
        </div>

        <div class="meta-row">
          <div class="meta-label">Ends</div>
          <div><?php echo htmlspecialchars($endDateStr); ?><br>
            <span style="font-size:13px; color:var(--muted);">
              <?php echo htmlspecialchars($endTimeStr); ?>
            </span>
          </div>
        </div>

        <div class="meta-row">
          <div class="meta-label">Attendees</div>
          <div>
            <?php
              $att = $event['attendees_count'];
              echo is_null($att) ? '—' : (int)$att . ' students';
            ?>
          </div>
        </div>

        <div class="stack">
          <div class="section-title" style="font-size:14px; margin-bottom:4px;">
            Points preview
          </div>

          <?php if (!is_null($pointsBadge)): ?>
            <div class="badge-row">
              <span class="pill gold">
                +<?php echo (int)$pointsBadge; ?> points
              </span>
              <span class="pill neutral">
                Based on current attendees: 
                <?php echo is_null($att) ? 'N/A' : (int)$att; ?>
              </span>
            </div>
          <?php else: ?>
            <p style="font-size:13px; color:var(--muted); margin:0;">
              No matching rule found for this attendees count yet.
            </p>
          <?php endif; ?>

          <?php if (!empty($eventRules)): ?>
            <div style="margin-top:10px;">
              <div style="font-size:12px; font-weight:700; color:var(--muted); margin-bottom:4px;">
                Event attendance rules (summary):
              </div>
              <div style="font-size:12px; color:var(--muted); line-height:1.5;">
                <?php foreach ($eventRules as $rule): ?>
                  <?php
                    $min = is_null($rule['min_attendees']) ? 0 : (int)$rule['min_attendees'];
                    $max = is_null($rule['max_attendees']) ? null : (int)$rule['max_attendees'];
                  ?>
                  •
                  <?php if (is_null($max)): ?>
                    <?php echo $min; ?>+ attendees → <?php echo (int)$rule['points']; ?> pts<br>
                  <?php else: ?>
                    <?php echo $min; ?>–<?php echo $max; ?> attendees → <?php echo (int)$rule['points']; ?> pts<br>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </aside>
    </div>

  <?php endif; ?>

</div>

<?php include 'footer.php'; ?>

</body>
</html>
