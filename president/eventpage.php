<?php
session_start();

if (!isset($_SESSION['student_id']) || ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'club_president')) {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

// Helper to fix image paths
function cch_img($path) {
    if (!$path) return '';
    // Full URL
    if (preg_match('/^https?:\/\//i', $path)) return $path;
    // Absolute path
    if ($path[0] === '/') return $path;
    // Path relative to uploads
    return '../' . ltrim($path, '/');
}

$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

$event = null;

if ($eventId > 0) {
    $sql = "
        SELECT
            e.*,
            c.club_name,
            sp.company_name AS sponsor_name
        FROM event e
        INNER JOIN club c
            ON e.club_id = c.club_id
        LEFT JOIN sponsor sp
            ON sp.sponsor_id = e.sponsor_id
        WHERE e.event_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $res  = $stmt->get_result();
    $event = $res->fetch_assoc();
    $stmt->close();
}

if (!$event) {
    http_response_code(404);
    $title = "Event not found";
} else {
    $title = $event['event_name'];
}

function formatWhenFull($startStr, $endStr): string {
    if (!$startStr) return 'Date to be announced';
    $start = new DateTime($startStr);
    if ($endStr) {
        $end = new DateTime($endStr);
        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            return $start->format('l • M d, Y • g:i A') . ' – ' . $end->format('g:i A');
        }
        return $start->format('M d, Y g:i A') . ' – ' . $end->format('M d, Y g:i A');
    }
    return $start->format('l • M d, Y • g:i A');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Event Details</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  :root{
    --navy:#242751; --royal:#4871db; --lightBlue:#a9bff8; --gold:#e5b758;
    --paper:#eef2f7; --ink:#0e1228; --card:#ffffff;
    --shadow:0 18px 38px rgba(12,22,60,.16); --radius:22px; --maxw:1100px;
  }
  *{box-sizing:border-box} html,body{margin:0;padding:0}
  body{font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:linear-gradient(180deg,#f5f7fb,#eef2f7);color:var(--ink)}
  .wrap{ max-width:var(--maxw); margin:40px auto 0; padding:0 20px; }
  .content{ max-width:var(--maxw); margin:40px auto 60px; padding:0 20px; }
  .headline{ margin:10px 0 16px; font-weight:800; line-height:1.1; font-size:clamp(32px, 4.7vw, 52px); color:var(--navy); display:flex; align-items:center; justify-content:space-between; gap:12px }
  .meta{ display:flex; flex-wrap:wrap; align-items:center; gap:14px; margin-bottom:22px; color:#666c85; font-weight:700; }
  .badge{ background:var(--royal); color:#fff; padding:6px 12px; border-radius:999px; font-size:13px; }
  .dot{ width:6px;height:6px;border-radius:50%;background:#c5c9d7; }
  .edit-btn{
    display:inline-block; padding:10px 16px; border-radius:999px; font-weight:800; font-size:14px; text-decoration:none;
    color:#fff; background:linear-gradient(135deg,#5d7ff2,#3664e9); box-shadow:0 8px 20px rgba(54,100,233,.22)
  }
  .edit-btn:hover{ background:linear-gradient(135deg,#4d70ee,#2958e0); }
  .hero{ position:relative; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow); background:#d0d8ff; aspect-ratio:16 / 9; }
  .hero img{ width:100%; height:100%; object-fit:cover; display:block; }
  .credit{ position:absolute;right:12px;bottom:10px; background:rgba(0,0,0,.55); color:#fff; font-size:12px; padding:6px 10px; border-radius:999px; }
  article{ margin-top:28px; background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); padding:30px; }
  article p{ margin:0 0 18px; line-height:1.75; font-size:18px; } article p.lead{ font-size:19px; font-weight:600; }
  .summary{ display:grid; gap:18px; margin-top:22px; grid-template-columns: 1.2fr .8fr; } @media (max-width: 880px){ .summary{ grid-template-columns:1fr; } }
  .info{ background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); padding:22px; }
  .info-grid{ display:grid; gap:14px; grid-template-columns:1fr 1fr; } @media (max-width:700px){ .info-grid{ grid-template-columns:1fr; } }
  .info-item{ display:flex; gap:12px; align-items:flex-start; background:#f6f8ff; padding:14px 16px; border-radius:14px; }
  .info-item .icon{ width:28px; height:28px; display:grid; place-items:center; border-radius:10px; background:#e7ecff; font-weight:800; color:var(--royal); flex:0 0 28px; }
  .info-item b{ display:block; font-size:14px; color:#425; margin-bottom:4px; } .info-item span{ display:block; font-size:15px; color:#233; }
  .cta{ display:flex; flex-wrap:wrap; gap:12px; margin-top:16px; }
  .btn{ border:0; border-radius:12px; padding:12px 16px; font-weight:800; cursor:pointer; box-shadow:0 8px 18px rgba(10,23,60,.10); background:#f2f5ff; color:#1a1f36; }
  .btn.primary{ background:var(--royal); color:#fff; } .btn.ghost{ background:#fff; border:2px solid #e7ecff; }
  .side-card{ background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); padding:22px; }
  .side-card h3{ margin:0 0 10px; font-size:18px; color:var(--navy); }
  .tagline{ color:#596180; font-weight:600; }
  .map-wrap{ margin-top:26px; } .map{ border:0; width:100%; height:320px; border-radius:16px; box-shadow:var(--shadow); }
  footer{ margin-top:0 !important; }
</style>
</head>

<body>

<?php include('header.php'); ?>

<main class="wrap">
  <?php if (!$event): ?>
    <h1 class="headline">Event not found</h1>
    <p style="font-size:18px;color:#6b7280;">This event does not exist or was removed.</p>
  <?php else: ?>

    <?php
      $categoryShow = trim((string)($event['category'] ?? '')) === '' ? '—' : $event['category'];
      $sponsorShow  = trim((string)($event['sponsor_name'] ?? '')) === '' ? 'No sponsor listed' : $event['sponsor_name'];
    ?>

    <h1 class="headline"><?php echo htmlspecialchars($event['event_name']); ?></h1>

    <div class="meta">
      <span class="badge">Event</span>
      <span class="dot"></span>
      <span>
        Hosted by: <?php echo htmlspecialchars($event['club_name']); ?>
        • Sponsored by <?php echo htmlspecialchars($sponsorShow); ?>

      </span>
    </div>

    <figure class="hero">
      <?php if (!empty($event['banner_image'])): ?>
        <img src="<?php echo htmlspecialchars(cch_img($event['banner_image'])); ?>"
             alt="Event banner">
      <?php else: ?>
        <img src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?q=80&w=1600&auto=format&fit=crop"
             alt="Students attending an event">
      <?php endif; ?>
      <figcaption class="credit">Photo: CCH Media</figcaption>
    </figure>

    <section class="summary">
      <div class="info">
        <div class="info-grid">
          <div class="info-item">
            <div class="icon">🗓</div>
            <div>
              <b>When</b>
              <span id="whenText">
                <?php echo htmlspecialchars(formatWhenFull($event['starting_date'], $event['ending_date'])); ?>
              </span>
            </div>
          </div>
          <div class="info-item">
            <div class="icon">📍</div>
            <div>
              <b>Where</b>
              <span id="whereText">
                <?php
                  echo htmlspecialchars(
                    $event['event_location'] ?: 'Location to be announced'
                  );
                ?>
              </span>
            </div>
          </div>
          <div class="info-item">
            <div class="icon">🏷</div>
            <div>
              <b>Category</b>
              <span><?php echo htmlspecialchars($categoryShow); ?></span>
            </div>
          </div>
          <div class="info-item">
            <div class="icon">🤝</div>
            <div>
              <b>Sponsored by</b>
              <span><?php echo htmlspecialchars($sponsorShow); ?></span>
            </div>
          </div>
        </div>

        <div class="cta">
          <button class="btn primary" id="addCalBtn">Add to Calendar</button>
          <button class="btn ghost" id="shareBtn">Share</button>
        </div>
      </div>

      <aside class="side-card">
        <h3>Tickets & Notes</h3>
        <p class="tagline">General admission is free. Seats are first-come, first-served.</p>
        <ul style="margin:10px 0 0 18px; line-height:1.7;">
          <li>Please bring your student ID.</li>
          <li>QR check-in available at entrance.</li>
          <li>Snacks & coffee provided.</li>
        </ul>
      </aside>
    </section>

    <article class="content">
      <p class="lead">
        <?php echo htmlspecialchars($event['description'] ?: 'Event description will be added soon.'); ?>
      </p>

      <div class="map-wrap">
        <h2 style="color:var(--navy); margin:0 0 12px;">Location</h2>
        <iframe
          class="map"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          src="https://www.google.com/maps?q=<?php echo urlencode($event['event_location'] ?: 'Jordan University'); ?>&output=embed">
        </iframe>
      </div>
    </article>
  <?php endif; ?>
</main>

<?php include('footer.php'); ?>

<script>
<?php if ($event): ?>
document.getElementById('shareBtn').addEventListener('click', async () => {
  const shareData = {
    title: '<?php echo addslashes($event['event_name']); ?>',
    text: 'Join me at this CCH event!',
    url: window.location.href
  };
  try{
    if(navigator.share){
      await navigator.share(shareData);
    }else{
      await navigator.clipboard.writeText(shareData.url);
      alert('Link copied to clipboard!');
    }
  }catch(e){}
});

document.getElementById('addCalBtn').addEventListener('click', () => {
  const title = '<?php echo addslashes($event['event_name']); ?>';
  const desc  = '<?php echo addslashes($event['description'] ?? 'CCH event'); ?>';
  const loc   = '<?php echo addslashes($event['event_location'] ?? 'Campus'); ?>';
  const start = '<?php echo $event['starting_date'] ? (new DateTime($event['starting_date']))->format("Ymd\THis") : ""; ?>';
  const end   = '<?php echo $event['ending_date'] ? (new DateTime($event['ending_date']))->format("Ymd\THis") : ""; ?>';

  if(!start){
    alert('Start date not set for this event yet.');
    return;
  }

  const ics =
`BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Campus Clubs Hub//EN
CALSCALE:GREGORIAN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:${start}
${end ? 'DTEND:'+end : ''}
SUMMARY:${title}
DESCRIPTION:${desc}
LOCATION:${loc}
UID:${Date.now()}@cch.local
END:VEVENT
END:VCALENDAR`;

  const blob = new Blob([ics], {type: 'text/calendar;charset=utf-8'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'cch-event.ics';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
});
<?php endif; ?>
</script>

</body>
</html>
